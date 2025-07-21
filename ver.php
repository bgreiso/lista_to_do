<?php
require_once 'config.php';
require_once 'auth.php';

$id_tarea = (int)$_GET['id_usuario'];
$tarea = [];
$campos_adicionales = [];

// Obtener información básica de la tarea
$stmt = $conexion->prepare("
    SELECT t.*, u.nombre as asignado, e.nombre as estado, 
           c.nombre as creador, d.nombre as departamento
    FROM tareas t
    JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
    JOIN estatus e ON t.id_estatus = e.id_estatus
    JOIN usuarios c ON t.id_usuario = c.id_usuario
    LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
    WHERE t.id_tarea = ?
");
$stmt->bind_param("i", $id_tarea);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: todas.php');
    exit;
}

$tarea = $result->fetch_assoc();

// Obtener campos adicionales
$stmt_campos = $conexion->prepare("
    SELECT ca.nombre, ca.tipo, tc.valor 
    FROM tareas_campos tc
    JOIN campos_adicionales ca ON tc.id_campo = ca.id_campo
    WHERE tc.id_tarea = ?
");
$stmt_campos->bind_param("i", $id_tarea);
$stmt_campos->execute();
$result_campos = $stmt_campos->get_result();
$campos_adicionales = $result_campos->fetch_all(MYSQLI_ASSOC);

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalle de Tarea #<?= $tarea['id_tarea'] ?></h1>
        <div>
            <a href="editar.php?id=<?= $tarea['id_tarea'] ?>" class="btn btn-warning">
                <i class="bi bi-pencil"></i> Editar
            </a>
            <a href="tablero.php" class="btn btn-outline-secondary">
                <i class="bi bi-kanban"></i> Volver al tablero
            </a>
        </div>
    </div>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Información General</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="text-primary"><?= htmlspecialchars($tarea['titulo']) ?></h4>
                    <div class="mb-4">
                        <p class="lead"><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                    </div>
                    
                    <h5><i class="bi bi-tools"></i> Herramientas/Recursos</h5>
                    <p><?= nl2br(htmlspecialchars($tarea['herramientas'])) ?></p>
                </div>
                <div class="col-md-4 border-start">
                    <div class="mb-3">
                        <strong>Estado:</strong>
                        <span class="badge bg-<?= 
                            $tarea['estado'] == 'Pendiente' ? 'warning' : 
                            ($tarea['estado'] == 'En progreso' ? 'primary' : 'success') 
                        ?> p-2">
                            <i class="bi bi-circle-fill"></i> <?= $tarea['estado'] ?>
                        </span>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person-check"></i> Asignado a:</strong>
                        <p><?= htmlspecialchars($tarea['asignado']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-building"></i> Departamento:</strong>
                        <p><?= htmlspecialchars($tarea['departamento'] ?? 'N/A') ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-person-plus"></i> Creada por:</strong>
                        <p><?= htmlspecialchars($tarea['creador']) ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-plus"></i> Fecha creación:</strong>
                        <p><?= date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])) ?></p>
                    </div>
                    
                    <?php if ($tarea['fecha_actualizacion']): ?>
                    <div class="mb-3">
                        <strong><i class="bi bi-calendar-check"></i> Última actualización:</strong>
                        <p><?= date('d/m/Y H:i', strtotime($tarea['fecha_actualizacion'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($campos_adicionales)): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-secondary text-white">
            <h6 class="m-0 font-weight-bold">Campos Adicionales</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($campos_adicionales as $campo): ?>
                    <div class="col-md-6 mb-3">
                        <strong><?= htmlspecialchars($campo['nombre']) ?>:</strong>
                        <p class="border p-2 rounded bg-light">
                            <?php if ($campo['tipo'] === 'booleano'): ?>
                                <span class="badge bg-<?= $campo['valor'] ? 'success' : 'danger' ?>">
                                    <?= $campo['valor'] ? 'Sí' : 'No' ?>
                                </span>
                            <?php else: ?>
                                <?= htmlspecialchars($campo['valor']) ?>
                            <?php endif; ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Sección de historial de cambios -->
    <div class="card shadow">
        <div class="card-header py-3 bg-info text-white">
            <h6 class="m-0 font-weight-bold"><i class="bi bi-clock-history"></i> Historial de Cambios</h6>
        </div>
        <div class="card-body">
            <ul class="list-group">
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>Tarea creada</span>
                        <small><?= date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])) ?></small>
                    </div>
                </li>
                <?php if ($tarea['fecha_actualizacion']): ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between">
                        <span>Última actualización</span>
                        <small><?= date('d/m/Y H:i', strtotime($tarea['fecha_actualizacion'])) ?></small>
                    </div>
                </li>
                <?php endif; ?>
                <!-- Aquí podrías agregar más elementos de historial -->
            </ul>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>