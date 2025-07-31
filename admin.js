document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    function applySidebarState(isCollapsed) {
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            sidebarToggle.querySelector('i').classList.remove('bi-chevron-left');
            sidebarToggle.querySelector('i').classList.add('bi-chevron-right');
            mainContent.classList.add('collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            sidebarToggle.querySelector('i').classList.remove('bi-chevron-right');
            sidebarToggle.querySelector('i').classList.add('bi-chevron-left');
            mainContent.classList.remove('collapsed');
        }
    }

    // Inicialización de tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Inicialización de popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});