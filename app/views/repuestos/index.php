<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: /../layouts/topbar.php.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
$csrf = $_SESSION['csrf_token'];
$puedeEditar = in_array($_SESSION['rol'], ['administrador', 'recepcionista'], true);
$estadoFiltro = $filtros['estado'] ?? 'activo';

/**
 * Escapa texto de un repuesto antes de imprimirlo como contenido HTML.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escaparRepuesto($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventario | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
    <?php
    $tituloTopbar = 'Inventario';
    require __DIR__ . '/../layouts/topbar.php';
    ?>
    <div class="ag-content">
        <div class="ag-page-header">
            <div>
                <p class="ag-eyebrow">Control de existencias</p>
                <h1>Inventario de repuestos</h1>
                <p class="ag-page-description">Consulta disponibilidad y registra cada entrada, salida o ajuste.</p>
                <a class="btn btn-outline-primary mt-2" href="?page=reporte-movimientos">Reporte de Movimientos (Bs.)</a>
            </div>
        </div>

        <?php if (!empty($mensaje)): ?>
            <div class="alert alert-success" role="status"><?= escaparRepuesto($mensaje) ?></div>
        <?php endif; ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= escaparRepuesto($error) ?></div>
        <?php endif; ?>

        <div class="ag-guidance-note">
            <span class="ag-guidance-icon" aria-hidden="true">i</span>
            <p><strong>Importante:</strong> usa <strong>Entrada</strong> para sumar unidades, <strong>Salida</strong> para descontarlas y <strong>Ajuste</strong> para establecer el stock contado.</p>
        </div>

        <?php if ($_SESSION['rol'] === 'administrador'): ?>
            <section class="card shadow-sm mb-4" aria-labelledby="reporte-inventario-titulo">
                <div class="card-body">
                    <h2 class="h5" id="reporte-inventario-titulo">Reporte Excel de inventario</h2>
                    <p class="form-text">Se incluyen repuestos activos. El periodo filtra los movimientos; el stock mostrado es el actual y las filas con stock bajo se resaltan.</p>
                    <form action="?page=reporte-inventario" method="GET" class="row g-3 align-items-end" data-inventory-report>
                        <div class="col-md-4">
                            <label class="form-label" for="reporte-inventario-desde">Movimientos desde</label>
                            <input class="form-control" type="date" name="desde" id="reporte-inventario-desde">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="reporte-inventario-hasta">Movimientos hasta</label>
                            <input class="form-control" type="date" name="hasta" id="reporte-inventario-hasta">
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-outline-success w-100" type="submit">Descargar Excel</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($puedeEditar): ?>
            <section class="card shadow-sm mb-4" aria-labelledby="nuevo-repuesto-titulo">
                <div class="card-body">
                    <h2 class="h5" id="nuevo-repuesto-titulo">Nuevo repuesto</h2>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparRepuesto($csrf) ?>">
                        <input type="hidden" name="accion" value="crear">
                        <div class="col-md-3">
                            <label class="form-label" for="codigo-repuesto">Código *</label>
                            <input id="codigo-repuesto" name="codigo" class="form-control text-uppercase" maxlength="40" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="nombre-repuesto">Nombre *</label>
                            <input id="nombre-repuesto" name="nombre" class="form-control" maxlength="120" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="precio-repuesto">Precio de referencia</label>
                            <input id="precio-repuesto" name="precio_referencia" type="number" step="0.01" min="0" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="descripcion-repuesto">Descripción</label>
                            <input id="descripcion-repuesto" name="descripcion" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="stock-repuesto">Stock inicial</label>
                            <input id="stock-repuesto" name="stock" type="number" min="0" step="1" value="0" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="stock-minimo-repuesto">Stock mínimo</label>
                            <input id="stock-minimo-repuesto" name="stock_minimo" type="number" min="0" step="1" value="0" class="form-control">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary" type="submit">Registrar repuesto</button>
                        </div>
                    </form>
                </div>
            </section>
        <?php endif; ?>

        <section class="card shadow-sm" aria-label="Listado de repuestos">
            <div class="card-body">
                <form method="GET" class="row g-2 align-items-end mb-3" data-live-search="repuestos">
                    <input type="hidden" name="page" value="repuestos">
                    <div class="col-md-3">
                        <label class="form-label" for="buscar-repuesto">Buscar</label>
                        <input id="buscar-repuesto" name="q" value="<?= escaparRepuesto($busqueda) ?>" maxlength="120" class="form-control" placeholder="Código, nombre o descripción">
                    </div>
                    <?php if ($_SESSION['rol'] === 'administrador'): ?>
                        <div class="col-md-2">
                            <label class="form-label" for="estado-repuesto">Estado</label>
                            <select class="form-select" name="estado" id="estado-repuesto">
                                <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                                <option value="inactivo" <?= $estadoFiltro === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                                <option value="todos" <?= $estadoFiltro === 'todos' ? 'selected' : '' ?>>Todos</option>
                            </select>
                        </div>
                    <?php endif; ?>
                    <div class="col-md-1">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="bajos" value="1" id="solo-stock-bajo" <?= $soloBajos ? 'checked' : '' ?>>
                            <label class="form-check-label" for="solo-stock-bajo">Stock bajo</label>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="repuestos-fecha-inicio">Fecha Inicio</label>
                        <input class="form-control" type="date" name="fecha_inicio" id="repuestos-fecha-inicio" value="<?= escaparRepuesto($filtros['fecha_inicio'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label" for="repuestos-fecha-fin">Fecha Fin</label>
                        <input class="form-control" type="date" name="fecha_fin" id="repuestos-fecha-fin" value="<?= escaparRepuesto($filtros['fecha_fin'] ?? '') ?>">
                    </div>
                    <div class="col-md-1">
                        <button class="btn btn-outline-primary w-100" type="submit">Buscar</button>
                    </div>
                    <div class="col-md-1">
                        <a class="btn btn-secondary w-100" href="?page=repuestos">Limpiar</a>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" data-live-search-results="repuestos">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col">Código</th>
                                <th scope="col">Nombre</th>
                                <th scope="col">FECHA REGISTRO</th>
                                <th scope="col">Stock actual</th>
                                <th scope="col">Mínimo</th>
                                <th scope="col">Precio de referencia</th>
                                <th scope="col">Disponibilidad</th>
                                <?php if ($puedeEditar): ?><th scope="col">Acciones</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($repuestos)): ?>
                            <tr>
                                <td colspan="<?= $puedeEditar ? 8 : 7 ?>" class="text-center py-4">
                                    <?php if ($busqueda !== '' || $soloBajos || !empty($filtros['fecha_inicio']) || !empty($filtros['fecha_fin'])): ?>
                                        No hay repuestos que coincidan con los filtros.
                                        <a href="?page=repuestos">Limpiar filtros</a>
                                    <?php else: ?>
                                        Aún no hay repuestos registrados.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($repuestos as $repuesto): ?>
                                <tr>
                                    <td><strong><?= escaparRepuesto($repuesto['codigo']) ?></strong></td>
                                    <td>
                                        <?= escaparRepuesto($repuesto['nombre']) ?><br>
                                        <small class="text-secondary"><?= escaparRepuesto($repuesto['descripcion']) ?></small>
                                    </td>
                                    <td class="text-nowrap"><?= escaparRepuesto((new DateTimeImmutable($repuesto['fecha_registro']))->format('d/m/Y H:i')) ?></td>
                                    <td><?= (int) $repuesto['stock'] ?></td>
                                    <td><?= (int) $repuesto['stock_minimo'] ?></td>
                                    <td><?= $repuesto['precio_referencia'] === null ? '—' : 'Bs. ' . number_format((float) $repuesto['precio_referencia'], 2, '.', ',') ?></td>
                                    <td>
                                        <?php if ((int) $repuesto['activo'] !== 1): ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php elseif ((int) $repuesto['stock'] <= (int) $repuesto['stock_minimo']): ?>
                                            <span class="badge bg-danger">Reponer</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if ($puedeEditar): ?>
                                        <td class="d-flex gap-1 flex-wrap">
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-primary editar-repuesto"
                                                data-id="<?= (int) $repuesto['id'] ?>"
                                                data-codigo="<?= escaparRepuesto($repuesto['codigo']) ?>"
                                                data-nombre="<?= escaparRepuesto($repuesto['nombre']) ?>"
                                                data-descripcion="<?= escaparRepuesto($repuesto['descripcion']) ?>"
                                                data-minimo="<?= (int) $repuesto['stock_minimo'] ?>"
                                                data-precio="<?= escaparRepuesto($repuesto['precio_referencia']) ?>"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editarRepuestoModal"
                                            >Editar</button>
                                            <?php if ((int) $repuesto['activo'] === 1): ?>
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-success movimiento-repuesto"
                                                    data-id="<?= (int) $repuesto['id'] ?>"
                                                    data-nombre="<?= escaparRepuesto($repuesto['nombre']) ?>"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#movimientoModal"
                                                >Registrar movimiento</button>
                                            <?php endif; ?>
                                            <form method="POST" data-confirm="<?= (int) $repuesto['activo'] === 1 ? '¿Desactivar este repuesto?' : '¿Activar este repuesto?' ?>">
                                                <input type="hidden" name="csrf_token" value="<?= escaparRepuesto($csrf) ?>">
                                                <input type="hidden" name="accion" value="<?= (int) $repuesto['activo'] === 1 ? 'desactivar' : 'activar' ?>">
                                                <input type="hidden" name="id" value="<?= (int) $repuesto['id'] ?>">
                                                <button class="btn btn-sm btn-outline-secondary" type="submit">
                                                    <?= (int) $repuesto['activo'] === 1 ? 'Desactivar' : 'Activar' ?>
                                                </button>
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
    <div class="modal fade" id="editarRepuestoModal" tabindex="-1" aria-labelledby="editarRepuestoTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="editarRepuestoTitulo">Editar repuesto</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparRepuesto($csrf) ?>">
                        <input type="hidden" name="accion" value="editar">
                        <input type="hidden" name="id" id="editar-id">
                        <div class="col-md-4">
                            <label class="form-label" for="editar-codigo">Código *</label>
                            <input name="codigo" id="editar-codigo" class="form-control" maxlength="40" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" for="editar-nombre">Nombre *</label>
                            <input name="nombre" id="editar-nombre" class="form-control" maxlength="120" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="editar-descripcion">Descripción</label>
                            <input name="descripcion" id="editar-descripcion" class="form-control" maxlength="255">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editar-minimo">Stock mínimo</label>
                            <input name="stock_minimo" id="editar-minimo" type="number" min="0" step="1" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="editar-precio">Precio</label>
                            <input name="precio_referencia" id="editar-precio" type="number" step="0.01" min="0" class="form-control">
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

    <div class="modal fade" id="movimientoModal" tabindex="-1" aria-labelledby="movimientoTitulo" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" data-movement-form>
                    <div class="modal-header">
                        <h2 class="modal-title h5" id="movimientoTitulo">Movimiento: <span id="movimiento-nombre"></span></h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparRepuesto($csrf) ?>">
                        <input type="hidden" name="accion" value="movimiento">
                        <input type="hidden" name="id" id="movimiento-id">
                        <div class="col-md-6">
                            <label class="form-label" for="tipo-movimiento">Tipo</label>
                            <select name="tipo" id="tipo-movimiento" class="form-select" required>
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="ajuste">Ajuste de stock</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="cantidad-movimiento">Cantidad / nuevo stock</label>
                            <input name="cantidad" id="cantidad-movimiento" type="number" min="0" step="1" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="observacion-movimiento">Observación</label>
                            <input name="observacion" id="observacion-movimiento" class="form-control" maxlength="255">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button class="btn btn-primary" type="submit">Registrar movimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/live-search.js" defer></script>
<script src="js/repuestos.js" defer></script>
<script src="js/reporte-repuestos.js" defer></script>
</body>
</html>
