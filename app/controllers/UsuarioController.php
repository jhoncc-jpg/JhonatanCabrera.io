<?php

/*
 * Proposito: Coordina las solicitudes y acciones de UsuarioController entre la entrada web, los validadores, los repositorios y las vistas.
 * Dependencias: /AuthController.php, /../repositories/UsuarioRepository.php, /../validators/UsuarioValidator.php, /../validators/CsrfValidator.php, /../views/usuarios/index.php.
 * Consumidores/dependientes: app\controllers\RouterController.php.
 */

require_once __DIR__ . '/AuthController.php';
require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../validators/UsuarioValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

/**
 * Coordina las solicitudes de Usuario: aplica autorizacion, valida entradas y comunica el repositorio con su vista.
 * Efectos secundarios: define la unidad de trabajo; los efectos concretos se describen en cada metodo.
 */
// CUIDADO AL MODIFICAR: esta clase participa en operaciones sensibles; conserve los contratos con los modulos dependientes.
class UsuarioController
{
    private UsuarioRepository $usuarioRepository;

    /**
     * Atiende la pagina de Usuario: prepara datos, procesa la solicitud y carga la vista.
     * Parametros: no recibe argumentos.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion; puede producir salida o cargar una vista.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    public function index(): void
    {
        AuthController::requireRoles(['administrador']);
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $mensaje = $_SESSION['mensaje_usuario'] ?? null;
        unset($_SESSION['mensaje_usuario']);
        $error = null;
        $usuarios = [];
        $roles = [];
        $repositorio = null;
        $validador = new UsuarioValidator();
        try {
            $filtros = $validador->filtros($_GET);
        } catch (InvalidArgumentException $exception) {
            http_response_code(400);
            $error = $exception->getMessage();
            $filtros = ['busqueda' => '', 'estado' => 'todos', 'activo' => null];
        }

        try {
            $this->usuarioRepository = new UsuarioRepository();
            $repositorio = $this->usuarioRepository;
            $roles = $this->usuarioRepository->obtenerRoles();

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
                    } catch (PDOException $exception) {
                        error_log('No se pudo guardar el usuario: ' . $exception->getMessage());
                        http_response_code(500);
                        $error = 'No se pudo guardar el usuario.';
                    } catch (RuntimeException $exception) {
                        error_log('Ocurrió un error al guardar el usuario: ' . $exception->getMessage());
                        http_response_code(500);
                        $error = 'No se pudo guardar el usuario.';
                    }
                }
            }

            $usuarios = $repositorio->listar($filtros['busqueda'], $filtros['activo']);
        } catch (PDOException $exception) {
            error_log('No se pudo consultar el módulo de usuarios: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la operación de usuarios.';
            $usuarios = [];
        } catch (RuntimeException $exception) {
            error_log('Ocurrió un error en el módulo de usuarios: ' . $exception->getMessage());
            http_response_code(500);
            $error = 'No se pudo completar la operación de usuarios.';
            $usuarios = [];
        }

        require __DIR__ . '/../views/usuarios/index.php';
    }

    /**
     * Valida y procesa el formulario de Usuario, invocando la operacion del repositorio.
     * Parametro $repositorio: repositorio utilizado para persistir usuarios y roles.
     * Parametro $validador: valor de tipo UsuarioValidator recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function procesarFormulario(UsuarioRepository $repositorio, UsuarioValidator $validador): void
    {
        $accion = $_POST['accion'] ?? null;
        if (!is_string($accion)) {
            throw new InvalidArgumentException('La acción solicitada no es válida.');
        }

        if ($accion === 'crear') {
            $datos = $validador->nuevo($_POST);
            $nombreRol = $repositorio->nombreRol($datos['rol_id']);
            if ($nombreRol === null) {
                throw new InvalidArgumentException('Seleccione un rol válido.');
            }
            if ($repositorio->existeUsuario($datos['usuario'])) {
                throw new InvalidArgumentException('Ya existe una cuenta con ese nombre de usuario.');
            }

            $repositorio->crear(
                $datos['rol_id'],
                $nombreRol,
                $datos['nombre'],
                $datos['usuario'],
                password_hash($datos['password'], PASSWORD_BCRYPT)
            );
            $this->redirigir('Usuario creado correctamente.');
        }

        $id = $validador->id($_POST['id'] ?? null, 'Usuario inválido.');
        $esUsuarioActual = $id === (int) $_SESSION['usuario_id'];

        if ($accion === 'desactivar' || $accion === 'activar') {
            $activo = $accion === 'activar';
            if (!$activo && $esUsuarioActual) {
                throw new InvalidArgumentException('No puedes desactivar tu propia cuenta.');
            }
            if (!$repositorio->cambiarEstado($id, $activo)) {
                throw new InvalidArgumentException('No se encontró el usuario seleccionado.');
            }
        } elseif ($accion === 'rol') {
            $rolId = $validador->id($_POST['rol_id'] ?? null, 'Seleccione un rol válido.');
            $nombreRol = $repositorio->nombreRol($rolId);
            if ($nombreRol === null) {
                throw new InvalidArgumentException('Seleccione un rol válido.');
            }
            if ($esUsuarioActual && $nombreRol !== 'administrador') {
                throw new InvalidArgumentException('No puedes quitarte el rol de administrador.');
            }
            if (!$repositorio->cambiarRol($id, $rolId, $nombreRol)) {
                throw new InvalidArgumentException('No se encontró el usuario seleccionado.');
            }
        } elseif ($accion === 'restablecer') {
            $password = $validador->password($_POST);
            if (!$repositorio->restablecerPassword($id, password_hash($password, PASSWORD_BCRYPT))) {
                throw new InvalidArgumentException('No se encontró el usuario seleccionado.');
            }
        } else {
            throw new InvalidArgumentException('Acción inválida.');
        }

        $this->redirigir('Usuario actualizado correctamente.');
    }

    /**
     * Construye y envia la redireccion de respuesta con el mensaje correspondiente.
     * Parametro $mensaje: valor de tipo string recibido para esta operacion.
     * Devuelve: no devuelve valor (void).
     * Efectos secundarios: lee o modifica datos de la solicitud o sesion; puede modificar la respuesta HTTP o el estado de sesion.
     */
    // CUIDADO AL MODIFICAR: mantenga la validacion y el contrato de datos con los modulos que dependen de esta operacion.
    private function redirigir(string $mensaje): void
    {
        $_SESSION['mensaje_usuario'] = $mensaje;
        header('Location: index.php?page=usuarios');
        exit;
    }
}
