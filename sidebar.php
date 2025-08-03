<div class="sidebar">
    <div class="sidebar-top-wrapper">
        <div class="sidebar-top">
            <a href="#" class="logo__wrapper">
                <i class="bi bi-ui-checks-grid fs-4"></i>
                <span class="hide company-name">Panel de Control</span>
            </a>
        </div>
        <button class="expand-btn" type="button">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-labelledby="exp-btn" role="img">
                <title id="exp-btn">Expandir/Contraer Menú</title>
                <path d="M6.00979 2.72L10.3565 7.06667C10.8698 7.58 10.8698 8.42 10.3565 8.93333L6.00979 13.28"
                    stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
    </div>

    <div class="sidebar-links">
        <ul>
            <li>
                <a href="panel_control.php" title="Dashboard" class="tooltip <?= basename($_SERVER['PHP_SELF']) == 'panel_control.php' ? 'active' : '' ?>">
                    <i class="bi bi-diagram-3-fill fs-5"></i>
                    <span class="link hide">Dashboard</span>
                    <span class="tooltip__content">Dashboard</span>
                </a>
            </li>
            
            <li class="separator-container">
                <div class="separator"></div>
            </li>
            
            <li class="sidebar-heading hide">
                Gestión
            </li>
            
            <?php if (esAdmin()): ?>
            <li>
                <a href="#collapseUsuarios" title="Usuarios" class="tooltip" data-bs-toggle="collapse">
                    <i class="bi bi-person-badge-fill fs-5"></i>
                    <span class="link hide">Usuarios</span>
                    <span class="tooltip__content">Usuarios</span>
                </a>
                <div id="collapseUsuarios" class="collapse">
                    <div class="submenu">
                        <a href="listar.php" class="<?= basename($_SERVER['PHP_SELF']) == 'listar.php' ? 'active' : '' ?>">
                            <h5><i class="bi bi-person-lines-fill"></i></h5>
                            <span class="link hide">Listado</span>
                        </a>
                        <a href="crear.php" class="<?= basename($_SERVER['PHP_SELF']) == 'crear.php' ? 'active' : '' ?>">
                            <h5><i class="bi bi-person-plus"></i></h5>
                            <span class="link hide">Crear</span>
                        </a>
                    </div>
                </div>
            </li>
            <?php endif; ?>
            
            <li>
                <a href="#collapseTareas" title="Tareas" class="tooltip" data-bs-toggle="collapse">
                    <i class="bi bi-ui-checks fs-5"></i>
                    <span class="link hide">Tareas</span>
                    <span class="tooltip__content">Tareas</span>
                </a>
                <div id="collapseTareas" class="collapse">
                    <div class="submenu">
                        <a href="tablero.php" class="<?= basename($_SERVER['PHP_SELF']) == 'tablero.php' ? 'active' : '' ?>">
                            <h5><i class="bi bi-grid-3x2-gap"></i></h5> 
                            <span class="link hide">Tablero</span>
                        </a>
                        <a href="todas.php" class="<?= basename($_SERVER['PHP_SELF']) == 'todas.php' ? 'active' : '' ?>">
                            <h5><i class="bi bi-list-ul"></i></h5> 
                            <span class="link hide">Lista</span>
                        </a>
                        <a href="agregar_tarea.php" class="<?= basename($_SERVER['PHP_SELF']) == 'agregar_tarea.php' ? 'active' : '' ?>">
                            <h5><i class="bi bi-file-earmark-plus"></i></h5>
                            <span class="link hide">Crear Tarea</span>
                        </a>
                    </div>
                </div>
            </li>

            <li>
                <a href="reportes.php" title="Reportes" class="tooltip <?= basename($_SERVER['PHP_SELF']) == 'reportes.php' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-data fs-5"></i>
                    <span class="link hide">Reportes</span>
                    <span class="tooltip__content">Reportes</span>
                </a>
            </li>
        </ul>
    </div>
    
    <div class="separator separator--top"></div>
    
    <div class="sidebar__profile">
        <div class="avatar__wrapper">
            <img class="avatar" src="icon_profile.webp" alt="Foto de perfil" style="width:40px;height:40px;object-fit:cover">
        </div>
        <div class="avatar__name hide">
            <div class="user-name">
                <?php
                $nombreUsuario = 'Invitado';
                if (isset($_SESSION['usuario'])) {
                    $nombreUsuario = htmlspecialchars($_SESSION['usuario']);
                } elseif (isset($_SESSION['id_usuario'])) {
                    $nombreUsuario = 'Usuario ' . (int)$_SESSION['id_usuario'];
                }
                echo $nombreUsuario;
                ?>
            </div>
            <div class="email"><?= esAdmin() ? 'Administrador' : 'Usuario' ?></div>
        </div>
        <a href="cerrar_sesion.php" class="logout hide" title="Cerrar sesión">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>
</div>