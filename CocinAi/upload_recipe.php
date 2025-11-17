<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

$error = '';
$success = '';

// Modo edición
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : (isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0);
$editing = $edit_id > 0;

// Valores por defecto
$nombre = $descripcion = $ingredientes = $instrucciones = '';
$calorias = $carbohidratos = $proteinas = $grasas = null;
$categoria_id = 0;
$imagen_db = null;

// Si editando, cargar receta y verificar propietario
if ($editing) {
    $stmt = $conn->prepare("SELECT id, nombre, descripcion, ingredientes, instrucciones, calorias, carbohidratos, proteinas, grasas, categoria_id, imagen, usuario_id FROM Receta WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $edit_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $rec = $res->fetch_assoc();
        $stmt->close();
        if (!$rec) {
            $error = "Receta no encontrada.";
            $editing = false;
        } elseif (intval($rec['usuario_id']) !== intval($_SESSION['user_id'])) {
            $error = "No tienes permiso para editar esta receta.";
            $editing = false;
        } else {
            $nombre = $rec['nombre'];
            $descripcion = $rec['descripcion'];
            $ingredientes = $rec['ingredientes'];
            $instrucciones = $rec['instrucciones'];
            $calorias = $rec['calorias'];
            $carbohidratos = $rec['carbohidratos'];
            $proteinas = $rec['proteinas'];
            $grasas = $rec['grasas'];
            $categoria_id = $rec['categoria_id'];
            $imagen_db = $rec['imagen'];
        }
    } else {
        $error = "Error al preparar la consulta: " . $conn->error;
        $editing = false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error)) {
    // Recolectar entradas
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $calorias = isset($_POST['calorias']) && $_POST['calorias'] !== '' ? intval($_POST['calorias']) : null;
    $carbohidratos = isset($_POST['carbohidratos']) && $_POST['carbohidratos'] !== '' ? intval($_POST['carbohidratos']) : null;
    $proteinas = isset($_POST['proteinas']) && $_POST['proteinas'] !== '' ? intval($_POST['proteinas']) : null;
    $grasas = isset($_POST['grasas']) && $_POST['grasas'] !== '' ? intval($_POST['grasas']) : null;
    $categoria_id = isset($_POST['categoria_id']) ? intval($_POST['categoria_id']) : 0;

    // Validaciones básicas
    if ($nombre === '') {
        $error = "El nombre de la receta es obligatorio.";
    } elseif ($categoria_id <= 0) {
        $error = "Selecciona una categoría válida.";
    } elseif ($calorias !== null && $calorias < 0) {
        $error = "Calorías inválidas.";
    }

    // Manejo de imagen
    $new_image_uploaded = !empty($_FILES['imagen']['name']) && ($_FILES['imagen']['error'] === UPLOAD_ERR_OK);
    if (empty($error) && $new_image_uploaded) {
        $maxBytes = 5 * 1024 * 1024; // 5 MB
        if ($_FILES['imagen']['size'] > $maxBytes) {
            $error = "La imagen supera el tamaño máximo de 5 MB.";
        } else {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['imagen']['tmp_name']);
            finfo_close($finfo);

            $allowed_mimes = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp'
            ];
            if (!isset($allowed_mimes[$mime])) {
                $error = "Formato de imagen no permitido.";
            } else {
                $ext = $allowed_mimes[$mime];
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadDir . '/' . $newName;
                if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                    $imagen_db = 'uploads/' . $newName;
                } else {
                    $error = "Error al mover la imagen subida.";
                }
            }
        }
    }

    if (empty($error)) {
        $usuario_id = intval($_SESSION['user_id']);
        if ($editing) {
            // UPDATE
            $sql = "UPDATE Receta SET nombre = ?, descripcion = ?, ingredientes = ?, instrucciones = ?, calorias = ?, carbohidratos = ?, proteinas = ?, grasas = ?, categoria_id = ?, imagen = ? WHERE id = ? AND usuario_id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Error en la base de datos (preparar): " . $conn->error;
            } else {
                $types = str_repeat('s',4) . str_repeat('i',5) . 's' . 'ii'; // 'ssss' + 5*i + 's' + 'ii' => coincide con parámetros abajo
                $stmt->bind_param($types, $nombre, $descripcion, $ingredientes, $instrucciones, $calorias, $carbohidratos, $proteinas, $grasas, $categoria_id, $imagen_db, $edit_id, $usuario_id);
                if ($stmt->execute()) {
                    $success = "Receta actualizada correctamente.";
                    header("Location: recipe.php?id=" . $edit_id);
                    exit;
                } else {
                    $error = "Error al actualizar la receta: " . $stmt->error;
                }
                $stmt->close();
            }
        } else {
            // INSERT
            $sql = "INSERT INTO Receta (nombre, descripcion, ingredientes, instrucciones, calorias, carbohidratos, proteinas, grasas, usuario_id, categoria_id, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                $error = "Error en la base de datos (preparar): " . $conn->error;
            } else {
                $types = str_repeat('s',4) . str_repeat('i',6) . 's'; // 4s + 6i + s
                $imagen_param = $imagen_db;
                $stmt->bind_param($types, $nombre, $descripcion, $ingredientes, $instrucciones, $calorias, $carbohidratos, $proteinas, $grasas, $usuario_id, $categoria_id, $imagen_param);
                if ($stmt->execute()) {
                    $new_id = $stmt->insert_id;
                    $success = "Receta subida correctamente.";
                    header("Location: recipe.php?id=" . $new_id);
                    exit;
                } else {
                    $error = "Error al guardar la receta: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
}

// Obtener categorías para select
$cats = [];
$res = $conn->query("SELECT id, nombre FROM Categoria");
if ($res) {
    while ($r = $res->fetch_assoc()) $cats[] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $editing ? 'Editar receta' : 'Subir receta'; ?> - CocinAI</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
      integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
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
            <?php if (!empty($_SESSION['user_id'])): ?><a href="profile.php">Mi perfil</a><?php endif; ?>
        </nav>
    </header>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <div>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
        <div>
            <a class="btn-sm" href="profile.php">Mi perfil</a>
            <a class="btn-sm" href="logout.php">Salir</a>
        </div>
    </div>

    <?php if (!empty($error)): ?><div class="form-message form-error"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="form-message form-success"><?php echo htmlspecialchars($success); ?></div><?php endif; ?>

    <div class="form-card" aria-labelledby="form-title">
        <h2 id="form-title"><?php echo $editing ? 'Editar receta' : 'Subir receta'; ?></h2>
        <form method="post" enctype="multipart/form-data" action="upload_recipe.php<?php echo $editing ? '?edit=' . intval($edit_id) : ''; ?>">
            <div class="form-grid">
                <div>
                    <?php if ($editing): ?>
                        <input type="hidden" name="edit_id" value="<?php echo intval($edit_id); ?>" />
                    <?php endif; ?>

                    <div class="form-field">
                        <label class="required">Nombre</label>
                        <input name="nombre" placeholder="Nombre receta" required value="<?php echo htmlspecialchars($nombre ?? ''); ?>" />
                    </div>

                    <div class="form-field">
                        <label>Descripción</label>
                        <textarea name="descripcion" placeholder="Descripción"><?php echo htmlspecialchars($descripcion ?? ''); ?></textarea>
                    </div>

                    <div class="form-field">
                        <label>Ingredientes (coma separado)</label>
                        <textarea name="ingredientes" placeholder="Ingredientes (coma separado)"><?php echo htmlspecialchars($ingredientes ?? ''); ?></textarea>
                    </div>

                    <div class="form-field">
                        <label>Instrucciones</label>
                        <textarea name="instrucciones" placeholder="Instrucciones"><?php echo htmlspecialchars($instrucciones ?? ''); ?></textarea>
                    </div>

                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <div style="flex:1;" class="form-field">
                            <label>Calorías</label>
                            <input name="calorias" type="number" placeholder="Calorías" value="<?php echo htmlspecialchars($calorias ?? ''); ?>" />
                        </div>
                        <div style="flex:1;" class="form-field">
                            <label>Carbohidratos (g)</label>
                            <input name="carbohidratos" type="number" placeholder="Carbohidratos" value="<?php echo htmlspecialchars($carbohidratos ?? ''); ?>" />
                        </div>
                    </div>

                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <div style="flex:1;" class="form-field">
                            <label>Proteínas (g)</label>
                            <input name="proteinas" type="number" placeholder="Proteínas" value="<?php echo htmlspecialchars($proteinas ?? ''); ?>" />
                        </div>
                        <div style="flex:1;" class="form-field">
                            <label>Grasas (g)</label>
                            <input name="grasas" type="number" placeholder="Grasas" value="<?php echo htmlspecialchars($grasas ?? ''); ?>" />
                        </div>
                    </div>

                    <div class="form-field">
                        <label class="required">Categoría</label>
                        <select name="categoria_id" class="select-inline">
                            <option value="0">Seleccionar categoría</option>
                            <?php foreach ($cats as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo (($categoria_id ?? 0) == $c['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c['nombre']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <aside>
                    <div class="form-field">
                        <label>Imagen</label>
                        <div class="image-preview" aria-hidden="false">
                            <?php if (!empty($imagen_db) && file_exists(__DIR__ . '/' . $imagen_db)): ?>
                                <img src="<?php echo htmlspecialchars($imagen_db); ?>" alt="imagen actual">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/600x400?text=Imagen+receta" alt="preview">
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($imagen_db)): ?>
                            <div class="replace-checkbox">
                                <label><input type="checkbox" name="replace_image" value="1"> Reemplazar imagen</label>
                            </div>
                        <?php endif; ?>
                        <input type="file" name="imagen" accept="image/*" />
                        <div class="form-note">Tamaño máximo recomendado: 5MB. Formatos: jpg, png, webp.</div>
                    </div>

                    <div style="margin-top:12px;">
                        <button class="btn" type="submit"><?php echo $editing ? 'Actualizar receta' : 'Subir receta'; ?></button>
                    </div>
                </aside>
            </div>
        </form>
    </div>

    <footer class="footer">&copy; <?php echo date('Y'); ?> CocinAI</footer>
</div>
</body>
</html>
