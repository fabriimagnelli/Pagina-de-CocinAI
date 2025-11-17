document.addEventListener('DOMContentLoaded', function() {
    const loginBtn = document.getElementById('loginBtn');
    const signupBtn = document.getElementById('signupBtn');
    const searchBtn = document.getElementById('searchBtn');
    const searchInput = document.getElementById('searchInput');
    const ctaBtn = document.getElementById('ctaBtn');

    if (loginBtn) {
        loginBtn.addEventListener('click', function() {
            window.location.href = 'pages/login.php';
        });
    }

    if (signupBtn) {
        signupBtn.addEventListener('click', function() {
            window.location.href = 'pages/signup.php';
        });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = `pages/recipes.php?search=${encodeURIComponent(query)}`;
            } else {
                window.location.href = 'pages/recipes.php';
            }
        });
    }

    // Permitir buscar con Enter
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });
    }

    if (ctaBtn) {
        ctaBtn.addEventListener('click', function() {
            window.location.href = 'pages/recipes.php';
        });
    }
});
