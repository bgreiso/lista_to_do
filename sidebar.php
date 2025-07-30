<div class="sidebar bg-primary text-white collapsed" id="sidebar">
    <div class="sidebar-header d-flex align-items-center justify-content-between p-4">
        <div class="d-flex align-items-center">
            <div class="sidebar-brand-icon">
                <i class="bi bi-shield-lock fs-4"></i>
            </div>
            <div class="sidebar-brand-text mx-3">Panel de Administrador</div>
        </div>
        <button class="btn btn-sm btn-outline-light rounded-circle" id="sidebarToggle">
            <i class="bi bi-arrow-left-circle-fill"></i>
        </button>
    </div>
    
    <hr class="sidebar-divider my-0">
    
    <div class="nav-items-container">
        <div class="sidebar-heading p-3">
            Navegación Principal
        </div>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="panel_control.php">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <hr class="sidebar-divider">
            
            <div class="sidebar-heading p-3">
                Gestión
            </div>
            
            <?php if (esAdmin()): ?>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseUsuarios" aria-expanded="false">
                    <i class="bi bi-people"></i>
                    <span>Usuarios</span>
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div id="collapseUsuarios" class="collapse" data-bs-parent=".nav-items-container">
                    <div class="bg-primary py-2">
                        <a href="listar.php" class="nav-link text-white">
                            <i class="bi bi-list-ul"></i> Listar Usuarios
                        </a>
                        <a href="crear.php" class="nav-link text-white">
                            <i class="bi bi-person-plus"></i> Crear Usuario
                        </a>
                    </div>
                </div>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTareas" aria-expanded="false">
                    <i class="bi bi-list-task"></i>
                    <span>Tareas</span>
                    <i class="bi bi-chevron-down float-end"></i>
                </a>
                <div id="collapseTareas" class="collapse" data-bs-parent=".nav-items-container">
                    <div class="bg-primary py-2">
                        <a href="tablero.php" class="nav-link text-white">
                            <i class="bi bi-list-ul"></i> Tablero de Tareas
                        </a>
                        <a href="todas.php" class="nav-link text-white">
                            <i class="bi bi-list-ul"></i> Lista de Tareas
                        </a>
                        <a href="agregar_tarea.php" class="nav-link text-white">
                            <i class="bi bi-plus-circle"></i> Crear Tarea
                        </a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="reportes.php">
                    <i class="bi bi-people"></i>
                    Reportes
                </a>
            </li>

        </ul>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const themeToggle = document.getElementById('themeToggle'); // Get the theme toggle button
    const body = document.body;

    // Función para aplicar el estado del sidebar
    function applySidebarState(isCollapsed) {
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            sidebarToggle.querySelector('i').classList.remove('bi-chevron-left');
            sidebarToggle.querySelector('i').classList.add('bi-chevron-right');
        } else {
            sidebar.classList.remove('collapsed');
            sidebarToggle.querySelector('i').classList.remove('bi-chevron-right');
            sidebarToggle.querySelector('i').classList.add('bi-chevron-left');
        }
    }

    // Cargar el estado del sidebar guardado
    const savedSidebarState = localStorage.getItem('sidebarCollapsed');
    if (savedSidebarState !== null) { // Si hay un estado guardado
        applySidebarState(savedSidebarState === 'true'); // 'true' es un string, hay que convertirlo a booleano
    } else {
        // Si no hay un estado guardado, iniciar siempre colapsado (o el estado por defecto que prefieras)
        applySidebarState(true); 
    }
    
    // Manejar el click del botón del sidebar
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        const isCurrentlyCollapsed = sidebar.classList.contains('collapsed');
        applySidebarState(!isCurrentlyCollapsed); // Toggle state
        localStorage.setItem('sidebarCollapsed', !isCurrentlyCollapsed); // Guardar el nuevo estado
    });
    
    // Ajustar para dispositivos móviles
    function handleResize() {
        if (window.innerWidth <= 768) {
            applySidebarState(true); // Siempre colapsado en móviles
            localStorage.setItem('sidebarCollapsed', true); // Guardar el estado colapsado para móviles
        }
    }
    
    window.addEventListener('resize', handleResize);
    handleResize();


});
</script>