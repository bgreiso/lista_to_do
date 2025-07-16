<?php 
include 'config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    // Consulta con validación de error
    $sql = "SELECT `id_usuario`, `contraseña`, `id_rol` FROM `usuarios` WHERE `usuario` = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        die("Error en la consulta: " . $conexion->error);
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario_datos = $resultado->fetch_assoc();
        if (password_verify($contrasena, $usuario_datos['contraseña'])) {
            $_SESSION['id_usuario'] = $usuario_datos['id_usuario'];
            $_SESSION['usuario'] = $usuario;
            $_SESSION['id_rol'] = $usuario_datos['id_rol'];
            header('Location: panel_control.php');
            exit;
        }
    }
    $error = "Usuario o contraseña incorrectos";
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