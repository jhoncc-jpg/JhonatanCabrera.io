
<?php

/*
 * Proposito: Renderiza la interfaz o el documento de login con datos preparados por su controlador.
 * Dependencias: PHP 8+ y los datos del contexto de ejecucion.
 * Consumidores/dependientes: el controlador de la misma seccion, que entrega los datos preparados.
 */
// Esta plantilla recibe $error desde AuthController y devuelve credenciales
// mediante POST; el controlador valida y autentica al usuario.
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión | AUTOGEST PRO</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css?v=20261116-responsive1" rel="stylesheet">
</head>
<body class="ag-login-body">
<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="termsModalTitle">Términos y condiciones</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>

            <div class="modal-body ag-login-terms-content">
                <p>Al utilizar AUTOGEST PRO, el usuario se compromete a proporcionar información veraz, proteger sus credenciales y usar el sistema únicamente con fines autorizados del taller.</p>
                <p>Las órdenes, clientes, vehículos, inventario, pagos y reportes registrados deben corresponder a operaciones reales. El usuario es responsable de mantener actualizados los datos y de no compartir su cuenta.</p>
                <p>El taller se reserva el derecho de limitar o suspender el acceso ante usos indebidos, intentos de vulneración o incumplimiento de las políticas internas. La información registrada puede ser tratada conforme a la normativa aplicable y utilizada para la gestión operativa del taller.</p>
                <p>El sistema debe utilizarse de acuerdo con las leyes vigentes y con las políticas de privacidad, seguridad y manejo de datos establecidas por Taller Automotriz Almaraz.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="forgotPasswordModalTitle">Recuperar contraseña</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <p>Para restablecer tu contraseña, solicita ayuda a un administrador del taller.</p>
                <p class="mb-0">El administrador debe ingresar a <strong>Gestión de usuarios</strong> y usar la opción <strong>Restablecer contraseña</strong> de tu cuenta.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Entendido</button>
            </div>
        </div>
    </div>
</div>

<main class="ag-login-shell">
    <section class="ag-login-visual" aria-label="Taller automotriz">
        <div class="ag-login-visual-content">
            <p>Calidad y servicio que marcan la diferencia</p>
            <span>Taller Automotriz Almaraz</span>
        </div>
    </section>

    <section class="ag-login-panel">
        <div class="ag-login-card" role="form" aria-labelledby="login-title">
            <div class="ag-login-brand" aria-label="AUTOGEST PRO">
                <span class="ag-login-brand-name">AUTOGEST</span>
                <span class="ag-login-brand-pro">PRO</span>
            </div>

            <h1 id="login-title">Iniciar sesión</h1>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <!-- Los campos name=... son las claves que lee AuthController en $_POST. -->
            <form method="POST" action="?page=login">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="ag-login-field">
                    <label for="usuario">Usuario</label>
                    <input
                        type="text"
                        id="usuario"
                        name="usuario"
                        maxlength="50"
                        autocomplete="username"
                        placeholder="Ingresa tu usuario"
                        required
                    >
                </div>

                <div class="ag-login-field">
                    <label for="password">Contraseña</label>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="********"
                        required
                    >
                </div>

                <div class="ag-login-options">
                    <label class="ag-login-check">
                        <input
                            type="checkbox"
                            id="recordarme"
                            name="recordarme"
                            value="1"
                        >
                        <span></span>
                        Recordarme
                    </label>
                    <button type="button" class="ag-login-terms-link p-0 border-0 bg-transparent" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">¿Olvidaste tu contraseña?</button>
                </div>

                <button type="submit" class="ag-login-submit">
                    Iniciar sesión
                </button>

                <div class="ag-login-terms">
                    Al continuar, puedes consultar nuestros
                    <button
                        type="button"
                        class="ag-login-terms-link"
                        data-bs-toggle="modal"
                        data-bs-target="#termsModal"
                    >Términos y condiciones</button>
                </div>
            </form>

        </div>
    </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
