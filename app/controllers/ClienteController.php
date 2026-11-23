
<?php

/*
 * Proposito: Coordina las solicitudes y acciones de ClienteController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/ClienteRepository.php, /../validators/ClienteValidator.php, /../validators/CsrfValidator.php, /../views/clientes/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php, app\views\clientes\index.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/ClienteRepository.php';
require_once __DIR__ . '/../validators/ClienteValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

// Recibe las acciones de la pantalla de clientes y prepara sus datos de vista.
/**
 * Coordina las solicitudes de Cliente: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class ClienteController
{
    /**
     * Atiende la pagina de Cliente: prepara datos, procesa la solicitud y carga la vista.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function index(): void
    {
        AuthController::requireRoles(['administrador', 'recepcionista']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $mensaje = $_SESSION['mensaje_cliente'] ?? null;
        unset($_SESSION['mensaje_cliente']);
        $error = null;
        $esAdministrador = $_SESSION['rol'] === 'administrador';
        $validador = new ClienteValidator();

        try {
            $filtros = $validador->filters($_GET, $esAdministrador);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = [
                'busqueda' => '',
                'estado' => 'activo',
                'activo' => true,
                'desde' => null,
                'hasta' => null,
            ];
        }

        $busqueda = $filtros['busqueda'];

        try {
            $repositorio = new ClienteRepository();

            // En POST se valida y guarda el formulario; en GET solo se consulta.
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!(new CsrfValidator())->isValid($_POST, $_SESSION)) {
                    http_response_code(403);
                    $error = 'La solicitud no es válida. Recarga la página.';
                } else {
                    try {
                        $this->procesarFormulario($repositorio, $validador);
                    } catch (InvalidArgumentException $exception) {
                        http_response_code(422);
                        $error = $exception->getMessage();
                    }
                }
            }

            $clientes = $repositorio->listar(
                $filtros['busqueda'],
                $filtros['activo'],
                $filtros['desde'],
                $filtros['hasta']
            );
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el módulo de clientes: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo procesar la operación de clientes.';
            $clientes = [];
        } catch (RuntimeException $exception) {
            error_log('No se pudo guardar un cliente: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo guardar el cliente.';
            $clientes = [];
        }

        require __DIR__ . '/../views/clientes/index.php';
    }

    /**
     * Valida y procesa el formulario de Cliente, invocando la operacion del repositorio.
     * Parametro $repositorio: repositorio utilizado para persistir los datos validados.
     * Parametro $validador: valor de tipo ClienteValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarFormulario(ClienteRepository $repositorio, ClienteValidator $validador): void
    {
        $accion = $_POST['accion'] ?? '';
        if (!is_string($accion)) {
            throw new InvalidArgumentException('Acción inválida.');
        }

        // Activar/desactivar cambia el estado, conservando el historial del cliente.
        if ($accion === 'desactivar' || $accion === 'activar') {
            $id = $this->idFormulario();
            if (!$repositorio->cambiarEstado($id, $accion === 'activar')) {
                throw new RuntimeException('No se pudo actualizar el estado del cliente.');
            }
            $_SESSION['mensaje_cliente'] = $accion === 'activar'
                ? 'Cliente activado correctamente.'
                : 'Cliente desactivado correctamente.';
            header('Location: ?page=clientes');
            exit;
        }

        $datos = $validador->validate($_POST);

        if ($accion === 'crear') {
            // El repositorio guarda los cambios y el mensaje se muestra
            // después de redirigir (patrón POST/Redirect/GET).
            if (!$repositorio->crear($datos)) {
                throw new RuntimeException('No se pudo registrar el cliente.');
            }
            $_SESSION['mensaje_cliente'] = 'Cliente registrado correctamente.';
        } elseif ($accion === 'editar') {
            $id = $this->idFormulario();
            if (!$repositorio->actualizar($id, $datos)) {
                throw new RuntimeException('No se pudo actualizar el cliente.');
            }
            $_SESSION['mensaje_cliente'] = 'Cliente actualizado correctamente.';
        } else {
            throw new InvalidArgumentException('Acción inválida.');
        }

        header('Location: ?page=clientes');
        exit;
    }

    /**
     * Obtiene y valida el identificador de registro enviado en el formulario.
     * Parametros: no recibe argumentos.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function idFormulario(): int
    {
        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if ($id === false || $id === null || $id < 1) {
            throw new InvalidArgumentException('El cliente seleccionado no es válido.');
        }

        return $id;
    }
}
