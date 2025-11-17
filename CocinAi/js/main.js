document.addEventListener('DOMContentLoaded', function() {
    // Botones de autenticación
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');

    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            // Redirigir a página de login
            window.location.href = 'pages/login.html';
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener('click', function() {
            // Redirigir a página de signup
            window.location.href = 'pages/signup.html';
        });
    }

    // Botón de búsqueda
    const searchBtn = document.querySelector('.hero-search .btn-primary');
    const searchInput = document.querySelector('.search-input');

    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `pages/recipes.html?search=${encodeURIComponent(query)}`;
            }
        });
    }

    // Botón CTA
    const ctaBtn = document.querySelector('.cta .btn-large');
    if (ctaBtn) {
        ctaBtn.addEventListener('click', function() {
            window.location.href = 'pages/signup.html';
        });
    }

    // Comienza ahora scroll o redirección
    const beginBtn = document.querySelector('.cta .btn-large');
    if (beginBtn) {
        beginBtn.addEventListener('click', function() {
            window.location.href = 'pages/recipes.html';
        });
    }
});
