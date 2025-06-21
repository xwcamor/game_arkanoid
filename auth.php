<?php
session_start();
require_once 'config_db.php'; // Contiene la conexión $conn

// Función para redirigir de forma segura
function redirigir($url) {
    header("Location: " . $url);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['accion'])) {
    $accion = $_POST['accion'];

    if ($accion == "registro") {
        // Proceso de registro
        $nombre_usuario = trim($_POST['nombre_usuario']);
        $email = trim($_POST['email']);
        $contrasena = $_POST['contrasena'];
        $confirmar_contrasena = $_POST['confirmar_contrasena'];

        // Validaciones básicas
        if (empty($nombre_usuario) || empty($email) || empty($contrasena) || empty($confirmar_contrasena)) {
            redirigir("index.php?vista=registro&error_reg=campos_vacios"); // Deberíamos tener un estado inicial en index
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            redirigir("index.php?vista=registro&error_reg=email_invalido");
        }
        if ($contrasena !== $confirmar_contrasena) {
            redirigir("index.php?vista=registro&error_reg=contrasenas_no_coinciden");
        }

        // Verificar si el nombre de usuario ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ?");
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirigir("index.php?vista=registro&error_reg=usuario_existe");
        }
        $stmt->close();

        // Verificar si el email ya existe
        $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $stmt->close();
            redirigir("index.php?vista=registro&error_reg=email_existe");
        }
        $stmt->close();

        // Hashear la contraseña
        $contrasena_hashed = password_hash($contrasena, PASSWORD_DEFAULT);

        // Insertar nuevo usuario
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre_usuario, email, contrasena) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre_usuario, $email, $contrasena_hashed);

        if ($stmt->execute()) {
            $stmt->close();
            // $_SESSION["loggedin"] = true; // Opcional: loguear directamente o requerir login
            // $_SESSION["id_usuario"] = $conn->insert_id; // El ID del nuevo usuario
            // $_SESSION["nombre_usuario"] = $nombre_usuario;
            redirigir("index.php?vista=login&registro=exitoso");
        } else {
            $stmt->close();
            redirigir("index.php?vista=registro&error_reg=db_error");
        }

    } elseif ($accion == "login") {
        // Proceso de inicio de sesión
        $nombre_usuario = trim($_POST['nombre_usuario']);
        $contrasena = $_POST['contrasena'];

        if (empty($nombre_usuario) || empty($contrasena)) {
            redirigir("index.php?vista=login&error=campos_vacios");
        }

        $stmt = $conn->prepare("SELECT id, nombre_usuario, contrasena FROM usuarios WHERE nombre_usuario = ?");
        $stmt->bind_param("s", $nombre_usuario);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($id_usuario, $nombre_usuario_db, $contrasena_hashed_db);
            if ($stmt->fetch()) {
                if (password_verify($contrasena, $contrasena_hashed_db)) {
                    // Contraseña correcta, iniciar sesión
                    $_SESSION["loggedin"] = true;
                    $_SESSION["id_usuario"] = $id_usuario;
                    $_SESSION["nombre_usuario"] = $nombre_usuario_db;
                    $stmt->close();
                    redirigir("index.php"); // Redirigir a la página principal del juego
                } else {
                    // Contraseña incorrecta
                    $stmt->close();
                    redirigir("index.php?vista=login&error=credenciales");
                }
            } else {
                 $stmt->close();
                 redirigir("index.php?vista=login&error=desconocido"); // Error al obtener datos
            }
        } else {
            // Usuario no encontrado
            $stmt->close();
            redirigir("index.php?vista=login&error=credenciales");
        }
    } else {
        // Acción no reconocida
        redirigir("index.php");
    }
} else {
    // Si no es POST o no hay acción, redirigir a la página principal (o de login)
    redirigir("index.php?vista=login");
}

// Cerrar la conexión a la base de datos (si $conn está definida y es un objeto mysqli)
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?> 