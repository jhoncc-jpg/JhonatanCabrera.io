<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con RepuestoValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\ReporteInventarioController.php, app\controllers\RepuestoController.php.
 */

/**
 * Valida y normaliza los datos de Repuesto antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class RepuestoValidator
{
    /**
     * Normaliza los criterios de busqueda y filtros para el modelo.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $esAdministrador: valor de tipo bool recibido para esta operacion.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function filters(array $input, bool $esAdministrador): array
    {
        $busqueda = trim($this->stringValue($input, 'q'));
        $busqueda = mb_substr($busqueda, 0, 120, 'UTF-8');
        $soloBajos = $this->booleanFilter($input, 'bajos');
        $fechaInicio = $this->dateFilter($input, 'fecha_inicio');
        $fechaFin = $this->dateFilter($input, 'fecha_fin');
        if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio > $fechaFin) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }
        $estado = 'activo';

        if ($esAdministrador) {
            $valorEstado = $input['estado'] ?? null;
            if ($valorEstado !== null && !is_string($valorEstado)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
            $estado = $valorEstado === null || $valorEstado === ''
                ? 'activo'
                : $valorEstado;
            if (!in_array($estado, ['activo', 'inactivo', 'todos'], true)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
        }

        return [
            'busqueda' => $busqueda,
            'soloBajos' => $soloBajos,
            'estado' => $estado,
            'activo' => $estado === 'todos' ? null : $estado === 'activo',
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];
    }

    /**
     * Valida el estado solicitado para el reporte de inventario.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function inventoryReportState(array $input): string
    {
        $estado = $this->stringValue($input, 'estado_reporte');
        if ($estado === '') {
            $estado = 'todos';
        }
        if (!in_array($estado, ['todos', 'activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('El filtro de estado del reporte no es válido.');
        }

        return $estado;
    }

    /**
     * Valida y normaliza los datos de un movimiento de inventario.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function movement(array $input): array
    {
        $id = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);
        $quantity = filter_var($input['cantidad'] ?? null, FILTER_VALIDATE_INT);
        $type = $input['tipo'] ?? '';
        if (!is_scalar($type)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }
        $type = (string) $type;

        if ($id === false || $id === null || $id < 1 || $quantity === false || $quantity === null) {
            throw new InvalidArgumentException('Datos de movimiento incompletos.');
        }
        if (!in_array($type, ['entrada', 'salida', 'ajuste'], true)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }
        if ($quantity < 0 || ($type !== 'ajuste' && $quantity === 0)) {
            throw new InvalidArgumentException('La cantidad del movimiento no es válida.');
        }

        $observation = $input['observacion'] ?? '';
        if (!is_scalar($observation)) {
            throw new InvalidArgumentException('La observación no es válida.');
        }
        $observation = trim((string) $observation);
        if (mb_strlen($observation, 'UTF-8') > 255) {
            throw new InvalidArgumentException('La observación no puede superar 255 caracteres.');
        }

        return [
            'id' => (int) $id,
            'tipo' => $type,
            'cantidad' => (int) $quantity,
            'observacion' => $observation,
        ];
    }

    /**
     * Valida los datos recibidos y devuelve los valores normalizados.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function validate(array $input): array
    {
        $stockInput = $input['stock'] ?? '';
        $minimumInput = $input['stock_minimo'] ?? '';
        $priceInput = $input['precio_referencia'] ?? '';
        if (!is_scalar($stockInput) || !is_scalar($minimumInput) || !is_scalar($priceInput)) {
            throw new InvalidArgumentException('Los valores numéricos no son válidos.');
        }
        $data = [
            'codigo' => strtoupper(trim($this->stringValue($input, 'codigo'))),
            'nombre' => trim($this->stringValue($input, 'nombre')),
            'descripcion' => trim($this->stringValue($input, 'descripcion')),
            'stock' => $stockInput === '' ? 0 : filter_var($stockInput, FILTER_VALIDATE_INT),
            'stock_minimo' => $minimumInput === '' ? 0 : filter_var($minimumInput, FILTER_VALIDATE_INT),
            'precio_referencia' => $priceInput !== '' ? filter_var($priceInput, FILTER_VALIDATE_FLOAT) : null,
        ];

        if ($data['stock'] === false || $data['stock_minimo'] === false) {
            throw new InvalidArgumentException('El stock debe ser un número entero válido.');
        }
        if ($data['codigo'] === '') {
            throw new InvalidArgumentException('El código es obligatorio.');
        }
        if ($data['nombre'] === '') {
            throw new InvalidArgumentException('El nombre es obligatorio.');
        }
        if ($data['stock'] < 0 || $data['stock_minimo'] < 0) {
            throw new InvalidArgumentException('El stock no puede ser negativo.');
        }
        if ($data['precio_referencia'] === false) {
            throw new InvalidArgumentException('El precio no es válido.');
        }
        if ($data['precio_referencia'] !== null && $data['precio_referencia'] < 0) {
            throw new InvalidArgumentException('El precio no puede ser negativo.');
        }

        foreach ([['codigo', 40], ['nombre', 120], ['descripcion', 255]] as [$field, $length]) {
            if (mb_strlen($data[$field], 'UTF-8') > $length) {
                throw new InvalidArgumentException('Uno de los campos supera la longitud permitida.');
            }
        }

        return $data;
    }

    /**
     * Extrae el campo solicitado, verifica que sea texto y devuelve su version recortada.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function stringValue(array $input, string $key): string
    {
        $value = $input[$key] ?? '';
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('El campo recibido no es válido.');
        }

        return (string) $value;
    }

    /**
     * Interpreta el valor del control como filtro booleano de estado.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Devuelve: valor de tipo bool, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function booleanFilter(array $input, string $key): bool
    {
        $value = $input[$key] ?? '';
        if (!is_scalar($value) || !in_array((string) $value, ['', '1'], true)) {
            throw new InvalidArgumentException('El filtro de existencias no es válido.');
        }

        return (string) $value === '1';
    }

    /**
     * Valida una fecha opcional del filtro y la devuelve normalizada.
     * Parametro $input: valores GET recibidos.
     * Parametro $key: nombre de la fecha a validar.
     * Devuelve: fecha Y-m-d o null cuando el campo está vacío.
     */
    private function dateFilter(array $input, string $key): ?string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException('El filtro de fecha no es válido.');
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errores = DateTimeImmutable::getLastErrors();
        if (
            $fecha === false
            || ($errores !== false && ($errores['warning_count'] > 0 || $errores['error_count'] > 0))
            || $fecha->format('Y-m-d') !== $value
        ) {
            throw new InvalidArgumentException('El filtro de fecha no es válido.');
        }

        return $value;
    }
}
