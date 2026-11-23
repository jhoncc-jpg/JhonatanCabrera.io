<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con VehiculoValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\VehiculoController.php.
 */

/**
 * Valida y normaliza los datos de Vehiculo antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class VehiculoValidator
{
    private const ESTADOS = ['registrado', 'en_revision', 'pendiente_repuesto', 'en_reparacion', 'listo', 'entregado'];

    /**
     * Normaliza los criterios de busqueda y filtros para el modelo.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function filters(array $input): array
    {
        $busqueda = trim($this->stringValue($input, 'q'));
        if (mb_strlen($busqueda, 'UTF-8') > 100) {
            throw new InvalidArgumentException('La búsqueda no puede superar 100 caracteres.');
        }

        $estado = $input['estado_filtro'] ?? '';
        if (!is_string($estado) || ($estado !== '' && !in_array($estado, self::ESTADOS, true))) {
            throw new InvalidArgumentException('El filtro de estado no es válido.');
        }

        $fechaDesde = $this->dateValue($input, 'desde');
        $fechaHasta = $this->dateValue($input, 'hasta');
        if ($fechaDesde !== null && $fechaHasta !== null && $fechaDesde > $fechaHasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        return [
            'busqueda' => $busqueda,
            'estado' => $estado !== '' ? $estado : null,
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
        ];
    }

    /**
     * Valida los datos recibidos y devuelve los valores normalizados.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $action: accion CRUD a la que corresponden los datos.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: consulta la base de datos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function validate(array $input, string $action): array
    {
        if (!in_array($action, ['crear', 'editar'], true)) {
            throw new InvalidArgumentException('Acción inválida');
        }

        $owner = $this->stringValue($input, 'propietario');
        $owner = mb_substr(trim($owner), 0, 100, 'UTF-8');
        $data = [
            'propietario' => $owner,
            'telefono_propietario' => trim($this->stringValue($input, 'telefono_propietario')),
            'direccion_propietario' => trim($this->stringValue($input, 'direccion_propietario')),
            'placa' => strtoupper(trim($this->stringValue($input, 'placa'))),
            'marca' => trim($this->stringValue($input, 'marca')),
            'modelo' => trim($this->stringValue($input, 'modelo')),
            'anio' => $this->yearValue($input['anio'] ?? ''),
            'color' => trim($this->stringValue($input, 'color')),
            'diagnostico' => mb_substr(trim($this->stringValue($input, 'diagnostico')), 0, 3000, 'UTF-8'),
            'estado' => trim($this->stringValue($input, 'estado')) ?: 'registrado',
            'fecha_registro' => null,
        ];

        if (!in_array($data['estado'], self::ESTADOS, true)) {
            throw new InvalidArgumentException('El estado del vehículo no es válido.');
        }

        if ($data['propietario'] === '') {
            throw new InvalidArgumentException('El nombre del propietario es obligatorio.');
        }
        if ($data['placa'] === '') {
            throw new InvalidArgumentException('La placa es obligatoria.');
        }
        if (!preg_match('/^[A-Z0-9 ]+$/', $data['placa'])) {
            throw new InvalidArgumentException('La placa solo puede contener letras, números y espacios.');
        }
        if ($data['marca'] === '') {
            throw new InvalidArgumentException('La marca es obligatoria.');
        }
        if ($data['modelo'] === '') {
            throw new InvalidArgumentException('El modelo es obligatorio.');
        }
        if ($action === 'crear') {
            $selectedDate = trim($this->stringValue($input, 'fecha_registro_fecha'));
            $selectedTime = trim($this->stringValue($input, 'fecha_registro_hora'));
            if ($selectedDate === '' || $selectedTime === '') {
                throw new InvalidArgumentException('Debes seleccionar la fecha y la hora.');
            }
            $dateTime = DateTime::createFromFormat('!Y-m-d H:i', $selectedDate . ' ' . $selectedTime);
            if (!$dateTime || $dateTime->format('Y-m-d H:i') !== $selectedDate . ' ' . $selectedTime) {
                throw new InvalidArgumentException('La fecha y hora seleccionadas no son válidas.');
            }
            if ($dateTime->format('Y-m-d') < date('Y-m-d')) {
                throw new InvalidArgumentException('La fecha de registro no puede ser anterior a hoy.');
            }
            $data['fecha_registro'] = $dateTime->format('Y-m-d H:i:s');
        }

        $data['placa'] = mb_substr($data['placa'], 0, 20, 'UTF-8');
        $data['marca'] = mb_substr($data['marca'], 0, 60, 'UTF-8');
        $data['modelo'] = mb_substr($data['modelo'], 0, 60, 'UTF-8');
        $data['color'] = mb_substr($data['color'], 0, 40, 'UTF-8');
        return $data;
    }

    /**
     * Valida y normaliza los precios de las tareas segun el catalogo permitido.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $catalog: lista permitida de tareas para comprobar la seleccion.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function prices(array $input, array $catalog): array
    {
        $sentPrices = $input['precios'] ?? null;
        if (!is_array($sentPrices)) {
            throw new InvalidArgumentException('No se recibieron tarifas para guardar.');
        }

        $prices = [];
        foreach ($catalog as $task => $_currentPrice) {
            if (!array_key_exists($task, $sentPrices) || !is_scalar($sentPrices[$task])) {
                throw new InvalidArgumentException('Completa todas las tarifas del catálogo.');
            }
            $price = filter_var($sentPrices[$task], FILTER_VALIDATE_FLOAT);
            if ($price === false || $price < 0) {
                throw new InvalidArgumentException('Las tarifas deben ser números iguales o mayores que cero.');
            }
            $prices[$task] = (float) $price;
        }
        return $prices;
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
     * Valida la fecha del campo solicitado y devuelve su valor normalizado o null si esta vacia.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Devuelve: valor de tipo ?string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function dateValue(array $input, string $key): ?string
    {
        $value = $input[$key] ?? '';
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('El filtro de fecha no es válido.');
        }
        $value = trim((string) $value);
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

    /**
     * Valida que el dato represente un ano permitido y devuelve el entero o null.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Devuelve: valor de tipo ?int, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function yearValue($value): ?int
    {
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('El año del vehículo no es válido.');
        }
        if ((string) $value === '') {
            return null;
        }

        $anio = filter_var($value, FILTER_VALIDATE_INT);
        if ($anio === false || $anio < 1900 || $anio > 2100) {
            throw new InvalidArgumentException('El año debe estar entre 1900 y 2100.');
        }

        return $anio;
    }
}
