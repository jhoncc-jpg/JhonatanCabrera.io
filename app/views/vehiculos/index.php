<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
// VehiculoController entrega vehículos, propietarios, tarifas, mensajes y permisos.
// Las funciones siguientes convierten valores internos en etiquetas visibles.
/**
 * Escapa texto de un vehiculo antes de imprimirlo como contenido HTML.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escaparVehiculo($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
/**
 * Traduce el estado interno de un vehiculo a la etiqueta que muestra la vista.
 * Parametro $estado: nuevo estado que se valida o persiste.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function etiquetaEstadoVehiculo(string $estado): string
{
    return [
        'registrado' => 'Registrado',
        'en_revision' => 'En revisión',
        'pendiente_repuesto' => 'Pendiente de repuesto',
        'en_reparacion' => 'En reparación',
        'listo' => 'Listo para entrega',
        'entregado' => 'Entregado',
    ][$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}
/**
 * Asocia el estado de un vehiculo con la clase CSS que representa su apariencia.
 * Parametro $estado: nuevo estado que se valida o persiste.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function claseEstadoVehiculo(string $estado): string
{
    if (in_array($estado, ['listo', 'entregado'], true)) return 'is-complete';
    if ($estado === 'registrado') return 'is-neutral';
    return 'is-active';
}
$csrf = $_SESSION['csrf_token'];
$rolActual = $_SESSION['rol'] ?? '';
$puedeCrear = $rolActual === 'recepcionista';
$puedeEditar = $rolActual === 'recepcionista';
$puedeVerPrecios = in_array($rolActual, ['administrador', 'recepcionista'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vehículos - AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=vehiculos-mvc1" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.css" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
<?php $tituloTopbar = 'Vehículos'; require __DIR__ . '/../layouts/topbar.php'; ?>
<div class="ag-content">
    <div class="ag-page-header"><div><p class="ag-eyebrow">Flota del taller</p><h1>Vehículos</h1><p class="ag-page-description">Mantén los datos del vehículo vinculados a su propietario.</p></div></div>

    <?php if (!empty($mensaje)): ?><div class="alert alert-success"><?= escaparVehiculo($mensaje) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= escaparVehiculo($error) ?></div><?php endif; ?>
    <div class="ag-guidance-note"><span class="ag-guidance-icon" aria-hidden="true">2</span><p><strong>Antes de continuar:</strong> escribe el nombre del propietario. Si ya está registrado, el vehículo se vincula a su ficha; si es nuevo, completa su teléfono y dirección para crear su ficha correctamente.</p></div>

    <?php if ($rolActual === 'administrador'): ?>
    <section class="card shadow-sm mb-4" id="tarifas-trabajos">
        <div class="card-body">
            <h2 class="h5">Precios de trabajos sugeridos para las OT</h2>
            <p class="text-muted">Los cambios se aplican a los trabajos sugeridos que se agreguen desde ahora. Las órdenes existentes conservan sus precios registrados.</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= escaparVehiculo($csrf) ?>">
                <input type="hidden" name="accion" value="actualizar_tarifas">
                <?php foreach ($catalogoTareas as $grupo => $tareas): ?>
                    <details class="mb-3">
                        <summary class="fw-semibold"><?= escaparVehiculo($grupo) ?></summary>
                        <div class="table-responsive mt-2">
                            <table class="table table-sm align-middle">
                                <thead><tr><th>Trabajo</th><th class="ag-price-column">Precio (Bs)</th></tr></thead>
                                <tbody>
                                <?php foreach ($tareas as $tarea): ?>
                                    <tr>
                                        <td><?= escaparVehiculo($tarea) ?></td>
                                        <td><input class="form-control" type="number" step="0.01" min="0" required name="precios[<?= escaparVehiculo($tarea) ?>]" value="<?= escaparVehiculo($tarifasTrabajos[$tarea] ?? '') ?>"></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </details>
                <?php endforeach; ?>
                <button class="btn btn-primary">Guardar precios sugeridos</button>
            </form>
        </div>
    </section>
    <?php endif; ?>

    <!-- enctype permite que el navegador envíe imágenes dentro de $_FILES. -->
    <?php if ($puedeCrear): ?>
    <div class="card shadow-sm mb-4"><div class="card-body">
        <h2 class="h5">Nuevo vehículo</h2>
        <form method="POST" class="row g-3" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= escaparVehiculo($csrf) ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="col-md-4"><label class="form-label">Propietario *</label><input name="propietario" class="form-control" maxlength="100" placeholder="Nombre del propietario" required><small class="text-muted">Si aún no está registrado, completa también teléfono y dirección.</small></div>
            <div class="col-md-4"><label class="form-label">Teléfono del propietario</label><input name="telefono_propietario" class="form-control" maxlength="20"></div>
            <div class="col-md-4"><label class="form-label">Dirección del propietario</label><input name="direccion_propietario" class="form-control" maxlength="200"></div>
            <div class="col-md-2"><label class="form-label">Placa *</label><input name="placa" id="crear-placa" class="form-control placa-input text-uppercase" maxlength="20" pattern="[A-Za-z0-9 ]+" title="Solo puede contener letras, números y espacios" required></div>
            <div class="col-md-3"><label class="form-label">Marca *</label><input name="marca" class="form-control" maxlength="60" required></div>
            <div class="col-md-3"><label class="form-label">Modelo *</label><input name="modelo" class="form-control" maxlength="60" required></div>
            <div class="col-md-2"><label class="form-label">Año</label><input name="anio" type="number" min="1900" max="2100" class="form-control"></div>
            <div class="col-md-4"><label class="form-label">Color</label><input name="color" class="form-control" maxlength="40"></div>
            <div class="col-md-6"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" class="form-control" rows="3" maxlength="3000" placeholder="Describe el diagnóstico inicial"></textarea></div>
            <div class="col-md-3"><label class="form-label">Estado</label><select name="estado" class="form-select">
                <option value="registrado">Registrado</option>
                <option value="en_revision">En revisión</option>
                <option value="pendiente_repuesto">Pendiente de repuesto</option>
                <option value="en_reparacion">En reparación</option>
                <option value="listo">Listo</option>
                <option value="entregado">Entregado</option>
            </select></div>
            <div class="col-md-3">
                <label class="form-label">Fecha de registro</label>
                <input name="fecha_registro_fecha" type="text" lang="es" class="form-control fecha-registro-es" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Hora de registro</label>
                <input name="fecha_registro_hora" type="text" lang="es" class="form-control hora-registro-es" value="<?= date('H:i') ?>" required>
            </div>
            <div class="col-md-3"><label class="form-label">Imágenes</label><input name="imagenes[]" id="imagenesVehiculo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" multiple><small class="text-muted">Máximo 8, 10 MB por imagen. Puedes quitar una antes de guardar.</small><div id="previsualizacionVehiculo" class="d-flex flex-wrap gap-2 mt-2"></div></div>
            <div class="col-md-12"><button class="btn btn-primary">Registrar vehículo</button></div>
        </form>
    </div></div>
    <?php endif; ?>

    <div class="card shadow-sm"><div class="card-body">
        <!-- El atributo data-live-search conecta este formulario con live-search.js. -->
        <form method="GET" class="row g-2 mb-3" data-live-search="vehiculos">
            <input type="hidden" name="page" value="vehiculos">
            <div class="col-md-4"><input name="q" value="<?= escaparVehiculo($busqueda) ?>" maxlength="100" class="form-control" placeholder="Placa, marca, modelo o propietario"></div>
            <div class="col-md-2"><select name="estado_filtro" class="form-select" aria-label="Filtrar por estado">
                <option value="">Todos los estados</option>
                <?php foreach (['registrado' => 'Registrado', 'en_revision' => 'En revisión', 'pendiente_repuesto' => 'Pendiente de repuesto', 'en_reparacion' => 'En reparación', 'listo' => 'Listo', 'entregado' => 'Entregado'] as $valorEstado => $etiquetaEstado): ?>
                    <option value="<?= escaparVehiculo($valorEstado) ?>" <?= ($filtros['estado'] ?? null) === $valorEstado ? 'selected' : '' ?>><?= escaparVehiculo($etiquetaEstado) ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-md-2"><input name="desde" type="date" value="<?= escaparVehiculo($filtros['desde'] ?? '') ?>" class="form-control" aria-label="Fecha desde"></div>
            <div class="col-md-2"><input name="hasta" type="date" value="<?= escaparVehiculo($filtros['hasta'] ?? '') ?>" class="form-control" aria-label="Fecha hasta"></div>
            <div class="col-md-auto"><button class="btn btn-outline-primary">Filtrar</button></div>
        </form>

        <div class="table-responsive"><table class="table table-striped table-hover align-middle" data-live-search-results="vehiculos">
            <thead class="table-dark"><tr><th>Placa</th><th>Propietario</th><th>Marca</th><th>Modelo</th><th>Año</th><th>Color</th><th>Diagnóstico</th><th>Estado</th><?php if($puedeVerPrecios): ?><th>Total en órdenes (Bs)</th><?php endif; ?><th>Fecha y hora</th><th>Imágenes</th><?php if ($puedeEditar): ?><th>Acciones</th><?php endif; ?></tr></thead>
            <tbody>
            <?php if (empty($vehiculos)): ?>
                <tr><td colspan="<?= $puedeEditar ? ($puedeVerPrecios ? 12 : 11) : 10 ?>" class="text-center py-4"><?php if ($busqueda !== '' || !empty($filtros['estado']) || !empty($filtros['desde']) || !empty($filtros['hasta'])): ?>No se encontraron vehículos con esos filtros. <a href="?page=vehiculos">Limpiar filtros</a><?php else: ?>Aún no hay vehículos registrados. Usa el formulario <strong>Nuevo vehículo</strong> para agregar uno.<?php endif; ?></td></tr>
            <?php else: foreach ($vehiculos as $vehiculo): ?>
                <tr>
                    <td><strong><?= escaparVehiculo($vehiculo['placa']) ?></strong></td>
                    <td class="ag-name-column">
                        <?php
                        $propietario = trim((string) ($vehiculo['propietario'] ?? ''));
                        if ($propietario === '') {
                            echo '<span class="text-muted">—</span>';
                        } else {
                            $palabrasNombre = preg_split('/\s+/', $propietario, 2);
                            $primerNombre = $palabrasNombre[0] ?? '';
                            $hayMasNombre = count($palabrasNombre) > 1 || mb_strlen($propietario, 'UTF-8') > mb_strlen($primerNombre, 'UTF-8');
                        ?>
                            <button type="button"
                                class="ag-inline-toggle"
                                aria-expanded="false"
                                aria-label="Mostrar nombre completo">
                                <?= escaparVehiculo($primerNombre) ?><?= $hayMasNombre ? '…' : '' ?>
                            </button>
                            <div class="ag-inline-full" hidden><?= nl2br(escaparVehiculo($propietario)) ?></div>
                        <?php } ?>
                    </td>
                    <td><?= escaparVehiculo($vehiculo['marca']) ?></td><td><?= escaparVehiculo($vehiculo['modelo']) ?></td><td><?= escaparVehiculo($vehiculo['anio']) ?></td><td><?= escaparVehiculo($vehiculo['color']) ?></td>
                    <td class="ag-diagnosis-column">
                        <?php
                        $diagnostico = trim((string) ($vehiculo['diagnostico'] ?? ''));
                        if ($diagnostico === '') {
                            echo '<span class="text-muted">—</span>';
                        } else {
                            $palabras = preg_split('/\s+/', $diagnostico, 2);
                            $primeraPalabra = $palabras[0] ?? '';
                            $hayMasTexto = count($palabras) > 1 || mb_strlen($diagnostico, 'UTF-8') > mb_strlen($primeraPalabra, 'UTF-8');
                        ?>
                            <button type="button"
                                class="ag-diagnosis-toggle"
                                aria-expanded="false"
                                aria-label="Mostrar diagnóstico completo">
                                <?= escaparVehiculo($primeraPalabra) ?><?= $hayMasTexto ? '…' : '' ?>
                            </button>
                            <div class="ag-diagnosis-full" hidden><?= nl2br(escaparVehiculo($diagnostico)) ?></div>
                        <?php } ?>
                    </td>
                    <td><span class="badge ag-order-status <?= escaparVehiculo(claseEstadoVehiculo((string) $vehiculo['estado'])) ?>"><?= escaparVehiculo(etiquetaEstadoVehiculo((string) $vehiculo['estado'])) ?></span></td>
                    <?php if($puedeVerPrecios): ?><td class="text-nowrap">Bs <?= number_format((float)($vehiculo['total_ordenes']??0),2,'.',',') ?></td><?php endif; ?>
                    <td class="text-nowrap">
                        <?php if (!empty($vehiculo['fecha_registro'])): ?>
                            <?= escaparVehiculo(date('d/m/Y H:i', strtotime($vehiculo['fecha_registro']))) ?>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($vehiculo['imagenes'])): ?>
                            <?php $primeraImagen = $vehiculo['imagenes'][0]['ruta'] ?? ''; ?>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= escaparVehiculo($primeraImagen) ?>" alt="Foto del vehículo" class="ag-vehicle-list-thumb">
                                <button type="button" class="btn btn-sm btn-outline-primary ver-fotos-vehiculo"
                                    data-rutas="<?= escaparVehiculo(json_encode(array_map(function ($imagen) { return $imagen['ruta']; }, $vehiculo['imagenes']), JSON_UNESCAPED_SLASHES)) ?>"
                                    data-vehiculo="<?= escaparVehiculo((string) ($vehiculo['placa'] ?? 'vehiculo')) ?>"
                                    data-bs-toggle="modal" data-bs-target="#galeriaVehiculoModal">
                                    Ver <?= count($vehiculo['imagenes']) ?> foto<?= count($vehiculo['imagenes']) === 1 ? '' : 's' ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Sin fotos</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($puedeCrear): ?><td class="ag-actions-cell">
                        <button type="button" class="btn btn-sm btn-outline-primary editar-vehiculo" data-id="<?= (int) $vehiculo['id'] ?>" data-propietario="<?= escaparVehiculo($vehiculo['propietario']) ?>" data-placa="<?= escaparVehiculo($vehiculo['placa']) ?>" data-marca="<?= escaparVehiculo($vehiculo['marca']) ?>" data-modelo="<?= escaparVehiculo($vehiculo['modelo']) ?>" data-anio="<?= escaparVehiculo($vehiculo['anio']) ?>" data-color="<?= escaparVehiculo($vehiculo['color']) ?>" data-diagnostico="<?= escaparVehiculo($vehiculo['diagnostico']) ?>" data-estado="<?= escaparVehiculo($vehiculo['estado']) ?>" data-fecha_registro_fecha="<?= escaparVehiculo(!empty($vehiculo['fecha_registro']) ? date('Y-m-d', strtotime($vehiculo['fecha_registro'])) : '') ?>" data-fecha_registro_hora="<?= escaparVehiculo(!empty($vehiculo['fecha_registro']) ? date('H:i', strtotime($vehiculo['fecha_registro'])) : '') ?>" data-bs-toggle="modal" data-bs-target="#editarVehiculoModal">Editar</button>
                        <form method="POST" data-confirm-delete><input type="hidden" name="csrf_token" value="<?= escaparVehiculo($csrf) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int) $vehiculo['id'] ?>"><button class="btn btn-sm btn-outline-danger">Eliminar</button></form>
                    </td><?php endif; ?>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table></div>
    </div></div>
</div>

<div class="modal fade" id="editarVehiculoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
    <form method="POST" enctype="multipart/form-data"><div class="modal-header"><h2 class="modal-title h5">Editar vehículo</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body row g-3">
        <input type="hidden" name="csrf_token" value="<?= escaparVehiculo($csrf) ?>"><input type="hidden" name="accion" value="editar"><input type="hidden" name="id" id="editar-id">
        <div class="col-md-6"><label class="form-label">Propietario *</label><input name="propietario" id="editar-propietario" class="form-control" maxlength="100" required></div>
        <div class="col-md-6"><label class="form-label">Placa *</label><input name="placa" id="editar-placa" class="form-control placa-input text-uppercase" maxlength="20" pattern="[A-Za-z0-9 ]+" title="Solo puede contener letras, números y espacios" required></div>
        <div class="col-md-4"><label class="form-label">Marca *</label><input name="marca" id="editar-marca" class="form-control" maxlength="60" required></div>
        <div class="col-md-4"><label class="form-label">Modelo *</label><input name="modelo" id="editar-modelo" class="form-control" maxlength="60" required></div>
        <div class="col-md-4"><label class="form-label">Año</label><input name="anio" id="editar-anio" type="number" min="1900" max="2100" inputmode="numeric" class="form-control"></div>
        <div class="col-md-4"><label class="form-label">Color</label><input name="color" id="editar-color" class="form-control" maxlength="40"></div>
        <div class="col-md-7"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" id="editar-diagnostico" class="form-control" rows="3" maxlength="3000"></textarea></div>
        <div class="col-md-5"><label class="form-label">Estado</label><select name="estado" id="editar-estado" class="form-select">
            <option value="registrado">Registrado</option><option value="en_revision">En revisión</option><option value="pendiente_repuesto">Pendiente de repuesto</option><option value="en_reparacion">En reparación</option><option value="listo">Listo</option><option value="entregado">Entregado</option>
        </select></div>
        <div class="col-md-4"><label class="form-label">Fecha de registro (no editable)</label><input id="editar-fecha_registro_fecha" type="text" lang="es" class="form-control" readonly disabled></div>
        <div class="col-md-4"><label class="form-label">Hora de registro (no editable)</label><input id="editar-fecha_registro_hora" type="text" lang="es" class="form-control" readonly disabled></div>
        <div class="col-md-12"><label class="form-label">Agregar imágenes</label><input name="imagenes[]" id="editarImagenesVehiculo" type="file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" multiple><small class="text-muted">Máximo 8 imágenes por vehículo en total, 10 MB por archivo</small><div id="previsualizacionEditarVehiculo" class="d-flex flex-wrap gap-2 mt-2"></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary">Guardar cambios</button></div></form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/live-search.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/flatpickr.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr@4.6.13/dist/l10n/es.js" defer></script>
<script src="js/vehiculos.js" defer></script>
<div class="modal fade" id="galeriaVehiculoModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h2 class="modal-title h5">Fotos del vehículo</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <div class="position-relative d-inline-block">
            <img id="fotoVehiculoGrande" class="ag-vehicle-main-image" src="" alt="Imagen seleccionada del vehículo">
            <a id="descargarFotoVehiculo" class="btn btn-light btn-sm position-absolute top-0 end-0 m-2 shadow-sm" href="" download aria-label="Descargar imagen" title="Descargar imagen">⬇</a>
        </div>
        <div id="miniaturasVehiculo" class="ag-vehicle-thumbnails"></div>
    </div>
</div></div></div>
</main>
</body>
</html>
