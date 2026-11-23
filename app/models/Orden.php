<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Orden, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\OrdenController.php, app\models\Mecanico.php, app\repositories\OrdenRepository.php, app\views\layouts\topbar.php, app\views\ordenes\index.php.
 */

/**
 * Representa la estructura de datos Orden utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Orden
{
    public ?int $id = null;
    public string $codigo = '';
    public int $vehiculoId = 0;
    public int $usuarioId = 0;
    public ?string $fechaIngreso = null;
    public ?string $fechaPrevistaRetorno = null;
    public ?string $fechaEntrega = null;
    public string $estado = 'registrada';
    public ?string $diagnostico = null;
    public ?string $observaciones = null;
    public ?float $costoFinal = null;

    public ?Vehiculo $vehiculo = null;
    /** @var Mecanico[] */
    public array $mecanicos = [];
}
