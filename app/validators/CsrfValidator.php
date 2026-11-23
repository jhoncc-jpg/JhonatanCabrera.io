<?php

/*
 * Proposito: Valida y normaliza entradas y filtros relacionados con CsrfValidator antes de que el controlador los use.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\ClienteController.php, app\controllers\MecanicoController.php, app\controllers\OrdenController.php, app\controllers\RepuestoController.php, app\controllers\UsuarioController.php, app\controllers\VehiculoController.php.
 */

/**
 * Valida y normaliza los datos de Csrf antes de que el controlador los procese.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class CsrfValidator
{
    /**
     * Comprueba que los datos cumplan la condicion de validez esperada.
     * Parametro $request: datos recibidos en la solicitud HTTP.
     * Parametro $session: datos de la sesion que se van a comprobar.
     * Devuelve: valor de tipo bool, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function isValid(array $request, array $session): bool
    {
        $token = $request['csrf_token'] ?? null;
        $sessionToken = $session['csrf_token'] ?? null;
        return is_string($token)
            && is_string($sessionToken)
            && hash_equals($sessionToken, $token);
    }
}
