<?php
session_start();
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Completa todos los campos.";
    } else {
        $stmt = $conn->prepare("SELECT id, nombre_usuario, password FROM Usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->bind_result($id, $nombre, $hash);
        if ($stmt->fetch()) {
            if (password_verify($password, $hash)) {
                // login correcto
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $nombre;
                header('Location: index.php');
                exit;
            } else {
                $error = "Credenciales inválidas.";
            }
        } else {
            $error = "Usuario no encontrado.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Iniciar sesión</title></head>
<body>
<?php if (!empty($error)): ?>
    <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<form method="post" action="login.php">
    <input name="email" type="email" placeholder="Email" required />
    <input name="password" type="password" placeholder="Contraseña" required />
    <button type="submit">Entrar</button>
</form>
<p>No tienes cuenta? <a href="register.php">Registrate</a></p>
</body>
</html>
