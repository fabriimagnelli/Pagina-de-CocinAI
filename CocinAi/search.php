<?php
session_start();
require_once __DIR__ . '/Nueva carpeta/BD/conexion.php';

// Parámetros de búsqueda
$query = trim($_GET['q'] ?? '');
$categoria = intval($_GET['categoria'] ?? 0);
$tiempo = intval($_GET['tiempo'] ?? 0);
$dificultad = trim($_GET['dificultad'] ?? '');

// Obtener categorías para el filtro
$categorias = [];
$catQuery = $conn->query("SELECT id, nombre FROM Categoria ORDER BY nombre");
while ($cat = $catQuery->fetch_assoc()) {
    $categorias[] = $cat;
}

// Construir consulta
$where = [];
$params = [];
$types = '';

if (!empty($query)) {
    $where[] = "(r.nombre LIKE ? OR r.descripcion LIKE ? OR r.ingredientes LIKE ?)";
    $queryParam = "%$query%";
    $params[] = &$queryParam;
    $params[] = &$queryParam;
    $params[] = &$queryParam;
    $types .= 'sss';
}

if ($categoria > 0) {
    $where[] = "r.categoria_id = ?";
    $params[] = &$categoria;
    $types .= 'i';
}

$sql = "SELECT r.*, u.nombre_usuario as autor, c.nombre as categoria 
        FROM Receta r 
        LEFT JOIN Usuario u ON r.usuario_id = u.id 
        LEFT JOIN Categoria c ON r.categoria_id = c.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY r.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$resultados = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Buscar Recetas - CocinAI</title>
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

        <form class="search-form" method="get">
            <div class="search-grid">
                <div class="form-field">
                    <label>Buscar</label>
                    <input type="text" name="q" value="<?php echo htmlspecialchars($query); ?>" 
                           placeholder="Nombre, ingredientes...">
                </div>
                
                <div class="form-field">
                    <label>Categoría</label>
                    <select name="categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                <?php echo $categoria == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-field">
                    <button type="submit" class="btn">Buscar</button>
                </div>
            </div>
        </form>

        <div class="search-results">
            <h2>Resultados de búsqueda</h2>
            <?php if (empty($resultados)): ?>
                <p>No se encontraron recetas que coincidan con tu búsqueda.</p>
            <?php else: ?>
                <div class="grid">
                    <?php foreach ($resultados as $r): ?>
                        <article class="card">
                            <?php if (!empty($r['imagen']) && file_exists(__DIR__ . '/' . $r['imagen'])): ?>
                                <img src="<?php echo htmlspecialchars($r['imagen']); ?>" 
                                     alt="<?php echo htmlspecialchars($r['nombre']); ?>">
                            <?php endif; ?>
                            <div class="body">
                                <h4><?php echo htmlspecialchars($r['nombre']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars(substr($r['descripcion'], 0, 100)); ?>...</p>
                                <div class="meta">
                                    <span>Por <?php echo htmlspecialchars($r['autor']); ?></span>
                                    <?php if (!empty($r['categoria'])): ?>
                                        <span><?php echo htmlspecialchars($r['categoria']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="recipe.php?id=<?php echo $r['id']; ?>" class="btn-sm">Ver receta</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
