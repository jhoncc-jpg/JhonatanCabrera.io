
<?php

/*
 * Propósito: coordinar las operaciones de autenticación y cierre de sesión
 * del sistema AutoGest. Controla la creación y validación de credenciales,
 * establece los datos disponibles en $_SESSION y protege rutas por sesión y rol.
 *
 * Dependencias: importa el repositorio UsuarioRepository para buscar la cuenta,
 * AuthValidator para validar el formato básico de las credenciales y
 * CsrfValidator para comprobar que las solicitudes POST sean legítimas.
 *
 * Consumen este archivo: RouterController lo usa para iniciar sesión, cerrar
 * sesión y exigir autenticación o roles; el resto de controladores pueden
 * reutilizar sus métodos estáticos de autorización.
 */
require_once __DIR__ . '/../repositories/UsuarioRepository.php';
require_once __DIR__ . '/../validators/AuthValidator.php';
require_once __DIR__ . '/../validators/CsrfValidator.php';

/**
 * Coordina el inicio y cierre de sesión y protege las rutas según la identidad
 * y el rol guardados en la sesión.
 * Parámetros y devolución: la clase no recibe ni devuelve valores al instanciarse.
 * Efectos secundarios: sus métodos pueden consultar usuarios, modificar la sesión
 * y responder con redirecciones o errores HTTP.
 */
// CUIDADO AL MODIFICAR: esta clase define la frontera común de autenticación
// y autorización utilizada por las rutas administrativas y operativas.
class AuthController
{
    // CUIDADO AL MODIFICAR: alterar o ampliar estos roles requiere mantener
    // sincronizada la autorización del Router y las reglas de la interfaz.
    private const ROLES_VALIDOS = [
        'administrador',
        'recepcionista',
        'mecanico',
    ];

    /**
     * Comprueba si una sesión contiene una identidad y un rol válidos.
     * Parámetros: $session es el arreglo de sesión que se desea evaluar.
     * Devuelve: true cuando existe un identificador positivo y un rol permitido.
     * Efectos secundarios: ninguno, porque solamente realiza validaciones.
     */
    public static function isAuthenticated(array $session): bool
    {
        $id = $session['usuario_id'] ?? null;
        $rol = $session['rol'] ?? null;

        return (is_int($id) || (is_string($id) && ctype_digit($id)))
            && (int) $id > 0
            && self::isValidRole($rol);
    }

    /**
     * Verifica si un valor corresponde a uno de los roles admitidos.
     * Parámetros: $rol es el valor recibido, que puede ser de cualquier tipo.
     * Devuelve: true únicamente si $rol es una cadena perteneciente a ROLES_VALIDOS.
     * Efectos secundarios: ninguno.
     */
    public static function isValidRole(mixed $rol): bool
    {
        return is_string($rol) && in_array($rol, self::ROLES_VALIDOS, true);
    }

    /**
     * Exige una sesión autenticada antes de permitir el acceso a una ruta.
     * Parámetros: no recibe parámetros y consulta $_SESSION internamente.
     * Devuelve: no devuelve valor; finaliza la ejecución si la autorización falla.
     * Efectos secundarios: limpia datos de identidad en sesión, redirige al login
     * y detiene el flujo mediante exit.
     */
    public static function requireAuthenticated(): void
    {
        if (!self::isAuthenticated($_SESSION ?? [])) {
            if (isset($_SESSION) && is_array($_SESSION)) {
                unset($_SESSION['usuario_id'], $_SESSION['nombre'], $_SESSION['rol']);
            }
            header('Location: ?page=login');
            exit;
        }
    }

    /**
     * Exige autenticación y comprueba que el usuario tenga uno de los roles dados.
     * Parámetros: $roles contiene la lista blanca de roles permitidos para la ruta.
     * Devuelve: no devuelve valor; devuelve el flujo normalmente si la autorización pasa.
     * Efectos secundarios: puede responder con HTTP 403 y detener la ejecución.
     */
    public static function requireRoles(array $roles): void
    {
        self::requireAuthenticated();

        if (!in_array($_SESSION['rol'], $roles, true)) {
            http_response_code(403);
            echo 'Acceso denegado.';
            exit;
        }
    }

    /**
     * Atiende el inicio de sesión mediante GET o el envío del formulario por POST.
     * Parámetros: no recibe argumentos; usa $_SERVER, $_POST y $_SESSION.
     * Devuelve: no devuelve valor; carga una vista o redirige al área correspondiente.
     * Efectos secundarios: puede consultar la base de datos, regenerar la sesión,
     * crear cookies, almacenar credenciales de identidad y modificar variables de sesión.
     */
    public function login(): void
    {
        if (self::isAuthenticated($_SESSION ?? [])) {
            $destino = $_SESSION['rol'] === 'mecanico' ? 'ordenes' : 'home';
            header('Location: ?page=' . $destino);
            exit;
        }

        // CUIDADO AL MODIFICAR: el token debe existir antes de mostrar el formulario,
        // de modo que la vista y el controlador validen la misma operación CSRF.
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $error = null;

        // GET muestra el formulario; POST procesa los datos enviados por este.
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // El control CSRF evita que una solicitud POST sea enviada desde fuera
            // de la interfaz: una validación fallida no debe continuar al repositorio.
            if (!(new CsrfValidator())->isValid($_POST, $_SESSION)) {
                http_response_code(403);
                $error = 'La solicitud no es válida. Recarga la página.';
            } else {
                try {
                    [$nombreUsuario, $password] = (new AuthValidator())->credentials($_POST);
                } catch (InvalidArgumentException $exception) {
                    $error = $exception->getMessage();
                }

                if ($error === null) {
                    // CUIDADO AL MODIFICAR: el usuario debe existir, estar activo,
                    // tener un rol admitido y coincidir con el hash almacenado.
                    $repositorio = new UsuarioRepository();
                    $usuario = $repositorio->buscarPorUsuario($nombreUsuario);

                    if (
                        $usuario &&
                        $usuario->activo &&
                        self::isValidRole($usuario->rol) &&
                        password_verify($password, $usuario->passwordHash)
                    ) {
                        // La regeneración reemplaza el identificador de sesión previo
                        // para reducir el riesgo de fijación de sesión.
                        session_regenerate_id(true);

                        // CUIDADO AL MODIFICAR: estas claves son consultadas por
                        // RouterController y por los controladores que autorizan rutas.
                        $_SESSION['usuario_id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['rol'] = $usuario->rol;
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        if (isset($_POST['recordarme']) && $_POST['recordarme'] === '1') {
                            // "Recordarme" prolonga la cookie de sesión; no cambia la
                            // validez de la cuenta ni el comportamiento de la autorización.
                            $duracion = 30 * 24 * 60 * 60;
                            $params = session_get_cookie_params();
                            setcookie(
                                session_name(),
                                session_id(),
                                [
                                    'expires' => time() + $duracion,
                                    'path' => $params['path'],
                                    'domain' => $params['domain'],
                                    'secure' => $params['secure'],
                                    'httponly' => $params['httponly'],
                                    'samesite' => $params['samesite'] ?? 'Lax'
                                ]
                            );
                        }

                        header('Location: ' . ($usuario->rol === 'mecanico' ? '?page=ordenes' : '?page=home'));
                        exit;
                    }

                    $error = 'Usuario o contraseña incorrectos.';
                }
            }
        }

        // La vista recibe el resultado $error y renderiza el formulario; no realiza
        // consultas ni modifica los datos, por lo que mantener su separación ayuda
        // a preservar una responsabilidad clara del controlador.
        require __DIR__ . '/../views/auth/login.php';
    }

    /**
     * Cierra una sesión autenticada después de validar el método y el token CSRF.
     * Parámetros: no recibe argumentos; usa $_SERVER, $_POST y $_SESSION.
     * Devuelve: no devuelve valor y redirige al formulario de inicio de sesión.
     * Efectos secundarios: vacía $_SESSION, elimina la cookie de sesión,
     * destruye la sesión del servidor y puede enviar respuestas HTTP de error.
     */
    public function logout(): void
    {
        // CUIDADO AL MODIFICAR: rechazar cualquier método distinto de POST evita
        // cierres accidentales provocados por enlaces o solicitudes externas.
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo 'Método no permitido.';
            return;
        }

        if (!(new CsrfValidator())->isValid($_POST, $_SESSION)) {
            http_response_code(403);
            echo 'Solicitud no válida.';
            return;
        }

        $_SESSION = [];

        // Elimina también la cookie del navegador antes de destruir la sesión.
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                [
                    'expires' => time() - 42000,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => 'Lax'
                ]
            );
        }

        session_destroy();

        header('Location: ?page=login');
        exit;
    }
}
