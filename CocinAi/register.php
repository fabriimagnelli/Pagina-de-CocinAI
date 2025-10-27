<?php
session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre_usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($nombre && $email && $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO Usuario (nombre_usuario, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nombre, $email, $hash);
        
        if ($stmt->execute()) {
            header('Location: login.php');
            exit;
        }
        $error = "Error al registrar usuario";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - CocinAI</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; justify-content: center; align-items: center;">
        <div style="background: white; padding: 40px; border-radius: 15px; width: 100%; max-width: 400px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 30px;">Registro</h2>
            
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="text" name="nombre_usuario" placeholder="Nombre de usuario" required 
                       style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                
                <input type="email" name="email" placeholder="Email" required 
                       style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                
                <input type="password" name="password" placeholder="Contraseña" required 
                       style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                
                <button type="submit" class="btn" style="width: 100%;">Registrarse</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                ¿Ya tienes cuenta? <a href="login.php" style="color: var(--color-tertiary);">Iniciar sesión</a>
            </p>
        </div>
    </div>
</body>
</html>
