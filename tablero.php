<?php
require_once 'config.php';
require_once 'auth.php';

// Procesar acciones de tareas
if (isset($_GET['iniciar']) && is_numeric($_GET['iniciar'])) {
    $id_tarea = intval($_GET['iniciar']);
    $conexion->query("UPDATE tareas SET fecha_actualizacion = NOW(), id_estatus = 2 WHERE id_tarea = $id_tarea");
    header('Location: tablero.php');
    exit();
}

if (isset($_GET['finalizar']) && is_numeric($_GET['finalizar'])) {
    $id_tarea = intval($_GET['finalizar']);
    $conexion->query("UPDATE tareas SET fecha_finalizacion = NOW(), id_estatus = 3 WHERE id_tarea = $id_tarea");
    header('Location: tablero.php');
    exit();
}

// Obtener estados disponibles
$estados = [];
$query_estados = $conexion->query("SELECT * FROM estatus");
if ($query_estados) {
    $estados = $query_estados->fetch_all(MYSQLI_ASSOC);
}

// Obtener tareas para el usuario actual (o todas si es admin)
$tareas_por_estado = [];

foreach ($estados as $estado) {
    $id_estatus = $estado['id_estatus'];
    
    if (esAdmin()) {
        $stmt = $conexion->prepare("
            SELECT t.*, u.nombre as asignado, e.nombre as estado 
            FROM tareas t
            JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
            JOIN estatus e ON t.id_estatus = e.id_estatus
            WHERE t.id_estatus = ?
        ");
    } else {
        $stmt = $conexion->prepare("
            SELECT t.*, u.nombre as asignado, e.nombre as estado 
            FROM tareas t
            JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
            JOIN estatus e ON t.id_estatus = e.id_estatus
            WHERE t.id_estatus = ? AND t.id_usuario_asignado = ?
        ");
        $stmt->bind_param("ii", $id_estatus, $_SESSION['id_usuario']);
    }
    
    if (esAdmin()) {
        $stmt->bind_param("i", $id_estatus);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $tareas_por_estado[$id_estatus] = $result->fetch_all(MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Tablero de Tareas</h1>
    
    <div class="kanban-board">
        <div class="row">
            <?php foreach ($estados as $estado): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h5 class="m-0 font-weight-bold text-primary"><?= $estado['nombre'] ?></h5>
                            <span class="badge bg-secondary"><?= count($tareas_por_estado[$estado['id_estatus']]) ?></span>
                        </div>
                        <div class="card-body" id="estado-<?= $estado['id_estatus'] ?>" data-estado-id="<?= $estado['id_estatus'] ?>">
                            <?php foreach ($tareas_por_estado[$estado['id_estatus']] as $tarea): ?>
                                <div class="card task-card mb-3" data-task-id="<?= $tarea['id_tarea'] ?>" draggable="true">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                                        <small class="text-muted">
                                            <?= date('d/m/Y', strtotime($tarea['fecha_creacion'])) ?>
                                        </small>
                                        <div class="task-actions">
                                            <!-- Ícono para ver detalles -->
                                            <a href="ver.php?id=<?= $tarea['id_tarea'] ?>" 
                                               class="text-primary me-2" 
                                               title="Ver detalles"
                                               data-bs-toggle="tooltip">
                                                <i class="bi bi-eye-fill"></i>
                                            </a>
                                            <!-- Ícono para agregar comentario -->
                                            <a href="#" 
                                               class="text-success" 
                                               title="Agregar comentario"
                                               data-bs-toggle="modal" 
                                               data-bs-target="#comentarioModal"
                                               data-tarea-id="<?= $tarea['id_tarea'] ?>"
                                               data-bs-toggle="tooltip">
                                                <i class="bi bi-chat-left-text-fill"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($tarea['titulo']) ?></h5>
                                        <p class="card-text"><?= htmlspecialchars(substr($tarea['descripcion'], 0, 100)) ?>...</p>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge bg-info"><?= $tarea['asignado'] ?></span>
                                            <?php if ($tarea['id_estatus'] == 1 || $tarea['id_estatus'] == 1): ?>
                                                <form method="get" class="me-1">
                                                    <input type="hidden" name="iniciar" value="<?= $tarea['id_tarea'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-warning" title="Iniciar tarea">
                                                        <a><i class="fas fa-play"></i> Iniciar</a>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if ($tarea['id_estatus'] == 2 || $tarea['id_estatus'] == 2): ?>
                                                <form method="get">
                                                    <input type="hidden" name="finalizar" value="<?= $tarea['id_tarea'] ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" title="Finalizar tarea">
                                                        <a><i class="fas fa-check"></i> Completar</a>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal para comentarios -->
<div class="modal fade" id="comentarioModal" tabindex="-1" aria-labelledby="comentarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="comentarioModalLabel">Agregar Comentario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formComentario" method="post" action="comentarios.php">
                    <input type="hidden" name="id_tarea" id="tareaComentarioId">
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Comentario</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" form="formComentario" class="btn btn-primary">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    // Tooltips para los íconos
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Configurar modal de comentarios
    const comentarioModal = document.getElementById('comentarioModal');
    if (comentarioModal) {
        comentarioModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const tareaId = button.getAttribute('data-tarea-id');
            const modalInput = comentarioModal.querySelector('#tareaComentarioId');
            modalInput.value = tareaId;
        });
    }
});
</script>

<?php include 'footer.php'; ?>