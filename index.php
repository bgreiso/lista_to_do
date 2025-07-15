<?php include 'config.php'; ?>

<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];

    $stmt = $conexion->prepare("SELECT id, contrasena FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 1) {
        $usuario_datos = $resultado->fetch_assoc();
        if (password_verify($contrasena, $usuario_datos['contrasena'])) {
            $_SESSION['id_usuario'] = $usuario_datos['id'];
            $_SESSION['usuario'] = $usuario;
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
                <i class="bi bi-box-arrow-in-right"></i> Ingresar
            </button>
            
            <div class="login-links">
                <a href="registro.php">¿No tienes cuenta? Regístrate</a>
            </div>
        </form>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
