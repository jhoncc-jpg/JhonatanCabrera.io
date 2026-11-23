<?php

/*
 * Proposito: Proporciona un punto de acceso de compatibilidad a la conexion PDO compartida.
 * Dependencias: /database.php.
 * Consumidores/dependientes: los modulos que usan Conexion para obtener PDO.
 */

require_once __DIR__ . '/database.php';

// Mantiene una interfaz en español para los módulos nuevos sin duplicar la configuración actual.
/**
 * Ofrece el metodo de conexion con nombre en espanol que delega en la configuracion compartida.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Conexion
{
    /**
     * Obtiene y devuelve la conexion PDO compartida.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo PDO, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function conectar(): PDO
    {
        return (new Database())->connect();
    }
}
