<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

$followed_id = isset($_POST['followed_id']) ? intval($_POST['followed_id']) : 0;
$follower_id = intval($_SESSION['user_id']);
$redirect = $_SERVER['HTTP_REFERER'] ?? 'index.php';

if ($followed_id <= 0 || $followed_id === $follower_id) {
    header("Location: $redirect");
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id FROM Seguimiento WHERE follower_id = ? AND followed_id = ? LIMIT 1");
    $stmt->bind_param("ii", $follower_id, $followed_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($res) {
        $del = $conn->prepare("DELETE FROM Seguimiento WHERE id = ?");
        $del->bind_param("i", $res['id']);
        $del->execute();
        $del->close();
    } else {
        $ins = $conn->prepare("INSERT INTO Seguimiento (follower_id, followed_id) VALUES (?, ?)");
        $ins->bind_param("ii", $follower_id, $followed_id);
        $ins->execute();
        $ins->close();
    }
} catch (Exception $e) {
    // silencioso
}

header("Location: $redirect");
exit;
?>
