<?php include 'config.php'; ?>

<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $departamento = $_POST['departamento'];
    
    // Verificar si el usuario ya existe
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows > 0) {
        $error = "El usuario ya existe";
    } else {
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("INSERT INTO usuarios (usuario, contrasena, departamento) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $usuario, $contrasena_hash, $departamento);
        
        if ($stmt->execute()) {
            header('Location: inicio_sesion.php');
            exit;
        } else {
            $error = "Error al registrar: " . $conexion->error;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registro de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="estilos.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="mb-0"><i class="bi bi-person-plus"></i> Registro de Usuario</h2>
                    </div>
                    <div class="card-body p-4">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <form class="row g-3 needs-validation" method="POST" novalidate>
                            <!-- Nombre Completo -->
                            <div class="col-md-12">
                                <label for="nombre_completo" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                                <div class="invalid-feedback">
                                    Por favor ingresa tu nombre completo.
                                </div>
                            </div>
                            
                            <!-- Usuario -->
                            <div class="col-md-6">
                                <label for="usuario" class="form-label">Nombre de Usuario</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text" id="inputGroupPrepend"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="usuario" name="usuario" 
                                           aria-describedby="inputGroupPrepend" required>
                                    <div class="invalid-feedback">
                                        Por favor elige un nombre de usuario.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Departamento -->
                            <div class="col-md-6">
                                <label for="departamento" class="form-label">Departamento</label>
                                <select class="form-select" id="departamento" name="departamento" required>
                                    <option selected disabled value="">Seleccionar...</option>
                                    <option value="Ventas">Ventas</option>
                                    <option value="Marketing">Desarrollo</option>
                                    <option value="TI">Marketing</option>
                                    <option value="RH">Recursos Humanos</option>
                                    <option value="Operaciones">Administración</option>
                                    <option value="Operaciones">Soporte</option>
                                </select>
                                <div class="invalid-feedback">
                                    Por favor selecciona un departamento válido.
                                </div>
                            </div>
                            
                            <!-- Contraseña -->
                            <div class="col-md-6">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                                <div class="invalid-feedback">
                                    La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.
                                </div>
                                <small class="form-text text-muted">
                                    Mínimo 8 caracteres con mayúsculas, minúsculas y números
                                </small>
                            </div>
                            
                            <!-- Confirmar Contraseña -->
                            <div class="col-md-6">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" required>
                                <div class="invalid-feedback">
                                    Las contraseñas deben coincidir.
                                </div>
                            </div>
                            
                            <!-- Términos y Condiciones -->
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="" id="terminos" required>
                                    <label class="form-check-label" for="terminos">
                                        Acepto los términos y condiciones
                                    </label>
                                    <div class="invalid-feedback">
                                        Debes aceptar los términos para continuar.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Botón de Registro -->
                            <div class="col-12">
                                <button class="btn btn-primary px-4" type="submit">
                                    <i class="bi bi-person-plus"></i> Registrarse
                                </button>
                                <a href="inicio_sesion.php" class="btn btn-outline-secondary ms-2">
                                    <i class="bi bi-box-arrow-in-right"></i> Ya tengo cuenta
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>