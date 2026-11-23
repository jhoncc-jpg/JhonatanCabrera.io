<?php

/*
 * Proposito: Coordina las solicitudes y acciones de RouterController entre la entrada web, los validadores, los modelos y las vistas.
 * Dependencias: /AuthController.php, /HomeController.php, /ClienteController.php, /VehiculoController.php, /MecanicoController.php, /RepuestoController.php, /OrdenController.php, /UsuarioController.php, /ReporteController.php, /ReporteInventarioController.php.
 * Consumidores/dependientes: public\index.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/HomeController.php';
require_once __DIR__ . '/ClienteController.php';
require_once __DIR__ . '/VehiculoController.php';
require_once __DIR__ . '/MecanicoController.php';
require_once __DIR__ . '/RepuestoController.php';
require_once __DIR__ . '/OrdenController.php';
require_once __DIR__ . '/UsuarioController.php';
require_once __DIR__ . '/ReporteController.php';
require_once __DIR__ . '/ReporteInventarioController.php';

/**
 * Coordina las solicitudes de Router: aplica autorizacion y comunica los controladores con sus repositorios y vistas.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class RouterController
{
    /**
     * Interpreta la pagina solicitada, comprueba el acceso y delega la ejecucion al controlador correspondiente.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function dispatch(): void
    {
        $page = $_GET['page'] ?? 'home';
        if (!is_string($page)) {
            http_response_code(404);
            echo 'Página no encontrada.';
            return;
        }

        switch ($page) {
            case 'login':
                if (AuthController::isAuthenticated($_SESSION ?? [])) {
                    $destino = $_SESSION['rol'] === 'mecanico' ? 'ordenes' : 'home';
                    $this->redirect($destino);
                }
                (new AuthController())->login();
                return;

            case 'logout':
                (new AuthController())->logout();
                return;

            case 'home':
                AuthController::requireAuthenticated();
                if (($_SESSION['rol'] ?? '') === 'mecanico') {
                    $this->redirect('ordenes');
                }
                (new HomeController())->index();
                return;

            case 'clientes':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new ClienteController())->index();
                return;

            case 'vehiculos':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new VehiculoController())->index();
                return;

            case 'mecanicos':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new MecanicoController())->index();
                return;

            case 'ordenes':
                AuthController::requireRoles(['administrador', 'recepcionista', 'mecanico']);
                (new OrdenController())->index();
                return;

            case 'repuestos':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new RepuestoController())->index();
                return;

            case 'reportes':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new ReporteController())->index();
                return;

            case 'reporte-movimientos':
                AuthController::requireRoles(['administrador', 'recepcionista']);
                (new ReporteController())->movimientos();
                return;

            case 'reporte-inventario':
                (new ReporteInventarioController())->download();
                return;

            case 'usuarios':
                AuthController::requireRoles(['administrador']);
                (new UsuarioController())->index();
                return;

            default:
                http_response_code(404);
                echo 'Página no encontrada.';
        }
    }

    /**
     * Redirige al destino de pagina indicado mediante una respuesta HTTP Location.
     * Parametro $page: valor de tipo string recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function redirect(string $page): void
    {
        header('Location: ?page=' . rawurlencode($page));
        exit;
    }
}
