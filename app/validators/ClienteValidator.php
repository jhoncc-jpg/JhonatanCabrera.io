<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con ClienteValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\ClienteController.php.
 */

/**
 * Valida y normaliza los datos de Cliente antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class ClienteValidator
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
        $busqueda = mb_substr($busqueda, 0, 100, 'UTF-8');

        $estado = 'activo';
        if ($esAdministrador) {
            $estadoRecibido = $input['estado'] ?? null;
            if ($estadoRecibido === null && ($input['todos'] ?? '') === '1') {
                $estadoRecibido = 'todos';
            }
            if ($estadoRecibido !== null && !is_string($estadoRecibido)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
            $estado = $estadoRecibido === null || $estadoRecibido === ''
                ? 'activo'
                : $estadoRecibido;
            if (!in_array($estado, ['activo', 'inactivo', 'todos'], true)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
        }

        $fechaDesde = $this->dateValue($input, 'desde');
        $fechaHasta = $this->dateValue($input, 'hasta');
        if ($fechaDesde !== null && $fechaHasta !== null && $fechaDesde > $fechaHasta) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        return [
            'busqueda' => $busqueda,
            'estado' => $estado,
            'activo' => $estado === 'todos' ? null : $estado === 'activo',
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
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
        $data = [
            'nombre' => trim($this->stringValue($input, 'nombre')),
            'telefono' => trim($this->stringValue($input, 'telefono')),
            'direccion' => trim($this->stringValue($input, 'direccion')),
            'correo' => trim($this->stringValue($input, 'correo')),
        ];

        if ($data['nombre'] === '') {
            throw new InvalidArgumentException('El nombre es obligatorio');
        }
        if ($data['telefono'] === '') {
            throw new InvalidArgumentException('El teléfono es obligatorio.');
        }
        if ($data['direccion'] === '') {
            throw new InvalidArgumentException('La dirección es obligatoria.');
        }
        if ($data['correo'] !== '' && !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('El correo no es válido');
        }

        $data['nombre'] = mb_substr($data['nombre'], 0, 100, 'UTF-8');
        $data['telefono'] = mb_substr($data['telefono'], 0, 20, 'UTF-8');
        $data['direccion'] = mb_substr($data['direccion'], 0, 200, 'UTF-8');
        $data['correo'] = mb_substr($data['correo'], 0, 100, 'UTF-8');
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
}
