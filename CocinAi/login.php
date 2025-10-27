<?php
session_start();
require_once 'includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT id, nombre_usuario, password FROM Usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['nombre_usuario'];
                header('Location: index.php');
                exit;
            }
        }
        $error = "Email o contraseña incorrectos";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - CocinAI</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container" style="min-height: 100vh; display: flex; justify-content: center; align-items: center;">
        <div style="background: white; padding: 40px; border-radius: 15px; width: 100%; max-width: 400px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">
            <h2 style="text-align: center; margin-bottom: 30px;">Iniciar Sesión</h2>
            
            <?php if (isset($error)): ?>
                <div style="color: red; margin-bottom: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" style="display: flex; flex-direction: column; gap: 20px;">
                <input type="email" name="email" placeholder="Email" required 
                    style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                
                <input type="password" name="password" placeholder="Contraseña" required 
                    style="padding: 15px; border: 1px solid #ddd; border-radius: 8px;">
                
                <button type="submit" class="btn" style="width: 100%;">Iniciar Sesión</button>
            </form>
            
            <p style="text-align: center; margin-top: 20px;">
                ¿No tienes cuenta? <a href="register.php" style="color: var(--color-tertiary);">Regístrate</a>
            </p>
        </div>
    </div>
</body>
</html>
