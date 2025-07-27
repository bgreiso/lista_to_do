<?php
include 'config.php';

// Inicializar variables
$error = '';
$departamentos = [];

// Obtener departamentos
$query_deptos = $conexion->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre");
if ($query_deptos) {
    $departamentos = $query_deptos->fetch_all(MYSQLI_ASSOC);
}

// Manejar solicitud AJAX para cargos
if (isset($_GET['get_cargos'])) {
    try {
        $departamento_id = (int)$_GET['departamento_id'];
        
        if (!$conexion || $conexion->connect_error) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        $query = $conexion->prepare("SELECT id_cargo, nombre FROM cargos WHERE id_departamento = ? ORDER BY nombre");
        
        if (!$query) {
            throw new Exception("Error en la consulta: " . $conexion->error);
        }
        
        $query->bind_param("i", $departamento_id);
        
        if (!$query->execute()) {
            throw new Exception("Error al ejecutar: " . $query->error);
        }
        
        $result = $query->get_result();
        $cargos = $result->fetch_all(MYSQLI_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $cargos]);
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Procesar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $contrasena = $_POST['contrasena'] ?? '';
    $id_departamento = $_POST['id_departamento'] ?? 0;
    $id_cargo = $_POST['id_cargo'] ?? 0;
    
    try {
        // Verificar si es el primer usuario registrado
        $query_count = $conexion->query("SELECT COUNT(*) as total FROM usuarios");
        $total_usuarios = $query_count->fetch_assoc()['total'];
        
        // Asignar rol: 1 (admin) para el primer usuario, 2 (normal) para los demás
        $id_rol = ($total_usuarios == 0) ? 1 : 2;
        
        // Verificar que las contraseñas coincidan
        if ($_POST['contrasena'] !== $_POST['confirmar_contrasena']) {
            throw new Exception("Las contraseñas no coinciden");
        }
        
        // Verificar si el usuario ya existe
        $stmt = $conexion->prepare("SELECT id_usuario FROM usuarios WHERE usuario = ?");
        if (!$stmt) {
            throw new Exception("Error al preparar consulta: " . $conexion->error);
        }
        
        $stmt->bind_param("s", $usuario);
        $stmt->execute();
        $resultado = $stmt->get_result();
        
        if ($resultado->num_rows > 0) {
            throw new Exception("El usuario ya existe");
        }
        
        // Verificar que el departamento y cargo existen
        $stmt_check = $conexion->prepare("SELECT 1 FROM cargos WHERE id_cargo = ? AND id_departamento = ?");
        if (!$stmt_check) {
            throw new Exception("Error al verificar cargo: " . $conexion->error);
        }
        
        $stmt_check->bind_param("ii", $id_cargo, $id_departamento);
        $stmt_check->execute();
        
        if (!$stmt_check->get_result()->num_rows) {
            throw new Exception("La combinación de departamento y cargo no es válida");
        }
        
        // Crear hash de contraseña
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        
        // Insertar nuevo usuario
        $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, usuario, contraseña, id_departamento, id_cargo, id_rol) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar inserción: " . $conexion->error);
        }
        
        $stmt->bind_param("sssiii", $nombre, $usuario, $contrasena_hash, $id_departamento, $id_cargo, $id_rol);
        
        if ($stmt->execute()) {
            // Si es el primer usuario (admin), iniciar sesión automáticamente
            if ($id_rol == 1) {
                $_SESSION['id_usuario'] = $stmt->insert_id;
                $_SESSION['usuario'] = $usuario;
                $_SESSION['rol'] = $id_rol;
                header('Location: panel_control.php');
            } else {
                $_SESSION['registro_exitoso'] = true;
                header('Location: inicio_sesion.php');
            }
            exit;
        } else {
            throw new Exception("Error al registrar: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuario</title>
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
            max-width: 600px;
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
        
        .btn-outline-secondary {
            border-radius: 8px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
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
        
        .form-select {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 201, 240, 0.25);
        }
        
        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-8">
                <div class="login-card">
                    <div class="login-header">
                        <div class="login-logo">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h2 class="mb-0">Registro de Usuario</h2>
                    </div>
                    <div class="login-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="mt-4" id="formRegistro" novalidate>
                            <div class="row g-3">
                                <!-- Nombre Completo -->
                                <div class="col-md-12">
                                    <label for="nombre" class="form-label fw-bold">Nombre Completo</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-login">
                                            <i class="fas fa-user"></i>
                                        </span>
                                        <input type="text" class="form-control form-control-login" id="nombre" name="nombre" required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor ingresa tu nombre completo.
                                    </div>
                                </div>
                                
                                <!-- Usuario -->
                                <div class="col-md-6">
                                    <label for="usuario" class="form-label fw-bold">Nombre de Usuario</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-login">
                                            <i class="fas fa-at"></i>
                                        </span>
                                        <input type="text" class="form-control form-control-login" id="usuario" name="usuario" required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor elige un nombre de usuario.
                                    </div>
                                </div>
                                
                                <!-- Departamento -->
                                <div class="col-md-6">
                                    <label for="id_departamento" class="form-label fw-bold">Departamento</label>
                                    <select class="form-select" id="id_departamento" name="id_departamento" required>
                                        <option value="" selected disabled>Seleccione...</option>
                                        <?php foreach ($departamentos as $depto): ?>
                                            <option value="<?= $depto['id_departamento'] ?>"><?= htmlspecialchars($depto['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <!-- Cargo (se carga dinámicamente) -->
                                <div class="col-md-6">
                                    <label for="id_cargo" class="form-label fw-bold">Cargo</label>
                                    <select class="form-select" id="id_cargo" name="id_cargo" required disabled>
                                        <option value="" selected disabled>Primero seleccione un departamento</option>
                                    </select>
                                </div>
                                
                                <!-- Contraseña -->
                                <div class="col-md-6">
                                    <label for="contrasena" class="form-label fw-bold">Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-login">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control form-control-login" id="contrasena" name="contrasena" required
                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                                    </div>
                                    <div class="invalid-feedback">
                                        La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.
                                    </div>
                                </div>
                                
                                <!-- Confirmar Contraseña -->
                                <div class="col-md-6">
                                    <label for="confirmar_contrasena" class="form-label fw-bold">Confirmar Contraseña</label>
                                    <div class="input-group">
                                        <span class="input-group-text input-group-text-login">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <input type="password" class="form-control form-control-login" id="confirmar_contrasena" name="confirmar_contrasena" required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Las contraseñas deben coincidir.
                                    </div>
                                </div>
                                
                                <!-- Términos y Condiciones -->
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terminos" required>
                                        <label class="form-check-label" for="terminos">
                                            Acepto los términos y condiciones
                                        </label>
                                        <div class="invalid-feedback">
                                            Debes aceptar los términos para continuar.
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Botones -->
                                <div class="col-md-6">
                                    <button class="btn btn-login" type="submit">
                                        <i class="fas fa-user-plus me-2"></i> Registrarse
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <a href="inicio_sesion.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-sign-in-alt me-2"></i> Ya tengo cuenta
                                    </a>
                                </div>
                            </div>
                        </form>
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
    <script>
    // Cargar cargos cuando cambia el departamento
    document.getElementById('id_departamento').addEventListener('change', function() {
        const deptoId = this.value;
        const cargoSelect = document.getElementById('id_cargo');
        
        if (!deptoId) {
            cargoSelect.innerHTML = '<option value="" selected disabled>Primero seleccione un departamento</option>';
            cargoSelect.disabled = true;
            return;
        }
        
        // Mostrar carga mientras se obtienen los datos
        cargoSelect.innerHTML = '<option value="">Cargando cargos...</option>';
        cargoSelect.disabled = true;
        
        fetch(`registro.php?get_cargos=1&departamento_id=${deptoId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data.length > 0) {
                    cargoSelect.innerHTML = '<option value="" selected disabled>Seleccione un cargo</option>';
                    data.data.forEach(cargo => {
                        const option = document.createElement('option');
                        option.value = cargo.id_cargo;
                        option.textContent = cargo.nombre;
                        cargoSelect.appendChild(option);
                    });
                    cargoSelect.disabled = false;
                } else {
                    cargoSelect.innerHTML = '<option value="" selected disabled>No hay cargos para este departamento</option>';
                    cargoSelect.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                cargoSelect.innerHTML = '<option value="" selected disabled>Error al cargar cargos</option>';
                cargoSelect.disabled = true;
            });
    });

    // Validar que las contraseñas coincidan
    document.getElementById('formRegistro').addEventListener('submit', function(e) {
        const contrasena = document.getElementById('contrasena');
        const confirmar = document.getElementById('confirmar_contrasena');
        
        if (contrasena.value !== confirmar.value) {
            confirmar.setCustomValidity("Las contraseñas no coinciden");
            confirmar.reportValidity();
            e.preventDefault();
        } else {
            confirmar.setCustomValidity("");
        }
    });
    </script>
</body>
</html>