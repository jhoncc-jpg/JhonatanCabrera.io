<?php

/*
 * Proposito: Coordina las solicitudes y acciones de OrdenController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/OrdenRepository.php, /../validators/OrdenValidator.php, /../validators/CsrfValidator.php, /../views/ordenes/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php, app\views\ordenes\index.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../validators/OrdenValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

/**
 * Coordina las solicitudes de Orden: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class OrdenController
{
    /**
     * Atiende la pagina de Orden: prepara datos, procesa la solicitud y carga la vista.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function index(): void
    {
        AuthController::requireAuthenticated();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $validador = new OrdenValidator();
        $error = null;
        try {
            $filtros = $validador->filters($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = ['busqueda' => '', 'estado' => '', 'desde' => null, 'hasta' => null];
        }

        $busqueda = $filtros['busqueda'];
        $estadoFiltro = $filtros['estado'];
        $mensaje = $_SESSION['mensaje_orden'] ?? null;
        unset($_SESSION['mensaje_orden']);
        $modelo = null;
        $catalogoTareas = OrdenCatalogo::catalogoTareas();
        $tarifasTrabajos = [];
        $ordenes = [];
        $vehiculos = [];
        $mecanicos = [];
        $detalle = null;
        $id = 0;

        try {
            $modelo = new OrdenRepository();
            if (in_array($_SESSION['rol'], ['administrador', 'recepcionista'], true)) {
                $tarifasTrabajos = $modelo->tarifasTareas();
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                try {
                    $this->post($modelo, $validador);
                } catch (InvalidArgumentException $exception) {
                    http_response_code(422);
                    $error = $exception->getMessage();
                } catch (PDOException $exception) {
                    error_log('No se pudo guardar la orden: ' . $exception->getMessage());
                    http_response_code(500);
                    $error = 'No se pudo guardar la orden.';
                } catch (RuntimeException $exception) {
                    http_response_code(403);
                    $error = $exception->getMessage();
                }
            }

            $rol = (string) $_SESSION['rol'];
            $esMecanico = $rol === 'mecanico';
            $ordenes = $modelo->listar(
                $busqueda,
                $esMecanico,
                (string) ($_SESSION['nombre'] ?? ''),
                (int) $_SESSION['usuario_id'],
                $estadoFiltro !== '' ? $estadoFiltro : null,
                $filtros['desde'],
                $filtros['hasta']
            );
            $vehiculos = $modelo->vehiculos();
            $mecanicos = $modelo->mecanicos();

            $idSolicitado = $_GET['id'] ?? null;
            if ($idSolicitado !== null && $idSolicitado !== '') {
                $id = $validador->positiveInteger($idSolicitado, 'La orden solicitada no es válida.');
                if ($esMecanico && !$modelo->asignadaA(
                    $id,
                    (string) ($_SESSION['nombre'] ?? ''),
                    (int) $_SESSION['usuario_id']
                )) {
                    http_response_code(403);
                    $error = 'La orden no está asignada a este mecánico.';
                } else {
                    $detalle = $modelo->detalle($id);
                    if ($detalle === null) {
                        http_response_code(404);
                        $error = 'No se encontró la orden solicitada.';
                    }
                }
            }
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el módulo de órdenes: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la operación de órdenes.';
            $ordenes = [];
            $vehiculos = [];
            $mecanicos = [];
            $detalle = null;
        }

        require __DIR__ . '/../views/ordenes/index.php';
    }

    /**
     * Procesa la solicitud POST de una orden: valida CSRF y datos, autoriza la accion e invoca el repositorio correspondiente.
     * Parametro $modelo: repositorio de órdenes recibido para esta operación.
     * Parametro $validador: valor de tipo OrdenValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: consulta la base de datos; lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function post(OrdenRepository $modelo, OrdenValidator $validador): void
    {
        if (!(new CsrfValidator())->isValid($_POST, $_SESSION)) {
            throw new RuntimeException('La solicitud no es válida. Recarga la página.');
        }

        $accion = $validador->text($_POST, 'accion', 40, 'La acción solicitada no es válida.');
        $rol = (string) $_SESSION['rol'];
        if (!in_array($rol, ['administrador', 'recepcionista', 'mecanico'], true)) {
            throw new RuntimeException('No autorizado.');
        }

        if ($accion === 'crear') {
            $this->requireRoles($rol, ['administrador', 'recepcionista']);
            $vehiculoId = $validador->positiveInteger($_POST['vehiculo_id'] ?? null, 'Seleccione un vehículo válido.');
            $mecanicos = $validador->mechanicIds($_POST['mecanico_ids'] ?? null);
            $ingreso = new DateTimeImmutable();
            $retorno = $validador->parseDate(
                $validador->text($_POST, 'fecha_prevista_retorno', 10, 'La fecha prevista no es válida.'),
                'Y-m-d',
                'Fecha prevista de retorno'
            );
            $validador->validateDateRange($ingreso, $retorno);
            $id = $modelo->crear([
                'vehiculo' => $vehiculoId,
                'usuario' => (int) $_SESSION['usuario_id'],
                'ingreso' => $ingreso->format('Y-m-d H:i:s'),
                'retorno' => $retorno?->format('Y-m-d'),
                'diagnostico' => $validador->text($_POST, 'diagnostico', 3000, 'El diagnóstico supera la longitud permitida.'),
                'observaciones' => $validador->text($_POST, 'observaciones', 3000, 'Las observaciones superan la longitud permitida.'),
                'mecanicos' => $mecanicos,
            ]);
            $_SESSION['mensaje_orden'] = 'Orden creada correctamente.';
            header('Location: ?page=ordenes&id=' . $id);
            exit;
        }

        $id = $validador->positiveInteger($_POST['id'] ?? null, 'La orden indicada no es válida.');

        if ($accion === 'editar') {
            $this->requireRoles($rol, ['administrador']);
            $vehiculoId = $validador->positiveInteger($_POST['vehiculo_id'] ?? null, 'Seleccione un vehículo válido.');
            $mecanicos = $validador->mechanicIds($_POST['mecanico_ids'] ?? null);
            $retorno = $validador->parseDate(
                $validador->text($_POST, 'fecha_prevista_retorno', 10, 'La fecha prevista no es válida.'),
                'Y-m-d',
                'Fecha prevista de retorno'
            );
            $orden = $modelo->detalle($id);
            if ($orden === null) {
                throw new InvalidArgumentException('Orden no encontrada.');
            }
            $ingreso = new DateTimeImmutable((string) $orden['orden']['fecha_ingreso']);
            $validador->validateDateRange($ingreso, $retorno);
            $estado = $validador->enumValue(
                $_POST['estado'] ?? null,
                ['registrada', 'diagnostico', 'en_reparacion', 'pendiente_repuesto', 'lista', 'entregada', 'cancelada'],
                'El estado de la orden no es válido.'
            );
            $costo = $validador->number($_POST['costo_final'] ?? '', 'El costo final no es válido.', true);
            $modelo->editar(
                $id,
                $vehiculoId,
                $retorno?->format('Y-m-d'),
                $estado,
                $validador->text($_POST, 'diagnostico', 3000, 'El diagnóstico supera la longitud permitida.'),
                $validador->text($_POST, 'observaciones', 3000, 'Las observaciones superan la longitud permitida.'),
                $costo,
                $mecanicos,
                (int) $_SESSION['usuario_id']
            );
            $_SESSION['mensaje_orden'] = 'Orden editada correctamente.';
        } elseif ($accion === 'eliminar') {
            $this->requireRoles($rol, ['administrador', 'recepcionista']);
            $modelo->eliminar($id, (int) $_SESSION['usuario_id']);
            $_SESSION['mensaje_orden'] = 'Orden eliminada correctamente.';
            header('Location: ?page=ordenes');
            exit;
        } elseif ($accion === 'estado') {
            $estadosPermitidos = in_array($rol, ['administrador', 'recepcionista'], true)
                ? ['registrada', 'diagnostico', 'en_reparacion', 'pendiente_repuesto', 'lista', 'entregada', 'cancelada']
                : ['diagnostico', 'lista'];
            $estado = $validador->enumValue(
                $_POST['estado'] ?? null,
                $estadosPermitidos,
                'No tiene permiso para cambiar a este estado.'
            );
            $this->requireAssignment($modelo, $id, $rol);
            if ($rol === 'mecanico' && $estado === 'lista') {
                $resumen = $modelo->resumenTareas($id);
                if ((int) $resumen['total'] === 0) {
                    throw new InvalidArgumentException('Agregue los trabajos realizados antes de avisar a recepción.');
                }
                if ((int) $resumen['pendientes'] > 0) {
                    throw new InvalidArgumentException('Marque todos los trabajos como realizados antes de terminar la orden.');
                }
                if ((float) $resumen['monto'] <= 0) {
                    throw new InvalidArgumentException('La orden no tiene un monto calculado para enviar a recepción.');
                }
            }
            $observacion = $validador->text($_POST, 'observaciones', 255, 'La observación no puede superar 255 caracteres.');
            $modelo->estado($id, (int) $_SESSION['usuario_id'], $estado, $observacion);
            $_SESSION['mensaje_orden'] = $rol === 'mecanico' && $estado === 'lista'
                ? 'Orden marcada como terminada. Recepción ya puede coordinar la entrega.'
                : 'Estado actualizado.';
        } elseif ($accion === 'actualizar_tecnico') {
            $this->requireRoles($rol, ['mecanico']);
            $this->requireAssignment($modelo, $id, $rol);
            $orden = $modelo->detalle($id);
            if ($orden === null) {
                throw new InvalidArgumentException('Orden no encontrada.');
            }
            $retorno = $validador->parseDate(
                $validador->text($_POST, 'fecha_prevista_retorno', 10, 'La fecha prevista no es válida.'),
                'Y-m-d',
                'Fecha prevista de retorno'
            );
            $ingreso = new DateTimeImmutable((string) $orden['orden']['fecha_ingreso']);
            $validador->validateDateRange($ingreso, $retorno);
            $modelo->actualizarTecnico(
                $id,
                $validador->text($_POST, 'diagnostico', 3000, 'El diagnóstico supera la longitud permitida.'),
                $validador->text($_POST, 'observaciones', 3000, 'Las observaciones superan la longitud permitida.'),
                $retorno?->format('Y-m-d'),
                (int) $_SESSION['usuario_id']
            );
            $_SESSION['mensaje_orden'] = 'Información técnica actualizada y enviada a administración y recepción.';
        } elseif ($accion === 'tareas_catalogo') {
            $this->requireRoles($rol, ['administrador', 'mecanico']);
            $this->requireAssignment($modelo, $id, $rol);
            $catalogo = [];
            foreach (OrdenCatalogo::catalogoTareas() as $tareas) {
                $catalogo = array_merge($catalogo, $tareas);
            }
            $seleccionadas = $validador->catalogTasks($_POST['tareas_catalogo'] ?? null, $catalogo);
            $agregadas = $modelo->agregarTareasCatalogo($id, $seleccionadas);
            $_SESSION['mensaje_orden'] = $agregadas > 0
                ? 'Se agregaron ' . $agregadas . ' trabajo' . ($agregadas === 1 ? '' : 's') . ' pendiente' . ($agregadas === 1 ? '' : 's') . ' a la orden.'
                : 'Los trabajos seleccionados ya estaban registrados en esta orden.';
        } elseif ($accion === 'completar_tareas') {
            $this->requireRoles($rol, ['administrador', 'mecanico']);
            $this->requireAssignment($modelo, $id, $rol);
            $tareas = $validador->selectedIds(
                $_POST['tareas_completadas'] ?? null,
                'Seleccione al menos un trabajo realizado.',
                'La selección de trabajos no es válida.'
            );
            $completadas = $modelo->completarTareas($id, $tareas);
            $_SESSION['mensaje_orden'] = $completadas > 0
                ? 'Se marcaron ' . $completadas . ' trabajo' . ($completadas === 1 ? '' : 's') . ' como completado' . ($completadas === 1 ? '' : 's') . '.'
                : 'Los trabajos seleccionados ya estaban completados.';
        } elseif ($accion === 'precio') {
            $this->requireRoles($rol, ['administrador', 'recepcionista']);
            $precio = $validador->number($_POST['costo_final'] ?? null, 'Ingrese un precio válido, igual o mayor que cero.');
            $modelo->actualizarPrecio($id, $precio);
            $_SESSION['mensaje_orden'] = 'Precio de la orden guardado correctamente.';
        } elseif ($accion === 'tarea') {
            $this->requireRoles($rol, ['administrador', 'mecanico']);
            $this->requireAssignment($modelo, $id, $rol);
            $descripcion = $validador->text($_POST, 'descripcion', 255, 'La descripción del trabajo no puede superar 255 caracteres.');
            if ($descripcion === '') {
                throw new InvalidArgumentException('La descripción del trabajo es obligatoria.');
            }
            $estado = $validador->enumValue(
                $_POST['estado_tarea'] ?? null,
                ['pendiente', 'en_proceso', 'completada'],
                'El estado de la tarea no es válido.'
            );
            $costo = $rol === 'mecanico'
                ? null
                : $validador->number($_POST['costo_tarea'] ?? '', 'El costo de la tarea no es válido.', true);
            $modelo->tarea($id, $descripcion, $estado, $costo);
            $_SESSION['mensaje_orden'] = 'Tarea registrada.';
        } elseif ($accion === 'pago') {
            $this->requireRoles($rol, ['administrador', 'recepcionista']);
            $monto = $validador->number($_POST['monto'] ?? null, 'El monto del pago no es válido.');
            if ($monto <= 0) {
                throw new InvalidArgumentException('El monto del pago debe ser mayor que cero.');
            }
            $metodo = $validador->enumValue(
                $_POST['metodo'] ?? null,
                ['efectivo', 'transferencia', 'otro'],
                'El método de pago no es válido.'
            );
            $modelo->pago(
                $id,
                (int) $_SESSION['usuario_id'],
                $monto,
                $metodo,
                $validador->text($_POST, 'referencia', 100, 'La referencia no puede superar 100 caracteres.'),
                $validador->text($_POST, 'observaciones', 255, 'Las observaciones no pueden superar 255 caracteres.')
            );
            $_SESSION['mensaje_orden'] = 'Pago registrado.';
        } else {
            throw new InvalidArgumentException('Acción inválida.');
        }

        header('Location: ?page=ordenes&id=' . $id);
        exit;
    }

    /**
     * Exige una sesion autenticada y un rol autorizado.
     * Parametro $rol: rol que se consulta o se va a asignar.
     * Parametro $permitidos: valor de tipo array recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function requireRoles(string $rol, array $permitidos): void
    {
        if (!in_array($rol, $permitidos, true)) {
            throw new RuntimeException('No tiene permisos para realizar esta acción.');
        }
    }

    /**
     * Impide que un mecanico consulte o modifique una orden que no tiene asignada.
     * Parametro $modelo: repositorio de órdenes recibido para esta operación.
     * Parametro $id: identificador del registro que se consulta o modifica.
     * Parametro $rol: rol que se consulta o se va a asignar.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function requireAssignment(OrdenRepository $modelo, int $id, string $rol): void
    {
        if (
            $rol === 'mecanico'
            && !$modelo->asignadaA($id, (string) ($_SESSION['nombre'] ?? ''), (int) $_SESSION['usuario_id'])
        ) {
            throw new RuntimeException('La orden no está asignada a este mecánico.');
        }
    }
}
