<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Repuesto, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\RepuestoController.php, app\repositories\RepuestoRepository.php, app\views\layouts\topbar.php, app\views\reportes\RepuestoPdfView.php.
 */

/**
 * Representa la estructura de datos Repuesto utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Repuesto
{
    public ?int $id = null;
    public string $codigo = '';
    public string $nombre = '';
    public ?string $descripcion = null;
    public int $stock = 0;
    public int $stockMinimo = 0;
    public ?float $precioReferencia = null;
    public bool $activo = true;
    private ?string $fechaRegistro = null;

    public function getFechaRegistro(): ?string
    {
        return $this->fechaRegistro;
    }

    public function setFechaRegistro(?string $fechaRegistro): void
    {
        $this->fechaRegistro = $fechaRegistro;
    }
}
