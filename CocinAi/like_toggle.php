<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

$receta_id = isset($_POST['receta_id']) ? intval($_POST['receta_id']) : 0;
$usuario_id = intval($_SESSION['user_id']);
$redirect = $_SERVER['HTTP_REFERER'] ?? ('recipe.php?id=' . $receta_id);

if ($receta_id <= 0) {
    header("Location: $redirect");
    exit;
}

try {
    // comprobar si ya existe like
    $stmt = $conn->prepare("SELECT id FROM LikeReceta WHERE usuario_id = ? AND receta_id = ? LIMIT 1");
    $stmt->bind_param("ii", $usuario_id, $receta_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        // quitar like
        $del = $conn->prepare("DELETE FROM LikeReceta WHERE id = ?");
        $del->bind_param("i", $res['id']);
        $del->execute();
        $del->close();
    } else {
        // insertar like
        $ins = $conn->prepare("INSERT INTO LikeReceta (usuario_id, receta_id) VALUES (?, ?)");
        $ins->bind_param("ii", $usuario_id, $receta_id);
        $ins->execute();
        $ins->close();
    }
} catch (Exception $e) {
    // silencioso
}

header("Location: $redirect");
exit;
?>
