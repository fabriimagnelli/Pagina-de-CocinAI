<?php
session_start();
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre === '' || $email === '' || $password === '') {
        $error = "Completa todos los campos.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Usuario (nombre_usuario, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $hash);
        if ($stmt->execute()) {
            header('Location: login.php');
            exit;
        } else {
            // posible email duplicado
            $error = "Error al registrar. El email puede estar en uso.";
        }
        $stmt->close();
    }
}
?>
<!-- Formulario mínimo -->
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Registro</title></head>
<body>
<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="post" action="register.php">
    <input name="nombre_usuario" placeholder="Nombre de usuario" required />
    <input name="email" type="email" placeholder="Email" required />
    <input name="password" type="password" placeholder="Contraseña" required />
    <button type="submit">Registrarse</button>
</form>
<p>Ya tienes cuenta? <a href="login.php">Iniciar sesión</a></p>
</body>
</html>
