<?php
session_start();
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

// Validar id de receta
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener receta con autor y categoría
$stmt = $conn->prepare("
    SELECT r.*, u.nombre_usuario as autor, c.nombre as categoria
    FROM Receta r 
    LEFT JOIN Usuario u ON r.usuario_id = u.id
    LEFT JOIN Categoria c ON r.categoria_id = c.id
    WHERE r.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$receta = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$receta) {
    header('Location: index.php');
    exit;
}

// Procesar nuevo comentario
$comment_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['user_id'])) {
    $texto = trim($_POST['texto'] ?? '');
    if (empty($texto)) {
        $comment_error = "El comentario no puede estar vacío.";
    } else {
        $stmt = $conn->prepare("INSERT INTO Comentario (texto, fecha, usuario_id, receta_id) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("sii", $texto, $_SESSION['user_id'], $id);
        if ($stmt->execute()) {
            header("Location: recipe.php?id=" . $id . "#comentarios");
            exit;
        } else {
            $comment_error = "Error al guardar el comentario.";
        }
        $stmt->close();
    }
}

// Obtener comentarios
$comentarios = [];
$stmt = $conn->prepare("
    SELECT c.*, u.nombre_usuario
    FROM Comentario c
    JOIN Usuario u ON c.usuario_id = u.id
    WHERE c.receta_id = ?
    ORDER BY c.fecha DESC
");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $comentarios[] = $row;
}
$stmt->close();

// Obtener conteos y estado social
$likes_count = 0;
$favs_count = 0;
$user_liked = false;
$user_fav = false;
$user_follows_author = false;
$followers_count = 0;
$author_id = intval($receta['usuario_id'] ?? 0);

try {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM LikeReceta WHERE receta_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $likes_count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Favorito WHERE receta_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $favs_count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
    $stmt->close();

    if (!empty($_SESSION['user_id'])) {
        $uid = intval($_SESSION['user_id']);
        $stmt = $conn->prepare("SELECT 1 FROM LikeReceta WHERE usuario_id = ? AND receta_id = ? LIMIT 1");
        $stmt->bind_param("ii", $uid, $id);
        $stmt->execute();
        $user_liked = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        $stmt = $conn->prepare("SELECT 1 FROM Favorito WHERE usuario_id = ? AND receta_id = ? LIMIT 1");
        $stmt->bind_param("ii", $uid, $id);
        $stmt->execute();
        $user_fav = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($author_id && $author_id !== $uid) {
            $stmt = $conn->prepare("SELECT 1 FROM Seguimiento WHERE follower_id = ? AND followed_id = ? LIMIT 1");
            $stmt->bind_param("ii", $uid, $author_id);
            $stmt->execute();
            $user_follows_author = (bool)$stmt->get_result()->fetch_assoc();
            $stmt->close();
        }
    }

    if ($author_id) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Seguimiento WHERE followed_id = ?");
        $stmt->bind_param("i", $author_id);
        $stmt->execute();
        $followers_count = $stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        $stmt->close();
    }
} catch (Exception $e) {
    // silencioso: si falla algo, dejamos valores por defecto
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?php echo htmlspecialchars($receta['nombre']); ?> - CocinAI</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container">
    <header>
        <div><a href="index.php" class="logo">CocinAI</a></div>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="recipes.php">Recetas</a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="profile.php">Mi Perfil</a>
            <?php endif; ?>
        </nav>
    </header>

    <article class="recipe-detail">
        <?php if (!empty($receta['imagen']) && file_exists(__DIR__ . '/' . $receta['imagen'])): ?>
            <img src="<?php echo htmlspecialchars($receta['imagen']); ?>" alt="<?php echo htmlspecialchars($receta['nombre']); ?>" class="hero-img">
        <?php endif; ?>

        <h1><?php echo htmlspecialchars($receta['nombre']); ?></h1>
        
        <div class="recipe-meta">
            <span>Por <?php echo htmlspecialchars($receta['autor']); ?></span>
            <?php if (!empty($receta['categoria'])): ?>
                <span>Categoría: <?php echo htmlspecialchars($receta['categoria']); ?></span>
            <?php endif; ?>
        </div>

        <div class="recipe-content">
            <p><?php echo nl2br(htmlspecialchars($receta['descripcion'])); ?></p>
            
            <div class="grid cols-2">
                <div>
                    <h3>Ingredientes</h3>
                    <ul>
                        <?php foreach (explode(',', $receta['ingredientes']) as $ingrediente): ?>
                            <li><?php echo htmlspecialchars(trim($ingrediente)); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div>
                    <h3>Instrucciones</h3>
                    <div class="instructions">
                        <?php echo nl2br(htmlspecialchars($receta['instrucciones'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de interacción y compartir -->
        <div style="display:flex; gap:12px; align-items:center; margin-bottom:12px;">
            <!-- Like -->
            <form method="post" action="like_toggle.php" style="display:inline;">
                <input type="hidden" name="receta_id" value="<?php echo intval($id); ?>">
                <button class="btn-sm" type="submit" aria-pressed="<?php echo $user_liked ? 'true' : 'false'; ?>">
                    <?php echo $user_liked ? '❤️' : '🤍'; ?> Me gusta
                    <span class="small" style="margin-left:6px;"><?php echo intval($likes_count); ?></span>
                </button>
            </form>

            <!-- Favorito -->
            <form method="post" action="favorite_toggle.php" style="display:inline;">
                <input type="hidden" name="receta_id" value="<?php echo intval($id); ?>">
                <button class="btn-sm" type="submit" aria-pressed="<?php echo $user_fav ? 'true' : 'false'; ?>">
                    <?php echo $user_fav ? '🔖 Guardado' : '🔖 Guardar'; ?>
                    <span class="small" style="margin-left:6px;"><?php echo intval($favs_count); ?></span>
                </button>
            </form>

            <!-- Seguir autor (si no es tu propia receta y estás logueado) -->
            <?php if (!empty($_SESSION['user_id']) && $author_id && intval($_SESSION['user_id']) !== $author_id): ?>
                <form method="post" action="follow_toggle.php" style="display:inline;">
                    <input type="hidden" name="followed_id" value="<?php echo $author_id; ?>">
                    <button class="btn-sm" type="submit"><?php echo $user_follows_author ? 'Dejar de seguir' : 'Seguir'; ?></button>
                </form>
                <div class="small text-muted" style="margin-left:8px;"><?php echo intval($followers_count); ?> seguidores</div>
            <?php endif; ?>

            <!-- Compartir -->
            <div style="margin-left:auto; display:flex; gap:8px;">
                <?php $shareUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_SERVER['REQUEST_URI']; ?>
                <a class="btn-sm" href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode($shareUrl); ?>" target="_blank" rel="noopener">Compartir FB</a>
                <a class="btn-sm" href="https://twitter.com/intent/tweet?url=<?php echo urlencode($shareUrl); ?>&text=<?php echo urlencode($receta['nombre']); ?>" target="_blank" rel="noopener">Compartir TW</a>
            </div>
        </div>

        <section id="comentarios" class="comments-section">
            <h3>Comentarios</h3>
            
            <?php if (!empty($_SESSION['user_id'])): ?>
                <form method="post" class="comment-form">
                    <?php if (!empty($comment_error)): ?>
                        <div class="form-error"><?php echo htmlspecialchars($comment_error); ?></div>
                    <?php endif; ?>
                    <textarea name="texto" required placeholder="Escribe tu comentario..."></textarea>
                    <button type="submit" class="btn">Comentar</button>
                </form>
            <?php else: ?>
                <p><a href="login.php">Inicia sesión</a> para comentar.</p>
            <?php endif; ?>

            <div class="comments-list">
                <?php if (empty($comentarios)): ?>
                    <p class="text-muted">No hay comentarios aún.</p>
                <?php else: ?>
                    <?php foreach ($comentarios as $c): ?>
                        <div class="comment">
                            <div class="comment-header">
                                <strong><?php echo htmlspecialchars($c['nombre_usuario']); ?></strong>
                                <span class="text-muted"><?php echo date('d/m/Y H:i', strtotime($c['fecha'])); ?></span>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($c['texto'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </article>
</div>
</body>
</html>
