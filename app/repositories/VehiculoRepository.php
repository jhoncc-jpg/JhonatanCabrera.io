<?php

require_once __DIR__ . '/../../config/database.php';

class VehiculoRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function listar(string $busqueda = '', bool $incluirPrecios = false, ?string $estado = null, ?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $totalOrdenes = $incluirPrecios
            ? ', (SELECT COALESCE(SUM(o.costo_final), 0) FROM ordenes_trabajo o WHERE o.vehiculo_id = v.id) AS total_ordenes'
            : '';
        $sql = 'SELECT v.*, c.nombre AS propietario' . $totalOrdenes . '
                FROM vehiculos v
                INNER JOIN clientes c ON c.id = v.cliente_id';
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(v.placa LIKE :placa OR v.marca LIKE :marca OR v.modelo LIKE :modelo OR c.nombre LIKE :propietario)';
            $termino = '%' . $busqueda . '%';
            $parametros['placa'] = $termino;
            $parametros['marca'] = $termino;
            $parametros['modelo'] = $termino;
            $parametros['propietario'] = $termino;
        }
        if ($estado !== null) {
            $condiciones[] = 'v.estado = :estado';
            $parametros['estado'] = $estado;
        }
        if ($fechaDesde !== null) {
            $condiciones[] = 'v.fecha_registro >= :fecha_desde';
            $parametros['fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta !== null) {
            $condiciones[] = 'v.fecha_registro < :fecha_hasta';
            $parametros['fecha_hasta'] = (new DateTimeImmutable($fechaHasta))->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY v.placa ASC';
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        $vehiculos = $consulta->fetchAll(PDO::FETCH_ASSOC);

        $consultaImagenes = $this->conexion->prepare(
            'SELECT id, nombre_archivo, ruta
             FROM vehiculo_imagenes
             WHERE vehiculo_id = :vehiculo_id
             ORDER BY id ASC'
        );
        foreach ($vehiculos as &$vehiculo) {
            $consultaImagenes->execute(['vehiculo_id' => $vehiculo['id']]);
            $vehiculo['imagenes'] = $consultaImagenes->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($vehiculo);

        return $vehiculos;
    }

    public function listarPropietarios(): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, nombre FROM clientes WHERE activo = 1 ORDER BY nombre ASC'
        );
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function buscarPropietario(string $nombre): ?int
    {
        $consulta = $this->conexion->prepare(
            'SELECT id FROM clientes
             WHERE activo = 1 AND LOWER(nombre) = LOWER(:nombre)
             ORDER BY id ASC LIMIT 1'
        );
        $consulta->execute(['nombre' => trim($nombre)]);
        $cliente = $consulta->fetch(PDO::FETCH_ASSOC);
        return $cliente ? (int) $cliente['id'] : null;
    }

    public function crearPropietario(string $nombre, string $telefono, string $direccion): int
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO clientes (nombre, telefono, direccion, activo)
             VALUES (:nombre, :telefono, :direccion, 1)'
        );
        $consulta->execute([
            'nombre' => mb_substr($nombre, 0, 100, 'UTF-8'),
            'telefono' => mb_substr(trim($telefono), 0, 20, 'UTF-8'),
            'direccion' => mb_substr(trim($direccion), 0, 200, 'UTF-8'),
        ]);
        return (int) $this->conexion->lastInsertId();
    }

    public function obtener(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT v.*, c.nombre AS propietario
             FROM vehiculos v
             INNER JOIN clientes c ON c.id = v.cliente_id
             WHERE v.id = :id LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $vehiculo = $consulta->fetch(PDO::FETCH_ASSOC);
        return $vehiculo ?: null;
    }

    public function placaExiste(string $placa, ?int $exceptoId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM vehiculos WHERE placa = :placa';
        $parametros = ['placa' => strtoupper(trim($placa))];
        if ($exceptoId !== null) {
            $sql .= ' AND id <> :id';
            $parametros['id'] = $exceptoId;
        }
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return (int) $consulta->fetchColumn() > 0;
    }

    public function crear(array $datos): int
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO vehiculos
                (cliente_id, placa, marca, modelo, anio, color, diagnostico, estado, fecha_registro)
             VALUES
                (:cliente_id, :placa, :marca, :modelo, :anio, :color, :diagnostico, :estado, :fecha_registro)'
        );
        $consulta->execute($datos);
        return (int) $this->conexion->lastInsertId();
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE vehiculos
             SET cliente_id = :cliente_id, placa = :placa, marca = :marca,
                 modelo = :modelo, anio = :anio, color = :color,
                 diagnostico = :diagnostico, estado = :estado
             WHERE id = :id'
        );
        $datos['id'] = $id;
        unset($datos['fecha_registro']);
        return $consulta->execute($datos);
    }

    public function guardarImagen(int $vehiculoId, string $nombreArchivo, string $ruta): bool
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO vehiculo_imagenes (vehiculo_id, nombre_archivo, ruta)
             VALUES (:vehiculo_id, :nombre_archivo, :ruta)'
        );
        return $consulta->execute([
            'vehiculo_id' => $vehiculoId,
            'nombre_archivo' => mb_substr($nombreArchivo, 0, 255, 'UTF-8'),
            'ruta' => mb_substr($ruta, 0, 255, 'UTF-8'),
        ]);
    }

    public function contarImagenes(int $vehiculoId): int
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM vehiculo_imagenes WHERE vehiculo_id = :vehiculo_id'
        );
        $consulta->execute(['vehiculo_id' => $vehiculoId]);
        return (int) $consulta->fetchColumn();
    }

    public function imagenes(int $vehiculoId): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, nombre_archivo, ruta
             FROM vehiculo_imagenes
             WHERE vehiculo_id = :vehiculo_id
             ORDER BY id ASC'
        );
        $consulta->execute(['vehiculo_id' => $vehiculoId]);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function eliminar(int $id): bool
    {
        $consulta = $this->conexion->prepare('DELETE FROM vehiculos WHERE id = :id');
        return $consulta->execute(['id' => $id]);
    }
}
