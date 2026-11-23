<?php

/*
 * Proposito: Renderiza la interfaz o el documento de sidebar con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
// Este menú compartido se incluye desde las vistas de los módulos.
// La lista define qué enlaces aparecen para cada rol.
$rolMenu = $_SESSION['rol'] ?? '';
$paginaMenu = $_GET['page'] ?? 'home';
$nombreMenu = htmlspecialchars($_SESSION['nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8');
$rolMenuTexto = htmlspecialchars(match ($rolMenu) {
    'administrador' => 'Administrador',
    'recepcionista' => 'Recepcionista',
    'mecanico' => 'Mecánico',
    default => $rolMenu,
}, ENT_QUOTES, 'UTF-8');
$csrfMenu = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');

$menuItems = [
    'home' => ['Inicio', '⌂', ['administrador', 'recepcionista']],
    'ordenes' => [$rolMenu === 'mecanico' ? 'Mis órdenes' : 'Órdenes de trabajo', '▣', ['administrador', 'recepcionista', 'mecanico']],
    'repuestos' => ['Inventario', '⬡', ['administrador', 'recepcionista']],
    'clientes' => ['Clientes', '♙', ['administrador', 'recepcionista']],
    'vehiculos' => ['Vehículos', '◇', ['administrador', 'recepcionista']],
    'mecanicos' => ['Mecánicos', '♟', ['administrador', 'recepcionista']],
    'reportes' => ['Reportes', '▥', ['administrador', 'recepcionista']],
    'usuarios' => ['Usuarios', '♟', ['administrador']],
];
$brandHref = $rolMenu === 'mecanico' ? '?page=ordenes' : '?page=home';
?>
<aside class="ag-sidebar" id="agSidebar" aria-label="Menú principal">
    <a class="ag-sidebar-brand" href="<?= $brandHref ?>">
        <img class="ag-brand-mark" src="../img/logo2.png?v=2" alt="AUTOGEST PRO">
        <span>AUTOGEST <b>PRO</b></span>
    </a>
    <button class="ag-sidebar-close" type="button" aria-label="Cerrar menú">×</button>
    <nav class="ag-sidebar-nav" aria-label="Navegación principal">
        <?php foreach ($menuItems as $clave => [$titulo, $icono, $rolesPermitidos]): ?>
            <?php if (!in_array($rolMenu, $rolesPermitidos, true)) continue; ?>
            <a class="<?= $paginaMenu === $clave ? 'active' : '' ?>" href="?page=<?= $clave ?>" aria-current="<?= $paginaMenu === $clave ? 'page' : 'false' ?>">
                <span><?= $icono ?></span><?= $titulo ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="ag-sidebar-footer">
        <div class="small">
            <strong><?= $nombreMenu ?></strong><br>
            <span><?= $rolMenuTexto ?></span>
        </div>
        <form method="POST" action="?page=logout">
            <input type="hidden" name="csrf_token" value="<?= $csrfMenu ?>">
            <button class="btn btn-link p-0">Cerrar sesión</button>
        </form>
    </div>
</aside>
<script src="js/navigation.js" defer></script>
