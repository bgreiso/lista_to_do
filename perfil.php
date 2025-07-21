<?php
require_once 'auth.php';
require_once 'config.php';

// Obtener información del usuario actual
$usuario = [];
$query = $conexion->prepare("SELECT u.*, d.nombre as departamento, c.nombre as cargo, r.nombre as rol 
                           FROM usuarios u
                           LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
                           LEFT JOIN cargos c ON u.id_cargo = c.id_cargo
                           LEFT JOIN roles r ON u.id_rol = r.id_rol
                           WHERE u.id_usuario = ?");
$query->bind_param("i", $_SESSION['id_usuario']);
$query->execute();
$resultado = $query->get_result();
$usuario = $resultado->fetch_assoc();

$mensaje = '';
$error = '';

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_contrasena'])) {
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    
    try {
        // Verificar contraseña actual
        if (!password_verify($contrasena_actual, $usuario['contraseña'])) {
            throw new Exception("La contraseña actual es incorrecta");
        }
        
        // Validar nueva contraseña
        if ($nueva_contrasena !== $confirmar_contrasena) {
            throw new Exception("Las nuevas contraseñas no coinciden");
        }
        
        if (strlen($nueva_contrasena) < 8 || !preg_match('/[A-Z]/', $nueva_contrasena) || 
            !preg_match('/[a-z]/', $nueva_contrasena) || !preg_match('/[0-9]/', $nueva_contrasena)) {
            throw new Exception("La nueva contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número");
        }
        
        // Actualizar contraseña
        $nueva_contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
        $stmt = $conexion->prepare("UPDATE usuarios SET contraseña = ? WHERE id_usuario = ?");
        $stmt->bind_param("si", $nueva_contrasena_hash, $_SESSION['id_usuario']);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Contraseña actualizada correctamente</div>';
        } else {
            throw new Exception("Error al actualizar la contraseña: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Procesar actualización de información personal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    
    try {
        // Validaciones básicas
        if (empty($nombre)) {
            throw new Exception("El nombre es obligatorio");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) && !empty($email)) {
            throw new Exception("El correo electrónico no es válido");
        }
        
        // Actualizar información
        $stmt = $conexion->prepare("UPDATE usuarios SET nombre = ?, email = ?, telefono = ? WHERE id_usuario = ?");
        $stmt->bind_param("sssi", $nombre, $email, $telefono, $_SESSION['id_usuario']);
        
        if ($stmt->execute()) {
            $mensaje = '<div class="alert alert-success">Perfil actualizado correctamente</div>';
            // Actualizar datos en sesión
            $_SESSION['nombre'] = $nombre;
            // Recargar datos del usuario
            $query->execute();
            $resultado = $query->get_result();
            $usuario = $resultado->fetch_assoc();
        } else {
            throw new Exception("Error al actualizar el perfil: " . $stmt->error);
        }
    } catch (Exception $e) {
        $error = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person-circle"></i> Mi Perfil</h2>
        <a href="panel_control.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al Panel
        </a>
    </div>
    
    <?= $error ?>
    <?= $mensaje ?>
    
    <div class="row">
        <!-- Columna izquierda - Información del perfil -->
        <div class="col-md-4 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-info-circle"></i> Información del Usuario
                </div>
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar bg-secondary text-white d-inline-flex align-items-center justify-content-center rounded-circle" 
                             style="width: 100px; height: 100px; font-size: 2.5rem;">
                            <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
                        </div>
                    </div>
                    
                    <h4><?= htmlspecialchars($usuario['nombre']) ?></h4>
                    <p class="text-muted mb-1">@<?= htmlspecialchars($usuario['usuario']) ?></p>
                    
                    <hr>
                    
                    <div class="text-start">
                        <p><strong><i class="bi bi-briefcase"></i> Cargo:</strong> <?= htmlspecialchars($usuario['cargo'] ?? 'No especificado') ?></p>
                        <p><strong><i class="bi bi-building"></i> Departamento:</strong> <?= htmlspecialchars($usuario['departamento'] ?? 'No especificado') ?></p>
                        <p><strong><i class="bi bi-shield"></i> Rol:</strong> <?= htmlspecialchars($usuario['rol']) ?></p>
                        <p><strong><i class="bi bi-calendar"></i> Miembro desde:</strong> <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Columna derecha - Configuraciones -->
        <div class="col-md-8">
            <!-- Tarjeta de información personal -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-person-lines-fill"></i> Información Personal
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nombre" class="form-label">Nombre Completo *</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="usuario" class="form-label">Nombre de Usuario</label>
                                <input type="text" class="form-control" id="usuario" 
                                       value="<?= htmlspecialchars($usuario['usuario']) ?>" disabled>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Correo Electrónico</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($usuario['email'] ?? '') ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="tel" class="form-control" id="telefono" name="telefono" 
                                       value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" name="actualizar_perfil" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Tarjeta de cambio de contraseña -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="bi bi-shield-lock"></i> Seguridad
                </div>
                <div class="card-body">
                    <form method="post" class="needs-validation" novalidate>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="contrasena_actual" class="form-label">Contraseña Actual *</label>
                                <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual" required>
                                <div class="invalid-feedback">
                                    Por favor ingresa tu contraseña actual.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="nueva_contrasena" class="form-label">Nueva Contraseña *</label>
                                <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena" required
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}">
                                <div class="invalid-feedback">
                                    La contraseña debe tener al menos 8 caracteres, una mayúscula, una minúscula y un número.
                                </div>
                                <div class="form-text">
                                    <small>Requisitos: Mínimo 8 caracteres, 1 mayúscula, 1 minúscula y 1 número</small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña *</label>
                                <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena" required>
                                <div class="invalid-feedback">
                                    Las contraseñas deben coincidir.
                                </div>
                            </div>
                            
                            <div class="col-12 mt-3">
                                <button type="submit" name="cambiar_contrasena" class="btn btn-primary">
                                    <i class="bi bi-key"></i> Cambiar Contraseña
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Validación del formulario de cambio de contraseña
    const formContrasena = document.querySelector('form.needs-validation');
    formContrasena.addEventListener('submit', function(event) {
        if (!formContrasena.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Validación personalizada para confirmar contraseña
            const nuevaContrasena = document.getElementById('nueva_contrasena');
            const confirmarContrasena = document.getElementById('confirmar_contrasena');
            
            if (nuevaContrasena.value !== confirmarContrasena.value) {
                confirmarContrasena.setCustomValidity("Las contraseñas no coinciden");
                confirmarContrasena.reportValidity();
            } else {
                confirmarContrasena.setCustomValidity("");
            }
        }
        
        formContrasena.classList.add('was-validated');
    }, false);
});
</script>

<?php include 'footer.php'; ?>