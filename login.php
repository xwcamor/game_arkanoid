<?php
// session_start(); // Se iniciará en auth.php o en index.php principal

// Si el usuario ya está logueado, quizás redirigir al juego o a index.php
// if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
//     header("location: index.php"); // O la página del juego
//     exit;
// }

$error_login = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] == 'credenciales') {
        $error_login = "Nombre de usuario o contraseña incorrectos.";
    } elseif ($_GET['error'] == 'no_aprobado') {
        $error_login = "Tu cuenta aún no ha sido aprobada.";
    } else {
        $error_login = "Ha ocurrido un error durante el inicio de sesión.";
    }
}
if (isset($_GET['registro']) && $_GET['registro'] == 'exitoso') {
    $mensaje_exito = "¡Registro exitoso! Ahora puedes iniciar sesión.";
}

?>
<div class="formulario-auth">
    <h2>Iniciar Sesión</h2>
    <?php if (!empty($error_login)): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_login); ?></p>
    <?php endif; ?>
    <?php if (isset($mensaje_exito)): ?>
        <p style="color: green; text-align: center;"><?php echo htmlspecialchars($mensaje_exito); ?></p>
    <?php endif; ?>
    <form action="auth.php" method="post">
        <input type="hidden" name="accion" value="login">
        <div>
            <label for="nombre_usuario_login">Nombre de Usuario:</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario_login" required>
        </div>
        <div>
            <label for="contrasena_login">Contraseña:</label>
            <input type="password" name="contrasena" id="contrasena_login" required>
        </div>
        <div>
            <button type="submit">Iniciar Sesión</button>
        </div>
    </form>
    <div class="enlace-formulario">
        <p>¿No tienes una cuenta? <a href="#" onclick="mostrarRegistro()">Regístrate aquí</a></p>
    </div>
</div>

<script>
function mostrarRegistro() {
    document.getElementById('pantallaLogin').style.display = 'none';
    document.getElementById('pantallaRegistro').style.display = 'block';
    // Actualizar URL sin recargar para reflejar el cambio (opcional)
    // history.pushState(null, '', 'index.php?accion=registro'); 
    // O simplemente limpiar los mensajes de error/éxito si los hubiera
    const errorLogin = document.querySelector('.formulario-auth p[style*="color: red"]');
    if(errorLogin) errorLogin.style.display = 'none';
    const exitoRegistro = document.querySelector('.formulario-auth p[style*="color: green"]');
    if(exitoRegistro) exitoRegistro.style.display = 'none';
}
</script> 