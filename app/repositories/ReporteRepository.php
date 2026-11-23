<?php

require_once __DIR__ . '/../../config/database.php';

class ReporteRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    /**
     * Obtiene movimientos valorizados con los filtros solicitados.
     * Parametros: fechas, tipo de movimiento y texto de búsqueda opcionales.
     * Devuelve: movimientos con sus datos relacionados mediante consultas preparadas.
     */
    public function obtenerReporteMovimientos(
        string $fechaInicio = '',
        string $fechaFin = '',
        string $tipo = '',
        string $busqueda = ''
    ): array {
        $condiciones = [];
        $parametros = [];

        if ($fechaInicio !== '') {
            $condiciones[] = 'DATE(m.fecha_movimiento) >= :fecha_inicio';
            $parametros['fecha_inicio'] = $fechaInicio;
        }
        if ($fechaFin !== '') {
            $condiciones[] = 'DATE(m.fecha_movimiento) <= :fecha_fin';
            $parametros['fecha_fin'] = $fechaFin;
        }
        if ($tipo !== '') {
            if (!in_array($tipo, ['entrada', 'salida', 'ajuste'], true)) {
                throw new InvalidArgumentException('El tipo de movimiento no es válido.');
            }
            $condiciones[] = 'm.tipo = :tipo';
            $parametros['tipo'] = $tipo;
        }
        if ($busqueda !== '') {
            $condiciones[] = '(r.codigo LIKE :codigo OR r.nombre LIKE :nombre)';
            $termino = '%' . $busqueda . '%';
            $parametros['codigo'] = $termino;
            $parametros['nombre'] = $termino;
        }

        $sql = 'SELECT m.id, m.fecha_movimiento, m.tipo, m.cantidad, m.observaciones,
                       r.codigo, r.nombre AS repuesto_nombre,
                       COALESCE(r.precio_referencia, 0) AS precio_unitario,
                       (m.cantidad * COALESCE(r.precio_referencia, 0)) AS total_bs,
                       u.nombre AS usuario_nombre, o.codigo AS orden_codigo
                FROM movimientos_inventario m
                INNER JOIN repuestos r ON m.repuesto_id = r.id
                INNER JOIN usuarios u ON m.usuario_id = u.id
                LEFT JOIN ordenes_trabajo o ON m.orden_id = o.id';
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY m.fecha_movimiento DESC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los totales generales de órdenes, inventario e ingresos.
     * Parametros $fechaDesde y $fechaHasta: limites validados del periodo.
     * Devuelve: valores agregados obtenidos con consultas preparadas.
     */
    public function resumen(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        [$condicionesOrden, $parametrosOrden] = $this->rangoFechas('o.fecha_ingreso', $fechaDesde, $fechaHasta);
        $whereOrden = $condicionesOrden === [] ? '' : ' WHERE ' . implode(' AND ', $condicionesOrden);
        [$condicionesPago, $parametrosPago] = $this->rangoFechas('p.fecha_pago', $fechaDesde, $fechaHasta);
        $wherePago = $condicionesPago === [] ? '' : ' WHERE ' . implode(' AND ', $condicionesPago);

        $ordenes = $this->conexion->prepare(
            'SELECT COUNT(*) AS total FROM ordenes_trabajo o' . $whereOrden
        );
        $ordenes->execute($parametrosOrden);

        $abiertas = $this->conexion->prepare(
            "SELECT COUNT(*) AS total FROM ordenes_trabajo o
             WHERE o.estado NOT IN ('entregada', 'cancelada')"
             . ($whereOrden === '' ? '' : ' AND ' . implode(' AND ', $condicionesOrden))
        );
        $abiertas->execute($parametrosOrden);

        $inventario = $this->conexion->prepare(
            'SELECT COUNT(*) AS total, COALESCE(SUM(stock), 0) AS unidades
             FROM repuestos WHERE activo = 1'
        );
        $inventario->execute();

        $bajos = $this->conexion->prepare(
            'SELECT COUNT(*) AS total FROM repuestos
             WHERE activo = 1 AND stock <= stock_minimo'
        );
        $bajos->execute();

        $ingresos = $this->conexion->prepare(
            'SELECT COALESCE(SUM(p.monto), 0) AS total FROM pagos p' . $wherePago
        );
        $ingresos->execute($parametrosPago);

        return [
            'ordenes' => $ordenes->fetch(PDO::FETCH_ASSOC),
            'abiertas' => $abiertas->fetch(PDO::FETCH_ASSOC),
            'inventario' => $inventario->fetch(PDO::FETCH_ASSOC),
            'bajos' => $bajos->fetch(PDO::FETCH_ASSOC),
            'ingresos' => $ingresos->fetch(PDO::FETCH_ASSOC),
        ];
    }

    /**
     * Consulta vehículos con los datos del cliente para exportar el registro.
     * Parametros $fechaDesde y $fechaHasta: limites validados del filtro de registro.
     * Devuelve: filas preparadas desde MySQL mediante una consulta PDO.
     */
    public function vehiculos(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        [$condiciones, $parametros] = $this->rangoFechas('v.fecha_registro', $fechaDesde, $fechaHasta);
        $sql = 'SELECT v.id, c.nombre AS propietario, c.telefono AS telefono_cliente,
                       v.placa, v.marca, v.modelo, v.anio, v.color,
                       v.diagnostico, v.estado, v.fecha_registro
                FROM vehiculos v
                INNER JOIN clientes c ON v.cliente_id = c.id';
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY v.fecha_registro DESC, v.placa ASC';
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Consulta órdenes y sus relaciones para exportar el detalle del trabajo.
     * Parametros $fechaDesde y $fechaHasta: limites validados por fecha de ingreso.
     * Devuelve: filas preparadas desde MySQL, con los mecánicos agrupados por orden.
     */
    public function ordenes(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        [$condiciones, $parametros] = $this->rangoFechas('o.fecha_ingreso', $fechaDesde, $fechaHasta);
        $sql = 'SELECT o.codigo, o.fecha_ingreso, o.fecha_prevista_retorno, o.diagnostico,
                       o.estado, o.costo_final, o.precio_confirmado, o.fecha_entrega,
                       v.placa, v.marca, v.modelo, c.nombre AS cliente_nombre,
                       u.nombre AS usuario_nombre,
                       GROUP_CONCAT(DISTINCT m.nombre ORDER BY m.nombre SEPARATOR \', \') AS mecanicos
                FROM ordenes_trabajo o
                INNER JOIN vehiculos v ON v.id = o.vehiculo_id
                INNER JOIN clientes c ON c.id = v.cliente_id
                INNER JOIN usuarios u ON u.id = o.usuario_id
                LEFT JOIN orden_mecanicos om ON om.orden_id = o.id
                LEFT JOIN mecanicos m ON m.id = om.mecanico_id';
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' GROUP BY o.id, o.codigo, o.fecha_ingreso, o.fecha_prevista_retorno,
                           o.diagnostico, o.estado, o.costo_final, o.precio_confirmado,
                           o.fecha_entrega, v.placa, v.marca, v.modelo,
                           c.nombre, u.nombre
                  ORDER BY o.fecha_ingreso DESC, o.codigo ASC';

        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Construye condiciones parametrizadas para un intervalo de fechas permitido.
     * Parametros: columna de fecha autorizada y limites opcionales ya validados.
     * Devuelve: condiciones SQL y parametros para ejecutar la consulta.
     */
    private function rangoFechas(string $columna, ?string $fechaDesde, ?string $fechaHasta): array
    {
        $columnasPermitidas = ['o.fecha_ingreso', 'p.fecha_pago', 'v.fecha_registro'];
        if (!in_array($columna, $columnasPermitidas, true)) {
            throw new InvalidArgumentException('La columna de fecha no es válida.');
        }

        $condiciones = [];
        $parametros = [];
        if ($fechaDesde !== null) {
            $condiciones[] = $columna . ' >= :fecha_desde';
            $parametros['fecha_desde'] = $fechaDesde . ' 00:00:00';
        }
        if ($fechaHasta !== null) {
            $condiciones[] = $columna . ' < :fecha_hasta';
            $parametros['fecha_hasta'] = (new DateTimeImmutable($fechaHasta))
                ->modify('+1 day')
                ->format('Y-m-d 00:00:00');
        }

        return [$condiciones, $parametros];
    }
}
