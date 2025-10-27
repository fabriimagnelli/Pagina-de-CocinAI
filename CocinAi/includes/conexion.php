<?php
$host = "localhost";
$usuario = "root";
$clave = "";
$base_datos = "recetas_cocina";

try {
    $conn = new mysqli($host, $usuario, $clave, $base_datos);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Error de conexión: " . $e->getMessage());
}
?>
