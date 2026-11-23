<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con AuthValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, antes de invocar el modelo.
 */

/**
 * Valida y normaliza los datos de Auth antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class AuthValidator
{
    /**
     * Valida y normaliza las credenciales de autenticacion.
     * Parametro $input: datos de entrada recibidos y pendientes de validar.
     * Devuelve: valor de tipo array, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function credentials(array $input): array
    {
        $username = $input['usuario'] ?? '';
        $password = $input['password'] ?? '';
        $username = is_scalar($username) ? trim((string) $username) : '';
        $password = is_string($password) ? $password : '';

        if ($username === '' || $password === '') {
            throw new InvalidArgumentException('Introduce tu usuario y contraseña.');
        }

        return [$username, $password];
    }
}
