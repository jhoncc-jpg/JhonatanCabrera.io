<!DOCTYPE html>
<html lang="es">
<head><!--
  Proposito: Renderiza la interfaz de reportes con datos preparados por su controlador.
  Dependencias: datos preparados por ReporteController y Bootstrap 5.
  Consumidores/dependientes: ReporteController incluye esta vista para mostrar filtros y resultados.
-->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=reportes-mvc1" rel="stylesheet">
</head>
<body class="ag-app">
<?php require __DIR__ . '/../layouts/sidebar.php'; ?>
<main class="ag-main">
<?php $tituloTopbar = 'Reportes'; require __DIR__ . '/../layouts/topbar.php'; ?>
<div class="ag-shell">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Reportes</h1>
    </div>

    <?php if (!empty($mensaje)): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <form method="GET" class="row g-2 align-items-end mb-4">
            <input type="hidden" name="page" value="reportes">
            <div class="col-md-4">
                <label class="form-label" for="reporte-desde">Desde</label>
                <input id="reporte-desde" name="desde" type="date" class="form-control" value="<?= htmlspecialchars($filtros['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="reporte-hasta">Hasta</label>
                <input id="reporte-hasta" name="hasta" type="date" class="form-control" value="<?= htmlspecialchars($filtros['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="col-md-4"><button class="btn btn-outline-primary">Aplicar periodo</button></div>
        </form>
        <p class="form-text">El periodo filtra órdenes e ingresos; las existencias y el stock bajo muestran el estado actual del inventario.</p>

        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Órdenes</span><h2><?= (int) $reportes['ordenes']['total'] ?></h2></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Órdenes abiertas</span><h2><?= (int) $reportes['abiertas']['total'] ?></h2></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Existencias</span><h2><?= (int) $reportes['inventario']['unidades'] ?></h2></div></div></div>
            <div class="col-md-3"><div class="card"><div class="card-body"><span class="text-muted">Ingresos registrados</span><h2>Bs. <?= number_format((float) $reportes['ingresos']['total'], 2) ?></h2></div></div></div>
        </div>
        <div class="card"><div class="card-body">
            <p><strong>Productos con stock bajo:</strong> <?= (int) $reportes['bajos']['total'] ?></p>
            <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="?page=reportes&amp;download=resumen<?= !empty($filtros['desde']) ? '&amp;desde=' . rawurlencode($filtros['desde']) : '' ?><?= !empty($filtros['hasta']) ? '&amp;hasta=' . rawurlencode($filtros['hasta']) : '' ?>" download>Descargar resumen Excel</a>
                <a class="btn btn-outline-success" href="?page=reportes&amp;download=vehiculos<?= !empty($filtros['desde']) ? '&amp;desde=' . rawurlencode($filtros['desde']) : '' ?><?= !empty($filtros['hasta']) ? '&amp;hasta=' . rawurlencode($filtros['hasta']) : '' ?>" download>Descargar registro de vehículos Excel</a>
                <a class="btn btn-outline-primary" href="?page=reportes&amp;download=ordenes<?= !empty($filtros['desde']) ? '&amp;desde=' . rawurlencode($filtros['desde']) : '' ?><?= !empty($filtros['hasta']) ? '&amp;hasta=' . rawurlencode($filtros['hasta']) : '' ?>" download>Descargar órdenes Excel</a>
            </div>
        </div></div>
</div>
</main>
</body>
</html>
