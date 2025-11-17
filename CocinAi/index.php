<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CocinAi - Recetas Inteligentes</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-main">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="logo">
                    <span class="logo-icon">🍳</span>
                    <h1><a href="index.php" style="text-decoration: none; color: inherit;">CocinAi</a></h1>
                </div>
                <nav class="nav-header">
                    <a href="index.php" class="nav-link active">Inicio</a>
                    <a href="pages/recipes.php" class="nav-link">Recetas</a>
                    <a href="#about" class="nav-link">Acerca de</a>
                </nav>
                <div class="auth-buttons">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <button class="btn btn-login" id="loginBtn">Iniciar sesión</button>
                        <button class="btn btn-signup" id="signupBtn">Registrarse</button>
                    <?php else: ?>
                        <a href="pages/profile.php" class="btn btn-login">Mi Perfil</a>
                        <a href="pages/logout.php" class="btn btn-signup">Cerrar sesión</a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <h2 class="hero-title">Descubre Recetas Inteligentes</h2>
                <p class="hero-subtitle">Crea platos deliciosos con la ayuda de inteligencia artificial</p>
                <div class="hero-search">
                    <input type="text" class="search-input" id="searchInput" placeholder="Busca por ingrediente o plato...">
                    <button class="btn btn-primary" id="searchBtn">Buscar</button>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">⭐</div>
                    <h3>Recetas Verificadas</h3>
                    <p>Accede a miles de recetas probadas y calificadas por nuestra comunidad</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h3>Recomendaciones IA</h3>
                    <p>Obtén sugerencias personalizadas basadas en tus gustos y disponibilidad</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">📋</div>
                    <h3>Listas de Compras</h3>
                    <p>Genera automáticamente listas de ingredientes para tus recetas</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">❤️</div>
                    <h3>Tus Favoritas</h3>
                    <p>Guarda y organiza tus recetas favoritas en un solo lugar</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta">
            <div class="cta-content">
                <h2>¿Listo para cocinar?</h2>
                <p>Únete a miles de usuarios que ya están disfrutando de recetas inteligentes</p>
                <button class="btn btn-primary btn-large" id="ctaBtn">Comienza Ahora</button>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer">
            <p>&copy; 2024 CocinAi. Todos los derechos reservados.</p>
            <div class="footer-links">
                <a href="#privacidad">Privacidad</a>
                <a href="#terminos">Términos</a>
                <a href="#contacto">Contacto</a>
            </div>
        </footer>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>