<?php
session_start(); // Iniciar o reanudar la sesión

// Determinar qué vista mostrar (login, registro o juego)
$vista_actual = 'login'; // Vista por defecto
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    $vista_actual = 'juego';
} elseif (isset($_GET['vista'])) {
    if ($_GET['vista'] === 'registro') {
        $vista_actual = 'registro';
    }
    // Si es 'login' o cualquier otro valor, se mantiene 'login' o se redirige desde auth.php
}

// Variables para los mensajes que vienen de auth.php o logout.php
$error_login = '';
$error_registro = '';
$mensaje_exito = '';

if ($vista_actual === 'login') {
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'credenciales') {
            $error_login = "Nombre de usuario o contraseña incorrectos.";
        } elseif ($_GET['error'] == 'campos_vacios') {
            $error_login = "Por favor, completa todos los campos.";
        } elseif ($_GET['error'] == 'desconocido') {
            $error_login = "Ha ocurrido un error inesperado.";
        } else {
            $error_login = "Error durante el inicio de sesión.";
        }
    }
    if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso') {
        $mensaje_exito = "¡Registro exitoso! Ahora puedes iniciar sesión.";
    }
    if (isset($_GET['logout']) && $_GET['logout'] == 'exitoso') {
        $mensaje_exito = "Has cerrado sesión exitosamente.";
    }
} elseif ($vista_actual === 'registro') {
    if (isset($_GET['error_reg'])) {
        if ($_GET['error_reg'] == 'usuario_existe') {
            $error_registro = "El nombre de usuario ya está en uso.";
        } elseif ($_GET['error_reg'] == 'email_existe') {
            $error_registro = "El correo electrónico ya está registrado.";
        } elseif ($_GET['error_reg'] == 'contrasenas_no_coinciden') {
            $error_registro = "Las contraseñas no coinciden.";
        } elseif ($_GET['error_reg'] == 'campos_vacios') {
            $error_registro = "Por favor, completa todos los campos.";
        } elseif ($_GET['error_reg'] == 'email_invalido') {
            $error_registro = "El formato del correo electrónico no es válido.";
        } elseif ($_GET['error_reg'] == 'db_error') {
            $error_registro = "Error al procesar el registro. Intenta de nuevo.";
        } else {
            $error_registro = "Ha ocurrido un error durante el registro.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arkanoid</title>
    <link rel="stylesheet" href="style.css">
    <!-- Si decides usar Bootstrap, el enlace CDN iría aquí -->
    <script>
        // --- Gestión de Sonido de Fondo (Global) ---
        const musicaLogin = new Audio('sonidos/musica_fondo.mp3'); // Específica para login/registro
        musicaLogin.loop = true;
        musicaLogin.volume = 0.3;

        window.musicaMuteadaUsuario = false; // Estado global de muteo
        let musicaLoginDebeSonar = false; // Controla si la música de login DEBERÍA estar sonando
        let musicaLoginRealmenteIniciada = false;

        function actualizarBotonSilencioApariencia() {
            const btnSilenciar = document.getElementById('btnSilenciarMusica');
            if (btnSilenciar) {
                if (window.musicaMuteadaUsuario) {
                    btnSilenciar.innerHTML = '&#x1F507;'; // Muteado
                    btnSilenciar.classList.add('muteado');
                    btnSilenciar.title = "Activar Música";
                } else {
                    btnSilenciar.innerHTML = '&#x1F50A;'; // Sonando
                    btnSilenciar.classList.remove('muteado');
                    btnSilenciar.title = "Desactivar Música";
                }
            }
        }

        function gestionarMusicaLogin(debeSonar) {
            musicaLoginDebeSonar = debeSonar;
            if (musicaLoginDebeSonar && !window.musicaMuteadaUsuario) {
                if (!musicaLoginRealmenteIniciada || musicaLogin.paused) {
                    musicaLogin.play().then(() => {
                        musicaLoginRealmenteIniciada = true;
                    }).catch(error => console.warn("Música de login bloqueada."));
                }
            } else {
                musicaLogin.pause();
            }
            actualizarBotonSilencioApariencia();
        }

        // Funciones para que script.js las llame (si es necesario para el mute)
        // Estas serán llamadas por el botón de silencio para afectar la música del juego.
        window.pausarMusicaDelJuegoExternamente = function() {
            if (typeof pausarMusicaJuego === 'function') {
                pausarMusicaJuego();
            }
        };

        window.reanudarMusicaDelJuegoExternamenteSiDebe = function() {
            if (typeof reanudarMusicaJuegoSiDebeSonarLocalmente === 'function') {
                reanudarMusicaJuegoSiDebeSonarLocalmente();
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            const btnSilenciar = document.getElementById('btnSilenciarMusica');
            if (btnSilenciar) {
                btnSilenciar.addEventListener('click', () => {
                    window.musicaMuteadaUsuario = !window.musicaMuteadaUsuario;
                    actualizarBotonSilencioApariencia();

                    if (window.musicaMuteadaUsuario) {
                        gestionarMusicaLogin(false); // Pausa la música de login
                        window.pausarMusicaDelJuegoExternamente(); // Intenta pausar la del juego
                    } else {
                        // Si se desmutea, reanudar la que corresponda
                        if (musicaLoginDebeSonar) { // Si estábamos en pantalla de login
                            gestionarMusicaLogin(true);
                        } else { // Si no, asumimos que podría estar el juego activo
                            window.reanudarMusicaDelJuegoExternamenteSiDebe();
                        }
                    }
                });
            }
            actualizarBotonSilencioApariencia();

            <?php if ($vista_actual !== 'juego'): ?>
                gestionarMusicaLogin(true); // Iniciar música de login si estamos en esa vista
            <?php else: ?>
                gestionarMusicaLogin(false); // Asegurarse de que la música de login esté detenida si vamos directo al juego
            <?php endif; ?>
        });
    </script>
</head>
<body class="<?php if ($vista_actual !== 'juego') echo 'auth-visible'; ?>">
    <div id="encabezado">
        <button id="btnSilenciarMusica" title="Activar/Desactivar Música">&#x1F50A;</button> <!-- Icono altavoz Unicode -->
        <h1>Arkanoid</h1>
        <?php if ($vista_actual === 'juego'): ?>
            <div id="infoUsuario">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION["nombre_usuario"]); ?>!</span>
                <a href="logout.php" style="color: white; margin-left: 15px;">Cerrar Sesión</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($vista_actual === 'juego'): ?>
        <div id="contenedorJuego">
            <canvas id="lienzoJuego" width="800" height="600"></canvas>
            <div id="controlesNivel">
                <p>Nivel: <span id="nivelActual">1</span></p>
                <p>Puntuación: <span id="puntuacion">0</span></p>
                <p>Vidas: <span id="vidas">3</span></p>
                <div id="botonesJuego">
                    <button id="btnIniciarJuego">Iniciar Juego</button>
                    <button id="btnReiniciarNivel">Usar Vida Extra</button>
                    <button id="btnReiniciarJuego">Reiniciar Juego (Nivel 1)</button>
                </div>
            </div>
        </div>
        <div id="mensajesJuego">
            <!-- Mensajes como "Game Over" o "Nivel Completado" -->
        </div>
        <script src="script.js"></script>
    <?php else: ?>
        <div id="contenedorAuth">
            <?php 
            // Incluir mensajes de error/éxito directamente en los formularios
            // para que se muestren dentro de su contexto visual.
            // login.php y registro.php ya tienen esta lógica con $_GET
            // por lo que las variables $error_login, $mensaje_exito, $error_registro
            // definidas arriba son para referencia o si quisiéramos mostrarlas fuera de los includes.
            ?>
            <div id="pantallaLogin" style="display: <?php echo ($vista_actual === 'login') ? 'block' : 'none'; ?>;">
                <?php include 'login.php'; ?>
            </div>
            <div id="pantallaRegistro" style="display: <?php echo ($vista_actual === 'registro') ? 'block' : 'none'; ?>;">
                <?php include 'registro.php'; ?>
            </div>
        </div>
        <script>
            // Intentar iniciar música si estamos en login/registro
            // Esto podría ser bloqueado por el navegador hasta la interacción del usuario
            gestionarMusicaLogin(true);

            // Script para limpiar parámetros GET (existente)
            if (window.history.replaceState) {
                // Limpia los parámetros GET si existen (error, error_reg, registro, logout)
                const url = new URL(window.location.href);
                let paramsChanged = false;
                if (url.searchParams.has('error') || url.searchParams.has('error_reg') || url.searchParams.has('registro') || url.searchParams.has('logout')) {
                    url.searchParams.delete('error');
                    url.searchParams.delete('error_reg');
                    url.searchParams.delete('registro');
                    url.searchParams.delete('logout');
                    // Mantenemos el parámetro 'vista' si es necesario para la navegación entre login/registro
                    // No es necesario si la navegación se hace solo con JS y no recarga la página con ?vista=
                    paramsChanged = true;
                }
                if (paramsChanged) {
                   // window.history.replaceState({ path: url.href }, '', url.href);
                   // Para simplicidad y evitar problemas si se recarga con F5 y pierde el estado de la vista, 
                   // es mejor que los scripts login.php y registro.php manejen sus propios mensajes.
                   // El PHP de arriba ya captura los mensajes para ser mostrados correctamente una vez.
                }
            }
        </script>
    <?php endif; ?>

</body>
</html> 