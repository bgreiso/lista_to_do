<?php
require_once 'auth.php';
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
    <link href="admin.css" rel="stylesheet">
    <link rel="icon" href="../assets/img/favicon.ico">
</head>
<body class="d-flex">
    <?php include 'sidebar.php'; ?>
    
    <div class="main-content">
        <!-- Barra de navegación superior mejorada -->
        <nav class="navbar navbar-expand navbar-light bg-primary topbar mb-4 static-top shadow">
            <!-- Botón para mostrar/ocultar sidebar en móviles -->
            <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3">
                <i class="bi bi-list"></i>
            </button>
            
            <!-- Título del panel -->
            <div class="d-none d-md-inline-block me-auto">
                <h1 class="h4 mb-0 text-gray-800">
                    <i class="bi bi-speedometer2 me-2"></i> Gestor de Tareas
                </h1>
            </div>
            
            <!-- Barra de búsqueda mejorada -->
            <form class="d-none d-sm-inline-block form-inline mx-4 my-0 mw-100 navbar-search">
                <div class="input-group">
                    <input type="text" class="form-control bg-light border-0 small" placeholder="Buscar..." 
                           aria-label="Search" aria-describedby="basic-addon2">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
            
            <!-- Menú superior derecho mejorado -->
            <ul class="navbar-nav ms-auto">
                <!-- Notificaciones -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="alertsDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-bell fs-5 text-gray-500"></i>
                        <span class="badge bg-danger badge-counter">3+</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in py-0" 
                         aria-labelledby="alertsDropdown">
                        <h6 class="dropdown-header bg-primary text-white py-2">
                            Centro de Notificaciones
                        </h6>
                        <a class="dropdown-item d-flex align-items-center small" href="#">
                            <div class="me-3">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                            </div>
                            <div>
                                <div class="fw-bold">Nueva solicitud</div>
                                <div class="text-muted">Hace 5 minutos</div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small text-gray-500" href="#">Mostrar todas</a>
                    </div>
                </li>
                
                <!-- Mensajes -->
                <li class="nav-item dropdown no-arrow mx-1">
                    <a class="nav-link dropdown-toggle position-relative" href="#" id="messagesDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-envelope fs-5 text-gray-500"></i>
                        <span class="badge bg-danger badge-counter">7</span>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in py-0" 
                         aria-labelledby="messagesDropdown">
                        <h6 class="dropdown-header bg-primary text-white py-2">
                            Centro de Mensajes
                        </h6>
                        <a class="dropdown-item d-flex align-items-center small" href="#">
                            <div class="me-3">
                                <img class="rounded-circle" src="icon_profile.webp" width="40">
                            </div>
                            <div>
                                <div class="fw-bold">Juan Pérez</div>
                                <div class="text-muted">¿Podemos reunirnos mañana?</div>
                            </div>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-center small text-gray-500" href="#">Leer todos los mensajes</a>
                    </div>
                </li>
                
                <!-- Usuario -->
                <li class="nav-item dropdown no-arrow">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                       role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="d-none d-lg-inline me-2 text-end">
                            <div class="fw-bold text-gray-800 small">
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
                            <div class="text-gray-600 small">
                                <?= esAdmin() ? 'Administrador' : 'Usuario' ?>
                            </div>
                        </div>
                        <img class="img-profile rounded-circle" src="icon_profile.webp" width="40">
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in py-0" 
                         aria-labelledby="userDropdown">
                        <a class="dropdown-item" href="perfil.php">
                            <i class="bi bi-person me-2"></i> Perfil
                        </a>
                        <a class="dropdown-item" href="#">
                            <i class="bi bi-gear me-2"></i> Configuración
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="cerrar_sesion.php">
                            <i class="bi bi-box-arrow-right me-2"></i> Cerrar sesión
                        </a>
                    </div>
                </li>
            </ul>
        </nav>
        
        <!-- Contenido de la página -->
        <div class="container-fluid">