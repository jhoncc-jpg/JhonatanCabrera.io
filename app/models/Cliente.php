<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Cliente, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\ClienteController.php, app\models\Vehiculo.php, app\views\home.php, app\views\layouts\topbar.php, app\views\ordenes\index.php.
 */

/**
 * Representa la estructura de datos Cliente utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Cliente
{
    public ?int $id = null;
    public string $nombre = '';
    public string $telefono = '';
    public string $direccion = '';
    public ?string $correo = null;
    public bool $activo = true;
    public ?string $creadoEn = null;

    /** @var Vehiculo[] */
    public array $vehiculos = [];
}
