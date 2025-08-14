document.addEventListener('DOMContentLoaded', function () {
    const menuToggle = document.querySelector('.menu-toggle');
    const nav = document.querySelector('header');
    
    menuToggle.addEventListener('click', function () {
        nav.classList.toggle('toggled');
        menuToggle.classList.toggle('open');
        
        const isExpanded = menuToggle.getAttribute('aria-expanded') === 'true' || false;
        menuToggle.setAttribute('aria-expanded', !isExpanded);
    });
});