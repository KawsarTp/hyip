document.addEventListener('DOMContentLoaded', () => {
    const menuButton = document.querySelector('[data-menu-button]');
    const sidebar = document.querySelector('[data-sidebar]');
    const backdrop = document.querySelector('[data-sidebar-backdrop]');

    const closeSidebar = () => {
        sidebar?.classList.add('-translate-x-full');
        backdrop?.classList.add('pointer-events-none', 'opacity-0');
    };

    menuButton?.addEventListener('click', () => {
        sidebar?.classList.toggle('-translate-x-full');
        backdrop?.classList.toggle('pointer-events-none');
        backdrop?.classList.toggle('opacity-0');
    });

    backdrop?.addEventListener('click', closeSidebar);
});
