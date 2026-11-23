
<?php

/*
 * Proposito: Crea cuentas iniciales desde la terminal y solicita las contrasenas de forma interactiva.
 * Dependencias: /../app/repositories/UsuarioRepository.php.
 * Consumidores/dependientes: el operador que ejecuta el script desde CLI despues de importar el esquema.
 */

// Este script es para terminal, no para el navegador, porque solicita contraseñas.
if (PHP_SAPI !== 'cli') {
    exit("Ejecuta este archivo desde la terminal.\n");
}

require_once __DIR__ . '/../app/repositories/UsuarioRepository.php';

try {
    $usuarios = new UsuarioRepository();

    // Cuentas iniciales que se crearán si todavía no existen en la base de datos.
    $cuentas = [
        ['nombre' => 'Jhonatan', 'usuario' => 'jhonatan', 'rol' => 'administrador'],
        ['nombre' => 'Juan', 'usuario' => 'juan', 'rol' => 'recepcionista'],
        ['nombre' => 'Rene', 'usuario' => 'rene', 'rol' => 'mecanico'],
    ];

    foreach ($cuentas as $cuenta) {
        if ($usuarios->existeUsuario($cuenta['usuario'])) {
            echo "El usuario {$cuenta['usuario']} ya existe; no se modificó.\n";
            continue;
        }

        $roles = $usuarios->roles();
        $rol = null;
        foreach ($roles as $rolDisponible) {
            if ($rolDisponible['nombre'] === $cuenta['rol']) {
                $rol = $rolDisponible;
                break;
            }
        }
        if ($rol === null) {
            echo "No existe el rol {$cuenta['rol']}. No se creó {$cuenta['usuario']}.\n";
            continue;
        }

        echo "\nCrear cuenta: {$cuenta['nombre']} ({$cuenta['rol']})\n";
        $password = readline('Escribe una contraseña de al menos 10 caracteres: ');

        if (strlen($password) < 10) {
            echo "Contraseña demasiado corta. No se creó esta cuenta.\n";
            continue;
        }

        // Solo se guarda el hash de la contraseña, nunca el texto recibido.
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $usuarios->crear(
            (int) $rol['id'],
            (string) $rol['nombre'],
            $cuenta['nombre'],
            $cuenta['usuario'],
            $hash
        );

        echo "Cuenta {$cuenta['usuario']} creada correctamente.\n";
    }

    echo "\nProceso finalizado.\n";

} catch (PDOException $e) {
    echo "Error de base de datos. Verifica la conexión y las tablas.\n";
}
