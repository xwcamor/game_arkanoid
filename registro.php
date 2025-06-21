<?php
// session_start(); // Se iniciará en auth.php o en index.php principal

$error_registro = '';
if (isset($_GET['error_reg'])) {
    if ($_GET['error_reg'] == 'usuario_existe') {
        $error_registro = "El nombre de usuario ya está en uso.";
    } elseif ($_GET['error_reg'] == 'email_existe') {
        $error_registro = "El correo electrónico ya está registrado.";
    } elseif ($_GET['error_reg'] == 'contrasenas_no_coinciden') {
        $error_registro = "Las contraseñas no coinciden.";
    } else {
        $error_registro = "Ha ocurrido un error durante el registro.";
    }
}
?>
<div class="formulario-auth">
    <h2>Crear Cuenta</h2>
    <?php if (!empty($error_registro)): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_registro); ?></p>
    <?php endif; ?>
    <form action="auth.php" method="post">
        <input type="hidden" name="accion" value="registro">
        <div>
            <label for="nombre_usuario_reg">Nombre de Usuario:</label>
            <input type="text" name="nombre_usuario" id="nombre_usuario_reg" required>
        </div>
        <div>
            <label for="email_reg">Correo Electrónico:</label>
            <input type="email" name="email" id="email_reg" required>
        </div>
        <div>
            <label for="contrasena_reg">Contraseña:</label>
            <input type="password" name="contrasena" id="contrasena_reg" required>
        </div>
        <div>
            <label for="confirmar_contrasena_reg">Confirmar Contraseña:</label>
            <input type="password" name="confirmar_contrasena" id="confirmar_contrasena_reg" required>
        </div>
        <div>
            <button type="submit">Registrarse</button>
        </div>
    </form>
    <div class="enlace-formulario">
        <p>¿Ya tienes una cuenta? <a href="#" onclick="mostrarLogin()">Inicia sesión aquí</a></p>
    </div>
</div>

<script>
function mostrarLogin() {
    document.getElementById('pantallaRegistro').style.display = 'none';
    document.getElementById('pantallaLogin').style.display = 'block';
    // Actualizar URL sin recargar para reflejar el cambio (opcional)
    // history.pushState(null, '', 'index.php?accion=login');
    // O simplemente limpiar los mensajes de error si los hubiera
    const errorRegistro = document.querySelector('.formulario-auth p[style*="color: red"]');
    if(errorRegistro) errorRegistro.style.display = 'none';
}
</script> 