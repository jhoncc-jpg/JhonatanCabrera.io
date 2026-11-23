<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Vehiculo, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\models\Cliente.php, app\models\Orden.php, app\repositories\VehiculoRepository.php.
 */

/**
 * Representa la estructura de datos Vehiculo utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Vehiculo
{
    public ?int $id = null;
    public int $clienteId = 0;
    public string $placa = '';
    public string $marca = '';
    public string $modelo = '';
    public ?int $anio = null;
    public ?string $color = null;
    public ?string $diagnostico = null;
    public string $estado = 'registrado';
    public ?string $fechaRegistro = null;

    public ?Cliente $cliente = null;
    /** @var string[] */
    public array $imagenes = [];
}
