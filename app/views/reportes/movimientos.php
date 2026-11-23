<?php

/*
 * Proposito: Muestra movimientos de inventario valorizados con datos preparados por ReporteController.
 * Dependencias: datos preparados por el controlador y Bootstrap 5.
 * Consumidores/dependientes: ReporteController incluye esta vista para mostrar filtros y resultados.
 */
function escaparMovimientoInventario($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$tiposMovimiento = [
    'entrada' => ['Entrada', 'bg-success'],
    'salida' => ['Salida', 'bg-danger'],
    'ajuste' => ['Ajuste', 'bg-warning text-dark'],
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Movimientos valorizados | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
    <?php $tituloTopbar = 'Movimientos de inventario'; require __DIR__ . '/../layouts/topbar.php'; ?>
    <div class="ag-content">
        <div class="ag-page-header">
            <div>
                <p class="ag-eyebrow">Inventario valorizado</p>
                <h1>Reporte de Movimientos (Bs.)</h1>
                <p class="ag-page-description">Consulta entradas, salidas y ajustes valorizados al precio de referencia vigente.</p>
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert"><?= escaparMovimientoInventario($error) ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <p class="text-muted mb-1">Total en Salidas (Bs.)</p>
                    <h2 class="h4 mb-0">Bs. <?= number_format((float) $resumen['salidas'], 2, ',', '.') ?></h2>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <p class="text-muted mb-1">Total en Entradas (Bs.)</p>
                    <h2 class="h4 mb-0">Bs. <?= number_format((float) $resumen['entradas'], 2, ',', '.') ?></h2>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <p class="text-muted mb-1">Total General (Bs.)</p>
                    <h2 class="h4 mb-0">Bs. <?= number_format((float) $resumen['total'], 2, ',', '.') ?></h2>
                </div></div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card h-100 shadow-sm"><div class="card-body">
                    <p class="text-muted mb-1">Cantidad total de unidades movidas</p>
                    <h2 class="h4 mb-0"><?= (int) $resumen['unidades'] ?></h2>
                </div></div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <input type="hidden" name="page" value="reporte-movimientos">
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="movimientos-fecha-inicio">Fecha Inicio</label>
                        <input class="form-control" type="date" id="movimientos-fecha-inicio" name="fecha_inicio" value="<?= escaparMovimientoInventario($filtros['fecha_inicio']) ?>">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="movimientos-fecha-fin">Fecha Fin</label>
                        <input class="form-control" type="date" id="movimientos-fecha-fin" name="fecha_fin" value="<?= escaparMovimientoInventario($filtros['fecha_fin']) ?>">
                    </div>
                    <div class="col-sm-6 col-lg-2">
                        <label class="form-label" for="movimientos-tipo">Tipo de movimiento</label>
                        <select class="form-select" id="movimientos-tipo" name="tipo">
                            <option value="" <?= $filtros['tipo'] === '' ? 'selected' : '' ?>>Todos</option>
                            <option value="entrada" <?= $filtros['tipo'] === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                            <option value="salida" <?= $filtros['tipo'] === 'salida' ? 'selected' : '' ?>>Salida</option>
                            <option value="ajuste" <?= $filtros['tipo'] === 'ajuste' ? 'selected' : '' ?>>Ajuste</option>
                        </select>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <label class="form-label" for="movimientos-busqueda">Buscar repuesto</label>
                        <input class="form-control" type="search" id="movimientos-busqueda" name="busqueda" maxlength="100" value="<?= escaparMovimientoInventario($filtros['busqueda']) ?>" placeholder="Código o nombre">
                    </div>
                    <div class="col-sm-6 col-lg-3 d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary" type="submit">Filtrar</button>
                        <a class="btn btn-secondary" href="?page=reporte-movimientos">Limpiar</a>
                        <button class="btn btn-success" type="submit" name="exportar" value="excel">Exportar Excel</button>
                    </div>
                </form>
                <p class="form-text mb-0 mt-2">La exportación se descarga como CSV compatible con Excel.</p>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>FECHA Y HORA</th>
                                <th>TIPO</th>
                                <th>CÓDIGO</th>
                                <th>REPUESTO</th>
                                <th class="text-end">CANTIDAD</th>
                                <th class="text-end">PRECIO UNIT. (Bs.)</th>
                                <th class="text-end">TOTAL (Bs.)</th>
                                <th>ORDEN / OBSERVACIÓN</th>
                                <th>USUARIO</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($filas === []): ?>
                                <tr><td class="text-center py-4" colspan="9">No hay movimientos para los filtros seleccionados.</td></tr>
                            <?php else: ?>
                                <?php foreach ($filas as $fila): ?>
                                    <?php
                                    $tipo = $tiposMovimiento[$fila['tipo']] ?? ['Desconocido', 'bg-secondary'];
                                    $fecha = (new DateTimeImmutable((string) $fila['fecha_movimiento']))->format('d/m/Y H:i');
                                    ?>
                                    <tr>
                                        <td><?= escaparMovimientoInventario($fecha) ?></td>
                                        <td><span class="badge <?= escaparMovimientoInventario($tipo[1]) ?>"><?= escaparMovimientoInventario($tipo[0]) ?></span></td>
                                        <td><?= escaparMovimientoInventario($fila['codigo']) ?></td>
                                        <td><?= escaparMovimientoInventario($fila['repuesto_nombre']) ?></td>
                                        <td class="text-end"><?= (int) $fila['cantidad'] ?></td>
                                        <td class="text-end">Bs. <?= number_format((float) $fila['precio_unitario'], 2, ',', '.') ?></td>
                                        <td class="text-end">Bs. <?= number_format((float) $fila['total_bs'], 2, ',', '.') ?></td>
                                        <td>
                                            <?php if (!empty($fila['orden_codigo'])): ?>
                                                <div><strong><?= escaparMovimientoInventario($fila['orden_codigo']) ?></strong></div>
                                            <?php endif; ?>
                                            <?php if (!empty($fila['observaciones'])): ?>
                                                <div class="small text-muted"><?= escaparMovimientoInventario($fila['observaciones']) ?></div>
                                            <?php endif; ?>
                                            <?php if (empty($fila['orden_codigo']) && empty($fila['observaciones'])): ?>—<?php endif; ?>
                                        </td>
                                        <td><?= escaparMovimientoInventario($fila['usuario_nombre']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</main>
</body>
</html>
