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
    $stmt = $conn->prepare("SELECT id FROM Favorito WHERE usuario_id = ? AND receta_id = ? LIMIT 1");
    $stmt->bind_param("ii", $usuario_id, $receta_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $del = $conn->prepare("DELETE FROM Favorito WHERE id = ?");
        $del->bind_param("i", $res['id']);
        $del->execute();
        $del->close();
    } else {
        $ins = $conn->prepare("INSERT INTO Favorito (usuario_id, receta_id) VALUES (?, ?)");
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
