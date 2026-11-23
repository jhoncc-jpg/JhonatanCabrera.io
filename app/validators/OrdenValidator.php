<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con OrdenValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\OrdenController.php.
 */

/**
 * Valida y normaliza los datos de Orden antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class OrdenValidator
{
    private const ESTADOS_ORDEN = [
        'registrada',
        'diagnostico',
        'en_reparacion',
        'pendiente_repuesto',
        'lista',
        'entregada',
        'cancelada',
    ];

    /**
     * Normaliza los criterios de busqueda y filtros para el modelo.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function filters(array $input): array
    {
        $busqueda = $this->text($input, 'q', 120, 'La búsqueda no puede superar 120 caracteres.');
        $estado = $input['estado'] ?? '';
        if (!is_string($estado) || ($estado !== '' && !in_array($estado, self::ESTADOS_ORDEN, true))) {
            throw new InvalidArgumentException('El filtro de estado no es válido.');
        }

        $desde = $this->dateFilter($input, 'desde');
        $hasta = $this->dateFilter($input, 'hasta');
        if ($desde !== null && $hasta !== null && $desde > $hasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        return [
            'busqueda' => $busqueda,
            'estado' => $estado,
            'desde' => $desde,
            'hasta' => $hasta,
        ];
    }

    /**
     * Obtiene el campo indicado, admite solo valores escalares y limita su longitud antes de devolverlo.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Parametro $maxLength: longitud maxima permitida para el texto.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function text(array $input, string $key, int $maxLength, string $message): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException($message);
        }
        $value = trim((string) $value);
        if (mb_strlen($value, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException($message);
        }

        return $value;
    }

    /**
     * Convierte y valida un entero positivo; lanza la excepcion con el mensaje indicado si no es valido.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function positiveInteger($value, string $message): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || (int) $id < 1) {
            throw new InvalidArgumentException($message);
        }
        return (int) $id;
    }

    /**
     * Valida que se haya seleccionado al menos un mecanico y devuelve sus identificadores positivos sin duplicados.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function mechanicIds($value): array
    {
        if (!is_array($value) || count($value) === 0) {
            throw new InvalidArgumentException('Seleccione al menos un mecánico.');
        }

        $ids = [];
        foreach ($value as $item) {
            $id = filter_var($item, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || (int) $id < 1) {
                throw new InvalidArgumentException('La selección de mecánicos no es válida.');
            }
            $ids[] = (int) $id;
        }
        $ids = array_values(array_unique($ids));
        if ($ids === []) {
            throw new InvalidArgumentException('Seleccione al menos un mecánico válido.');
        }
        return $ids;
    }

    /**
     * Comprueba que el valor sea una de las opciones permitidas y lo devuelve.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $allowed: valor de tipo array recibido para esta operacion.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function enumValue($value, array $allowed, string $message): string
    {
        if (!is_string($value) || !in_array($value, $allowed, true)) {
            throw new InvalidArgumentException($message);
        }
        return $value;
    }

    /**
     * Valida un importe no negativo con hasta dos decimales; puede devolver null si se permite dejarlo vacio.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Parametro $allowEmpty: valor de tipo bool recibido para esta operacion.
     * Devuelve: valor de tipo ?float, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function number($value, string $message, bool $allowEmpty = false): ?float
    {
        if ($allowEmpty && ($value === null || $value === '')) {
            return null;
        }
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException($message);
        }
        $formatted = trim((string) $value);
        $number = filter_var($formatted, FILTER_VALIDATE_FLOAT);
        if (
            !preg_match('/^\d+(?:\.\d{1,2})?$/D', $formatted)
            || $number === false
            || $number < 0
            || $number > 99999999.99
        ) {
            throw new InvalidArgumentException($message);
        }
        return (float) $number;
    }

    /**
     * Valida que haya identificadores positivos seleccionados y elimina valores repetidos.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $emptyMessage: valor de tipo string recibido para esta operacion.
     * Parametro $invalidMessage: valor de tipo string recibido para esta operacion.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function selectedIds($value, string $emptyMessage, string $invalidMessage): array
    {
        if (!is_array($value) || $value === []) {
            throw new InvalidArgumentException($emptyMessage);
        }
        $ids = [];
        foreach ($value as $item) {
            $id = filter_var($item, FILTER_VALIDATE_INT);
            if ($id === false || $id === null || (int) $id < 1) {
                throw new InvalidArgumentException($invalidMessage);
            }
            $ids[] = (int) $id;
        }
        return array_values(array_unique($ids));
    }

    /**
     * Comprueba que las tareas seleccionadas existan en el catalogo permitido.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $catalog: lista permitida de tareas para comprobar la seleccion.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function catalogTasks($value, array $catalog): array
    {
        if (!is_array($value) || $value === []) {
            throw new InvalidArgumentException('Seleccione al menos un trabajo.');
        }
        foreach ($value as $task) {
            if (!is_string($task) || !in_array($task, $catalog, true)) {
                throw new InvalidArgumentException('La selección contiene un trabajo inválido.');
            }
        }
        return $value;
    }

    /**
     * Interpreta la fecha con el formato y nombre de campo indicados; devuelve null para una fecha vacia.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $format: valor de tipo string recibido para esta operacion.
     * Parametro $field: nombre legible del campo que se valida.
     * Devuelve: valor de tipo ?DateTimeImmutable, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function parseDate(string $value, string $format, string $field): ?DateTimeImmutable
    {
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format($format) !== $value
        ) {
            throw new InvalidArgumentException($field . ' no es válida. Seleccione una fecha del calendario.');
        }
        return $date;
    }

    /**
     * Valida el limite de fecha del filtro y devuelve su valor normalizado o null.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Devuelve: valor de tipo ?string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function dateFilter(array $input, string $key): ?string
    {
        $value = $this->text($input, $key, 10, 'El filtro de fecha no es válido.');
        if ($value === '') {
            return null;
        }

        $date = $this->parseDate($value, 'Y-m-d', 'El filtro de fecha');
        return $date->format('Y-m-d');
    }

    /**
     * Verifica que los limites de fecha formen un intervalo valido.
     * Parametro $entry: valor de tipo ?DateTimeImmutable recibido para esta operacion.
     * Parametro $return: valor de tipo ?DateTimeImmutable recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function validateDateRange(?DateTimeImmutable $entry, ?DateTimeImmutable $return): void
    {
        if ($return !== null && $return->format('Y-m-d') < ($entry ?? new DateTimeImmutable())->format('Y-m-d')) {
            throw new InvalidArgumentException('La fecha prevista de retorno no puede ser anterior a la fecha de ingreso.');
        }
    }
}
