<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con MecanicoValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\MecanicoController.php.
 */

/**
 * Valida y normaliza los datos de Mecanico antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class MecanicoValidator
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
        $estado = 'todos';

        if ($esAdministrador) {
            $valorEstado = $input['estado'] ?? null;
            if ($valorEstado === null && ($input['activos'] ?? '') === '1') {
                $valorEstado = 'activo';
            }
            if ($valorEstado !== null && !is_string($valorEstado)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
            $estado = $valorEstado === null || $valorEstado === ''
                ? 'todos'
                : $valorEstado;
            if (!in_array($estado, ['activo', 'inactivo', 'todos'], true)) {
                throw new InvalidArgumentException('El filtro de estado no es válido.');
            }
        }

        return [
            'busqueda' => $busqueda,
            'estado' => $estado,
            'activo' => $estado === 'todos' ? null : $estado === 'activo',
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
            'especialidad' => trim($this->stringValue($input, 'especialidad')),
        ];

        if ($data['nombre'] === '') {
            throw new InvalidArgumentException('El nombre es obligatorio.');
        }

        foreach (['nombre' => 100, 'telefono' => 20, 'especialidad' => 100] as $field => $length) {
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
}
