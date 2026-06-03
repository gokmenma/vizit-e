document.addEventListener('DOMContentLoaded', function() {
    const menuTriggers = document.querySelectorAll('.mobile-bottom-nav__item[data-menu-id]');
    const overlay = document.querySelector('.menu-overlay');
    const closeButtons = document.querySelectorAll('.popup-menu__close');

    // Menüyü açan fonksiyon
    const openMenu = (menuId) => {
        const menu = document.getElementById(menuId);
        if (menu) {
            menu.classList.add('active');
            overlay.classList.add('active');
        }
    };

    // Tüm menüleri kapatan fonksiyon
    const closeAllMenus = () => {
        document.querySelectorAll('.popup-menu.active').forEach(menu => {
            menu.classList.remove('active');
        });
        overlay.classList.remove('active');
    };

    // Alt menüdeki butonlara tıklama olayı ekle
    menuTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(event) {
            event.preventDefault();
            const menuId = this.getAttribute('data-menu-id');
            openMenu(menuId);
        });
    });

    // Kapatma butonlarına tıklama olayı ekle
    closeButtons.forEach(button => {
        button.addEventListener('click', closeAllMenus);
    });

    // Overlay'a (karartılmış alana) tıklayınca menüyü kapat
    overlay.addEventListener('click', closeAllMenus);
});