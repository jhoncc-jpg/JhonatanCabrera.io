<?php

require_once __DIR__ . '/../../config/database.php';

class HomeRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function resumen(string $role, int $userId, string $name): array
    {
        if ($role === 'mecanico') {
            $asignacion = ' JOIN orden_mecanicos om ON om.orden_id = o.id
                            JOIN mecanicos m ON m.id = om.mecanico_id
                            WHERE (m.usuario_id = :usuario_id
                                   OR (m.usuario_id IS NULL AND m.nombre = :nombre))';
            $consultas = [
                'clientes' => 'SELECT COUNT(DISTINCT v.cliente_id)
                               FROM vehiculos v JOIN ordenes_trabajo o ON o.vehiculo_id = v.id' . $asignacion,
                'vehiculos' => 'SELECT COUNT(DISTINCT o.vehiculo_id)
                                FROM ordenes_trabajo o' . $asignacion,
                'ordenes_mes' => 'SELECT COUNT(DISTINCT o.id)
                                  FROM ordenes_trabajo o' . $asignacion
                    . " AND o.fecha_ingreso >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')",
                'ordenes_abiertas' => 'SELECT COUNT(DISTINCT o.id)
                                       FROM ordenes_trabajo o' . $asignacion
                    . " AND o.estado NOT IN ('entregada', 'cancelada')",
                'ordenes_listas' => 'SELECT COUNT(DISTINCT o.id)
                                     FROM ordenes_trabajo o' . $asignacion
                    . " AND o.estado = 'lista'",
                'ingresos_mes' => 'SELECT 0',
                'repuestos_bajos' => 'SELECT 0',
            ];
        } else {
            $consultas = [
                'clientes' => 'SELECT COUNT(*) FROM clientes WHERE activo = 1',
                'vehiculos' => 'SELECT COUNT(*) FROM vehiculos',
                'ordenes_mes' => "SELECT COUNT(*) FROM ordenes_trabajo WHERE fecha_ingreso >= DATE_FORMAT(CURRENT_DATE, '%Y-%m-01')",
                'ordenes_abiertas' => "SELECT COUNT(*) FROM ordenes_trabajo WHERE estado NOT IN ('entregada', 'cancelada')",
                'ordenes_listas' => "SELECT COUNT(*) FROM ordenes_trabajo WHERE estado = 'lista'",
                'ingresos_mes' => 'SELECT COALESCE(SUM(monto), 0) FROM pagos WHERE fecha_pago >= :inicio AND fecha_pago < :fin',
                'repuestos_bajos' => 'SELECT COUNT(*) FROM repuestos WHERE activo = 1 AND stock <= stock_minimo',
            ];
        }

        $resumen = [];
        foreach ($consultas as $campo => $sql) {
            $consulta = $this->conexion->prepare($sql);
            if ($role === 'mecanico' && $campo !== 'ingresos_mes' && $campo !== 'repuestos_bajos') {
                $consulta->execute(['usuario_id' => $userId, 'nombre' => $name]);
            } elseif ($role === 'mecanico') {
                $consulta->execute();
            } elseif ($campo === 'ingresos_mes') {
                $inicioMes = new DateTimeImmutable('first day of this month midnight');
                $consulta->execute([
                    'inicio' => $inicioMes->format('Y-m-d H:i:s'),
                    'fin' => $inicioMes->modify('first day of next month')->format('Y-m-d H:i:s'),
                ]);
            } else {
                $consulta->execute();
            }
            $resumen[$campo] = (float) $consulta->fetchColumn();
        }

        return $resumen;
    }
}
