<?php
// Activar reporte de errores para mysqli (ayuda en debugging)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$host = "localhost";
$usuario = "root";
$clave = "";
$base_datos = "recetas_cocina";

try {
    // Crear conexión
    $conn = new mysqli($host, $usuario, $clave, $base_datos);
    // Asegurar charset
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    // Manejo mínimo de error de conexión; puedes loguear en archivo
    die("Conexión fallida: " . $e->getMessage());
}

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Asegurar charset
$conn->set_charset("utf8mb4");

// ...existing code...
?>
