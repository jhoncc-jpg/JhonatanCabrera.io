<?php

/*
 * Proposito: Renderiza la interfaz o el documento de topbar con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
$tituloTopbar = is_string($tituloTopbar ?? null) ? $tituloTopbar : '';
$mostrarAccionesInicio = ($mostrarAccionesInicio ?? false) === true;
$rolTopbar = $_SESSION['rol'] ?? '';
?>
<header class="ag-topbar">
    <button class="ag-menu-toggle" type="button" aria-label="Abrir menú" aria-controls="agSidebar">☰</button>
    <strong><?= htmlspecialchars($tituloTopbar, ENT_QUOTES, 'UTF-8') ?></strong>
    <?php if ($mostrarAccionesInicio): ?>
        <div class="ag-top-actions">
            <div class="ag-create-menu">
                <button type="button" aria-label="Crear nuevo" id="createButton" aria-expanded="false" aria-controls="createMenu">＋</button>
                <div class="ag-create-options" id="createMenu" hidden>
                    <strong>Crear nuevo</strong>
                    <?php if ($rolTopbar === 'mecanico'): ?>
                        <a href="?page=ordenes"><span>▣</span><div><b>Ver órdenes</b><small>Consultar trabajos asignados</small></div></a>
                    <?php else: ?>
                        <a href="?page=ordenes"><span>▣</span><div><b>Orden de trabajo</b><small>Registrar un nuevo servicio</small></div></a>
                        <a href="?page=clientes"><span>♙</span><div><b>Cliente</b><small>Agregar propietario</small></div></a>
                        <a href="?page=vehiculos"><span>◇</span><div><b>Vehículo</b><small>Registrar un vehículo</small></div></a>
                        <a href="?page=mecanicos"><span>♟</span><div><b>Mecánico</b><small>Agregar personal técnico</small></div></a>
                        <a href="?page=repuestos"><span>⬡</span><div><b>Repuesto</b><small>Agregar al inventario</small></div></a>
                    <?php endif; ?>
                </div>
            </div>
            <button type="button" aria-label="Ayuda y manual del sistema" id="helpButton">?</button>
        </div>
    <?php endif; ?>
</header>
