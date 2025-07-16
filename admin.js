// Toggle del sidebar
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.body.classList.toggle('sidebar-toggled');
        document.querySelector('.sidebar').classList.toggle('toggled');
        
        if (document.querySelector('.sidebar').classList.contains('toggled')) {
            const collapses = document.querySelectorAll('.sidebar .collapse');
            collapses.forEach(function(collapse) {
                collapse.classList.remove('show');
            });
        }
    });
    
    // Cerrar el sidebar cuando se hace clic fuera en móviles
    document.body.addEventListener('click', function(e) {
        const isMobile = window.innerWidth < 768;
        const sidebar = document.querySelector('.sidebar');
        const toggleBtn = document.getElementById('sidebarToggle');
        
        if (isMobile && !e.target.closest('.sidebar') && !e.target.closest('#sidebarToggle') && !sidebar.classList.contains('toggled')) {
            sidebar.classList.add('toggled');
        }
    });
    
    // Inicializar DataTables
    if (document.getElementById('dataTable')) {
        new DataTable('#dataTable', {
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
            }
        });
    }
    
    // Activar tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});