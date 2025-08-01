<?php
require_once 'config.php';
require_once 'auth.php';

$tareas_por_estado = [];

$estados = [];
$query_estados = $conexion->query("SELECT * FROM estatus");
if ($query_estados) {
    $estados = $query_estados->fetch_all(MYSQLI_ASSOC);
}

// Obtener tareas para el usuario actual (o todas si es admin)
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

// Consulta para obtener las últimas 5 tareas
if (esAdmin()) {
    $query = $conexion->query("
        SELECT t.id_tarea, t.titulo, t.descripcion, t.fecha_creacion, 
               u.nombre as creador, e.nombre as estado
        FROM tareas t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        JOIN estatus e ON t.id_estatus = e.id_estatus
        ORDER BY t.fecha_creacion DESC
        LIMIT 5
    ");
} else {
    $query = $conexion->query("
        SELECT t.id_tarea, t.titulo, t.descripcion, t.fecha_creacion, 
               u.nombre as creador, e.nombre as estado
        FROM tareas t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        JOIN estatus e ON t.id_estatus = e.id_estatus
        WHERE t.id_usuario = '".$_SESSION['id_usuario']."'
        ORDER BY t.fecha_creacion DESC
        LIMIT 5
    ");
}

?>

<div class="card shadow mb-4">
    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
        <h6 class="m-0 font-weight-bold text-primary">Últimas Tareas</h6>
        <div class="dropdown no-arrow">
            <a class="dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="bi bi-three-dots-vertical"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                <a class="dropdown-item" href="todas.php">Ver todas</a>
                <a class="dropdown-item" href="agregar_tarea.php">Crear nueva</a>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php
        // Consulta para obtener las últimas 5 tareas
        $query = $conexion->query("
            SELECT t.id_tarea, t.titulo, t.descripcion, t.fecha_creacion, 
                   u.nombre as creador, e.nombre as estado
            FROM tareas t
            JOIN usuarios u ON t.id_usuario = u.id_usuario
            JOIN estatus e ON t.id_estatus = e.id_estatus
            ORDER BY t.fecha_creacion DESC
            LIMIT 5
        ");
        
        if ($query && $query->num_rows > 0): ?>
            <div class="list-group">
                <?php while ($tarea = $query->fetch_assoc()): ?>
                    <a href="ver.php?id=<?= $tarea['id_tarea'] ?>" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1"><?= htmlspecialchars($tarea['titulo']) ?></h6>
                            <small class="text-<?= 
                                $tarea['estado'] == 'Pendiente' ? 'warning' : 
                                ($tarea['estado'] == 'En progreso' ? 'primary' : 'success') 
                            ?>"><?= $tarea['estado'] ?></small>
                        </div>
                        <p class="mb-1"><?= htmlspecialchars(substr($tarea['descripcion'], 0, 100)) ?>...</p>
                        <small>Creado por: <?= htmlspecialchars($tarea['creador']) ?></small>
                        <small class="d-block text-muted"><?= date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])) ?></small>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No hay tareas recientes</div>
        <?php endif; ?>
    </div>
</div>