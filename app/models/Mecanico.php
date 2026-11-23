<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Mecanico, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\models\Orden.php.
 */

/**
 * Representa la estructura de datos Mecanico utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Mecanico
{
    public ?int $id = null;
    public string $nombre = '';
    public ?string $telefono = null;
    public ?string $especialidad = null;
    public bool $activo = true;
    public ?int $usuarioId = null;

    /** @var Orden[] */
    public array $ordenes = [];
}
