<?php

require_once __DIR__ . '/../../config/database.php';

/**
 * Acceso a datos de mecanicos mediante consultas PDO preparadas.
 */
class MecanicoRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function listar(string $busqueda = '', ?bool $activo = null): array
    {
        $sql = 'SELECT id, nombre, telefono, especialidad, activo FROM mecanicos';
        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $condiciones[] = '(nombre LIKE :nombre OR telefono LIKE :telefono OR especialidad LIKE :especialidad)';
            $termino = '%' . $busqueda . '%';
            $parametros['nombre'] = $termino;
            $parametros['telefono'] = $termino;
            $parametros['especialidad'] = $termino;
        }

        if ($activo !== null) {
            $condiciones[] = 'activo = :activo';
            $parametros['activo'] = $activo ? 1 : 0;
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
            'SELECT id, nombre, telefono, especialidad, activo
             FROM mecanicos
             WHERE id = :id
             LIMIT 1'
        );
        $consulta->execute(['id' => $id]);
        $mecanico = $consulta->fetch(PDO::FETCH_ASSOC);

        return $mecanico ?: null;
    }

    public function crear(array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'INSERT INTO mecanicos (nombre, telefono, especialidad, activo)
             VALUES (:nombre, :telefono, :especialidad, 1)'
        );

        return $consulta->execute([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'especialidad' => $datos['especialidad'],
        ]);
    }

    public function actualizar(int $id, array $datos): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE mecanicos
             SET nombre = :nombre, telefono = :telefono, especialidad = :especialidad
             WHERE id = :id'
        );

        return $consulta->execute([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'especialidad' => $datos['especialidad'],
            'id' => $id,
        ]);
    }

    public function cambiarEstado(int $id, bool $activo): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE mecanicos SET activo = :activo WHERE id = :id'
        );

        return $consulta->execute([
            'activo' => $activo ? 1 : 0,
            'id' => $id,
        ]);
    }

    public function eliminar(int $id): void
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM orden_mecanicos WHERE mecanico_id = :id'
        );
        $consulta->execute(['id' => $id]);

        if ((int) $consulta->fetchColumn() > 0) {
            throw new InvalidArgumentException(
                'No se puede eliminar un mecánico con órdenes en su historial. Puedes desactivarlo para conservar el registro.'
            );
        }

        $consulta = $this->conexion->prepare(
            'DELETE FROM mecanicos WHERE id = :id'
        );
        $consulta->execute(['id' => $id]);

        if ($consulta->rowCount() === 0) {
            throw new InvalidArgumentException('Mecánico no encontrado.');
        }
    }
}
