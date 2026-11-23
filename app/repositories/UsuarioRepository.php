<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../models/Usuario.php';

class UsuarioRepository
{
    private PDO $conexion;

    public function __construct(?PDO $conexion = null)
    {
        $this->conexion = $conexion ?? (new Database())->connect();
    }

    public function buscarPorUsuario(string $nombreUsuario): ?Usuario
    {
        $consulta = $this->conexion->prepare(
            'SELECT u.id, u.nombre, u.usuario, u.password_hash, u.activo, r.nombre AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id = u.rol_id
             WHERE u.usuario = :usuario LIMIT 1'
        );
        $consulta->execute(['usuario' => $nombreUsuario]);
        $datos = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$datos) {
            return null;
        }
        return new Usuario(
            (int) $datos['id'],
            (string) $datos['nombre'],
            (string) $datos['usuario'],
            (string) $datos['password_hash'],
            (string) $datos['rol'],
            (int) $datos['activo'] === 1
        );
    }

    public function listar(string $busqueda = '', ?bool $activo = null): array
    {
        $sql = 'SELECT u.id, u.nombre, u.usuario, u.activo, u.creado_en,
                       r.id AS rol_id, r.nombre AS rol
                FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id';
        $condiciones = [];
        $parametros = [];
        if ($busqueda !== '') {
            $condiciones[] = '(u.nombre LIKE :nombre OR u.usuario LIKE :usuario)';
            $parametros['nombre'] = '%' . $busqueda . '%';
            $parametros['usuario'] = '%' . $busqueda . '%';
        }
        if ($activo !== null) {
            $condiciones[] = 'u.activo = :activo';
            $parametros['activo'] = $activo ? 1 : 0;
        }
        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }
        $sql .= ' ORDER BY u.nombre ASC';
        $consulta = $this->conexion->prepare($sql);
        $consulta->execute($parametros);
        return $consulta->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los roles disponibles para mostrarlos en los formularios de usuarios.
     * Parametros: ninguno.
     * Devuelve: filas asociativas con id, nombre y descripcion.
     */
    public function obtenerRoles(): array
    {
        $consulta = $this->conexion->prepare(
            'SELECT id, nombre FROM roles ORDER BY id ASC'
        );
        $consulta->execute();
        $roles = $consulta->fetchAll(PDO::FETCH_ASSOC);

        if ($roles !== []) {
            return $roles;
        }

        $rolesBase = [
            ['nombre' => 'administrador', 'descripcion' => 'Administración del sistema'],
            ['nombre' => 'recepcionista', 'descripcion' => 'Gestión de clientes y órdenes'],
            ['nombre' => 'mecanico', 'descripcion' => 'Seguimiento técnico de reparaciones'],
        ];

        $this->conexion->beginTransaction();
        try {
            $insertar = $this->conexion->prepare(
                'INSERT IGNORE INTO roles (nombre, descripcion)
                 VALUES (:nombre, :descripcion)'
            );
            foreach ($rolesBase as $rol) {
                $insertar->execute($rol);
            }
            $this->conexion->commit();
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }

        $consulta->execute();
        $roles = $consulta->fetchAll(PDO::FETCH_ASSOC);
        if ($roles === []) {
            throw new RuntimeException('No se pudieron cargar ni crear los roles base.');
        }

        return $roles;
    }

    public function nombreRol(int $rolId): ?string
    {
        $consulta = $this->conexion->prepare(
            'SELECT nombre FROM roles
             WHERE id = :id AND nombre IN (:administrador, :recepcionista, :mecanico)
             LIMIT 1'
        );
        $consulta->execute([
            'id' => $rolId,
            'administrador' => 'administrador',
            'recepcionista' => 'recepcionista',
            'mecanico' => 'mecanico',
        ]);
        $nombre = $consulta->fetchColumn();
        return $nombre === false ? null : (string) $nombre;
    }

    public function existeUsuario(string $nombreUsuario): bool
    {
        $consulta = $this->conexion->prepare(
            'SELECT COUNT(*) FROM usuarios WHERE LOWER(usuario) = LOWER(:usuario)'
        );
        $consulta->execute(['usuario' => $nombreUsuario]);
        return (int) $consulta->fetchColumn() > 0;
    }

    public function crear(int $rolId, string $nombreRol, string $nombre, string $nombreUsuario, string $passwordHash): int
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'INSERT INTO usuarios (rol_id, nombre, usuario, password_hash)
                 VALUES (:rol_id, :nombre, :usuario, :password_hash)'
            );
            $consulta->execute([
                'rol_id' => $rolId,
                'nombre' => $nombre,
                'usuario' => $nombreUsuario,
                'password_hash' => $passwordHash,
            ]);
            $id = (int) $this->conexion->lastInsertId();
            if ($nombreRol === 'mecanico') {
                $this->sincronizarMecanico($id, $nombre);
            }
            $this->conexion->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }

    public function cambiarEstado(int $id, bool $activo): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE usuarios SET activo = :activo WHERE id = :id'
        );
        $consulta->execute(['activo' => $activo ? 1 : 0, 'id' => $id]);
        return $consulta->rowCount() > 0 || $this->existePorId($id);
    }

    public function cambiarRol(int $id, int $rolId, string $nombreRol): bool
    {
        $this->conexion->beginTransaction();
        try {
            $consulta = $this->conexion->prepare(
                'UPDATE usuarios SET rol_id = :rol_id WHERE id = :id'
            );
            $consulta->execute(['rol_id' => $rolId, 'id' => $id]);
            if ($consulta->rowCount() === 0 && !$this->existePorId($id)) {
                $this->conexion->rollBack();
                return false;
            }
            if ($nombreRol === 'mecanico') {
                $nombre = $this->nombreUsuarioPorId($id);
                if ($nombre === null) {
                    throw new InvalidArgumentException('No se encontró el usuario seleccionado.');
                }
                $this->sincronizarMecanico($id, $nombre);
            } else {
                $desvincular = $this->conexion->prepare(
                    'UPDATE mecanicos SET usuario_id = NULL WHERE usuario_id = :usuario_id'
                );
                $desvincular->execute(['usuario_id' => $id]);
            }
            $this->conexion->commit();
            return true;
        } catch (Throwable $exception) {
            if ($this->conexion->inTransaction()) {
                $this->conexion->rollBack();
            }
            throw $exception;
        }
    }

    public function restablecerPassword(int $id, string $passwordHash): bool
    {
        $consulta = $this->conexion->prepare(
            'UPDATE usuarios SET password_hash = :password_hash WHERE id = :id'
        );
        $consulta->execute(['password_hash' => $passwordHash, 'id' => $id]);
        return $consulta->rowCount() > 0 || $this->existePorId($id);
    }

    private function existePorId(int $id): bool
    {
        $consulta = $this->conexion->prepare('SELECT COUNT(*) FROM usuarios WHERE id = :id');
        $consulta->execute(['id' => $id]);
        return (int) $consulta->fetchColumn() > 0;
    }

    private function nombreUsuarioPorId(int $id): ?string
    {
        $consulta = $this->conexion->prepare('SELECT nombre FROM usuarios WHERE id = :id LIMIT 1');
        $consulta->execute(['id' => $id]);
        $nombre = $consulta->fetchColumn();
        return $nombre === false ? null : (string) $nombre;
    }

    private function sincronizarMecanico(int $usuarioId, string $nombre): void
    {
        $consulta = $this->conexion->prepare(
            'SELECT id FROM mecanicos WHERE usuario_id = :usuario_id LIMIT 1'
        );
        $consulta->execute(['usuario_id' => $usuarioId]);
        $mecanicoId = $consulta->fetchColumn();
        if ($mecanicoId !== false) {
            $actualizar = $this->conexion->prepare(
                'UPDATE mecanicos SET nombre = :nombre WHERE id = :id'
            );
            $actualizar->execute(['nombre' => $nombre, 'id' => $mecanicoId]);
            return;
        }

        $consulta = $this->conexion->prepare(
            'SELECT id FROM mecanicos
             WHERE nombre = :nombre AND usuario_id IS NULL
             ORDER BY id ASC LIMIT 1'
        );
        $consulta->execute(['nombre' => $nombre]);
        $mecanicoId = $consulta->fetchColumn();
        if ($mecanicoId !== false) {
            $actualizar = $this->conexion->prepare(
                'UPDATE mecanicos SET usuario_id = :usuario_id WHERE id = :id'
            );
            $actualizar->execute(['usuario_id' => $usuarioId, 'id' => $mecanicoId]);
            return;
        }

        $insertar = $this->conexion->prepare(
            'INSERT INTO mecanicos (usuario_id, nombre) VALUES (:usuario_id, :nombre)'
        );
        $insertar->execute([
            'usuario_id' => $usuarioId,
            'nombre' => $nombre,
        ]);
    }
}
