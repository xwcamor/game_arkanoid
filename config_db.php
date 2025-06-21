<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost'); // Servidor de la base de datos (usualmente localhost)
define('DB_USER', 'root');    // Usuario de la base de datos (cambia esto)
define('DB_PASS', '1234'); // Contraseña del usuario (cambia esto si tienes una)
define('DB_NAME', 'juego_arkanoid_db'); // Nombre de la base de datos

// Crear conexión
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión inicial fallida: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
if (!$conn->query($sql_create_db)) {
    die("Error al crear la base de datos: " . $conn->error);
}

// Seleccionar la base de datos
$conn->select_db(DB_NAME);

// Verificar conexión después de seleccionar la BD
if ($conn->connect_error) {
    die("Conexión fallida a la base de datos " . DB_NAME . ": " . $conn->connect_error);
}

// Establecer el charset a utf8mb4 para soportar una amplia gama de caracteres
if (!$conn->set_charset("utf8mb4")) {
    // printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", $conn->error);
    // Considera cómo manejar este error. Para este ejemplo, no detendremos la ejecución.
}

// Crear tabla de usuarios si no existe
$sql_create_table = "CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    contrasena VARCHAR(255) NOT NULL, -- Para contraseñas hasheadas
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if (!$conn->query($sql_create_table)) {
    die("Error al crear la tabla 'usuarios': " . $conn->error);
}

// (Opcional) Puedes definir una función para cerrar la conexión si la usas frecuentemente.
// function cerrarConexion($conexion) {
//     $conexion->close();
// }
?> 