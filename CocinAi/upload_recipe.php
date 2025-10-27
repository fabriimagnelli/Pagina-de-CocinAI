<?php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $ingredientes = trim($_POST['ingredientes'] ?? '');
    $instrucciones = trim($_POST['instrucciones'] ?? '');
    $calorias = intval($_POST['calorias'] ?? 0);
    $carbohidratos = intval($_POST['carbohidratos'] ?? 0);
    $proteinas = intval($_POST['proteinas'] ?? 0);
    $grasas = intval($_POST['grasas'] ?? 0);
    $categoria_id = intval($_POST['categoria_id'] ?? 0);

    $imagen_db = null;
    if (!empty($_FILES['imagen']['name'])) {
        $uploadDir = __DIR__ . '/uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fname = basename($_FILES['imagen']['name']);
        $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($ext, $allowed)) {
            $error = "Formato de imagen no permitido.";
        } else {
            $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            $dest = $uploadDir . '/' . $newName;
            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $dest)) {
                // guardar ruta relativa para servir luego
                $imagen_db = 'uploads/' . $newName;
            } else {
                $error = "Error al subir la imagen.";
            }
        }
    }

    if (empty($error)) {
        $stmt = $conn->prepare("INSERT INTO Receta (nombre, descripcion, ingredientes, instrucciones, calorias, carbohidratos, proteinas, grasas, categoria_id, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssiiiiis", $nombre, $descripcion, $ingredientes, $instrucciones, $calorias, $carbohidratos, $proteinas, $grasas, $categoria_id, $imagen_db);
        if ($stmt->execute()) {
            $success = "Receta subida correctamente.";
        } else {
            $error = "Error al guardar la receta.";
        }
        $stmt->close();
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
<head><meta charset="utf-8"><title>Subir receta</title></head>
<body>
<p>Hola, <?php echo htmlspecialchars($_SESSION['user_name']); ?> — <a href="logout.php">Salir</a></p>

<?php if (!empty($error)): ?><p style="color:red;"><?php echo htmlspecialchars($error); ?></p><?php endif; ?>
<?php if (!empty($success)): ?><p style="color:green;"><?php echo htmlspecialchars($success); ?></p><?php endif; ?>

<form method="post" enctype="multipart/form-data" action="upload_recipe.php">
    <input name="nombre" placeholder="Nombre receta" required />
    <textarea name="descripcion" placeholder="Descripción"></textarea>
    <textarea name="ingredientes" placeholder="Ingredientes (coma separado)"></textarea>
    <textarea name="instrucciones" placeholder="Instrucciones"></textarea>
    <input name="calorias" type="number" placeholder="Calorías" />
    <input name="carbohidratos" type="number" placeholder="Carbohidratos" />
    <input name="proteinas" type="number" placeholder="Proteínas" />
    <input name="grasas" type="number" placeholder="Grasas" />
    <select name="categoria_id">
        <option value="0">Seleccionar categoría</option>
        <?php foreach ($cats as $c): ?>
            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="file" name="imagen" accept="image/*" />
    <button type="submit">Subir receta</button>
</form>
</body>
</html>
