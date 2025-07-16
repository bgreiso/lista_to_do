
<?php
session_start();
require_once '../config.php';
include '../includes/header.php';


$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $id_usuario = $_SESSION['id_usuario'] ?? null;
    $id_estatus = 1; // Por defecto, pendiente

    if (!$id_usuario) {
        $mensaje = '<div class="alert alert-danger">No hay usuario en sesión. Inicie sesión para agregar tareas.</div>';
    } elseif (!$conexion) {
        $mensaje = '<div class="alert alert-danger">No hay conexión a la base de datos.</div>';
    } elseif ($titulo) {
        $stmt = $conexion->prepare("INSERT INTO tareas (titulo, descripcion, herramientas, id_usuario, id_estatus, fecha_creacion, fecha_actualizacion) VALUES (?, ?, ?, ?, ?, NOW(), NULL)");
        if ($stmt === false) {
            $mensaje = '<div class="alert alert-danger">Error en la preparación de la consulta: ' . $conexion->error . '</div>';
        } else {
            $stmt->bind_param("sssii", $titulo, $descripcion, $herramientas, $id_usuario, $id_estatus);
            if ($stmt->execute()) {
                $mensaje = '<div class="alert alert-success">Tarea agregada correctamente.</div>';
            } else {
                $mensaje = '<div class="alert alert-danger">Error al agregar la tarea: ' . $stmt->error . '</div>';
            }
        }
    } else {
        $mensaje = '<div class="alert alert-danger">El título es obligatorio.</div>';
    }
}
?>

<div class="container mt-4">
    <h2>Agregar Nueva Tarea</h2>
    <?= $mensaje ?>
    <form method="post">
        <div class="mb-3">
            <label for="titulo" class="form-label">Título</label>
            <input type="text" class="form-control" id="titulo" name="titulo" required>
        </div>
        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
        </div>
        <div class="mb-3">
            <label for="herramientas" class="form-label">Herramientas</label>
            <textarea class="form-control" id="herramientas" name="herramientas" rows="2"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Agregar Tarea</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>