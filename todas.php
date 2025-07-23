<?php
require_once 'config.php';
include 'header.php';

// Procesar acciones de tareas
if (isset($_GET['iniciar']) && is_numeric($_GET['iniciar'])) {
    $id_tarea = intval($_GET['iniciar']);
    $conexion->query("UPDATE tareas SET fecha_actualizacion = NOW(), id_estatus = 2 WHERE id_tarea = $id_tarea");
    header('Location: todas.php');
    exit();
}

if (isset($_GET['finalizar']) && is_numeric($_GET['finalizar'])) {
    $id_tarea = intval($_GET['finalizar']);
    $conexion->query("UPDATE tareas SET fecha_finalizacion = NOW(), id_estatus = 3 WHERE id_tarea = $id_tarea");
    header('Location: todas.php');
    exit();
}

// Filtros
$filtros = [
    'estatus' => $_GET['estatus'] ?? null,
    'departamento' => $_GET['departamento'] ?? null,
    'mes' => $_GET['mes'] ?? date('m'),
    'ano' => $_GET['ano'] ?? date('Y')
];

// Construir consulta base
$query = "SELECT 
            t.*, 
            uc.nombre AS creador_nombre,
            ua.nombre AS asignado_nombre,
            d.nombre AS departamento_nombre,
            e.nombre AS estatus
          FROM tareas t
          JOIN usuarios uc ON t.id_usuario = uc.id_usuario
          JOIN usuarios ua ON t.id_usuario_asignado = ua.id_usuario
          JOIN departamentos d ON ua.id_departamento = d.id_departamento
          JOIN estatus e ON t.id_estatus = e.id_estatus
          WHERE 1=1";

$params = [];
$types = '';

// Aplicar filtros
if ($filtros['estatus']) {
    $query .= " AND t.id_estatus = ?";
    $params[] = $filtros['estatus'];
    $types .= 'i';
}

if ($filtros['departamento']) {
    $query .= " AND d.id_departamento = ?";
    $params[] = $filtros['departamento'];
    $types .= 'i';
}

$query .= " ORDER BY t.fecha_creacion DESC";

// Preparar y ejecutar consulta con manejo de errores
$tareas = [];
$stmt = $conexion->prepare($query);

if (!$stmt) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if ($params) {
    $stmt->bind_param($types, ...$params);
}

if (!$stmt->execute()) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result) {
    $tareas = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!-- Resto del código HTML (formulario de filtros y tabla) -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Todas las Tareas</h1>
    
    <!-- Formulario de Filtros -->
    <form method="get" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label>Estatus</label>
                <select name="estatus" class="form-control">
                    <option value="">Todos</option>
                    <?php 
                    $estatus = $conexion->query("SELECT * FROM estatus");
                    while($e = $estatus->fetch_assoc()): ?>
                        <option value="<?= $e['id_estatus'] ?>" <?= $filtros['estatus'] == $e['id_estatus'] ? 'selected' : '' ?>>
                            <?= $e['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label>Departamento</label>
                <select name="departamento" class="form-control">
                    <option value="">Todos</option>
                    <?php 
                    $deptos = $conexion->query("SELECT * FROM departamentos");
                    while($d = $deptos->fetch_assoc()): ?>
                        <option value="<?= $d['id_departamento'] ?>" <?= $filtros['departamento'] == $d['id_departamento'] ? 'selected' : '' ?>>
                            <?= $d['nombre'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Mes</label>
                <select name="mes" class="form-control">
                    <?php for($i=1; $i<=12; $i++): ?>
                        <option value="<?= $i ?>" <?= $filtros['mes'] == $i ? 'selected' : '' ?>>
                            <?= DateTime::createFromFormat('!m', $i)->format('F') ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label>Año</label>
                <select name="ano" class="form-control">
                    <?php for($i=date('Y'); $i>=2020; $i--): ?>
                        <option value="<?= $i ?>" <?= $filtros['ano'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="todas.php" class="btn btn-secondary ms-2">Limpiar</a>
            </div>
        </div>
    </form>

    <!-- Tabla de Tareas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Tareas</h6>
        </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Creada por</th>
                        <th>Asignada a</th>
                        <th>Departamento</th>
                        <th>Estatus</th>
                        <th>Fecha Creación</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tareas as $tarea): ?>
                    <tr>
                        <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <div><?= htmlspecialchars($tarea['creador_nombre']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div>
                                    <div><?= htmlspecialchars($tarea['asignado_nombre']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-primary">
                                <?= htmlspecialchars($tarea['departamento_nombre']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= 
                                $tarea['estatus'] == 'Completado' ? 'success' : 
                                ($tarea['estatus'] == 'Pendiente' ? 'warning' : 
                                ($tarea['estatus'] == 'En Progreso' ? 'info' : 'secondary')) 
                            ?>">
                                <?= $tarea['estatus'] ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($tarea['fecha_creacion'])) ?></td>
                        <td>
                            <div class="d-flex justify-content-center gap-2">
                                <a href="editar_tarea.php?id=<?= $tarea['id_tarea'] ?>" 
                                   class="btn btn-sm btn-primary p-2" 
                                   title="Editar"
                                   data-bs-toggle="tooltip">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="eliminar_tarea.php?id=<?= $tarea['id_tarea'] ?>" 
                                   class="btn btn-sm btn-danger p-2" 
                                   title="Eliminar"
                                   data-bs-toggle="tooltip"
                                   onclick="return confirm('¿Estás seguro de eliminar esta tarea?')">
                                    <i class="bi bi-trash-fill"></i>
                                </a>
                                <a href="ver.php?id=<?= $tarea['id_tarea'] ?>" 
                                   class="btn btn-sm btn-success p-2" 
                                   title="Ver detalles"
                                   data-bs-toggle="tooltip">
                                    <i class="bi bi-eye-fill"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<?php include 'footer.php'; ?>