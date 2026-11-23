
<?php

/*
 * Proposito: Centraliza la creacion de conexiones PDO a la base de datos configurada.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\repositories\ClienteRepository.php, app\repositories\HomeRepository.php, app\repositories\MecanicoRepository.php, app\repositories\OrdenRepository.php, app\repositories\ReporteRepository.php, app\repositories\RepuestoRepository.php, app\repositories\UsuarioRepository.php, app\repositories\VehiculoRepository.php, config\Conexion.php, database\crear_usuarios.php.
 */

// Centraliza la conexión a MySQL para que los modelos no repitan la configuración.
/**
 * Centraliza la creacion de conexiones PDO y las opciones de acceso a MySQL.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Database
{
    private string $host = 'sql202.infinityfree.com';
    private string $database = 'if0_43513083_autogest_proo';
    private string $username = 'if0_43513083';
    private string $password = 'pPQaYPWfPrR7EB';

    /**
     * Crea y devuelve la conexion PDO usando las opciones configuradas.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo PDO, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function connect(): PDO
    {
        // DSN: indica a PDO el motor, servidor, base de datos y codificación.
        $dsn = "mysql:host={$this->host};dbname={$this->database};charset=utf8mb4";

        // PDO permite ejecutar SQL con parámetros preparados.
        return new PDO(
            $dsn,
            $this->username,
            $this->password,
            [
                // Los errores SQL se lanzan como excepciones para poder detectarlos.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Cada fila se devuelve como arreglo asociativo: ['campo' => valor].
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Obliga al controlador MySQL a preparar consultas reales.
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    }
}
