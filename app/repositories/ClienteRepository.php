<?php

require_once __DIR__ . '/../../config/database.php';

/**
 * Acceso a datos de clientes mediante consultas PDO preparadas.
 */
class ClienteRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function listar(
        string $busqueda = '',
        ?bool $activo = true,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null
    ): array {
        $sql = 'SELECT id, nombre, telefono, direccion, correo, activo, creado_en FROM clientes';
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(nombre LIKE :nombre OR telefono LIKE :telefono OR correo LIKE :correo OR direccion LIKE :direccion)';
            $termino = '%' . $busqueda . '%';
            $parametros['nombre'] = $termino;
            $parametros['telefono'] = $termino;
            $parametros['correo'] = $termino;
            $parametros['direccion'] = $termino;
        }

        if ($activo !== null) {
            $condiciones[] = 'activo = :activo';
            $parametros['activo'] = $activo ? 1 : 0;
        }

        if ($fechaDesde !== null) {
            $condiciones[] = 'DATE(creado_en) >= :fecha_inicio';
            $parametros['fecha_inicio'] = $fechaDesde;
        }

        if ($fechaHasta !== null) {
            $condiciones[] = 'DATE(creado_en) <= :fecha_fin';
            $parametros['fecha_fin'] = $fechaHasta;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY nombre ASC';
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);

        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtener(int $id): ?array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, nombre, telefono, direccion, correo, activo, creado_en
             FROM clientes
             WHERE id = :id
             LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $cliente = $consulta->fetch(PDO::FETCH_ASSOC);

        return $cliente ?: null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO clientes (nombre, telefono, direccion, correo, activo)
             VALUES (:nombre, :telefono, :direccion, :correo, 1)'
        );

        return $consulta->execute([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'direccion' => $datos['direccion'],
            'correo' => $datos['correo'],
        ]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE clientes
             SET nombre = :nombre, telefono = :telefono,
                 direccion = :direccion, correo = :correo
             WHERE id = :id'
        );

        return $consulta->execute([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'direccion' => $datos['direccion'],
            'correo' => $datos['correo'],
            'id' => $id,
        ]);
    }

    public function cambiarEstado(int $id, bool $activo): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE clientes SET activo = :activo WHERE id = :id'
        );

        return $consulta->execute([
            'activo' => $activo ? 1 : 0,
            'id' => $id,
        ]);
    }
}
