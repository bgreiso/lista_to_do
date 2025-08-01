<div class="sidebar bg-primary text-white">
    <div class="sidebar-brand d-flex align-items-center justify-content-center p-4">
        <div class="sidebar-brand-icon">
            <h4><i class="bi bi-ui-checks-grid"></i></h4>
        </div>
        <div class="sidebar-brand-text mx-3">Panel de Control</div>
    </div>
    
    <hr class="sidebar-divider my-0">
    
    <div class="nav-items-container">
        <div class="sidebar-heading p-3">
            Navegación Principal
        </div>
        
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" href="panel_control.php">
                    <i class="bi bi-diagram-3-fill"></i>
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
                        <i class="bi bi-person-badge-fill"></i>
                        <span>Usuarios</span>
                    </a>
                <div id="collapseUsuarios" class="collapse" data-bs-parent=".nav-items-container">
                    <div class="bg-dark bg-gradient py-2">
                        <a href="listar.php" class="nav-link text-white">
                            <i class="bi bi-person-lines-fill"></i> Listado
                        </a>
                        <a href="crear.php" class="nav-link text-white">
                            <i class="bi bi-person-plus"></i> Crear
                        </a>
                    </div>
                </div>
            </li>
            <?php endif; ?>
            
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapseTareas" aria-expanded="false">
                    <i class="bi bi-ui-checks"></i>
                    <span>Tareas</span>
                </a>
                <div id="collapseTareas" class="collapse" data-bs-parent=".nav-items-container">
                    <div class="bg-dark bg-gradient py-2">
                        <a href="tablero.php" class="nav-link text-white">
                            <i class="bi bi-grid-3x2-gap"></i> Tablero
                        </a>
                        <a href="todas.php" class="nav-link text-white">
                            <i class="bi bi-list-ul"></i> Lista
                        </a>
                        <a href="agregar_tarea.php" class="nav-link text-white">
                            <i class="bi bi-file-earmark-plus"></i> Crear Tarea
                        </a>
                    </div>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="reportes.php">
                    <i class="bi bi-clipboard-data"></i>
                    Reportes
                </a>
            </li>
        </ul>
    </div>
</div>