<?php

/*
 * Proposito: Coordina las solicitudes y acciones de VehiculoController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/VehiculoRepository.php, /../repositories/OrdenRepository.php, /../validators/VehiculoValidator.php, /../validators/CsrfValidator.php, /../views/vehiculos/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php, app\views\vehiculos\index.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/VehiculoRepository.php';
require_once __DIR__ . '/../repositories/OrdenRepository.php';
require_once __DIR__ . '/../validators/VehiculoValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

// Gestiona vehículos, sus propietarios, tarifas sugeridas e imágenes.
/**
 * Coordina las solicitudes de Vehiculo: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class VehiculoController
{
    /**
     * Atiende la pagina de Vehiculo: prepara datos, procesa la solicitud y carga la vista.
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

        $mensaje = $_SESSION['mensaje_vehiculo'] ?? null;
        unset($_SESSION['mensaje_vehiculo']);
        $error = null;
        $catalogoTareas = OrdenCatalogo::catalogoTareas();
        $validador = new VehiculoValidator();
        try {
            $filtros = $validador->filters($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = ['busqueda' => '', 'estado' => null, 'desde' => null, 'hasta' => null];
        }
        $busqueda = $filtros['busqueda'];
        $incluirPrecios = in_array($_SESSION['rol'], ['administrador', 'recepcionista'], true);

        try {
            $repositorio = new VehiculoRepository();
            $modeloOrden = new OrdenRepository();
            $tarifasTrabajos = $modeloOrden->tarifasTareas();
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                if (!(new CsrfValidator())->isValid($_POST, $_SESSION)) {
                    http_response_code(403);
                    $error = 'La solicitud no es válida. Recarga la página.';
                } else {
                    try {
                        if (($_POST['accion'] ?? '') === 'actualizar_tarifas') {
                            $this->procesarTarifas($modeloOrden, $validador);
                        } else {
                            $this->procesarFormulario($repositorio, $validador);
                        }
                    } catch (InvalidArgumentException $exception) {
                        http_response_code(422);
                        $error = $exception->getMessage();
                    }
                }
            }

            $vehiculos = $repositorio->listar(
                $busqueda,
                $incluirPrecios,
                $filtros['estado'],
                $filtros['desde'],
                $filtros['hasta']
            );
            $propietarios = $repositorio->listarPropietarios();
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el módulo de vehículos: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la operación de vehículos.';
            $vehiculos = [];
            $propietarios = [];
            $tarifasTrabajos = [];
        } catch (RuntimeException $exception) {
            error_log('No se pudo guardar el vehículo: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo guardar el vehículo.';
            $vehiculos = [];
            $propietarios = [];
            $tarifasTrabajos = [];
        }

        require __DIR__ . '/../views/vehiculos/index.php';
    }

    /**
     * Valida y persiste las tarifas de tareas recibidas.
     * Parametro $modelo: repositorio utilizado para consultar y actualizar tarifas.
     * Parametro $validador: valor de tipo VehiculoValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarTarifas(OrdenRepository $modelo, VehiculoValidator $validador): void
    {
        AuthController::requireRoles(['administrador']);
        $precios = $validador->prices(
            $_POST,
            OrdenCatalogo::preciosReferencialesTareas()
        );

        $modelo->guardarTarifasTareas($precios);
        $_SESSION['mensaje_vehiculo'] = 'Tarifas de trabajos sugeridos actualizadas correctamente.';
        header('Location: ?page=vehiculos#tarifas-trabajos');
        exit;
    }

    /**
     * Valida y procesa el formulario de Vehiculo, invocando la operacion del repositorio.
     * Parametro $repositorio: repositorio utilizado para operar con vehículos e imágenes.
     * Parametro $validador: valor de tipo VehiculoValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede leer o modificar archivos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarFormulario(VehiculoRepository $repositorio, VehiculoValidator $validador): void
    {
        $accion = $_POST['accion'] ?? '';
        if (!is_string($accion)) {
            throw new InvalidArgumentException('Acción inválida.');
        }

        if ($accion === 'eliminar') {
            // Primero se intenta eliminar el registro; si hay órdenes relacionadas,
            // la clave foránea impide borrarlo y se muestra un mensaje comprensible.
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                throw new InvalidArgumentException('Vehículo inválido');
            }

            try {
                $imagenes = $repositorio->imagenes((int) $id);
                $repositorio->eliminar((int) $id);
                foreach ($imagenes as $imagen) {
                    $archivo = __DIR__ . '/../../public/' . str_replace('/', DIRECTORY_SEPARATOR, (string) $imagen['ruta']);
                    if (is_file($archivo) && !unlink($archivo)) {
                        error_log('No se pudo borrar el archivo de imagen: ' . $archivo);
                    }
                }
                $_SESSION['mensaje_vehiculo'] = 'Vehículo eliminado correctamente.';
            } catch (PDOException $e) {
                throw new InvalidArgumentException('No se puede eliminar un vehículo con órdenes registradas.');
            }

            header('Location: ?page=vehiculos');
            exit;
        }

        // El repositorio busca el propietario o crea su ficha si aún no existe.
        $datosValidados = $validador->validate($_POST, $accion);
        $propietario = $datosValidados['propietario'];
        $telefonoPropietario = $datosValidados['telefono_propietario'];
        $direccionPropietario = $datosValidados['direccion_propietario'];
        $datos = $datosValidados;
        unset($datos['propietario'], $datos['telefono_propietario'], $datos['direccion_propietario']);
        if ($accion === 'crear') {
            // Primero se obtiene el cliente, luego se crea el vehículo y finalmente
            // se guardan las imágenes asociadas.
            if ($repositorio->placaExiste($datos['placa'])) {
                throw new InvalidArgumentException('Ya existe un vehículo con esa placa.');
            }
            $datos['cliente_id'] = $this->resolverPropietario(
                $repositorio,
                $propietario,
                $telefonoPropietario,
                $direccionPropietario
            );
            $vehiculoId = $repositorio->crear($datos);
            $this->guardarImagenes($repositorio, $vehiculoId);
            $_SESSION['mensaje_vehiculo'] = 'Vehículo registrado correctamente.';
        } elseif ($accion === 'editar') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) throw new InvalidArgumentException('Vehículo inválido');
            if ($repositorio->placaExiste($datos['placa'], (int) $id)) {
                throw new InvalidArgumentException('Ya existe otro vehículo con esa placa.');
            }
            $datos['cliente_id'] = $this->resolverPropietario(
                $repositorio,
                $propietario,
                $telefonoPropietario,
                $direccionPropietario
            );
            $repositorio->actualizar((int) $id, $datos);
            $this->guardarImagenes($repositorio, (int) $id);
            $_SESSION['mensaje_vehiculo'] = 'Vehículo actualizado correctamente.';
        }

        header('Location: ?page=vehiculos');
        exit;
    }

    /**
     * Resuelve el identificador del propietario con los datos proporcionados.
     * Parametro $repositorio: repositorio utilizado para operar con vehículos.
     * Parametro $nombre: nombre de la persona o entidad relacionada.
     * Parametro $telefono: telefono de contacto de la persona relacionada.
     * Parametro $direccion: direccion de contacto de la persona relacionada.
     * Devuelve: valor de tipo int, segun el resultado de la operacion.
     * Efectos secundarios: ninguno fuera de los valores devueltos; opera sobre los datos recibidos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function resolverPropietario(
        VehiculoRepository $repositorio,
        string $nombre,
        string $telefono,
        string $direccion
    ): int {
        $clienteId = $repositorio->buscarPropietario($nombre);
        if ($clienteId !== null) {
            return $clienteId;
        }
        if (trim($telefono) === '' || trim($direccion) === '') {
            throw new InvalidArgumentException('Para registrar un propietario nuevo, ingresa su teléfono y dirección.');
        }

        return $repositorio->crearPropietario($nombre, $telefono, $direccion);
    }

    /**
     * Procesa y registra imagenes validas adjuntas al vehiculo.
     * Parametro $repositorio: repositorio utilizado para operar con imágenes.
     * Parametro $vehiculoId: valor de tipo int recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede leer o modificar archivos.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function guardarImagenes(VehiculoRepository $repositorio, int $vehiculoId): void
    {
        // Los archivos llegan desde un formulario multipart/form-data en $_FILES.
        if (!isset($_FILES['imagenes']) || !is_array($_FILES['imagenes']['name'])) {
            return;
        }

        $archivos = [];
        foreach ($_FILES['imagenes']['name'] as $indice => $nombre) {
            if (!isset($_FILES['imagenes']['error'][$indice]) || (int) $_FILES['imagenes']['error'][$indice] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $archivos[] = $indice;
        }

        if (count($archivos) === 0) {
            return;
        }
        if (count($archivos) > 8) {
            throw new InvalidArgumentException('Solo puedes seleccionar hasta 8 imágenes.');
        }
        if ($repositorio->contarImagenes($vehiculoId) + count($archivos) > 8) {
            throw new InvalidArgumentException('Este vehículo ya alcanzó el máximo de 8 imágenes.');
        }

        $directorio = __DIR__ . '/../../public/uploads/vehiculos';
        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear la carpeta de imágenes.');
        }

        foreach ($archivos as $indice) {
            // No se confía en la extensión enviada por el navegador:
            // el tipo real se detecta leyendo el contenido de la imagen.
            $error = (int) $_FILES['imagenes']['error'][$indice];
            if ($error !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Una imagen no se pudo cargar. Inténtalo nuevamente.');
            }

            $temporal = $_FILES['imagenes']['tmp_name'][$indice];
            $nombreOriginal = basename((string) $_FILES['imagenes']['name'][$indice]);
            $tamano = (int) $_FILES['imagenes']['size'][$indice];
            if ($tamano <= 0 || $tamano > 10 * 1024 * 1024) {
                throw new InvalidArgumentException('Cada imagen debe pesar entre 1 byte y 10 MB.');
            }

            $informacion = @getimagesize($temporal);
            if ($informacion === false) {
                throw new InvalidArgumentException('Uno de los archivos no es una imagen válida.');
            }

            $tiposPermitidos = [
                IMAGETYPE_JPEG => 'jpg',
                IMAGETYPE_PNG => 'png',
                IMAGETYPE_WEBP => 'webp',
                IMAGETYPE_GIF => 'gif',
            ];
            $tipo = (int) ($informacion[2] ?? 0);
            if (!isset($tiposPermitidos[$tipo])) {
                throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG, WEBP o GIF.');
            }

            $extension = $tiposPermitidos[$tipo];
            $nombreSeguro = 'vehiculo_' . $vehiculoId . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
            $destino = $directorio . '/' . $nombreSeguro;
            if (!move_uploaded_file($temporal, $destino)) {
                throw new RuntimeException('No se pudo guardar una de las imágenes.');
            }

            $rutaRelativa = 'uploads/vehiculos/' . $nombreSeguro;
            if (!$repositorio->guardarImagen($vehiculoId, $nombreOriginal, $rutaRelativa)) {
                @unlink($destino);
                throw new RuntimeException('No se pudo registrar una de las imágenes.');
            }
        }
    }
}
