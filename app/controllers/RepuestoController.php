<?php

/*
 * Proposito: Coordina las solicitudes y acciones de RepuestoController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/RepuestoRepository.php, /../validators/RepuestoValidator.php, /../validators/CsrfValidator.php, /../views/repuestos/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/RepuestoRepository.php';
require_once __DIR__ . '/../validators/RepuestoValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

// Controla permisos y validaciones del módulo de inventario.
/**
 * Coordina las solicitudes de Repuesto: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class RepuestoController
{
    /**
     * Atiende la pagina de Repuesto: prepara datos, procesa la solicitud y carga la vista.
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

        $mensaje = $_SESSION['mensaje_repuesto'] ?? null;
        unset($_SESSION['mensaje_repuesto']);
        $error = null;
        $validador = new RepuestoValidator();
        $esAdministrador = $_SESSION['rol'] === 'administrador';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthController::requireRoles(['administrador', 'recepcionista']);
        }

        try {
            $filtros = $validador->filters($_GET, $esAdministrador);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = [
                'busqueda' => '',
                'soloBajos' => false,
                'estado' => 'activo',
                'activo' => true,
                'fecha_inicio' => null,
                'fecha_fin' => null,
            ];
        }

        $busqueda = $filtros['busqueda'];
        $soloBajos = $filtros['soloBajos'];

        try {
            $repositorio = new RepuestoRepository();
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

            $repuestos = $repositorio->listar(
                $busqueda,
                $soloBajos,
                $filtros['activo'],
                $filtros['fecha_inicio'],
                $filtros['fecha_fin']
            );
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el inventario: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo procesar la operación de repuestos.';
            $repuestos = [];
        } catch (RuntimeException $exception) {
            error_log('No se pudo guardar el movimiento de inventario: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la operación de inventario.';
            $repuestos = [];
        }

        require __DIR__ . '/../views/repuestos/index.php';
    }

    /**
     * Valida y procesa el formulario de Repuesto, invocando la operacion del repositorio.
     * Parametro $repositorio: repositorio utilizado para persistir repuestos y movimientos.
     * Parametro $validador: valor de tipo RepuestoValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarFormulario(RepuestoRepository $repositorio, RepuestoValidator $validador): void
    {
        $accion = $_POST['accion'] ?? '';
        if (!is_string($accion)) {
            throw new InvalidArgumentException('Acción inválida.');
        }

        if ($accion === 'movimiento') {
            $movement = $validador->movement($_POST);
            $repositorio->registrarMovimiento(
                $movement['id'],
                (int) $_SESSION['usuario_id'],
                $movement['tipo'],
                $movement['cantidad'],
                $movement['observacion']
            );
            $_SESSION['mensaje_repuesto'] = 'Movimiento de inventario registrado.';
            header('Location: ?page=repuestos');
            exit;
        }

        if ($accion === 'activar' || $accion === 'desactivar') {
            if (!$repositorio->cambiarEstado($this->idFormulario(), $accion === 'activar')) {
                throw new RuntimeException('No se pudo cambiar el estado del repuesto.');
            }
            $_SESSION['mensaje_repuesto'] = $accion === 'activar'
                ? 'Repuesto activado correctamente.'
                : 'Repuesto desactivado correctamente.';
            header('Location: ?page=repuestos');
            exit;
        }

        $datos = $validador->validate($_POST);

        if ($accion === 'crear') {
            if ($repositorio->codigoExiste($datos['codigo'])) {
                throw new InvalidArgumentException('Ya existe un repuesto con ese código.');
            }
            if (!$repositorio->crear($datos)) {
                throw new RuntimeException('No se pudo registrar el repuesto.');
            }
            $_SESSION['mensaje_repuesto'] = 'Repuesto registrado correctamente.';
        } elseif ($accion === 'editar') {
            $id = $this->idFormulario();
            if ($repositorio->codigoExiste($datos['codigo'], $id)) {
                throw new InvalidArgumentException('Ya existe otro repuesto con ese código.');
            }
            unset($datos['stock']);
            if (!$repositorio->actualizar($id, $datos)) {
                throw new RuntimeException('No se pudo actualizar el repuesto.');
            }
            $_SESSION['mensaje_repuesto'] = 'Repuesto actualizado correctamente.';
        } else {
            throw new InvalidArgumentException('Acción inválida.');
        }

        header('Location: ?page=repuestos');
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
            throw new InvalidArgumentException('El repuesto seleccionado no es válido.');
        }

        return $id;
    }
}
