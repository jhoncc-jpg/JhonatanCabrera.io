<?php

/*
 * Proposito: Coordina las solicitudes y acciones de MecanicoController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/MecanicoRepository.php, /../validators/MecanicoValidator.php, /../validators/CsrfValidator.php, /../views/mecanicos/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/MecanicoRepository.php';
require_once __DIR__ . '/../validators/MecanicoValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

// Coordina el formulario/listado y el repositorio de mecánicos.
/**
 * Coordina las solicitudes de Mecanico: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class MecanicoController
{
    /**
     * Atiende la pagina de Mecanico: prepara datos, procesa la solicitud y carga la vista.
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
        $mensaje = $_SESSION['mensaje_mecanico'] ?? null;
        unset($_SESSION['mensaje_mecanico']);
        $error = null;
        $esAdministrador = $_SESSION['rol'] === 'administrador';
        $validador = new MecanicoValidator();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthController::requireRoles(['administrador']);
        }

        try {
            $filtros = $validador->filters($_GET, $esAdministrador);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = ['busqueda' => '', 'estado' => 'todos', 'activo' => null];
        }

        $busqueda = $filtros['busqueda'];

        try {
            $repositorio = new MecanicoRepository();
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

            $mecanicos = $repositorio->listar($busqueda, $filtros['activo']);
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el módulo de mecánicos: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo procesar la operación de mecánicos.';
            $mecanicos = [];
        } catch (RuntimeException $exception) {
            error_log('No se pudo guardar un mecánico: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo guardar el mecánico.';
            $mecanicos = [];
        }

        require __DIR__ . '/../views/mecanicos/index.php';
    }

    /**
     * Valida y procesa el formulario de Mecanico, invocando la operacion del repositorio.
     * Parametro $repositorio: repositorio utilizado para persistir los datos validados.
     * Parametro $validador: valor de tipo MecanicoValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarFormulario(MecanicoRepository $repositorio, MecanicoValidator $validador): void
    {
        $accion = $_POST['accion'] ?? '';
        if (!is_string($accion)) {
            throw new InvalidArgumentException('Acción inválida.');
        }

        if ($accion === 'eliminar') {
            $repositorio->eliminar($this->idFormulario());
            $_SESSION['mensaje_mecanico'] = 'Mecánico eliminado correctamente.';
            header('Location: ?page=mecanicos');
            exit;
        }

        if ($accion === 'activar' || $accion === 'desactivar') {
            if (!$repositorio->cambiarEstado($this->idFormulario(), $accion === 'activar')) {
                throw new RuntimeException('No se pudo actualizar el estado del mecánico.');
            }
            $_SESSION['mensaje_mecanico'] = $accion === 'activar'
                ? 'Mecánico activado correctamente.'
                : 'Mecánico desactivado correctamente.';
            header('Location: ?page=mecanicos');
            exit;
        }

        $datos = $validador->validate($_POST);

        if ($accion === 'crear') {
            if (!$repositorio->crear($datos)) {
                throw new RuntimeException('No se pudo registrar el mecánico.');
            }
            $_SESSION['mensaje_mecanico'] = 'Mecánico registrado correctamente.';
        } elseif ($accion === 'editar') {
            if (!$repositorio->actualizar($this->idFormulario(), $datos)) {
                throw new RuntimeException('No se pudo actualizar el mecánico.');
            }
            $_SESSION['mensaje_mecanico'] = 'Mecánico actualizado correctamente.';
        } else {
            throw new InvalidArgumentException('Acción inválida.');
        }

        header('Location: ?page=mecanicos');
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
            throw new InvalidArgumentException('El mecánico seleccionado no es válido.');
        }

        return $id;
    }
}
