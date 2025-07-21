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
<html>
<head>
    <title>Inicio de Sesión</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="estilos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="text-center">
    <main class="form-signin">
        <form method="POST">
            <h1 class="h3 mb-4 fw-normal">Iniciar Sesión</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger login-alert"><?= $error ?></div>
            <?php endif; ?>

            <div class="form-floating">
                <input type="text" class="form-control" id="usuario" name="usuario" placeholder="Usuario" required>
                <label for="usuario">Usuario</label>
            </div>
            <div class="form-floating">
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Contraseña" required>
                <label for="contrasena">Contraseña</label>
            </div>

            <button class="w-100 btn btn-lg btn-primary btn-login" type="submit">
                <i class="bi bi-box-arrow-in-right"></i>  Ingresar
            </button>
            
            <div class="login-links">
                <a href="registro.php">¿No tienes cuenta? Regístrate</a>
            </div>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>