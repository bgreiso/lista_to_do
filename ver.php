<?php
require_once 'config.php';
require_once 'auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: tablero.php');
    exit();
}

$id_tarea = intval($_GET['id']);

// Obtener información de la tarea
$tarea = [];
$query = $conexion->prepare("
    SELECT t.*, u.nombre as asignado, e.nombre as estado, u_creador.nombre as creador
    FROM tareas t
    JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
    JOIN usuarios u_creador ON t.id_usuario = u_creador.id_usuario
    JOIN estatus e ON t.id_estatus = e.id_estatus
    WHERE t.id_tarea = ?
");
$query->bind_param("i", $id_tarea);
$query->execute();
$resultado = $query->get_result();

if ($resultado->num_rows === 0) {
    header('Location: tablero.php');
    exit();
}

$tarea = $resultado->fetch_assoc();

// Obtener campos adicionales
$campos_adicionales = [];
$query_campos = $conexion->prepare("
    SELECT * FROM campos_adicionales 
    WHERE id_tarea = ?
    ORDER BY id_campo ASC
");
$query_campos->bind_param("i", $id_tarea);
$query_campos->execute();
$resultado_campos = $query_campos->get_result();
$campos_adicionales = $resultado_campos->fetch_all(MYSQLI_ASSOC);

// Obtener subtareas
$subtareas = [];
$query_subtareas = $conexion->prepare("
    SELECT t.*, e.nombre as estado 
    FROM tareas t
    JOIN estatus e ON t.id_estatus = e.id_estatus
    WHERE t.id_tarea_padre = ?
    ORDER BY fecha_creacion ASC
");
$query_subtareas->bind_param("i", $id_tarea);
$query_subtareas->execute();
$resultado_subtareas = $query_subtareas->get_result();
$subtareas = $resultado_subtareas->fetch_all(MYSQLI_ASSOC);

// Obtener comentarios
$comentarios = [];
$query_comentarios = $conexion->prepare("
    SELECT c.*, u.nombre as usuario, u.id_usuario as id_usuario_comentario
    FROM comentarios c
    JOIN usuarios u ON c.id_usuario = u.id_usuario
    WHERE c.id_tarea = ?
    ORDER BY c.fecha_creacion DESC
");
$query_comentarios->bind_param("i", $id_tarea);
$query_comentarios->execute();
$resultado_comentarios = $query_comentarios->get_result();
$comentarios = $resultado_comentarios->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3><i class="bi bi-card-checklist"></i> Detalles de la Tarea</h3>
        <div>
            <a href="agregar_subtarea.php?id=<?= $id_tarea ?>" class="btn btn-success me-2">
                <i class="bi bi-bookmarks-fill"></i> Agregar Subtarea
            </a>
            <a href="editar_tarea.php?id=<?= $id_tarea ?>" class="btn btn-primary me-2">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="tablero.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver al Tablero
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success"><?= $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?></div>
    <?php endif; ?>

    <!-- Tarjeta de detalles de la tarea -->
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><?= htmlspecialchars($tarea['titulo']) ?></h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5><i class="bi bi-info-circle"></i> Información Básica</h5>
                    <ul class="list-group list-group-flush">
                    <?php if ($tarea['id_tarea_padre']): ?>
                        <li class="list-group-item">
                            <strong><i class="bi bi-diagram-2"></i> Tarea padre:</strong> 
                            <a href="ver.php?id=<?= $tarea['id_tarea_padre'] ?>">
                                Ver tarea principal
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="list-group-item">
                            <strong><i class="bi bi-person"></i> Creada por:</strong> 
                            <?= htmlspecialchars($tarea['creador']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="bi bi-person-check"></i> Asignada a:</strong> 
                            <?= htmlspecialchars($tarea['asignado']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="bi bi-card-text"></i> Categoría:</strong> 
                            <?= htmlspecialchars($tarea['categoria']) ?>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="bi bi-calendar"></i> Fecha creación:</strong> 
                            <?= date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])) ?>
                        </li>
                        <?php if ($tarea['fecha_finalizacion']): ?>
                        <li class="list-group-item">
                            <strong><i class="bi bi-calendar-check"></i> Fecha finalización:</strong> 
                            <?= date('d/m/Y H:i', strtotime($tarea['fecha_finalizacion'])) ?>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h5><i class="bi bi-tags"></i> Estado y Prioridad</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong><i class="bi bi-list-check"></i> Estado:</strong> 
                            <span class="badge bg-<?= 
                                $tarea['id_estatus'] == 3 ? 'success' : 
                                ($tarea['id_estatus'] == 2 ? 'warning' : 'info') 
                            ?>">
                                <?= htmlspecialchars($tarea['estado']) ?>
                            </span>
                        </li>
                        <li class="list-group-item">
                            <strong><i class="bi bi-exclamation-triangle"></i> Prioridad:</strong> 
                            <span class="badge bg-<?= 
                                $tarea['prioridad'] == 'alta' ? 'danger' : 
                                ($tarea['prioridad'] == 'media' ? 'warning' : 'secondary') 
                            ?>">
                                <?= ucfirst($tarea['prioridad'] ?? 'normal') ?>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="mb-4">
                <h5><i class="bi bi-card-text"></i> Descripción</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($tarea['descripcion'])) ?>
                </div>
            </div>
            
            <?php if (!empty($tarea['herramientas'])): ?>
            <div class="mb-4">
                <h5><i class="bi bi-tools"></i> Herramientas/Recursos</h5>
                <div class="p-3 bg-light rounded">
                    <?= nl2br(htmlspecialchars($tarea['herramientas'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sección de campos adicionales -->
            <?php if (!empty($campos_adicionales)): ?>
            <div class="mb-4">
                <h5><i class="bi bi-list-check"></i> Campos Adicionales</h5>
                <div class="row">
                    <?php foreach ($campos_adicionales as $campo): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <strong><?= htmlspecialchars($campo['nombre']) ?></strong>
                            </div>
                            <div class="card-body">
                                <?= nl2br(htmlspecialchars($campo['valor'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Sección de subtareas -->
            <?php if (!empty($subtareas)): ?>
            <div class="mb-4">
                <h5><i class="bi bi-list-task"></i> Subtareas</h5>
                <div class="row">
                <?php foreach ($subtareas as $subtarea): ?>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <strong><?= htmlspecialchars($subtarea['titulo']) ?></strong>
                                <span class="badge bg-<?= 
                                    $subtarea['id_estatus'] == 3 ? 'success' : 
                                    ($subtarea['id_estatus'] == 2 ? 'warning' : 'info') 
                                ?>">
                                    <?= htmlspecialchars($subtarea['estado'] ?? 'Pendiente') ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($subtarea['descripcion'])): ?>
                                    <p><?= nl2br(htmlspecialchars($subtarea['descripcion'])) ?></p>
                                <?php endif; ?>
                                <a href="ver.php?id=<?= $subtarea['id_tarea'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver detalles
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Sección de comentarios -->
    <div class="card shadow">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="bi bi-chat-left-text"></i> Comentarios</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#comentarioModal">
                <i class="bi bi-plus"></i> Nuevo Comentario
            </button>
        </div>
        <div class="card-body">
            <?php if (empty($comentarios)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-chat-square-text" style="font-size: 2rem;"></i>
                    <p class="mt-2">No hay comentarios aún</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($comentarios as $comentario): ?>
                        <div class="timeline-item mb-4">
                            <div class="timeline-header d-flex justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="avatar bg-<?= $comentario['id_usuario_comentario'] == $_SESSION['id_usuario'] ? 'primary' : 'secondary' ?> 
                                        text-white rounded-circle d-flex align-items-center justify-content-center" 
                                        style="width: 40px; height: 40px;">
                                        <?= strtoupper(substr($comentario['usuario'], 0, 1)) ?>
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="mb-0"><?= htmlspecialchars($comentario['usuario']) ?></h6>
                                        <small class="text-muted">
                                            <?= date('d/m/Y H:i', strtotime($comentario['fecha_creacion'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <?php if ($comentario['id_usuario'] == $_SESSION['id_usuario'] || esAdmin()): ?>
                                    <a href="eliminar_comentario.php?id=<?= $comentario['id_comentario'] ?>&id_tarea=<?= $id_tarea ?>" 
                                       class="text-danger" 
                                       onclick="return confirm('¿Estás seguro de eliminar este comentario?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-body mt-2 p-3 bg-light rounded">
                                <?= nl2br(htmlspecialchars($comentario['comentario'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal para nuevo comentario -->
<div class="modal fade" id="comentarioModal" tabindex="-1" aria-labelledby="comentarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="comentarioModalLabel">Nuevo Comentario</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="comentarios.php">
                <div class="modal-body">
                    <input type="hidden" name="id_tarea" value="<?= $id_tarea ?>">
                    <div class="mb-3">
                        <label for="comentario" class="form-label">Escribe tu comentario</label>
                        <textarea class="form-control" id="comentario" name="comentario" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Comentario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>