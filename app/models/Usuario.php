<?php

/*
 * Proposito: Encapsula los datos y operaciones de persistencia correspondientes a Usuario, usando la conexion compartida cuando aplica.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: app\controllers\HomeController.php, app\controllers\UsuarioController.php, app\repositories\UsuarioRepository.php, app\views\auth\login.php, app\views\layouts\sidebar.php, app\views\usuarios\index.php.
 */

/**
 * Representa la estructura de datos Usuario utilizada por el modelo y las vistas de su seccion.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
class Usuario
{
    /**
     * Inicializa Usuario y prepara sus dependencias.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $nombre: nombre de la persona o entidad relacionada.
     * Parametro $usuario: identificador del usuario que realiza la operacion.
     * Parametro $passwordHash: hash de contrasena que se guarda, nunca la contrasena en texto plano.
     * Parametro $rol: rol que se consulta o se va a asignar.
     * Parametro $activo: filtro o estado que determina si se incluyen registros activos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    public function __construct(
        public int $id,
        public string $nombre,
        public string $usuario,
        public string $passwordHash,
        public string $rol,
        public bool $activo
    ) {
    }
}
