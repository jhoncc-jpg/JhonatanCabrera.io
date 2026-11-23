<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: /../layouts/topbar.php.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
$csrf = $_SESSION['csrf_token'];
$puedeEditar = $_SESSION['rol'] === 'administrador';
$estadoFiltro = $filtros['estado'] ?? 'todos';

/**
 * Escapa texto de un mecanico antes de imprimirlo como contenido HTML.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escaparMecanico($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mecánicos | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
    <?php
    $tituloTopbar = 'Mecánicos';
    require __DIR__ . '/../layouts/topbar.php';
    ?>
    <div class="ag-content">
        <div class="ag-page-header">
            <div>
                <p class="ag-eyebrow">Equipo técnico</p>
                <h1>Mecánicos</h1>
                <p class="ag-page-description">Mantén actualizado el equipo disponible para asignar órdenes de trabajo.</p>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" role="status"><?= escaparMecanico($mensaje) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= escaparMecanico($error) ?></div>
        <?php endif; ?>

        <div class="ag-guidance-note">
            <span class="ag-guidance-icon" aria-hidden="true">i</span>
            <p>Los mecánicos inactivos permanecen en el historial y no aparecen al asignar una orden. Puedes reactivarlos cuando vuelvan a estar disponibles.</p>
        </div>

        <?php if ($puedeEditar): ?>
            <section class="card shadow-sm mb-4" aria-labelledby="nuevo-mecanico-titulo">
                <div class="card-body">
                    <h2 class="h5" id="nuevo-mecanico-titulo">Nuevo mecánico</h2>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparMecanico($csrf) ?>">
                        <input type="hidden" name="accion" value="crear">
                        <div class="col-md-4">
                            <label class="form-label" for="nombre-mecanico">Nombre *</label>
                            <input id="nombre-mecanico" name="nombre" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="telefono-mecanico">Teléfono</label>
                            <input id="telefono-mecanico" name="telefono" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="especialidad-mecanico">Especialidad</label>
                            <input id="especialidad-mecanico" name="especialidad" class="form-control" maxlength="100">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Registrar mecánico</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card shadow-sm" aria-label="Listado de mecánicos">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end mb-3" data-live-search="mecanicos">
                    <input type="hidden" name="page" value="mecanicos">
                    <div class="col-md-7">
                        <label class="form-label" for="buscar-mecanico">Buscar</label>
                        <input id="buscar-mecanico" name="q" value="<?= escaparMecanico($busqueda) ?>" class="form-control" maxlength="100" placeholder="Nombre, teléfono o especialidad">
                    </div>
                    <?php if ($puedeEditar): ?>
                        <div class="col-md-3">
                            <label class="form-label" for="estado-mecanico">Estado</label>
                            <select class="form-select" name="estado" id="estado-mecanico">
                                <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                                <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                                <option value="inactivo" <?= $estadoFiltro === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <button class="btn btn-outline-primary w-100" type="submit">Buscar</button>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" data-live-search-results="mecanicos">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Nombre</th>
                                <th scope="col">Teléfono</th>
                                <th scope="col">Especialidad</th>
                                <th scope="col">Estado</th>
                                <?php if ($puedeEditar): ?><th scope="col">Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($mecanicos)): ?>
                            <tr>
                                <td colspan="<?= $puedeEditar ? 5 : 4 ?>" class="text-center py-4">
                                    <?php if ($busqueda !== ''): ?>
                                        No se encontraron mecánicos para “<?= escaparMecanico($busqueda) ?>”.
                                        <a href="?page=mecanicos">Limpiar búsqueda</a>
                                    <?php else: ?>
                                        Aún no hay mecánicos registrados.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($mecanicos as $mecanico): ?>
                                <tr>
                                    <td><?= escaparMecanico($mecanico['nombre']) ?></td>
                                    <td><?= escaparMecanico($mecanico['telefono']) ?></td>
                                    <td><?= escaparMecanico($mecanico['especialidad']) ?></td>
                                    <td>
                                        <span class="badge <?= (int) $mecanico['activo'] === 1 ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= (int) $mecanico['activo'] === 1 ? 'Activo' : 'Inactivo' ?>
                                        </span>
                                    </td>
                                    <?php if ($puedeEditar): ?>
                                        <td class="d-flex gap-1 flex-wrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary editar-mecanico"
                                                data-id="<?= (int) $mecanico['id'] ?>"
                                                data-nombre="<?= escaparMecanico($mecanico['nombre']) ?>"
                                                data-telefono="<?= escaparMecanico($mecanico['telefono']) ?>"
                                                data-especialidad="<?= escaparMecanico($mecanico['especialidad']) ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editarMecanicoModal"
                                            >Editar</button>
                                            <form method="POST" data-confirm="<?= (int) $mecanico['activo'] === 1 ? '¿Confirmas la desactivación de este mecánico?' : '¿Confirmas la activación de este mecánico?' ?>">
                                                <input type="hidden" name="csrf_token" value="<?= escaparMecanico($csrf) ?>">
                                                <input type="hidden" name="accion" value="<?= (int) $mecanico['activo'] === 1 ? 'desactivar' : 'activar' ?>">
                                                <input type="hidden" name="id" value="<?= (int) $mecanico['id'] ?>">
                                                <button class="btn btn-sm <?= (int) $mecanico['activo'] === 1 ? 'btn-outline-danger' : 'btn-outline-success' ?>" type="submit">
                                                    <?= (int) $mecanico['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                                </button>
                                            </form>
                                            <form method="POST" data-confirm="¿Eliminar permanentemente este mecánico? Solo es posible si no tiene órdenes asociadas.">
                                                <input type="hidden" name="csrf_token" value="<?= escaparMecanico($csrf) ?>">
                                                <input type="hidden" name="accion" value="eliminar">
                                                <input type="hidden" name="id" value="<?= (int) $mecanico['id'] ?>">
                                                <button class="btn btn-sm btn-outline-dark" type="submit">Eliminar</button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</main>

<?php if ($puedeEditar): ?>
    <div class="modal fade" id="editarMecanicoModal" tabindex="-1" aria-labelledby="editarMecanicoTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editarMecanicoTitulo">Editar mecánico</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparMecanico($csrf) ?>">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" id="editar-id">
                        <div class="col-12">
                            <label class="form-label" for="editar-nombre">Nombre *</label>
                            <input name="nombre" id="editar-nombre" class="form-control" maxlength="100" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="editar-telefono">Teléfono</label>
                            <input name="telefono" id="editar-telefono" class="form-control" maxlength="20">
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="editar-especialidad">Especialidad</label>
                            <input name="especialidad" id="editar-especialidad" class="form-control" maxlength="100">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/live-search.js" defer></script>
<script src="js/mecanicos.js" defer></script>
</body>
</html>
