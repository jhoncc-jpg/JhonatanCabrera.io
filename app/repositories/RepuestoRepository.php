<?php

require_once __DIR__ . '/../../config/database.php';

class RepuestoRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function listar(
        string $busqueda = '',
        bool $soloBajos = false,
        ?bool $activo = true,
        ?string $fechaInicio = null,
        ?string $fechaFin = null
    ): array {
        $sql = 'SELECT id, codigo, nombre, descripcion, stock, stock_minimo,
                       precio_referencia, activo, fecha_registro
                FROM repuestos';
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(codigo LIKE :codigo OR nombre LIKE :nombre OR descripcion LIKE :descripcion)';
            $termino = '%' . $busqueda . '%';
            $parametros['codigo'] = $termino;
            $parametros['nombre'] = $termino;
            $parametros['descripcion'] = $termino;
        }
        if ($soloBajos) {
            $condiciones[] = 'stock <= stock_minimo';
        }
        if ($activo !== null) {
            $condiciones[] = 'activo = :activo';
            $parametros['activo'] = $activo ? 1 : 0;
        }
        if ($fechaInicio !== null) {
            $condiciones[] = 'DATE(fecha_registro) >= :fecha_inicio';
            $parametros['fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin !== null) {
            $condiciones[] = 'DATE(fecha_registro) <= :fecha_fin';
            $parametros['fecha_fin'] = $fechaFin;
        }
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY nombre ASC';
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta repuestos activos y agrega sus movimientos en el periodo solicitado.
     * Parametros $fechaDesde y $fechaHasta: limites validados para los movimientos.
     * Devuelve: repuestos activos con datos y totales de movimientos listos para exportar.
     */
    public function reporteInventario(?string $fechaDesde, ?string $fechaHasta): array
    {
        $condicionesMovimiento = ['m.repuesto_id = r.id'];
        $parametros = [];
        if ($fechaDesde !== null) {
            $condicionesMovimiento[] = 'm.fecha_movimiento >= :fecha_desde';
            $parametros['fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta !== null) {
            $condicionesMovimiento[] = 'm.fecha_movimiento < :fecha_hasta';
            $parametros['fecha_hasta'] = (new DateTimeImmutable($fechaHasta))->modify('+1 day')->format('Y-m-d 00:00:00');
        }

        $sql = 'SELECT r.id, r.codigo, r.nombre, r.descripcion, r.stock, r.stock_minimo,
                       r.precio_referencia,
                       CASE WHEN r.stock <= r.stock_minimo THEN 1 ELSE 0 END AS stock_bajo,
                       COUNT(m.id) AS movimientos_periodo,
                       COALESCE(SUM(CASE WHEN m.tipo = \'entrada\' THEN m.cantidad ELSE 0 END), 0) AS entradas_periodo,
                       COALESCE(SUM(CASE WHEN m.tipo = \'salida\' THEN m.cantidad ELSE 0 END), 0) AS salidas_periodo,
                       COALESCE(SUM(CASE WHEN m.tipo = \'ajuste\' THEN m.cantidad ELSE 0 END), 0) AS ajustes_periodo
                FROM repuestos r
                LEFT JOIN movimientos_inventario m ON ' . implode(' AND ', $condicionesMovimiento) . '
                WHERE r.activo = 1
                GROUP BY r.id, r.codigo, r.nombre, r.descripcion, r.stock, r.stock_minimo,
                         r.precio_referencia
                ORDER BY r.nombre ASC, r.codigo ASC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, codigo, nombre, descripcion, stock, stock_minimo,
                    precio_referencia, activo, fecha_registro
             FROM repuestos WHERE id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $repuesto = $consulta->fetch(PDO::FETCH_ASSOC);
        return $repuesto ?: null;
    }

    public function codigoExiste(string $codigo, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM repuestos WHERE codigo = :codigo';
        $parametros = ['codigo' => strtoupper(trim($codigo))];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return (int) $consulta->fetchColumn() > 0;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO repuestos (codigo, nombre, descripcion, stock, stock_minimo, precio_referencia, activo)
             VALUES (:codigo, :nombre, :descripcion, :stock, :stock_minimo, :precio_referencia, 1)'
        );
        return $consulta->execute($datos);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE repuestos
             SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion,
                 stock_minimo = :stock_minimo, precio_referencia = :precio_referencia
             WHERE id = :id'
        );
        return $consulta->execute([
            'codigo' => $datos['codigo'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'],
            'stock_minimo' => $datos['stock_minimo'],
            'precio_referencia' => $datos['precio_referencia'],
            'id' => $id,
        ]);
    }

    public function cambiarEstado(int $id, bool $activo): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE repuestos SET activo = :activo WHERE id = :id'
        );
        return $consulta->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
    }

    public function registrarMovimiento(int $repuestoId, int $usuarioId, string $tipo, int $cantidad, string $observacion): void
    {
        if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)
            || $cantidad < 0
            || ($tipo !== 'ajuste' && $cantidad === 0)) {
            throw new InvalidArgumentException('Movimiento de inventario inválido.');
        }

        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'SELECT stock, activo FROM repuestos WHERE id = :id FOR UPDATE'
            );
            $consulta->execute(['id' => $repuestoId]);
            $repuesto = $consulta->fetch(PDO::FETCH_ASSOC);
            if (!$repuesto) {
                throw new InvalidArgumentException('Repuesto no encontrado.');
            }
            if ((int) $repuesto['activo'] !== 1) {
                throw new InvalidArgumentException('No se puede registrar un movimiento en un repuesto inactivo.');
            }

            $stockActual = (int) $repuesto['stock'];
            if ($tipo === 'entrada') {
                $nuevoStock = $stockActual + $cantidad;
            } elseif ($tipo === 'salida') {
                $nuevoStock = $stockActual - $cantidad;
            } else {
                $nuevoStock = $cantidad;
            }
            if ($nuevoStock < 0) {
                throw new InvalidArgumentException('No hay existencias suficientes para realizar la salida.');
            }
            if ($tipo === 'ajuste' && $nuevoStock === $stockActual) {
                throw new InvalidArgumentException('El stock ya tiene esa cantidad; no se registró ningún movimiento.');
            }

            $consulta = $this->conexion->prepare(
                'UPDATE repuestos SET stock = :stock WHERE id = :id'
            );
            $consulta->execute(['stock' => $nuevoStock, 'id' => $repuestoId]);

            $consulta = $this->conexion->prepare(
                'INSERT INTO movimientos_inventario
                    (repuesto_id, usuario_id, tipo, cantidad, observaciones)
                 VALUES (:repuesto_id, :usuario_id, :tipo, :cantidad, :observaciones)'
            );
            $consulta->execute([
                'repuesto_id' => $repuestoId,
                'usuario_id' => $usuarioId,
                'tipo' => $tipo,
                'cantidad' => $tipo === 'ajuste' ? abs($nuevoStock - $stockActual) : $cantidad,
                'observaciones' => $observacion,
            ]);

            $this->conexion->commit();
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }

    public function movimientos(int $repuestoId): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT m.*, u.nombre AS usuario
             FROM movimientos_inventario m
             INNER JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.repuesto_id = :id
             ORDER BY m.fecha_movimiento DESC LIMIT 30'
        );
        $consulta->execute(['id' => $repuestoId]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }
}
