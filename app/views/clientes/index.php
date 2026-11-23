
<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
// La plantilla recibe del controlador $clientes, $busqueda, mensajes y permisos.
// escapar() evita insertar texto de la base de datos como HTML ejecutable.
/**
 * Escapa un valor antes de imprimirlo en HTML para que los caracteres especiales no se interpreten como marcado.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escapar($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
$csrf = $_SESSION['csrf_token'];
$rolActual = $_SESSION['rol'] ?? '';
$puedeCrear = in_array($rolActual, ['administrador', 'recepcionista'], true);
$puedeEditar = in_array($rolActual, ['administrador', 'recepcionista'], true);
$estadoFiltro = $filtros['estado'] ?? 'activo';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Clientes - AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=20261116-responsive1" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
<?php $tituloTopbar = 'Clientes'; require __DIR__ . '/../layouts/topbar.php'; ?>
<div class="ag-content">
    <div class="ag-page-header"><div><p class="ag-eyebrow">Personas</p><h1>Clientes</h1><p class="ag-page-description">Registra propietarios para asociarlos a sus vehículos y órdenes de trabajo.</p></div></div>

    <?php if (!empty($mensaje)): ?>
        <div class="alert alert-success"><?= escapar($mensaje) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= escapar($error) ?></div>
    <?php endif; ?>
    <div class="ag-guidance-note"><span class="ag-guidance-icon" aria-hidden="true">1</span><p><strong>Flujo recomendado:</strong> registra al cliente primero. Después podrás elegirlo al registrar su vehículo y crear una orden.</p></div>

    <!-- El formulario envía accion=crear; ClienteController procesa ese POST. -->
    <?php if ($puedeCrear): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h2 class="h5">Nuevo cliente</h2>
            <form method="POST" class="row g-3">
                <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                <input type="hidden" name="accion" value="crear">
                <div class="col-md-4"><label class="form-label">Nombre *</label><input name="nombre" class="form-control" maxlength="100" required></div>
                <div class="col-md-4"><label class="form-label">Teléfono *</label><input name="telefono" class="form-control" maxlength="20" required></div>
                <div class="col-md-4"><label class="form-label">Correo</label><input name="correo" type="email" class="form-control" maxlength="100"></div>
                <div class="col-12"><label class="form-label">Dirección *</label><input name="direccion" class="form-control" maxlength="200" required></div>
                <div class="col-12"><button class="btn btn-primary">Registrar cliente</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <!-- GET actualiza filtros y permite compartir/enlazar una búsqueda. -->
            <form method="GET" class="row g-2 mb-3" data-live-search="clientes">
                <input type="hidden" name="page" value="clientes">
                <div class="col-md-4"><label class="visually-hidden" for="buscar-cliente">Buscar clientes</label><input id="buscar-cliente" name="q" maxlength="100" value="<?= escapar($busqueda ?? '') ?>" class="form-control" placeholder="Buscar por nombre, teléfono, correo o dirección"></div>
                <?php if ($rolActual === 'administrador'): ?>
                <div class="col-md-2"><label class="visually-hidden" for="estado-cliente">Estado</label><select class="form-select" name="estado" id="estado-cliente"><option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option><option value="inactivo" <?= $estadoFiltro === 'inactivo' ? 'selected' : '' ?>>Inactivos</option><option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option></select></div>
                <?php endif; ?>
                <div class="col-md-2"><label class="visually-hidden" for="clientes-desde">Desde</label><input class="form-control" type="date" name="desde" id="clientes-desde" value="<?= escapar($filtros['desde'] ?? '') ?>" aria-label="Fecha inicial"></div>
                <div class="col-md-2"><label class="visually-hidden" for="clientes-hasta">Hasta</label><input class="form-control" type="date" name="hasta" id="clientes-hasta" value="<?= escapar($filtros['hasta'] ?? '') ?>" aria-label="Fecha final"></div>
                <div class="col-md-1"><button type="submit" class="btn btn-outline-primary w-100">Buscar</button></div>
                <div class="col-md-1"><a class="btn btn-secondary w-100" href="index.php?page=clientes">Limpiar</a></div>
            </form>

            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle" data-live-search-results="clientes">
                    <thead class="table-dark"><tr><th>Nombre</th><th>Teléfono</th><th>Correo</th><th>Dirección</th><th>FECHA REGISTRO</th><th>Estado</th><?php if ($puedeEditar): ?><th>Acciones</th><?php endif; ?></tr></thead>
                    <tbody>
                    <?php if (empty($clientes)): ?>
                        <tr><td colspan="<?= $puedeEditar ? 7 : 6 ?>" class="text-center py-4"><?php if (($busqueda ?? '') !== ''): ?>No se encontraron clientes para “<?= escapar($busqueda) ?>”. <a href="?page=clientes">Limpiar búsqueda</a><?php else: ?>Aún no hay clientes registrados. Usa el formulario <strong>Nuevo cliente</strong> para comenzar.<?php endif; ?></td></tr>
                    <?php else: foreach ($clientes as $cliente): ?>
                        <tr>
                            <td><?= escapar($cliente['nombre']) ?></td><td><?= escapar($cliente['telefono']) ?></td><td><?= escapar($cliente['correo']) ?></td><td><?= escapar($cliente['direccion']) ?></td><td><?= escapar((new DateTimeImmutable($cliente['creado_en']))->format('d/m/Y')) ?></td>
                            <td><span class="badge <?= (int) $cliente['activo'] === 1 ? 'bg-success' : 'bg-secondary' ?>"><?= (int) $cliente['activo'] === 1 ? 'Activo' : 'Inactivo' ?></span></td>
                            <?php if ($puedeEditar): ?>
                            <td class="d-flex gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary editar-cliente" data-id="<?= (int) $cliente['id'] ?>" data-nombre="<?= escapar($cliente['nombre']) ?>" data-telefono="<?= escapar($cliente['telefono']) ?>" data-correo="<?= escapar($cliente['correo']) ?>" data-direccion="<?= escapar($cliente['direccion']) ?>" data-bs-toggle="modal" data-bs-target="#editarClienteModal">Editar</button>
                                <form method="POST" data-confirm="<?= (int) $cliente['activo'] === 1 ? '¿Confirmas la desactivación de este cliente?' : '¿Confirmas la activación de este cliente?' ?>"><input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>"><input type="hidden" name="accion" value="<?= (int) $cliente['activo'] === 1 ? 'desactivar' : 'activar' ?>"><input type="hidden" name="id" value="<?= (int) $cliente['id'] ?>"><button class="btn btn-sm <?= (int) $cliente['activo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>"><?= (int) $cliente['activo'] === 1 ? 'Desactivar' : 'Activar' ?></button></form>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editarClienteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h2 class="modal-title h5">Editar cliente</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body row g-3">
                <input type="hidden" name="csrf_token" value="<?= escapar($csrf) ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" id="editar-id">
                <div class="col-12"><label class="form-label">Nombre *</label><input class="form-control" name="nombre" id="editar-nombre" maxlength="100" required></div>
                <div class="col-md-6"><label class="form-label">Teléfono *</label><input class="form-control" name="telefono" id="editar-telefono" maxlength="20" required></div>
                <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" name="correo" type="email" id="editar-correo" maxlength="100"></div>
                <div class="col-12"><label class="form-label">Dirección *</label><input class="form-control" name="direccion" id="editar-direccion" maxlength="200" required></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar cambios</button></div>
        </form>
    </div></div>
</div>
<!-- Los archivos externos conservan las interacciones del formulario y la búsqueda en vivo. -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/live-search.js" defer></script>
<script src="js/clientes.js" defer></script>
</main>
</body>
</html>
