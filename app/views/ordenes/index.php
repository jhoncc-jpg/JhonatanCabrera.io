<?php

/*
 * Proposito: Renderiza la interfaz o el documento de index con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
// OrdenController entrega la lista y, si se solicita ?id=..., el detalle de una OT.
// Estas funciones convierten estados internos en textos y estilos para la pantalla.
/**
 * Escapa texto de una orden antes de imprimirlo como contenido HTML.
 * Parametro $valor: valor de tipo mixed recibido para esta operacion.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function escaparOrden($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
/**
 * Traduce el estado interno de una orden a una etiqueta legible, adaptada al rol que consulta la vista.
 * Parametro $estado: nuevo estado que se valida o persiste.
 * Parametro $rol: rol que se consulta o se va a asignar.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function etiquetaEstadoOrden(string $estado, string $rol): string
{
    $etiquetas = [
        'registrada' => 'Nueva',
        'diagnostico' => 'En diagnóstico',
        'en_reparacion' => 'En reparación',
        'pendiente_repuesto' => 'Pendiente de repuesto',
        'lista' => $rol === 'mecanico' ? 'Trabajo terminado' : 'Lista para entrega',
        'entregada' => 'Entregada',
        'cancelada' => 'Cancelada',
    ];
    return $etiquetas[$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}
/**
 * Asocia el estado de una orden con la clase CSS que representa su apariencia.
 * Parametro $estado: nuevo estado que se valida o persiste.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function claseEstadoOrden(string $estado): string
{
    if (in_array($estado, ['lista', 'entregada'], true)) return 'is-complete';
    if ($estado === 'cancelada') return 'is-cancelled';
    return 'is-active';
}
/**
 * Traduce el estado interno de una tarea a un texto legible.
 * Parametro $estado: nuevo estado que se valida o persiste.
 * Devuelve: valor de tipo string, segun el resultado de la operacion.
 * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
 */
function etiquetaEstadoTarea(string $estado): string
{
    return [
        'pendiente' => 'Pendiente',
        'en_proceso' => 'En proceso',
        'completada' => 'Completada',
    ][$estado] ?? ucfirst(str_replace('_', ' ', $estado));
}
// La vista adapta botones/datos al rol, pero la autorización real se valida en PHP
// dentro de OrdenController y en las rutas.
$rolOrden = $_SESSION['rol'] ?? '';
$puedeCrearOrden = in_array($rolOrden, ['administrador', 'recepcionista'], true);
$puedeRegistrarTareas = in_array($rolOrden, ['administrador', 'mecanico'], true);
$puedeCambiarEstado = in_array($rolOrden, ['administrador', 'recepcionista', 'mecanico'], true);
$puedeRegistrarPago = in_array($rolOrden, ['administrador', 'recepcionista'], true);
$puedeVerCostos = in_array($rolOrden, ['administrador', 'recepcionista'], true);
$ordenesTerminadas = count(array_filter($ordenes, function ($orden) { return ($orden['estado'] ?? '') === 'lista'; }));
$parametroBusquedaOrden = ($busqueda !== '' ? '&q=' . rawurlencode($busqueda) : '')
    . (!empty($estadoFiltro) ? '&estado=' . rawurlencode($estadoFiltro) : '')
    . (!empty($filtros['desde']) ? '&desde=' . rawurlencode($filtros['desde']) : '')
    . (!empty($filtros['hasta']) ? '&hasta=' . rawurlencode($filtros['hasta']) : '');
$hayFiltrosOrden = $busqueda !== '' || $estadoFiltro !== '' || !empty($filtros['desde']) || !empty($filtros['hasta']);
$columnasOrdenes = 5 + ($rolOrden === 'mecanico' ? 2 : 0) + ($puedeVerCostos ? 1 : 0) + ($puedeCambiarEstado ? 1 : 0);
if (!$puedeVerCostos) {
    foreach ($ordenes as &$orden) {
        $orden['costo_final'] = null;
    }
    unset($orden);
    if (!empty($detalle)) {
        $detalle['orden']['costo_final'] = null;
        foreach ($detalle['tareas'] as &$tarea) {
            $tarea['costo'] = null;
        }
        unset($tarea);
        $detalle['pagos'] = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Órdenes | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=ordenes-mvc1" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
<?php $tituloTopbar = $rolOrden === 'mecanico' ? 'Mis órdenes' : 'Órdenes de trabajo'; require __DIR__ . '/../layouts/topbar.php'; ?>
<div class="ag-content">
    <div class="ag-page-header"><div><p class="ag-eyebrow">Servicio técnico</p><h1><?= $rolOrden === 'mecanico' ? 'Mis órdenes de trabajo' : 'Órdenes de trabajo' ?></h1><p class="ag-page-description"><?= $rolOrden === 'mecanico' ? 'Consulta tus órdenes asignadas y actualiza el avance de cada reparación.' : 'Registra, asigna y da seguimiento a los trabajos del taller.' ?></p></div><?php if(in_array($_SESSION['rol'],['administrador','recepcionista'],true)): ?><button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#crearOrdenModal" <?= empty($vehiculos) || empty($mecanicos) ? 'disabled title="Registra al menos un vehículo y un mecánico activo para crear una orden."' : '' ?>>＋ Nueva orden</button><?php endif; ?></div>
    <?php if ($puedeCrearOrden && empty($vehiculos)): ?><div class="alert alert-warning">Para crear una orden primero necesitas registrar un vehículo. <a href="?page=vehiculos" class="alert-link">Ir a Vehículos</a>.</div><?php elseif ($puedeCrearOrden && empty($mecanicos)): ?><div class="alert alert-warning">No hay mecánicos activos para asignar. <a href="?page=mecanicos" class="alert-link">Revisar el equipo técnico</a>.</div><?php endif; ?>
    <?php if(!empty($mensaje)): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje,ENT_QUOTES,'UTF-8') ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if($rolOrden==='recepcionista'&&in_array(($estadoFiltro ?? ''), ['', 'lista'], true)&&$ordenesTerminadas>0): ?><div class="alert alert-success" role="status">Tienes <?= $ordenesTerminadas ?> orden<?= $ordenesTerminadas===1?'':'es' ?> lista<?= $ordenesTerminadas===1?'':'s' ?> para coordinar su entrega.</div><?php endif; ?>
    <div class="ag-guidance-note"><span class="ag-guidance-icon" aria-hidden="true"><?= $rolOrden === 'mecanico' ? '✓' : 'i' ?></span><p><?php if($rolOrden==='mecanico'): ?>Abre <strong>Gestionar OT</strong> para registrar los trabajos realizados. Cuando todas las tareas estén completas, avisa a recepción desde la ficha.<?php elseif($rolOrden==='recepcionista'): ?>Crea la orden y asigna un mecánico. Cuando el trabajo figure como <strong>Lista para entrega</strong>, confirma el precio, registra el pago y coordina la entrega.<?php else: ?>Sigue cada orden desde su registro. El mecánico actualiza el trabajo técnico y recepción confirma el pago y la entrega.<?php endif; ?></p></div>
    <!-- La primera tabla lista órdenes; cada formulario POST incluye una accion. -->
    <div class="card"><div class="card-body">
        <form method="GET" class="row g-2 mb-3" data-live-search="ordenes">
            <input type="hidden" name="page" value="ordenes">
            <div class="col-lg-4"><label class="visually-hidden" for="buscar-orden">Buscar orden</label><input id="buscar-orden" class="form-control" name="q" maxlength="120" value="<?= escaparOrden($busqueda) ?>" placeholder="Código, cliente, placa o modelo"></div>
            <div class="col-lg-3"><label class="visually-hidden" for="filtro-estado-orden">Filtrar por estado</label><select class="form-select" name="estado" id="filtro-estado-orden"><option value="">Todos los estados</option><?php foreach(['registrada','diagnostico','en_reparacion','pendiente_repuesto','lista','entregada','cancelada'] as $estadoOpcion): ?><option value="<?= escaparOrden($estadoOpcion) ?>" <?= ($estadoFiltro ?? '') === $estadoOpcion ? 'selected' : '' ?>><?= escaparOrden(etiquetaEstadoOrden($estadoOpcion, $rolOrden)) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-4 col-lg-2"><label class="visually-hidden" for="filtro-orden-desde">Ingreso desde</label><input id="filtro-orden-desde" class="form-control" type="date" name="desde" value="<?= escaparOrden($filtros['desde'] ?? '') ?>" aria-label="Ingreso desde"></div>
            <div class="col-sm-4 col-lg-2"><label class="visually-hidden" for="filtro-orden-hasta">Ingreso hasta</label><input id="filtro-orden-hasta" class="form-control" type="date" name="hasta" value="<?= escaparOrden($filtros['hasta'] ?? '') ?>" aria-label="Ingreso hasta"></div>
            <div class="col-sm-4 col-lg-1"><button class="btn btn-primary w-100">Filtrar</button></div>
        </form>
        <nav class="ag-order-quick-filters" aria-label="Filtros rápidos de órdenes">
            <?php $filtrosRapidos = ['' => 'Todas', 'diagnostico' => 'Diagnóstico', 'en_reparacion' => 'En reparación', 'pendiente_repuesto' => 'Pendiente de repuesto', 'lista' => $rolOrden === 'mecanico' ? 'Terminadas' : 'Lista para entrega']; foreach ($filtrosRapidos as $valorFiltro => $textoFiltro): ?>
            <a class="<?= ($estadoFiltro ?? '') === $valorFiltro ? 'active' : '' ?>" href="?page=ordenes<?= $busqueda !== '' ? '&q='.rawurlencode($busqueda) : '' ?><?= $filtros['desde'] !== null ? '&desde='.rawurlencode($filtros['desde']) : '' ?><?= $filtros['hasta'] !== null ? '&hasta='.rawurlencode($filtros['hasta']) : '' ?><?= $valorFiltro !== '' ? '&estado='.rawurlencode($valorFiltro) : '' ?>"><?= escaparOrden($textoFiltro) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="table-responsive"><table class="table align-middle" data-live-search-results="ordenes"><thead><tr><th>Código OT</th><th>Cliente</th><th>Vehículo</th><th>Ingreso</th><?php if($rolOrden==='mecanico'): ?><th>Diagnóstico</th><th>Observaciones</th><?php endif; ?><th>Estado</th><?php if($puedeVerCostos): ?><th><?= $rolOrden === 'mecanico' ? 'Importe' : 'Total' ?></th><?php endif; ?><?php if($puedeCambiarEstado): ?><th><?= $rolOrden === 'mecanico' ? 'Gestión' : 'Acciones' ?></th><?php endif; ?></tr></thead><tbody>
        <?php if (!$ordenes): ?><tr><td colspan="<?= $columnasOrdenes ?>" class="text-center py-5 text-muted"><?php if ($hayFiltrosOrden): ?>No se encontraron órdenes con esos filtros. <a href="?page=ordenes">Limpiar filtros</a><?php elseif ($rolOrden === 'mecanico'): ?>Aún no tienes órdenes asignadas. Cuando te asignen una, aparecerá aquí.<?php else: ?>Aún no hay órdenes registradas. Usa <strong>Nueva orden</strong> para iniciar un servicio.<?php endif; ?></td></tr><?php endif; ?>
        <?php foreach ($ordenes as $orden): ?><tr><td><?php if($rolOrden==='mecanico'): ?><strong><?= escaparOrden($orden['codigo']) ?></strong><?php else: ?><a href="?page=ordenes&id=<?= (int)$orden['id'] ?><?= escaparOrden($parametroBusquedaOrden) ?>"><strong><?= escaparOrden($orden['codigo']) ?></strong></a><?php endif; ?></td><td><?= escaparOrden($orden['cliente']) ?></td><td><?= escaparOrden($orden['placa'] . ' · ' . $orden['marca'] . ' ' . $orden['modelo']) ?></td><td><?= !empty($orden['fecha_ingreso']) ? escaparOrden(date('d/m/Y H:i', strtotime($orden['fecha_ingreso']))) : '—' ?></td><?php if($rolOrden==='mecanico'): ?><td class="ag-order-text-column"><?= $orden['diagnostico']!==null&&trim((string)$orden['diagnostico'])!==''?nl2br(escaparOrden($orden['diagnostico'])):'<span class="text-muted">Sin diagnóstico</span>' ?></td><td class="ag-order-text-column"><?= $orden['observaciones']!==null&&trim((string)$orden['observaciones'])!==''?nl2br(escaparOrden($orden['observaciones'])):'<span class="text-muted">Sin observaciones</span>' ?></td><?php endif; ?><td><span class="badge ag-order-status <?= escaparOrden(claseEstadoOrden((string)$orden['estado'])) ?>"><?= escaparOrden(etiquetaEstadoOrden((string)$orden['estado'], $rolOrden)) ?></span></td><?php if($puedeVerCostos): ?><td><?= $orden['costo_final'] === null ? '—' : 'Bs ' . number_format((float)$orden['costo_final'], 2) ?></td><?php endif; ?><?php if($rolOrden==='mecanico'): ?><td><a class="btn btn-sm btn-outline-primary text-nowrap" href="?page=ordenes&id=<?= (int)$orden['id'] ?><?= escaparOrden($parametroBusquedaOrden) ?>">Gestionar OT</a></td><?php elseif(in_array($rolOrden,['administrador','recepcionista'],true)): ?><td><?php if($rolOrden==='administrador'): ?><a class="btn btn-sm btn-primary mb-1" href="?page=ordenes&id=<?= (int)$orden['id'] ?>&edit=1<?= escaparOrden($parametroBusquedaOrden) ?>">Editar</a><?php endif; ?><form method="POST" class="d-inline" data-confirm-delete><input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="eliminar"><input type="hidden" name="id" value="<?= (int)$orden['id'] ?>"><button class="btn btn-sm btn-danger mb-1">Eliminar</button></form></td><?php endif; ?></tr><?php endforeach; ?>
        </tbody></table></div>
    </div></div>
</div></main>
<!-- Si se pidió una orden por id, se muestra su ficha completa debajo del listado. -->
<?php if(!empty($detalle)&&isset($detalle['orden'])): $o=$detalle['orden']; ?>
<?php
$estadoOrdenTexto = etiquetaEstadoOrden((string) $o['estado'], $rolOrden);
$estadoOrdenClase = claseEstadoOrden((string) $o['estado']);
$tecnicosOrden = array_column($detalle['mecanicos'] ?? [], 'nombre');
?>
<div class="ag-content ag-order-detail ag-order-detail-panel" id="orden-detalle-panel" data-orden-id="<?= (int)$o['id'] ?>"><div class="card"><div class="card-body">
    <header class="ag-order-hero">
        <div>
            <p class="ag-eyebrow">Ficha de servicio</p>
            <h2>Orden de trabajo <span><?= escaparOrden($o['codigo']) ?></span></h2>
            <p class="ag-order-subtitle"><?= escaparOrden($o['cliente']) ?> <span aria-hidden="true">·</span> <?= escaparOrden($o['placa'].' '.$o['marca'].' '.$o['modelo']) ?></p>
            <span class="ag-order-status <?= escaparOrden($estadoOrdenClase) ?>"><?= escaparOrden($estadoOrdenTexto) ?></span>
        </div>
        <div class="ag-order-actions">
            <?php if($rolOrden==='administrador'): ?>
            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#editarOrdenModal">Editar orden</button>
            <?php endif; ?>
            <?php if(in_array($rolOrden,['administrador','recepcionista'],true)): ?>
            <form method="POST" data-confirm-delete>
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="accion" value="eliminar">
                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <button class="btn btn-outline-danger">Eliminar</button>
            </form>
            <?php endif; ?>
            <a class="btn btn-outline-secondary" href="?page=ordenes<?= escaparOrden($parametroBusquedaOrden) ?>">Volver a órdenes</a>
        </div>
    </header>
    <section class="ag-order-facts" aria-label="Información general de la orden">
        <article><span>Cliente</span><strong><?= escaparOrden($o['cliente']) ?></strong></article>
        <article><span>Vehículo</span><strong><?= escaparOrden($o['placa'].' · '.$o['marca'].' '.$o['modelo']) ?></strong><small><?= !empty($o['anio']) ? escaparOrden($o['anio']) : 'Año no registrado' ?></small></article>
        <article><span>Fecha de ingreso</span><strong><?= escaparOrden(date('d/m/Y', strtotime((string)$o['fecha_ingreso']))) ?></strong><small><?= escaparOrden(date('H:i', strtotime((string)$o['fecha_ingreso']))) ?> h</small></article>
        <article><span>Entrega prevista</span><strong><?= !empty($o['fecha_prevista_retorno']) ? escaparOrden(date('d/m/Y', strtotime((string)$o['fecha_prevista_retorno']))) : 'Sin definir' ?></strong><small><?= $tecnicosOrden ? 'Técnico'.(count($tecnicosOrden) > 1 ? 's' : '').': '.escaparOrden(implode(', ', $tecnicosOrden)) : 'Sin técnico asignado' ?></small></article>
    </section>
    <aside class="ag-order-next-step" role="status">
        <strong>¿Qué sigue?</strong>
        <p><?php if ($o['estado'] === 'cancelada'): ?>Esta orden fue cancelada. Revisa el historial si necesitas conocer el motivo.<?php elseif ($rolOrden === 'mecanico' && in_array($o['estado'], ['lista', 'entregada'], true)): ?>El trabajo técnico ya fue enviado a recepción. No es necesario volver a marcarlo como terminado.<?php elseif ($rolOrden === 'mecanico'): ?>Actualiza el diagnóstico, agrega los trabajos realizados y márcalos como completados. Después, avisa a recepción desde el cambio de estado.<?php elseif ($rolOrden === 'recepcionista' && $o['estado'] === 'lista'): ?>Confirma el precio, registra el pago y marca la orden como entregada cuando el cliente reciba el vehículo.<?php elseif ($rolOrden === 'recepcionista'): ?>Da seguimiento al avance del mecánico. Cuando la orden esté lista, podrás gestionar el pago y la entrega.<?php else: ?>Revisa el avance y las tareas. Coordina con recepción el cierre y la entrega al cliente.<?php endif; ?></p>
    </aside>
    <?php if (!empty($detalle['historial'])): ?>
    <section class="ag-order-history">
        <h3>Seguimiento de la orden</h3>
        <div class="ag-order-history-list">
            <?php foreach ($detalle['historial'] as $evento): ?>
            <article>
                <span class="ag-order-history-dot" aria-hidden="true"></span>
                <div><strong><?= escaparOrden(etiquetaEstadoOrden((string) $evento['estado_nuevo'], $rolOrden)) ?></strong>
                    <small><?= escaparOrden(date('d/m/Y H:i', strtotime((string)$evento['fecha_cambio']))) ?> · <?= escaparOrden($evento['usuario']) ?></small>
                    <?php if (!empty($evento['observaciones'])): ?><p><?= escaparOrden($evento['observaciones']) ?></p><?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <div class="row g-3">
        <div class="col-12">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <h3 class="h6 mb-2">Diagnóstico técnico</h3>
                        <div class="text-break"><?= $o['diagnostico'] !== null && trim((string) $o['diagnostico']) !== '' ? nl2br(escaparOrden($o['diagnostico'])) : '<span class="text-muted">Sin diagnóstico registrado.</span>' ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="border rounded p-3 h-100 bg-light">
                        <h3 class="h6 mb-2">Observaciones</h3>
                        <div class="text-break"><?= $o['observaciones'] !== null && trim((string) $o['observaciones']) !== '' ? nl2br(escaparOrden($o['observaciones'])) : '<span class="text-muted">Sin observaciones registradas.</span>' ?></div>
                    </div>
                </div>
            </div>
            <?php if ($rolOrden === 'mecanico'): ?>
            <div class="col-12">
                <div class="border rounded p-3">
                    <h3 class="h6 mb-1">Actualizar información técnica</h3>
                    <p class="form-text">Describe lo encontrado y cualquier novedad útil para recepción.</p>
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                        <input type="hidden" name="accion" value="actualizar_tecnico">
                        <input type="hidden" name="id" value="<?= (int) $o['id'] ?>">
                        <div class="col-md-6">
                            <label class="form-label">Diagnóstico técnico</label>
                            <textarea name="diagnostico" class="form-control" rows="4" maxlength="3000"><?= escaparOrden($o['diagnostico']) ?></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Observaciones técnicas</label>
                            <textarea name="observaciones" class="form-control" rows="4" maxlength="3000"><?= escaparOrden($o['observaciones']) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Fecha prevista de retorno</label>
                            <input type="date" name="fecha_prevista_retorno" class="form-control" min="<?= escaparOrden(substr((string)$o['fecha_ingreso'],0,10)) ?>" value="<?= escaparOrden($o['fecha_prevista_retorno']) ?>">
                        </div>
                        <div class="col-md-8 d-flex align-items-end">
                            <button class="btn btn-primary">Guardar información técnica</button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <h3 class="h6">Trabajos de reparación</h3>
            <p class="form-text">Registra cada trabajo y márcalo como realizado al terminar.</p>
            <?php if (!empty($detalle['tareas'])): ?>
            <form method="POST" class="mb-3">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="accion" value="completar_tareas">
                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <ul class="list-unstyled">
                    <?php foreach($detalle['tareas'] as $t): ?>
                    <li class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tareas_completadas[]" value="<?= (int)$t['id'] ?>" id="tarea-hecha-<?= (int)$t['id'] ?>" <?= $t['estado']==='completada'?'checked disabled':'' ?>>
                            <label class="form-check-label <?= $t['estado']==='completada'?'text-decoration-line-through':'' ?>" for="tarea-hecha-<?= (int)$t['id'] ?>"><?= escaparOrden($t['descripcion']) ?> <span class="ag-task-status"><?= escaparOrden(etiquetaEstadoTarea((string)$t['estado'])) ?></span><?php if ($puedeVerCostos&&$t['costo']!==null): ?> · Bs <?= number_format((float)$t['costo'],2,'.',',') ?><?php endif; ?></label>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($puedeRegistrarTareas): ?><button class="btn btn-outline-success btn-sm">Marcar seleccionados como realizados</button><?php endif; ?>
            </form>
            <?php else: ?>
            <p class="text-muted">Aún no hay trabajos agregados a esta orden.</p>
            <?php endif; ?>
            <?php if ($puedeRegistrarTareas): ?>
            <form method="POST" class="border rounded p-3 mb-3">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="accion" value="tareas_catalogo">
                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <h4 class="h6">Agregar trabajos sugeridos</h4>
                <p class="form-text">Selecciona solo los trabajos que correspondan al diagnóstico.</p>
                <?php foreach($catalogoTareas as $grupo=>$tareas): ?>
                <details class="mb-2">
                    <summary class="fw-semibold"><?= escaparOrden($grupo) ?></summary>
                    <div class="row g-2 pt-2">
                        <?php foreach($tareas as $indice=>$tarea): $campoId='tarea-catalogo-'.$indice.'-'.substr(sha1($grupo),0,8); ?>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="tareas_catalogo[]" value="<?= escaparOrden($tarea) ?>" id="<?= escaparOrden($campoId) ?>"><label class="form-check-label" for="<?= escaparOrden($campoId) ?>"><?= escaparOrden($tarea) ?><?php if($puedeVerCostos): ?> <span class="text-muted">(Bs <?= number_format((float) ($tarifasTrabajos[$tarea] ?? 0), 2, '.', ',') ?> mano de obra)</span><?php endif; ?></label></div></div>
                        <?php endforeach; ?>
                    </div>
                </details>
                <?php endforeach; ?>
                <button class="btn btn-outline-primary w-100">Agregar trabajos seleccionados</button>
            </form>
            <form method="POST" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="tarea"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <div class="col-md-<?= $puedeVerCostos?7:9 ?>"><label class="visually-hidden" for="nueva-tarea-<?= (int)$o['id'] ?>">Descripción del trabajo</label><input id="nueva-tarea-<?= (int)$o['id'] ?>" name="descripcion" class="form-control" placeholder="Describe otro trabajo" required></div>
                <div class="col-md-3"><label class="visually-hidden" for="estado-tarea-<?= (int)$o['id'] ?>">Estado inicial del trabajo</label><select id="estado-tarea-<?= (int)$o['id'] ?>" name="estado_tarea" class="form-select"><option value="pendiente">Pendiente</option><option value="en_proceso">En proceso</option><option value="completada">Completada</option></select></div>
                <?php if($puedeVerCostos): ?><div class="col-md-2"><input name="costo_tarea" type="number" step="0.01" min="0" max="99999999.99" class="form-control" placeholder="Costo"></div><?php endif; ?>
                <div class="col-12"><button class="btn btn-primary w-100">Guardar tarea</button></div>
            </form>
            <?php endif; ?>
        </div>
        <div class="col-md-6">
            <?php if ($puedeCambiarEstado): ?>
            <h3 class="h6">Actualizar estado de la OT</h3>
            <p class="form-text">El estado comunica a los demás en qué etapa está el vehículo.</p>
            <form method="POST" class="row g-2">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="estado"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <div class="col-md-7"><label class="visually-hidden" for="nuevo-estado-<?= (int)$o['id'] ?>">Nuevo estado de la orden</label><select id="nuevo-estado-<?= (int)$o['id'] ?>" name="estado" class="form-select" required>
                <option value="" selected disabled>Selecciona la siguiente etapa</option>
                <?php if ($rolOrden === 'mecanico'): ?>
                    <option value="diagnostico">En diagnóstico</option><option value="lista">Trabajo terminado · avisar a recepción</option>
                <?php else: ?>
                    <option value="registrada">Nueva</option><option value="diagnostico">En diagnóstico</option><option value="en_reparacion">En reparación</option><option value="pendiente_repuesto">Pendiente de repuesto</option><option value="lista">Lista para entrega</option><option value="entregada">Entregada</option><option value="cancelada">Cancelada</option>
                <?php endif; ?>
                </select></div>
                <div class="col-md-5"><button class="btn btn-primary w-100">Guardar estado</button></div>
                <div class="col-12"><label class="visually-hidden" for="observacion-estado-<?= (int)$o['id'] ?>">Nota para el historial</label><input id="observacion-estado-<?= (int)$o['id'] ?>" name="observaciones" class="form-control" placeholder="Nota opcional para el historial"></div>
            </form>
            <?php endif; ?>
            <?php if($puedeVerCostos): ?>
            <section class="border rounded p-3 mt-3">
                <h3 class="h6">Precio de esta orden</h3>
                <p class="mb-2"><strong><?= !empty($o['precio_confirmado'])?'Precio final confirmado:':'Estimación de mano de obra:' ?></strong> Bs <?= $o['costo_final']===null?'0.00':number_format((float)$o['costo_final'],2,'.',',') ?></p>
                <p class="form-text">Estimación provisional de mano de obra en Cochabamba, calculada por complejidad con referencia de Bs 40–100 por hora; no es tarifa oficial y no incluye repuestos.</p>
                <?php if($rolOrden==='recepcionista'||$rolOrden==='administrador'): ?>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                    <input type="hidden" name="accion" value="precio">
                    <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                    <div class="col-8"><label class="visually-hidden" for="precio-orden-<?= (int)$o['id'] ?>">Precio final en Bs</label><input id="precio-orden-<?= (int)$o['id'] ?>" name="costo_final" type="number" step="0.01" min="0" max="99999999.99" class="form-control" placeholder="Precio en Bs" value="<?= $o['costo_final']===null?'':escaparOrden($o['costo_final']) ?>" required></div>
                    <div class="col-4"><button class="btn btn-primary w-100">Guardar</button></div>
                </form>
                <?php endif; ?>
            </section>
            <h3 class="h6 mt-3">Pagos registrados</h3>
            <?php if (!empty($detalle['pagos'])): ?>
                <ul class="ag-payment-list"><?php foreach($detalle['pagos'] as $p): ?><li><strong>Bs <?= number_format((float)$p['monto'],2,'.',',') ?></strong><span><?= escaparOrden(date('d/m/Y H:i', strtotime((string)$p['fecha_pago']))) ?> · <?= escaparOrden(ucfirst((string)$p['metodo'])) ?></span></li><?php endforeach; ?></ul>
            <?php else: ?><p class="form-text">Todavía no hay pagos registrados para esta orden.</p><?php endif; ?>
            <?php if ($puedeRegistrarPago): ?>
            <h4 class="h6 mt-3">Registrar un pago</h4>
            <form method="POST" class="row g-2 mt-2">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>"><input type="hidden" name="accion" value="pago"><input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <div class="col-md-4"><label class="visually-hidden" for="pago-monto-<?= (int)$o['id'] ?>">Monto en bolivianos</label><input id="pago-monto-<?= (int)$o['id'] ?>" name="monto" type="number" step="0.01" min="0.01" max="99999999.99" class="form-control" placeholder="Monto (Bs)" required></div>
                <div class="col-md-3"><label class="visually-hidden" for="pago-metodo-<?= (int)$o['id'] ?>">Método de pago</label><select id="pago-metodo-<?= (int)$o['id'] ?>" name="metodo" class="form-select"><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="otro">Otro</option></select></div>
                <div class="col-md-3"><label class="visually-hidden" for="pago-referencia-<?= (int)$o['id'] ?>">Referencia de pago</label><input id="pago-referencia-<?= (int)$o['id'] ?>" name="referencia" class="form-control" placeholder="Referencia (opcional)"></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Registrar</button></div>
                <input type="hidden" name="observaciones" value="">
            </form>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div></div></div>
<?php endif; ?>
<?php if(!empty($detalle)&&isset($detalle['orden'])&&$rolOrden==='administrador'): ?>
<div class="modal fade" id="editarOrdenModal" tabindex="-1" <?= (($_GET['edit'] ?? '') === '1') ? 'data-open-on-load' : '' ?>>
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST">
            <div class="modal-header"><h2 class="modal-title h5">Editar orden <?= escaparOrden($o['codigo']) ?></h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= escaparOrden($_SESSION['csrf_token']) ?>">
                <input type="hidden" name="accion" value="editar">
                <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Vehículo</label><select name="vehiculo_id" class="form-select" required>
                        <?php foreach($vehiculos as $vehiculo): ?><option value="<?= (int)$vehiculo['id'] ?>" <?= ((int)$vehiculo['id']===(int)$o['vehiculo_id'])?'selected':'' ?>><?= escaparOrden($vehiculo['placa'].' · '.$vehiculo['cliente']) ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-md-6"><fieldset class="ag-check-list"><legend class="form-label">Mecánicos asignados *</legend><p class="form-text">Marca uno o más técnicos disponibles.</p><div class="ag-check-options">
                        <?php $asignados=array_map('intval',$detalle['mecanicos_asignados']??[]); foreach(($mecanicos??[]) as $mecanico): $campoMecanicoId='editar-mecanico-'.$o['id'].'-'.$mecanico['id']; ?><div class="ag-check-option"><input class="form-check-input ag-required-choice" type="checkbox" name="mecanico_ids[]" value="<?= (int)$mecanico['id'] ?>" id="<?= escaparOrden($campoMecanicoId) ?>" <?= in_array((int)$mecanico['id'],$asignados,true)?'checked':'' ?>><label for="<?= escaparOrden($campoMecanicoId) ?>"><?= escaparOrden($mecanico['nombre']) ?></label></div><?php endforeach; ?>
                        <?php if (empty($mecanicos)): ?><span class="text-muted">No hay mecánicos activos para asignar.</span><?php endif; ?>
                    </div></fieldset></div>
                    <div class="col-md-6"><label class="form-label">Fecha de ingreso (no editable)</label><input type="text" class="form-control" value="<?= escaparOrden(date('d/m/Y H:i', strtotime((string)$o['fecha_ingreso']))) ?>" readonly disabled></div>
                    <div class="col-md-6"><label class="form-label">Fecha prevista de entrega</label><input name="fecha_prevista_retorno" type="date" class="form-control" min="<?= escaparOrden(substr((string)$o['fecha_ingreso'],0,10)) ?>" value="<?= escaparOrden($o['fecha_prevista_retorno']) ?>"></div>
                    <div class="col-md-6"><label class="form-label">Estado</label><select name="estado" class="form-select" required>
                        <option value="" disabled <?= !in_array($o['estado'], ['registrada','diagnostico','en_reparacion','pendiente_repuesto','lista','entregada','cancelada'], true) ? 'selected' : '' ?>>Selecciona el estado</option>
                        <?php foreach(['registrada'=>'Nueva','diagnostico'=>'En diagnóstico','en_reparacion'=>'En reparación','pendiente_repuesto'=>'Pendiente de repuesto','lista'=>'Lista para entrega','entregada'=>'Entregada','cancelada'=>'Cancelada'] as $valor=>$texto): ?><option value="<?= $valor ?>" <?= $o['estado']===$valor?'selected':'' ?>><?= $texto ?></option><?php endforeach; ?>
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Diagnóstico</label><textarea name="diagnostico" class="form-control" rows="4" maxlength="3000"><?= escaparOrden($o['diagnostico']) ?></textarea></div>
                    <div class="col-md-6"><label class="form-label">Observaciones</label><textarea name="observaciones" class="form-control" rows="4" maxlength="3000"><?= escaparOrden($o['observaciones']) ?></textarea></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" <?= empty($mecanicos) ? 'disabled' : '' ?>>Guardar cambios</button></div>
        </form>
    </div></div>
</div>
<?php endif; ?>
<div class="modal fade" id="crearOrdenModal" tabindex="-1" aria-labelledby="crearOrdenTitulo" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="POST" class="modal-body row g-3" id="crear-orden-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'],ENT_QUOTES,'UTF-8') ?>">
            <input type="hidden" name="accion" value="crear">
            <div class="col-12 modal-header px-0"><h2 class="modal-title h5" id="crearOrdenTitulo">Registrar orden de trabajo</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
            <p class="col-12 form-text mb-0">Primero selecciona el vehículo y el técnico responsable. El diagnóstico registrado en el vehículo aparecerá para que puedas ampliarlo.</p>
            <div class="col-md-6"><label class="form-label" for="crear-vehiculo">Vehículo *</label><select id="crear-vehiculo" name="vehiculo_id" class="form-select" required><option value="">Selecciona un vehículo</option><?php foreach($vehiculos as $v): ?><option value="<?= (int)$v['id'] ?>" data-diagnostico="<?= escaparOrden($v['diagnostico'] ?? '') ?>"><?= escaparOrden($v['placa'].' · '.$v['cliente']) ?></option><?php endforeach;?></select></div>
            <div class="col-md-6"><fieldset class="ag-check-list"><legend class="form-label">Mecánicos asignados *</legend><p class="form-text">Marca uno o más técnicos.</p><div class="ag-check-options"><?php foreach(($mecanicos ?? []) as $mecanico): $campoMecanicoId='crear-mecanico-'.$mecanico['id']; ?><div class="ag-check-option"><input class="form-check-input ag-required-choice" type="checkbox" name="mecanico_ids[]" value="<?= (int)$mecanico['id'] ?>" id="<?= escaparOrden($campoMecanicoId) ?>"><label for="<?= escaparOrden($campoMecanicoId) ?>"><?= escaparOrden($mecanico['nombre']) ?></label></div><?php endforeach; ?><?php if (empty($mecanicos)): ?><span class="text-muted">No hay mecánicos activos. Registra o activa uno antes de crear la orden.</span><?php endif; ?></div></fieldset></div>
            <div class="col-md-6"><label class="form-label">Fecha y hora de ingreso</label><input type="text" class="form-control" value="<?= escaparOrden(date('d/m/Y H:i')) ?>" readonly disabled><small class="form-text">Se registra automáticamente al crear la orden.</small></div>
            <div class="col-md-6"><label class="form-label" for="crear-fecha-retorno">Fecha prevista de entrega</label><input id="crear-fecha-retorno" type="date" name="fecha_prevista_retorno" class="form-control" min="<?= date('Y-m-d') ?>"></div>
            <div class="col-md-6"><label class="form-label" for="crear-diagnostico">Diagnóstico inicial</label><textarea id="crear-diagnostico" name="diagnostico" class="form-control" rows="3" placeholder="El diagnóstico del vehículo aparecerá al seleccionarlo; puedes añadir más información"></textarea><small class="form-text">El texto existente se carga automáticamente y puedes ampliarlo.</small></div>
            <div class="col-md-6"><label class="form-label" for="crear-observaciones">Observaciones</label><textarea id="crear-observaciones" name="observaciones" class="form-control" rows="3" placeholder="Información adicional del servicio"></textarea></div>
            <div class="col-12 modal-footer px-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-primary" <?= empty($mecanicos) || empty($vehiculos) ? 'disabled' : '' ?>>Crear orden</button></div>
        </form>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
<script src="js/live-search.js" defer></script>
<script src="js/ordenes.js" defer></script>
</body></html>
