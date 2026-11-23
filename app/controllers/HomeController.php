
<?php

/*
 * Proposito: Coordina las solicitudes y acciones de HomeController entre la entrada web, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/HomeRepository.php, /../views/home.php.
 * Consumidores/dependientes: app\controllers\RouterController.php, app\views\home.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/HomeRepository.php';

/**
 * Coordina las solicitudes de Home: aplica autorizacion y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class HomeController
{
    /**
     * Atiende la pagina de Home: prepara datos, procesa la solicitud y carga la vista.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function index(): void
    {
        AuthController::requireAuthenticated();

        $rolSesion = (string) $_SESSION['rol'];
        $nombre = (string) ($_SESSION['nombre'] ?? 'Usuario');
        $tituloInicio = $rolSesion === 'recepcionista' ? 'Panel de recepción' : 'Panel del taller';
        $descripcionInicio = $rolSesion === 'recepcionista'
            ? 'Organiza la atención, las órdenes terminadas y las entregas pendientes.'
            : 'Consulta la actividad del taller y accede a las tareas principales.';
        $mostrarAccionesInicio = true;
        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        $periodoActual = $meses[(int) date('n')] . ' ' . date('Y');
        $errorResumen = null;
        $resumen = [
            'clientes' => 0,
            'vehiculos' => 0,
            'ordenes_mes' => 0,
            'ordenes_abiertas' => 0,
            'ordenes_listas' => 0,
            'ingresos_mes' => 0,
            'repuestos_bajos' => 0
        ];

        try {
            $resumen = (new HomeRepository())->resumen(
                $rolSesion,
                (int) ($_SESSION['usuario_id'] ?? 0),
                $nombre
            );
        } catch (PDOException $exception) {
            error_log('No se pudo cargar el resumen del taller: ' . $exception->getMessage());
            http_response_code(500);
            $errorResumen = 'No se pudo actualizar el resumen. Puedes seguir usando los módulos del sistema.';
        }

        require __DIR__ . '/../views/home.php';
    }
}
