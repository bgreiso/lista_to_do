<?php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/favicon.ico">

</head>
<body class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Barra de navegación superior -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Barra de búsqueda-->
            <form class="d-none d-sm-inline-block form-inline mr-auto ml-md-3 my-2 my-md-0 mw-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small" placeholder="Buscar..." aria-label="Search">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button">
                            <i class="bi bi-search"></i>
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Menú superior derecho -->
            <ul class="navbar-nav ml-auto">
                <!-- Notificaciones -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <span class="badge bg-danger badge-counter">3+</span>
                    </a>
                </li>
                
                <!-- Mensajes -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle" href="#" id="messagesDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-envelope"></i>
                        <span class="badge bg-danger badge-counter">7</span>
                    </a>
                </li>
                
                <!-- Usuario -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <?php
                        $nombreUsuario = isset($_SESSION['usuario']) ? htmlspecialchars($_SESSION['usuario']) : 'Administrador';
                        ?>
                        <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= $nombreUsuario ?></span>
                        <img class="img-profile rounded-circle" src="../assets/css/icon_profile.webp" width="32">
                    </a>
                    <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in">
                        <a class="dropdown-item" href="perfil.php">
                            <i class="bi bi-person"></i> Perfil
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="../cerrar_sesion.php" data-bs-toggle="modal" data-bs-target="#logoutModal">
                            <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Contenido de la página -->
        <div class="container-fluid">