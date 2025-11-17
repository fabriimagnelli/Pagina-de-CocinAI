<?php
session_start();
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

// Comprobar si existe la columna usuario_id
$has_user_col = false;
try {
    $colRes = $conn->query("SHOW COLUMNS FROM Receta LIKE 'usuario_id'");
    if ($colRes && $colRes->num_rows > 0) $has_user_col = true;
} catch (Exception $e) {
    // ignorar
}

// Obtener categorías
$categorias = [];
try {
    $cRes = $conn->query("SELECT id, nombre FROM Categoria ORDER BY nombre ASC");
    if ($cRes) while ($c = $cRes->fetch_assoc()) $categorias[] = $c;
} catch (Exception $e) {
    // ignorar
}

function fetch_recipes_by_category($conn, $cat_id, $has_user_col, $limit = 12) {
    $rows = [];
    if ($has_user_col) {
        $sql = "SELECT r.id, r.nombre, r.descripcion, r.imagen, u.nombre_usuario 
                FROM Receta r LEFT JOIN Usuario u ON r.usuario_id = u.id
                WHERE r.categoria_id = ? ORDER BY r.id DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cat_id, $limit);
    } else {
        $sql = "SELECT id, nombre, descripcion, imagen FROM Receta WHERE categoria_id = ? ORDER BY id DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $cat_id, $limit);
    }
    if ($stmt) {
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) $rows[] = $r;
        $stmt->close();
    }
    return $rows;
}

// Opcional: obtener todas las recetas recientes (para sección "Todas")
$all_recipes = [];
try {
    if ($has_user_col) {
        $sql = "SELECT r.id, r.nombre, r.descripcion, r.imagen, u.nombre_usuario 
                FROM Receta r LEFT JOIN Usuario u ON r.usuario_id = u.id
                ORDER BY r.id DESC LIMIT 24";
    } else {
        $sql = "SELECT id, nombre, descripcion, imagen FROM Receta ORDER BY id DESC LIMIT 24";
    }
    $res = $conn->query($sql);
    if ($res) while ($row = $res->fetch_assoc()) $all_recipes[] = $row;
} catch (Exception $e) {
    // ignorar
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title>Recetas - CocinAI</title>
	<link rel="stylesheet" href="styles.css" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css"
			integrity="sha512-Kc323vGBEqzTmouAECnVceyQqyqdsSiqLQISBL29aUW4U/M7pSPA/gEUZQqv1cwx4OnYxTxve5UMg5GT6L4JJg=="
			crossorigin="anonymous" referrerpolicy="no-referrer" />
	<style>
	.container { max-width:1100px; margin:20px auto; padding:0 20px; }
	header { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
	.grid { display:flex; flex-wrap:wrap; gap:16px; margin-top:12px; }
	.card { width:240px; background:#fff; border-radius:8px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,0.06); }
	.card img { width:100%; height:140px; object-fit:cover; display:block; }
	.card .body { padding:10px; }
	.section { margin-top:22px; }
	.section h2 { margin-bottom:8px; }
	.meta { font-size:12px; color:#888; margin-bottom:6px; }
	</style>
</head>
<body>
	<div class="container">
		<header>
			<div><a href="index.php" style="font-weight:700; font-size:20px; text-decoration:none; color:#111;">CocinAI</a></div>
			<nav>
				<a href="index.php">Inicio</a>
				<a href="recipes.php">Recetas</a>
				<?php if (!empty($_SESSION['user_id'])): ?>
					<a href="upload_recipe.php">Subir receta</a>
				<?php endif; ?>
			</nav>
		</header>

		<section class="section">
			<h2>Todas las recetas recientes</h2>
			<?php if (empty($all_recipes)): ?>
				<p>No hay recetas publicadas aún.</p>
			<?php else: ?>
				<div class="grid">
					<?php foreach ($all_recipes as $r): ?>
						<div class="card">
							<?php if (!empty($r['imagen']) && file_exists(__DIR__ . '/' . $r['imagen'])): ?>
								<img src="<?php echo htmlspecialchars($r['imagen']); ?>" alt="<?php echo htmlspecialchars($r['nombre']); ?>">
							<?php else: ?>
								<img src="https://via.placeholder.com/400x300?text=Sin+imagen" alt="sin imagen">
							<?php endif; ?>
							<div class="body">
								<div class="meta">
									<?php if (!empty($r['nombre_usuario'])): ?>
										<?php echo htmlspecialchars($r['nombre_usuario']); ?> —
									<?php endif; ?>
									ID <?php echo intval($r['id']); ?>
								</div>
								<h4><?php echo htmlspecialchars($r['nombre']); ?></h4>
								<p><?php echo htmlspecialchars(substr($r['descripcion'] ?? '', 0, 100)); ?><?php echo (strlen($r['descripcion'] ?? '')>100)?'...':''; ?></p>
								<a href="recipe.php?id=<?php echo intval($r['id']); ?>">Ver receta →</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php foreach ($categorias as $cat): ?>
			<?php
				$items = fetch_recipes_by_category($conn, intval($cat['id']), $has_user_col, 8);
				if (empty($items)) continue;
			?>
			<section class="section">
				<h2><?php echo htmlspecialchars($cat['nombre']); ?></h2>
				<div class="grid">
					<?php foreach ($items as $r): ?>
						<div class="card">
							<?php if (!empty($r['imagen']) && file_exists(__DIR__ . '/' . $r['imagen'])): ?>
								<img src="<?php echo htmlspecialchars($r['imagen']); ?>" alt="<?php echo htmlspecialchars($r['nombre']); ?>">
							<?php else: ?>
								<img src="https://via.placeholder.com/400x300?text=Sin+imagen" alt="sin imagen">
							<?php endif; ?>
							<div class="body">
								<div class="meta">
									<?php if (!empty($r['nombre_usuario'])): ?>
										<?php echo htmlspecialchars($r['nombre_usuario']); ?> —
									<?php endif; ?>
									ID <?php echo intval($r['id']); ?>
								</div>
								<h4><?php echo htmlspecialchars($r['nombre']); ?></h4>
								<p><?php echo htmlspecialchars(substr($r['descripcion'] ?? '', 0, 100)); ?><?php echo (strlen($r['descripcion'] ?? '')>100)?'...':''; ?></p>
								<a href="recipe.php?id=<?php echo intval($r['id']); ?>">Ver receta →</a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endforeach; ?>

		<footer style="margin-top:28px; text-align:center; color:#777;">&copy; <?php echo date('Y'); ?> CocinAI</footer>
	</div>
</body>
</html>
