<?php
session_start();
require 'config.php';

if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header('Location: panel_control.php');
    exit;
}

// Inicializar variables
$error = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = trim($_POST['contrasena'] ?? '');

    // Validaciones básicas
    if (empty($usuario) || empty($contrasena)) {
        $error = "Usuario y contraseña son obligatorios";
    } else {
        try {
            // Consulta preparada más segura
            $sql = "SELECT id_usuario, nombre, contraseña, id_rol FROM usuarios WHERE usuario = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
            
            $stmt->bind_param("s", $usuario);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            if ($resultado->num_rows === 1) {
                $usuario_datos = $resultado->fetch_assoc();
                
                // Verificación de contraseña con hash
                if (password_verify($contrasena, $usuario_datos['contraseña'])) {
                    // Regenerar ID de sesión por seguridad
                    session_regenerate_id(true);
                    
                    // Establecer variables de sesión
                    $_SESSION = [
                        'id_usuario' => $usuario_datos['id_usuario'],
                        'nombre' => $usuario_datos['nombre'],
                        'usuario' => $usuario,
                        'id_rol' => $usuario_datos['id_rol'],
                        'loggedin' => true,
                        'ultimo_acceso' => time()
                    ];
                    
                    $sql_update = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?";
                    $stmt_update = $conexion->prepare($sql_update);
                    $stmt_update->bind_param("i", $usuario_datos['id_usuario']);
                    $stmt_update->execute();

                    // Redirigir al panel de control
                    header('Location: panel_control.php');
                    exit;
                } else {
                    $error = "Credenciales incorrectas";
                }
            } else {
                $error = "Usuario no encontrado";
            }
        } catch (Exception $e) {
            $error = "Error en el sistema: " . $e->getMessage();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4cc9f0;
            --secondary-color: #4361ee;
            --accent-color: #f72585;
            --dark-color: #212529;
            --light-color: #f8f9fa;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border-top: 4px solid var(--primary-color);
            background: white;
            width: 100%;
            max-width: 400px;
            margin: auto;
        }
        
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control-login {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control-login:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 201, 240, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            width: 100%;
            color: white;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .input-group-text-login {
            background-color: #f1f3f5;
            border: 1px solid #e0e0e0;
            border-right: none;
            border-radius: 8px 0 0 8px !important;
        }
        
        .login-logo {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: white;
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem;
            font-size: 0.9rem;
            color: #6c757d;
            border-top: 1px solid #eee;
        }
        
        .login-links {
            margin-top: 1rem;
            text-align: center;
        }
        
        .login-links a {
            color: var(--secondary-color);
            text-decoration: none;
        }
        
        .login-links a:hover {
            text-decoration: underline;
        }
    </style>
    </head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 col-xl-5">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-logo">
                            <i class="fas fa-lock"></i>
                        </div>
                        <h2 class="mb-0">Iniciar Sesión</h2>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="mt-4">
                            <div class="mb-4">
                                <label for="usuario" class="form-label fw-bold">Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-login">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" class="form-control form-control-login" id="usuario" name="usuario" placeholder="Ingrese su usuario" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="contrasena" class="form-label fw-bold">Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text input-group-text-login">
                                        <i class="fas fa-key"></i>
                                    </span>
                                    <input type="password" class="form-control form-control-login" id="contrasena" name="contrasena" placeholder="Ingrese su contraseña" required>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mb-3">
                                <button type="submit" class="btn btn-login">
                                    <i class="fas fa-sign-in-alt me-2"></i> Ingresar
                                </button>
                            </div>
                        </form>
                        
                        <div class="login-links">
                            <a href="registro.php">¿No tienes cuenta? Regístrate</a>
                        </div>
                    </div>
                    <div class="login-footer">
                        Sistema de Tareas © <?= date('Y') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle con Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>