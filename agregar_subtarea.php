<?php
require_once 'config.php';
require_once 'auth.php';

// Verificar si se recibió el ID de la tarea padre
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje_error'] = 'ID de tarea no válido';
    header('Location: tablero.php');
    exit();
}

$id_tarea_padre = intval($_GET['id']);

// Obtener información de la tarea padre
$query = $conexion->prepare("
    SELECT t.*, u.nombre as asignado 
    FROM tareas t
    JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
    WHERE t.id_tarea = ?
");
$query->bind_param("i", $id_tarea_padre);
$query->execute();
$resultado = $query->get_result();

if ($resultado->num_rows === 0) {
    $_SESSION['mensaje_error'] = 'La tarea principal no existe';
    header('Location: tablero.php');
    exit();
}

$tarea_padre = $resultado->fetch_assoc();

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar datos del formulario
        if (empty($_POST['titulo'])) {
            throw new Exception('El título de la subtarea es obligatorio');
        }

        // Preparar valores para la inserción
        $titulo = $_POST['titulo'];
        $descripcion = $_POST['descripcion'] ?? '';
        $id_usuario = $_SESSION['id_usuario'];
        $id_usuario_asignado = $_POST['id_usuario_asignado'] ?? $tarea_padre['id_usuario_asignado'];
        $categoria = $_POST['categoria'] ?? $tarea_padre['categoria'];
        $herramientas = $_POST['herramientas'] ?? '';
        $prioridad = $_POST['prioridad'] ?? 'normal';

        // Insertar la nueva subtarea
        $query = $conexion->prepare("
            INSERT INTO tareas (
                titulo, 
                descripcion, 
                id_usuario, 
                id_usuario_asignado, 
                id_estatus, 
                id_tarea_padre,
                categoria,
                herramientas,
                prioridad
            ) VALUES (?, ?, ?, ?, 1, ?, ?, ?, ?)
        ");
        
        $query->bind_param(
            "ssiissss",
            $titulo,
            $descripcion,
            $id_usuario,
            $id_usuario_asignado,
            $id_tarea_padre,
            $categoria,
            $herramientas,
            $prioridad
        );
        
        if ($query->execute()) {
            $_SESSION['mensaje_exito'] = 'Subtarea creada correctamente';
            header("Location: ver.php?id=$id_tarea_padre");
            exit();
        } else {
            throw new Exception('Error al crear la subtarea: ' . $conexion->error);
        }
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();
    }
}

// Obtener usuarios para asignación (si es admin)
$usuarios = [];
if (esAdmin()) {
    $query_usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_rol = 2 ORDER BY nombre");
    if ($query_usuarios) {
        $usuarios = $query_usuarios->fetch_all(MYSQLI_ASSOC);
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-bookmark-plus"></i> Agregar Subtarea</h2>
        <a href="ver.php?id=<?= $id_tarea_padre ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la tarea
        </a>
    </div>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?></div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <i class="bi bi-info-circle"></i> Tarea Principal
        </div>
        <div class="card-body">
            <h5><?= htmlspecialchars($tarea_padre['titulo']) ?></h5>
            <p class="mb-1"><strong>Asignado a:</strong> <?= htmlspecialchars($tarea_padre['asignado']) ?></p>
            <p><strong>Categoría:</strong> <?= htmlspecialchars($tarea_padre['categoria']) ?></p>
        </div>
    </div>

    <form method="post" class="needs-validation" novalidate>
        <input type="hidden" name="id_tarea_padre" value="<?= $id_tarea_padre ?>">
        
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-card-heading"></i> Información de la Subtarea
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                    <div class="invalid-feedback">
                        Por favor ingresa un título para la subtarea.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="categoria" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="categoria" name="categoria" 
                               value="<?= htmlspecialchars($tarea_padre['categoria'] ?? '') ?>">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="prioridad" class="form-label">Prioridad</label>
                        <select class="form-select" id="prioridad" name="prioridad">
                            <option value="baja">Baja</option>
                            <option value="media" selected>Media</option>
                            <option value="alta">Alta</option>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="herramientas" class="form-label">Herramientas/Recursos Necesarios</label>
                    <textarea class="form-control" id="herramientas" name="herramientas" rows="2"></textarea>
                </div>
                
                <?php if (esAdmin() && !empty($usuarios)): ?>
                <div class="mb-3">
                    <label for="id_usuario_asignado" class="form-label">Asignar a</label>
                    <select class="form-select" id="id_usuario_asignado" name="id_usuario_asignado">
                        <?php foreach ($usuarios as $usuario): ?>
                            <option value="<?= $usuario['id_usuario'] ?>" <?= $usuario['id_usuario'] == $tarea_padre['id_usuario_asignado'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($usuario['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="id_usuario_asignado" value="<?= $tarea_padre['id_usuario_asignado'] ?>">
                <?php endif; ?>
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="ver.php?id=<?= $id_tarea_padre ?>" class="btn btn-outline-secondary me-md-2">
                <i class="bi bi-x-circle"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar Subtarea
            </button>
        </div>
    </form>
</div>

<script>
// Validación de formulario
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include 'footer.php'; ?>