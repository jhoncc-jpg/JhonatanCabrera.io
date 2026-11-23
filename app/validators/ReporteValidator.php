<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con ReporteValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\ReporteController.php, app\controllers\ReporteInventarioController.php.
 */

/**
 * Valida y normaliza los datos de Reporte antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class ReporteValidator
{
    /**
     * Normaliza y valida los criterios de busqueda y fechas recibidos.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function filtros(array $input): array
    {
        $desde = $this->fecha($input, 'desde');
        $hasta = $this->fecha($input, 'hasta');
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        return ['desde' => $desde, 'hasta' => $hasta];
    }

    /**
     * Valida los filtros del reporte valorizado de movimientos de inventario.
     * Parametro $input: valores GET recibidos para el reporte.
     * Devuelve: fechas, tipo y texto normalizados para el controlador.
     */
    public function filtrosMovimientos(array $input): array
    {
        $fechaInicio = $this->fecha($input, 'fecha_inicio');
        $fechaFin = $this->fecha($input, 'fecha_fin');
        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        $tipo = $input['tipo'] ?? '';
        if (!is_string($tipo) || !in_array($tipo, ['', 'entrada', 'salida', 'ajuste'], true)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }

        $busqueda = $input['busqueda'] ?? '';
        if (!is_string($busqueda)) {
            throw new InvalidArgumentException('El texto de búsqueda no es válido.');
        }

        return [
            'fecha_inicio' => $fechaInicio ?? '',
            'fecha_fin' => $fechaFin ?? '',
            'tipo' => $tipo,
            'busqueda' => mb_substr(trim($busqueda), 0, 100, 'UTF-8'),
        ];
    }

    /**
     * Valida y normaliza la fecha opcional del filtro solicitado.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Devuelve: valor de tipo ?string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function fecha(array $input, string $key): ?string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException('El filtro de fecha no es válido.');
        }
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException('El filtro de fecha no es válido.');
        }

        return $value;
    }
}
