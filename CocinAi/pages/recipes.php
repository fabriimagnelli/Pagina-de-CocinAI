<?php
session_start();
include('../config/db.php');

$search = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// ...existing code...

if ($search) {
    $query = "SELECT * FROM recipes WHERE title LIKE ? OR description LIKE ? LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$search%";
    $stmt->bind_param("ssii", $searchTerm, $searchTerm, $limit, $offset);
} else {
    $query = "SELECT * FROM recipes LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recetas - CocinAi</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container-main">
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">🍳</span>
                    <h1><a href="../index.php" style="text-decoration: none; color: inherit;">CocinAi</a></h1>
                </div>
                <nav class="nav-header">
                    <a href="../index.php" class="nav-link">Inicio</a>
                    <a href="recipes.php" class="nav-link active">Recetas</a>
                    <a href="#about" class="nav-link">Acerca de</a>
                </nav>
                <div class="auth-buttons">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-login" id="loginBtn">Iniciar sesión</button>
                        <button class="btn btn-signup" id="signupBtn">Registrarse</button>
                    <?php else: ?>
                        <a href="profile.php" class="btn btn-login">Mi Perfil</a>
                        <a href="logout.php" class="btn btn-signup">Cerrar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <section class="recipes-section">
            <div class="recipes-container">
                <h2>Recetas</h2>
                <div class="search-bar">
                    <input type="text" id="searchInput" placeholder="Buscar recetas..." value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn btn-primary" id="searchBtn">Buscar</button>
                </div>

                <div class="recipes-grid">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($recipe = $result->fetch_assoc()): ?>
                            <div class="recipe-card">
                                <div class="recipe-header">
                                    <h3><?php echo htmlspecialchars($recipe['title']); ?></h3>
                                </div>
                                <p class="recipe-description"><?php echo htmlspecialchars(substr($recipe['description'], 0, 100)) . '...'; ?></p>
                                <div class="recipe-info">
                                    <span>⏱️ <?php echo $recipe['cook_time']; ?> min</span>
                                    <span>👥 <?php echo $recipe['servings']; ?> porciones</span>
                                </div>
                                <a href="recipe-detail.php?id=<?php echo $recipe['id']; ?>" class="btn btn-primary btn-small">Ver Receta</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="no-results">No se encontraron recetas.</p>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <footer class="footer">
            <p>&copy; 2024 CocinAi. Todos los derechos reservados.</p>
            <div class="footer-links">
                <a href="#privacidad">Privacidad</a>
                <a href="#terminos">Términos</a>
                <a href="#contacto">Contacto</a>
            </div>
        </footer>
    </div>

    <script>
        document.getElementById('searchBtn').addEventListener('click', function() {
            const query = document.getElementById('searchInput').value.trim();
            if (query) {
                window.location.href = `recipes.php?search=${encodeURIComponent(query)}`;
            }
        });

        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('searchBtn').click();
            }
        });

        const loginBtn = document.getElementById('loginBtn');
        const signupBtn = document.getElementById('signupBtn');
        if (loginBtn) loginBtn.addEventListener('click', () => window.location.href = 'login.php');
        if (signupBtn) signupBtn.addEventListener('click', () => window.location.href = 'signup.php');
    </script>
</body>
</html>