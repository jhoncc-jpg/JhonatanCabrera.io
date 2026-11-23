
<?php

/*
 * Proposito: Actua como punto de entrada web y transfiere la solicitud al enrutador principal.
 * Dependencias: /../app/controllers/RouterController.php.
 * Consumidores/dependientes: el servidor web para cada solicitud de la aplicacion.
 */

// Este es el punto de entrada web: toda solicitud comienza aquí.
// La sesión conserva quién inició sesión y cuál es su rol entre páginas.
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

// Configura la cookie de sesión para que JavaScript no pueda leerla
// y para que el navegador la envíe de forma segura.
session_set_cookie_params([
    'httponly' => true,
    'secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/'
]);

session_start();

require_once __DIR__ . '/../app/controllers/RouterController.php';
(new RouterController())->dispatch();
