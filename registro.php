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
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form class="row g-3 needs-validation" method="POST" novalidate id="formRegistro">
                            <!-- Nombre Completo -->
                            <div class="col-md-12">
                                <label for="nombre" class="form-label">Nombre Completo</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required>
                                <div class="invalid-feedback">
                                    Por favor ingresa tu nombre completo.
                                </div>
                            </div>
                            
                            <!-- Usuario -->
                            <div class="col-md-6">
                                <label for="usuario" class="form-label">Nombre de Usuario</label>
                                <div class="input-group has-validation">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" class="form-control" id="usuario" name="usuario" required>
                                    <div class="invalid-feedback">
                                        Por favor elige un nombre de usuario.
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Departamento -->
                            <div class="col-md-6">
                                <label for="id_departamento" class="form-label">Departamento</label>
                                <select class="form-select" id="id_departamento" name="id_departamento" required>
                                    <option value="" selected disabled>Seleccione...</option>
                                    <?php foreach ($departamentos as $depto): ?>
                                        <option value="<?= $depto['id_departamento'] ?>"><?= htmlspecialchars($depto['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Cargo (se carga dinámicamente) -->
                            <div class="col-md-6">
                                <label for="id_cargo" class="form-label">Cargo</label>
                                <select class="form-select" id="id_cargo" name="id_cargo" required disabled>
                                    <option value="" selected disabled>Primero seleccione un departamento</option>
                                </select>
                            </div>
                            
                            <!-- Contraseña -->
                            <div class="col-md-6">
                                <label for="contrasena" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="contrasena" name="contrasena" required
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                                <div class="invalid-feedback">
                                    La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.
                                </div>
                            </div>
                            
                            <!-- Confirmar Contraseña -->
                            <div class="col-md-6">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Contraseña</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
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