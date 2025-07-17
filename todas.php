<?php
require_once '../config.php';
include '../includes/header.php';

// Marcar inicio de tarea
if (isset($_GET['iniciar']) && is_numeric($_GET['iniciar'])) {
    $id_tarea = intval($_GET['iniciar']);
    $conexion->query("UPDATE tareas SET fecha_actualizacion = NOW(), id_estatus = 3 WHERE id_tarea = $id_tarea");
    header('Location: todas.php');
    exit();
}
// Marcar finalización de tarea
if (isset($_GET['finalizar']) && is_numeric($_GET['finalizar'])) {
    $id_tarea = intval($_GET['finalizar']);
    $conexion->query("UPDATE tareas SET fecha_finalizacion = NOW(), id_estatus = 5 WHERE id_tarea = $id_tarea");
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

// Construir consulta con filtros
$query = "SELECT t.*, u.nombre as usuario, d.nombre as departamento, e.nombre as estatus 
          FROM tareas t
          JOIN usuarios u ON t.id_usuario = u.id_usuario
          JOIN departamentos d ON u.id_departamento = d.id_departamento
          JOIN estatus e ON t.id_estatus = e.id_estatus
          WHERE 1=1";

$params = [];
$types = '';

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

$stmt = $conexion->prepare($query);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

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
        </div>
    </div>
</form>

<!-- Tabla de Tareas -->
<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Todas las Tareas</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Usuario</th>
                        <th>Departamento</th>
                        <th>Estatus</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tareas as $tarea): ?>
                    <tr>
                        <td><?= $tarea['id_tarea'] ?></td>
                        <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                        <td><?= htmlspecialchars($tarea['usuario']) ?></td>
                        <td><?= htmlspecialchars($tarea['departamento']) ?></td>
                        <td><span class="badge bg-<?= 
                            $tarea['estatus'] == 'Completado' ? 'success' : 
                            ($tarea['estatus'] == 'Pendiente' ? 'warning' : 'secondary') 
                        ?>"><?= $tarea['estatus'] ?></span></td>
                        <td><?= date('d/m/Y', strtotime($tarea['fecha_creacion'])) ?></td>
                        <td>
                            <a href="../tareas/detalle.php?id=<?= $tarea['id_tarea'] ?>" class="btn btn-sm btn-info" title="Ver Detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            <?php if ($tarea['id_estatus'] == 1): ?>
                                <form method="get" style="display:inline;">
                                    <input type="hidden" name="iniciar" value="<?= $tarea['id_tarea'] ?>">
                                    <input type="checkbox" onchange="this.form.submit()" title="Aceptar tarea"> Aceptar
                                </form>
                            <?php endif; ?>
                            <?php if ($tarea['id_estatus'] == 2): ?>
                                <form method="get" style="display:inline;">
                                    <input type="hidden" name="finalizar" value="<?= $tarea['id_tarea'] ?>">
                                    <input type="checkbox" onchange="this.form.submit()" title="Finalizar tarea"> Finalizar
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>     
    </div>   
</div>
<?php include '../includes/footer.php'; ?>