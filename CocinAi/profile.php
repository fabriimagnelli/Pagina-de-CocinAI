<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

$usuario_id = intval($_SESSION['user_id']);

// Comprobar si la columna usuario_id existe en la tabla Receta
$schema_missing = false;
$error = '';
try {
    $colRes = $conn->query("SHOW COLUMNS FROM Receta LIKE 'usuario_id'");
    if ($colRes === false) {
        $error = "No se pudo comprobar la estructura de la tabla Receta: " . $conn->error;
    } elseif ($colRes->num_rows === 0) {
        $schema_missing = true;
        // Si el usuario solicita arreglar el esquema (por ejemplo haciendo click en el enlace)
        if (isset($_GET['fix_schema']) && $_GET['fix_schema'] == '1') {
            try {
                $conn->begin_transaction();
                // Añadir la columna si no existe
                $conn->query("ALTER TABLE Receta ADD COLUMN usuario_id INT NULL AFTER grasas");
                // Añadir constraint FK (puede fallar si ya existe o por privilegios)
                $conn->query("ALTER TABLE Receta ADD CONSTRAINT fk_receta_usuario FOREIGN KEY (usuario_id) REFERENCES Usuario(id)");
                // Asignar las recetas existentes al usuario en sesión (opcional, evita NULLs)
                $conn->query("UPDATE Receta SET usuario_id = " . $usuario_id . " WHERE usuario_id IS NULL");
                $conn->commit();
                // Recargar para continuar con la consulta normal
                header("Location: profile.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $error = "Error al modificar el esquema: " . $e->getMessage();
                $schema_missing = true;
            }
        }
    }
} catch (Exception $e) {
    $error = "Error al comprobar esquema: " . $e->getMessage();
}

// Si el esquema está bien, obtener recetas del usuario
$recetas = [];
if (!$schema_missing && empty($error)) {
    $stmt = $conn->prepare("SELECT id, nombre, descripcion, imagen FROM Receta WHERE usuario_id = ? ORDER BY id DESC");
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $recetas = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $error = "Error en la consulta: " . $conn->error;
    }
}

// Añadir conteo para mostrar en el perfil
$recetas_count = count($recetas);

// --- NUEVO: manejo editar perfil / subir avatar ---
$profile_error = '';
$profile_success = '';
$user_id = intval($_SESSION['user_id']);
$edit_profile = isset($_GET['edit_profile']) && $_GET['edit_profile'] == '1';

// Si se envía el formulario de actualización de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nuevo_nombre = trim($_POST['nombre_usuario'] ?? $_SESSION['user_name']);
    // Asegurar columna avatar en Usuario (si no existe, intentar crearla)
    $colAvatarRes = $conn->query("SHOW COLUMNS FROM Usuario LIKE 'avatar'");
    if ($colAvatarRes && $colAvatarRes->num_rows === 0) {
        try {
            $conn->query("ALTER TABLE Usuario ADD COLUMN avatar VARCHAR(255) NULL AFTER password");
        } catch (Exception $e) {
            // no fatal: permitimos continuar sin avatar si no se puede crear
        }
    }

    // Procesar posible archivo avatar
    $avatar_db = null;
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $maxBytes = 2 * 1024 * 1024; // 2 MB para avatar
        if ($_FILES['avatar']['size'] > $maxBytes) {
            $profile_error = "La foto de perfil supera el límite de 2 MB.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
            finfo_close($finfo);
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
            if (!isset($allowed[$mime])) {
                $profile_error = "Formato de imagen no permitido para avatar.";
            } else {
                $ext = $allowed[$mime];
                $uploadDir = __DIR__ . '/uploads/avatars';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = 'avatar_' . $user_id . '_' . time() . '.' . $ext;
                $dest = $uploadDir . '/' . $newName;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                    $avatar_db = 'uploads/avatars/' . $newName;
                } else {
                    $profile_error = "Error al guardar la foto de perfil.";
                }
            }
        }
    }

    // Si no hay error, actualizar usuario
    if (empty($profile_error)) {
        if ($avatar_db !== null) {
            $stmt = $conn->prepare("UPDATE Usuario SET nombre_usuario = ?, avatar = ? WHERE id = ?");
            $stmt->bind_param("ssi", $nuevo_nombre, $avatar_db, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE Usuario SET nombre_usuario = ? WHERE id = ?");
            $stmt->bind_param("si", $nuevo_nombre, $user_id);
        }
        if ($stmt->execute()) {
            $profile_success = "Perfil actualizado correctamente.";
            // Actualizar sesión para mostrar inmediatamente
            $_SESSION['user_name'] = $nuevo_nombre;
            if ($avatar_db !== null) $_SESSION['user_avatar'] = $avatar_db;
            // Redirigir para evitar reenvío de formulario
            header("Location: profile.php");
            exit;
        } else {
            $profile_error = "Error al guardar los cambios: " . $stmt->error;
        }
        $stmt->close();
    }
}

/* ------------------ NUEVO: manejo eliminación de receta ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_recipe_id'])) {
    $delete_id = intval($_POST['delete_recipe_id']);
    $user_id = intval($_SESSION['user_id']);

    // Preparar respuesta por defecto
    $profile_error = $profile_error ?? '';
    $profile_success = $profile_success ?? '';

    // Obtener receta y verificar propietario
    $stmt = $conn->prepare("SELECT imagen, usuario_id FROM Receta WHERE id = ? LIMIT 1");
    if (!$stmt) {
        $profile_error = "Error en la base de datos: " . $conn->error;
    } else {
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rec = $res->fetch_assoc();
        $stmt->close();

        if (!$rec) {
            $profile_error = "Receta no encontrada.";
        } elseif (intval($rec['usuario_id']) !== $user_id) {
            $profile_error = "No tienes permiso para eliminar esta receta.";
        } else {
            // Ejecutar eliminación dentro de transacción: eliminar comentarios luego la receta
            try {
                $conn->begin_transaction();

                // Eliminar comentarios asociados (si existen)
                $delComments = $conn->prepare("DELETE FROM Comentario WHERE receta_id = ?");
                if ($delComments) {
                    $delComments->bind_param("i", $delete_id);
                    $delComments->execute();
                    $delComments->close();
                }

                // Eliminar la receta (asegurar que sea del usuario)
                $delRec = $conn->prepare("DELETE FROM Receta WHERE id = ? AND usuario_id = ?");
                if (!$delRec) {
                    throw new Exception("Error al preparar eliminación: " . $conn->error);
                }
                $delRec->bind_param("ii", $delete_id, $user_id);
                $delRec->execute();
                $affected = $delRec->affected_rows;
                $delRec->close();

                if ($affected === 0) {
                    // No se eliminó (posible conflicto/permiso)
                    throw new Exception("No se pudo eliminar la receta (verifica permisos).");
                }

                $conn->commit();

                // Borrar archivo de imagen si existe
                if (!empty($rec['imagen'])) {
                    $imgPath = __DIR__ . '/' . $rec['imagen'];
                    if (file_exists($imgPath) && is_file($imgPath)) {
                        @unlink($imgPath);
                    }
                }

                // Mensaje y redirección para evitar reenvío del form
                $profile_success = "Receta eliminada correctamente.";
                header("Location: profile.php");
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $profile_error = "Error al eliminar la receta: " . $e->getMessage();
            }
        }
    }
}
/* ------------------ FIN manejador eliminación ------------------ */

/* ------------------ NUEVAS ESTADÍSTICAS Y FAVORITOS ------------------ */
$followers_count = 0;
$following_count = 0;
$likes_received = 0;       // likes que han recibido tus recetas
$favorites_received = 0;   // veces que tus recetas fueron marcadas como favoritas
$saved_recipes = [];       // recetas que el usuario guardó como favorito
$saved_count = 0;
$badges = [];

try {
    // Seguidores / Siguiendo
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Seguimiento WHERE followed_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $followers_count = intval($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM Seguimiento WHERE follower_id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $following_count = intval($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    // Likes recibidos sobre tus recetas
    $stmt = $conn->prepare("
        SELECT COUNT(lr.id) AS cnt
        FROM LikeReceta lr
        INNER JOIN Receta r ON lr.receta_id = r.id
        WHERE r.usuario_id = ?
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $likes_received = intval($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    // Favoritos recibidos sobre tus recetas
    $stmt = $conn->prepare("
        SELECT COUNT(f.id) AS cnt
        FROM Favorito f
        INNER JOIN Receta r ON f.receta_id = r.id
        WHERE r.usuario_id = ?
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $favorites_received = intval($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    // Recetas guardadas por este usuario (Favoritos)
    $stmt = $conn->prepare("
        SELECT r.id, r.nombre, r.descripcion, r.imagen, f.fecha
        FROM Favorito f
        JOIN Receta r ON f.receta_id = r.id
        WHERE f.usuario_id = ?
        ORDER BY f.fecha DESC
        LIMIT 40
    ");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) $saved_recipes[] = $row;
    $stmt->close();
    $saved_count = count($saved_recipes);

} catch (Exception $e) {
    // silencioso: si alguna consulta falla no bloqueamos el perfil
}

/* ------------------ BADGES / INSIGNIAS SIMPLES ------------------ */
if ($recetas_count >= 10) $badges[] = ['label'=>'Creador Destacado','icon'=>'🏅','hint'=>'10+ recetas publicadas'];
if ($likes_received >= 50) $badges[] = ['label'=>'Chef Experto','icon'=>'🔥','hint'=>'50+ likes recibidos'];
if ($followers_count >= 100) $badges[] = ['label'=>'Influencer','icon'=>'⭐','hint'=>'100+ seguidores'];
if ($saved_count >= 20) $badges[] = ['label'=>'Curador','icon'=>'🔖','hint'=>'20+ recetas guardadas'];
if (empty($badges) && $recetas_count>0) $badges[] = ['label'=>'Autor','icon'=>'✍️','hint'=>'Has publicado recetas'];

/* ------------------ FIN NUEVAS ESTADÍSTICAS ------------------ */
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Mi perfil - <?php echo htmlspecialchars($_SESSION['user_name']); ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
      crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="styles.css" />
</head>
<body>
<div class="container">
    <header>
        <div><a class="logo" href="index.php">CocinAI</a></div>
        <nav>
            <a href="index.php">Inicio</a>
            <a href="recipes.php">Recetas</a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="upload_recipe.php">Subir receta</a>
            <?php endif; ?>
        </nav>
    </header>

    <?php if (!empty($profile_error)): ?>
        <div class="form-message form-error"><?php echo htmlspecialchars($profile_error); ?></div>
    <?php endif; ?>
    <?php if (!empty($profile_success)): ?>
        <div class="form-message form-success"><?php echo htmlspecialchars($profile_success); ?></div>
    <?php endif; ?>

    <div class="profile-header" role="banner" aria-label="Perfil de usuario">
        <div class="profile-avatar">
            <img src="<?php echo !empty($_SESSION['user_avatar'] ?? null) ? htmlspecialchars($_SESSION['user_avatar']) : 'https://via.placeholder.com/150?text=User'; ?>" alt="Avatar">
        </div>
        <div class="profile-info">
            <h2><?php echo htmlspecialchars($_SESSION['user_name']); ?></h2>

            <!-- Insignias -->
            <?php if (!empty($badges)): ?>
                <div class="badge-list" aria-hidden="false" style="margin-top:8px;">
                    <?php foreach ($badges as $b): ?>
                        <span class="badge-item" title="<?php echo htmlspecialchars($b['hint']); ?>">
                            <span class="badge-icon"><?php echo $b['icon']; ?></span>
                            <span class="badge-label"><?php echo htmlspecialchars($b['label']); ?></span>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <p class="text-muted" style="margin-top:8px;">Mi perfil — <?php echo intval(count($recetas)); ?> recetas</p>
            <div class="profile-actions">
                <a class="btn-sm" href="profile.php">Ver perfil</a>
                <a class="btn-sm" href="profile.php?edit_profile=1">Editar perfil</a>
                <a class="btn-sm" href="upload_recipe.php">Subir receta</a>
                <a class="btn-sm" href="logout.php">Salir</a>
            </div>
            <div class="profile-stats" style="margin-top:12px;">
                <div class="profile-stat">Recetas: <?php echo intval($recetas_count); ?></div>
                <div class="profile-stat">Likes recibidos: <?php echo intval($likes_received); ?></div>
                <div class="profile-stat">Favoritos recibidos: <?php echo intval($favorites_received); ?></div>
                <div class="profile-stat">Seguidores: <?php echo intval($followers_count); ?></div>
                <div class="profile-stat">Siguiendo: <?php echo intval($following_count); ?></div>
            </div>
        </div>
    </div>

    <?php if ($edit_profile): ?>
        <section style="margin-bottom:18px;">
            <div class="form-card">
                <h3>Editar perfil</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-field">
                        <label class="required">Nombre de usuario</label>
                        <input name="nombre_usuario" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" />
                    </div>
                    <div class="form-field">
                        <label>Foto de perfil</label>
                        <div class="image-preview" style="min-height:120px;">
                            <?php if (!empty($_SESSION['user_avatar']) && file_exists(__DIR__ . '/' . ($_SESSION['user_avatar']))): ?>
                                <img src="<?php echo htmlspecialchars($_SESSION['user_avatar']); ?>" alt="avatar actual">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/300x200?text=Avatar" alt="avatar">
                            <?php endif; ?>
                        </div>
                        <input type="file" name="avatar" accept="image/*" />
                        <div class="form-note">Formato recomendado: jpg, png, webp. Máx 2 MB.</div>
                    </div>
                    <div style="margin-top:10px;">
                        <button class="btn" type="submit" name="update_profile">Guardar cambios</button>
                        <a class="btn-sm" href="profile.php" style="margin-left:8px;">Cancelar</a>
                    </div>
                </form>
            </div>
        </section>
    <?php endif; ?>

    <main>
        <h2>Mis recetas</h2>
        <?php if (empty($recetas)): ?>
            <p>No has subido recetas aún.</p>
        <?php else: ?>
            <section class="profile-recipes" aria-label="Recetas del usuario">
                <div class="grid" style="align-items:stretch;">
                    <?php foreach ($recetas as $r): ?>
                        <article class="profile-card" aria-labelledby="rec-<?php echo intval($r['id']); ?>">
                            <?php if (!empty($r['imagen']) && file_exists(__DIR__ . '/' . $r['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($r['imagen']); ?>" alt="<?php echo htmlspecialchars($r['nombre']); ?>" />
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300?text=Sin+imagen" alt="sin imagen" />
                            <?php endif; ?>
                            <div class="body">
                                <div class="meta text-muted">ID <?php echo intval($r['id']); ?></div>
                                <h4 id="rec-<?php echo intval($r['id']); ?>"><?php echo htmlspecialchars($r['nombre']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars(substr($r['descripcion'] ?? '', 0, 80)); ?><?php echo (strlen($r['descripcion'] ?? '')>80)?'...':''; ?></p>
                                <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                                    <a class="btn-sm" href="recipe.php?id=<?php echo intval($r['id']); ?>">Ver</a>
                                    <a class="btn-sm" href="upload_recipe.php?edit=<?php echo intval($r['id']); ?>">Editar</a>

                                    <!-- FORMULARIO DE ELIMINAR: POST para profile.php -->
                                    <form method="post" style="display:inline-block; margin:0;" onsubmit="return confirm('¿Eliminar esta receta? Esta acción no se puede deshacer.');">
                                        <input type="hidden" name="delete_recipe_id" value="<?php echo intval($r['id']); ?>" />
                                        <button type="submit" class="btn-sm" style="background:#fff2f2; border-color:#f8d7da; color:#b91c1c;">Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- NUEVA SECCIÓN: Recetas guardadas / Favoritos del usuario -->
        <section style="margin-top:22px;">
            <h2>Recetas guardadas (<?php echo intval($saved_count); ?>)</h2>
            <?php if (empty($saved_recipes)): ?>
                <p class="text-muted">No has guardado recetas todavía.</p>
            <?php else: ?>
                <div class="favorites-grid" style="margin-top:12px; display:flex; flex-wrap:wrap; gap:16px;">
                    <?php foreach ($saved_recipes as $sr): ?>
                        <article class="favorite-card" style="width:220px; background:#fff; border-radius:12px; box-shadow:var(--shadow-sm); overflow:hidden;">
                            <?php if (!empty($sr['imagen']) && file_exists(__DIR__ . '/' . $sr['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($sr['imagen']); ?>" alt="<?php echo htmlspecialchars($sr['nombre']); ?>" style="width:100%; height:130px; object-fit:cover; display:block;">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300?text=Sin+imagen" alt="sin imagen" style="width:100%; height:130px; object-fit:cover; display:block;">
                            <?php endif; ?>
                            <div style="padding:10px;">
                                <h4 style="margin:0 0 6px; font-size:15px;"><?php echo htmlspecialchars($sr['nombre']); ?></h4>
                                <p class="text-muted" style="margin:0 0 8px; font-size:13px;"><?php echo htmlspecialchars(substr($sr['descripcion'] ?? '',0,80)); ?><?php echo (strlen($sr['descripcion'] ?? '')>80)?'...':''; ?></p>
                                <div style="display:flex; gap:8px;">
                                    <a class="btn-sm" href="recipe.php?id=<?php echo intval($sr['id']); ?>">Ver</a>
                                    <form method="post" action="favorite_toggle.php" style="display:inline;">
                                        <input type="hidden" name="receta_id" value="<?php echo intval($sr['id']); ?>">
                                        <button class="btn-sm" type="submit">Quitar</button>
                                    </form>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </main>

    <footer class="footer">&copy; <?php echo date('Y'); ?> CocinAI</footer>
</div>
</body>
</html>
