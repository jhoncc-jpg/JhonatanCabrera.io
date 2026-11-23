<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */

/**
 * Escapa texto de un usuario antes de imprimirlo como contenido HTML.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escaparUsuario($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Traduce el identificador de rol del usuario a una etiqueta visible.
 * Parametro $rol: rol que se consulta o se va a asignar.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
// CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
function etiquetaRolUsuario(string $rol): string
{
    return [
        'administrador' => 'Administrador',
        'recepcionista' => 'Recepcionista',
        'mecanico' => 'Mecánico',
    ][$rol] ?? ucfirst($rol);
}

$csrf = escaparUsuario($_SESSION['csrf_token'] ?? '');
$usuarioActual = (int) ($_SESSION['usuario_id'] ?? 0);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=usuarios-mvc1" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
    <?php $tituloTopbar = 'Usuarios'; require __DIR__ . '/../layouts/topbar.php'; ?>
    <div class="ag-content">
        <div class="ag-page-header">
            <div>
                <p class="ag-eyebrow">Administración</p>
                <h1>Gestión de usuarios</h1>
                <p class="ag-page-description">Administra cuentas, roles y acceso al sistema.</p>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?><div class="alert alert-success"><?= escaparUsuario($mensaje) ?></div><?php endif; ?>
        <?php if (!empty($error)): ?><div class="alert alert-danger" role="alert"><?= escaparUsuario($error) ?></div><?php endif; ?>

        <section class="card shadow-sm mb-4">
            <div class="card-body">
                <h2 class="h5">Nuevo usuario</h2>
                <form method="POST" class="row g-3">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="accion" value="crear">
                    <div class="col-md-3">
                        <label class="form-label" for="nuevo-nombre">Nombre</label>
                        <input id="nuevo-nombre" name="nombre" class="form-control" maxlength="100" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nuevo-nombre-usuario">Nombre de usuario</label>
                        <input id="nuevo-nombre-usuario" name="usuario" class="form-control" minlength="3" maxlength="50" pattern="[A-Za-z0-9._-]+" title="Use letras, números, punto, guion o guion bajo" autocomplete="username" autocapitalize="off" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nuevo-password">Contraseña</label>
                        <input id="nuevo-password" name="password" type="password" class="form-control" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label" for="nuevo-rol">Rol</label>
                        <select id="nuevo-rol" name="rol_id" class="form-select" required>
                            <option value="" selected disabled>Seleccione un rol</option>
                            <?php foreach ($roles ?? [] as $rol): ?>
                                <option value="<?= (int) $rol['id'] ?>"><?= htmlspecialchars(ucfirst((string) $rol['nombre']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12"><button class="btn btn-primary" <?= empty($roles) ? 'disabled' : '' ?>>Crear usuario</button></div>
                </form>
            </div>
        </section>

        <section class="card shadow-sm">
            <div class="card-body table-responsive">
                <form method="GET" class="row g-2 mb-3">
                    <input type="hidden" name="page" value="usuarios">
                    <div class="col-md-7">
                        <label class="visually-hidden" for="buscar-usuario">Buscar usuario</label>
                        <input id="buscar-usuario" name="q" value="<?= escaparUsuario($filtros['busqueda']) ?>" maxlength="120" class="form-control" placeholder="Buscar por nombre o nombre de usuario">
                    </div>
                    <div class="col-md-3">
                        <label class="visually-hidden" for="filtro-estado-usuario">Filtrar por estado</label>
                        <select id="filtro-estado-usuario" name="estado" class="form-select">
                            <option value="todos" <?= $filtros['estado'] === 'todos' ? 'selected' : '' ?>>Todos los estados</option>
                            <option value="activo" <?= $filtros['estado'] === 'activo' ? 'selected' : '' ?>>Activos</option>
                            <option value="inactivo" <?= $filtros['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-2"><button class="btn btn-outline-primary w-100">Filtrar</button></div>
                </form>
                <table class="table align-middle">
                    <thead class="table-dark">
                    <tr><th>Nombre</th><th>Usuario</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($usuarios === []): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4"><?php if ($filtros['busqueda'] !== '' || $filtros['estado'] !== 'todos'): ?>No se encontraron usuarios con esos filtros. <a href="?page=usuarios">Limpiar filtros</a><?php else: ?>No hay usuarios registrados.<?php endif; ?></td></tr>
                    <?php else: foreach ($usuarios as $usuario): ?>
                        <tr>
                            <td><?= escaparUsuario($usuario['nombre']) ?></td>
                            <td><?= escaparUsuario($usuario['usuario']) ?></td>
                            <td>
                                <form method="POST" class="d-flex flex-wrap gap-2">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="accion" value="rol">
                                    <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                    <select name="rol_id" class="form-select form-select-sm" aria-label="Rol de <?= escaparUsuario($usuario['nombre']) ?>">
                                        <?php foreach ($roles ?? [] as $rol): ?>
                                            <option value="<?= (int) $rol['id'] ?>" <?= (int) $usuario['rol_id'] === (int) $rol['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars(ucfirst((string) $rol['nombre']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary">Cambiar</button>
                                </form>
                            </td>
                            <td><span class="badge <?= (int) $usuario['activo'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $usuario['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            <td>
                                <div class="d-flex flex-column align-items-start gap-2">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                        <input type="hidden" name="accion" value="<?= (int) $usuario['activo'] === 1 ? 'desactivar' : 'activar' ?>">
                                        <button class="btn btn-sm <?= (int) $usuario['activo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" <?= (int) $usuario['id'] === $usuarioActual && (int) $usuario['activo'] === 1 ? 'disabled title="No puedes desactivar tu propia cuenta."' : '' ?>>
                                            <?= (int) $usuario['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="d-flex flex-wrap gap-2">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="id" value="<?= (int) $usuario['id'] ?>">
                                        <input type="hidden" name="accion" value="restablecer">
                                        <input type="password" name="password" class="form-control form-control-sm" minlength="8" maxlength="72" required placeholder="Nueva contraseña" aria-label="Nueva contraseña para <?= escaparUsuario($usuario['nombre']) ?>">
                                        <button class="btn btn-sm btn-outline-warning text-nowrap">Restablecer</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/usuarios.js" defer></script>
</body>
</html>
