<?php
$host = "localhost";
$usuario = "root";
$clave = "";
$base_datos = "recetas_cocina";

// Crear conexión
$conn = new mysqli($host, $usuario, $clave, $base_datos);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Consultar las tablas
$sql = "SHOW TABLES";
$resultado = $conn->query($sql);

if ($resultado->num_rows > 0) {
    echo "<h2>Tablas en la base de datos '$base_datos':</h2><ul>";
    while($fila = $resultado->fetch_array()) {
        echo "<li>" . $fila[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "No se encontraron tablas en la base de datos.";
}

$conn->close();
?>
