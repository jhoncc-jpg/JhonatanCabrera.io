
<?php

/*
 * Proposito: Renderiza la interfaz o el documento de home con datos preparados por su controlador.
 * Dependencias: /layouts/topbar.php.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
$nombreSeguro = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=20261116-responsive1" rel="stylesheet">
</head>
<body class="ag-app">
<!-- El menú se comparte con los demás módulos y adapta sus enlaces al rol. -->
<?php require __DIR__ . '/layouts/sidebar.php'; ?>
<main class="ag-main">
    <?php
    $tituloTopbar = 'Taller Automotriz Almaraz';
    $mostrarAccionesInicio = true;
    require __DIR__ . '/layouts/topbar.php';
    ?>
    <div class="ag-content">
        <div class="ag-page-header"><div><p class="ag-eyebrow">Bienvenido, <?= $nombreSeguro ?></p><h1><?= htmlspecialchars($tituloInicio, ENT_QUOTES, 'UTF-8') ?></h1><p class="ag-page-description"><?= htmlspecialchars($descripcionInicio, ENT_QUOTES, 'UTF-8') ?></p></div><div class="ag-period">Resumen de <?= htmlspecialchars($periodoActual, ENT_QUOTES, 'UTF-8') ?></div></div>
        <?php if (!empty($errorResumen)): ?><div class="alert alert-warning" role="status"><?= htmlspecialchars($errorResumen, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <!-- Cada tarjeta muestra una cifra calculada por HomeController en la BD. -->
        <?php if (empty($errorResumen)): ?>
        <div class="ag-summary-grid">
            <article class="ag-summary-card"><div class="ag-summary-icon blue">OT</div><div><span>Órdenes registradas este mes</span><strong><?= number_format((float)$resumen['ordenes_mes'], 0) ?></strong></div></article>
            <article class="ag-summary-card"><div class="ag-summary-icon cyan">↻</div><div><span>Órdenes sin entregar</span><strong><?= number_format((float)$resumen['ordenes_abiertas'], 0) ?></strong></div></article>
            <article class="ag-summary-card"><div class="ag-summary-icon green"><?= $rolSesion === 'recepcionista' ? '✓' : 'Bs' ?></div><div><span><?= $rolSesion === 'recepcionista' ? 'Órdenes listas para entrega' : 'Pagos recibidos este mes' ?></span><strong><?= $rolSesion === 'recepcionista' ? number_format((float)$resumen['ordenes_listas'], 0) : 'Bs ' . number_format((float)$resumen['ingresos_mes'], 2, '.', ',') ?></strong></div></article>
            <article class="ag-summary-card"><div class="ag-summary-icon orange">!</div><div><span>Repuestos por reponer</span><strong><?= number_format((float)$resumen['repuestos_bajos'], 0) ?></strong></div></article>
        </div>
        <section class="ag-dashboard-welcome card"><div class="card-body"><div><p class="ag-eyebrow">Siguiente paso</p><h2>Atiende las órdenes del taller</h2><p>Hay <?= number_format((float)$resumen['clientes'], 0) ?> clientes activos, <?= number_format((float)$resumen['vehiculos'], 0) ?> vehículos registrados y <?= number_format((float)$resumen['ordenes_abiertas'], 0) ?> órdenes sin entregar.</p></div><a href="?page=ordenes" class="btn btn-primary">Ver órdenes de trabajo</a></div></section>
        <?php endif; ?>
        <!-- Este flujo resume para el usuario el orden recomendado del trabajo. -->
        <section class="ag-workflow-guide" aria-labelledby="flujo-taller-titulo">
            <div><p class="ag-eyebrow">Guía rápida</p><h2 id="flujo-taller-titulo">Flujo de una orden de trabajo</h2></div>
            <ol>
                <li><span>1</span><div><strong>Registrar</strong><small>Cliente y vehículo</small></div></li>
                <li><span>2</span><div><strong>Asignar</strong><small>Crear la OT y asignar técnico</small></div></li>
                <li><span>3</span><div><strong>Reparar</strong><small>Registrar y completar trabajos</small></div></li>
                <li><span>4</span><div><strong>Entregar</strong><small>Confirmar pago y entrega</small></div></li>
            </ol>
        </section>
        <?php if ($rolSesion !== 'mecanico'): ?>
        <div class="ag-module-grid">
            <a class="ag-module-card" href="?page=repuestos"><span>⬡</span><div><strong>Inventario</strong><small>Stock y movimientos</small></div></a>
            <a class="ag-module-card" href="?page=clientes"><span>♙</span><div><strong>Clientes</strong><small>Propietarios activos</small></div></a>
            <a class="ag-module-card" href="?page=vehiculos"><span>◇</span><div><strong>Vehículos</strong><small>Flota registrada</small></div></a>
            <a class="ag-module-card" href="?page=mecanicos"><span>♟</span><div><strong>Mecánicos</strong><small>Especialidades y disponibilidad</small></div></a>
        </div>
        <?php endif; ?>
    </div>
</main>

<div class="modal" id="helpModal" tabindex="-1" aria-hidden="true" aria-labelledby="helpModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content ag-help-modal">
            <div class="modal-header">
                <div>
                    <p class="ag-help-eyebrow">Centro de ayuda</p>
                    <h2 class="modal-title fs-5" id="helpModalTitle">Cómo usar AUTOGEST PRO</h2>
                </div>
                <button type="button" class="btn-close" data-help-close aria-label="Cerrar"></button>
            </div>
            <div class="modal-body ag-help-content">
                <section>
                    <h3>¿Qué hace el sistema?</h3>
                    <p>AUTOGEST PRO administra las operaciones del taller: clientes, vehículos, órdenes de trabajo, mecánicos, repuestos, pagos, usuarios y reportes.</p>
                </section>

                <section>
                    <h3>Flujo básico de trabajo</h3>
                    <ol>
                        <li>Registra al cliente y los datos del vehículo.</li>
                        <li>Crea la orden de trabajo y asigna uno o varios mecánicos.</li>
                        <li>El mecánico registra el diagnóstico y los trabajos realizados.</li>
                        <li>Cuando termine, avisa a recepción desde la ficha de la OT.</li>
                        <li>Recepción confirma el precio, registra los pagos y marca la orden como entregada.</li>
                    </ol>
                </section>

                <section>
                    <h3>Módulos disponibles</h3>
                    <div class="ag-help-grid">
                        <article><strong>Órdenes</strong><span>Crea, consulta y actualiza trabajos.</span></article>
                        <article><strong>Clientes</strong><span>Administra propietarios y datos de contacto.</span></article>
                        <article><strong>Vehículos</strong><span>Registra la flota y su historial.</span></article>
                        <article><strong>Inventario</strong><span>Controla stock, precios y disponibilidad.</span></article>
                        <article><strong>Mecánicos</strong><span>Gestiona personal y especialidades.</span></article>
                        <article><strong>Reportes</strong><span>Consulta información resumida del taller.</span></article>
                    </div>
                </section>

                <section>
                    <h3>Permisos según tu rol</h3>
                    <p><strong>Administrador:</strong> acceso completo al sistema.</p>
                    <p><strong>Recepcionista:</strong> gestiona clientes, vehículos, órdenes, inventario y reportes.</p>
                    <p><strong>Mecánico:</strong> consulta las órdenes y actualiza el trabajo técnico que le corresponde.</p>
                </section>

                <section>
                    <h3>Consejos de uso</h3>
                    <ul>
                        <li>Usa los filtros y campos de búsqueda para encontrar información rápidamente.</li>
                        <li>Revisa el estado y los datos antes de registrar una operación.</li>
                        <li>Mantén el inventario actualizado para evitar asignar repuestos sin stock.</li>
                        <li>Cierra la sesión al terminar si compartes el equipo.</li>
                    </ul>
                </section>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-help-close>Entendido</button>
            </div>
        </div>
    </div>
</div>

</body>
</html>
