<div class="sidebar bg-primary text-white">
    <div class="sidebar-brand d-flex align-items-center justify-content-center p-4">
        <div class="sidebar-brand-icon">
            <i class="bi bi-shield-lock"></i>
        </div>
        <div class="sidebar-brand-text mx-3">Panel de Administrador</div>
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
                    <div class="bg-dark py-2">
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
                    <div class="bg-dark py-2">
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