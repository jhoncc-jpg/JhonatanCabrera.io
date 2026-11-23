<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con UsuarioValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\UsuarioController.php.
 */

/**
 * Valida y normaliza los datos de Usuario antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class UsuarioValidator
{
    /**
     * Normaliza y valida los criterios de busqueda y fechas recibidos.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function filtros(array $input): array
    {
        $busqueda = $input['q'] ?? '';
        $estado = $input['estado'] ?? 'todos';
        if (!is_string($busqueda) || mb_strlen($busqueda, 'UTF-8') > 120) {
            throw new InvalidArgumentException('La búsqueda no puede superar 120 caracteres.');
        }
        if (!is_string($estado) || !in_array($estado, ['todos', 'activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('El filtro de estado no es válido.');
        }

        return [
            'busqueda' => trim($busqueda),
            'estado' => $estado,
            'activo' => $estado === 'todos' ? null : $estado === 'activo',
        ];
    }

    /**
     * Valida los datos de una cuenta nueva y devuelve sus campos normalizados.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function nuevo(array $input): array
    {
        $nombre = $this->texto($input, 'nombre', 'El nombre es obligatorio.');
        $usuario = strtolower($this->texto($input, 'usuario', 'El nombre de usuario es obligatorio.'));
        $password = $input['password'] ?? null;
        $rolId = $this->id($input['rol_id'] ?? null, 'Seleccione un rol válido.');

        if ($nombre === '' || mb_strlen($nombre, 'UTF-8') > 100) {
            throw new InvalidArgumentException('El nombre es obligatorio y no puede superar 100 caracteres.');
        }
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $usuario)) {
            throw new InvalidArgumentException('El usuario debe tener entre 3 y 50 caracteres: letras, números, punto, guion o guion bajo.');
        }
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('La contraseña debe tener entre 8 y 72 caracteres.');
        }

        return [
            'nombre' => $nombre,
            'usuario' => $usuario,
            'password' => $password,
            'rol_id' => $rolId,
        ];
    }

    /**
     * Valida los requisitos de contrasena y devuelve el valor aceptado.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function password(array $input): string
    {
        $password = $input['password'] ?? null;
        if (!is_string($password) || strlen($password) < 8 || strlen($password) > 72) {
            throw new InvalidArgumentException('La contraseña debe tener entre 8 y 72 caracteres.');
        }

        return $password;
    }

    /**
     * Valida que el identificador sea un entero positivo y lo devuelve como entero.
     * Parametro $value: valor candidato que se debe validar o normalizar.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function id($value, string $message): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || (int) $id < 1) {
            throw new InvalidArgumentException($message);
        }

        return (int) $id;
    }

    /**
     * Convierte el texto a la codificacion aceptada por la biblioteca PDF.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Parametro $key: nombre de la clave o campo que se debe leer.
     * Parametro $message: mensaje de error que se comunica si el valor no es valido.
     * Devuelve: valor de tipo string, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    private function texto(array $input, string $key, string $message): string
    {
        $value = $input[$key] ?? '';
        if (!is_string($value)) {
            throw new InvalidArgumentException($message);
        }

        return trim($value);
    }
}
