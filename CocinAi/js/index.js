// MENU MOBILE
const btnCloseMenu = document.querySelector('.btn-close');
const btnOpenMenu = document.querySelector('.btn-menu-responsive');
const menuMobile = document.querySelector('.menu-mobile');

if (btnOpenMenu && menuMobile) {
	btnOpenMenu.addEventListener('click', () => {
		menuMobile.classList.add('active');
	});
}

if (btnCloseMenu && menuMobile) {
	btnCloseMenu.addEventListener('click', () => {
		menuMobile.classList.remove('active');
	});
}