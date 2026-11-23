<?php

/*
 * Proposito: Actua como punto de entrada web y transfiere la solicitud al enrutador principal.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el servidor web para cada solicitud de la aplicacion.
 */
// Redirige la raíz del proyecto al punto de entrada público de la aplicación.
header('Location: ./public/index.php?page=login', true, 302);
exit;
