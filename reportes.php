<?php
require_once 'config.php';
require_once 'auth.php';
include 'header.php';

// Verificar autenticación
verificarAutenticacion();

// Verificar si el usuario es administrador
$es_admin = esAdmin();

// Obtener datos del usuario
$usuario = obtenerUsuario();

// Filtros
$filtros = [
    'mes' => $_GET['mes'] ?? date('m'),
    'ano' => $_GET['ano'] ?? date('Y'),
    'usuario' => $es_admin ? ($_GET['usuario'] ?? null) : $usuario['id']
];

// Consulta para tareas culminadas
$query = "SELECT 
            t.id_tarea,
            t.titulo,
            t.descripcion,
            t.fecha_creacion,
            t.fecha_finalizacion,
            ua.nombre as asignado,
            d.nombre as departamento
          FROM tareas t
          JOIN usuarios ua ON t.id_usuario_asignado = ua.id_usuario
          JOIN departamentos d ON ua.id_departamento = d.id_departamento
          WHERE t.id_estatus = 3
          AND MONTH(t.fecha_finalizacion) = ? 
          AND YEAR(t.fecha_finalizacion) = ?";

$params = [$filtros['mes'], $filtros['ano']];
$types = 'ii';

// Aplicar filtro de usuario si es necesario
if ($filtros['usuario']) {
    $query .= " AND t.id_usuario_asignado = ?";
    $params[] = $filtros['usuario'];
    $types .= 'i';
}

$query .= " ORDER BY t.fecha_finalizacion DESC";

// Obtener tareas culminadas
$tareas_culminadas = [];
$stmt = $conexion->prepare($query);

if ($stmt === false) {
    die("Error al preparar la consulta: " . $conexion->error);
}

if ($stmt->bind_param($types, ...$params) === false) {
    die("Error al vincular parámetros: " . $stmt->error);
}

if ($stmt->execute() === false) {
    die("Error al ejecutar la consulta: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result) {
    $tareas_culminadas = $result->fetch_all(MYSQLI_ASSOC);
}

// Consulta para estadísticas (gráfica)
$query_estadisticas = "SELECT 
                        SUM(CASE WHEN id_estatus = 3 THEN 1 ELSE 0 END) as culminadas,
                        SUM(CASE WHEN id_estatus = 2 THEN 1 ELSE 0 END) as en_progreso,
                        SUM(CASE WHEN id_estatus = 1 THEN 1 ELSE 0 END) as pendientes
                      FROM tareas
                      WHERE MONTH(fecha_creacion) = ? 
                      AND YEAR(fecha_creacion) = ?";

if ($filtros['usuario']) {
    $query_estadisticas .= " AND id_usuario_asignado = ?";
}

$stmt_estadisticas = $conexion->prepare($query_estadisticas);
if ($stmt_estadisticas) {
    $stmt_estadisticas->bind_param($types, ...$params);
    $stmt_estadisticas->execute();
    $estadisticas = $stmt_estadisticas->get_result()->fetch_assoc();
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Reporte Mensual de Tareas Culminadas</h1>
    
    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Mes:</label>
                    <select name="mes" class="form-control">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= $filtros['mes'] == $i ? 'selected' : '' ?>>
                                <?= DateTime::createFromFormat('!m', $i)->format('F') ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2">Año:</label>
                    <select name="ano" class="form-control">
                        <?php for($i=date('Y'); $i>=2020; $i--): ?>
                            <option value="<?= $i ?>" <?= $filtros['ano'] == $i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <?php if($es_admin): ?>
                <div class="form-group mr-3">
                    <label class="mr-2">Usuario:</label>
                    <select name="usuario" class="form-control">
                        <option value="">Todos</option>
                        <?php 
                        $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
                        while($u = $usuarios->fetch_assoc()): ?>
                            <option value="<?= $u['id_usuario'] ?>" <?= $filtros['usuario'] == $u['id_usuario'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary mr-2">
                    <i class="fas fa-filter"></i> Aplicar Filtros
                </button>
                <a href="reportes.php" class="btn btn-secondary">
                    <i class="fas fa-sync-alt"></i> Limpiar
                </a>
            </form>
        </div>
    </div>

    <!-- Gráfica de Alcance -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Alcance de Tareas en el Mes</h6>
                </div>
                <div class="card-body">
                    <div class="chart-pie pt-4">
                        <canvas id="alcanceChart"></canvas>
                    </div>
                    <div class="mt-4 text-center small">
                        <span class="mr-3">
                            <i class="fas fa-circle text-success"></i> Culminadas: <?= $estadisticas['culminadas'] ?? 0 ?>
                        </span>
                        <span class="mr-3">
                            <i class="fas fa-circle text-info"></i> En Progreso: <?= $estadisticas['en_progreso'] ?? 0 ?>
                        </span>
                        <span class="mr-3">
                            <i class="fas fa-circle text-warning"></i> Pendientes: <?= $estadisticas['pendientes'] ?? 0 ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Listado de Tareas Culminadas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Tareas Culminadas (<?= count($tareas_culminadas) ?>)</h6>
            <div>
                <a href="?<?= http_build_query(array_merge($_GET, ['exportar' => 1])) ?>" class="btn btn-sm btn-success">
                    <i class="fas fa-file-excel"></i> Exportar
                </a>
            </div>
        </div>
        <div class="card-body">
            <?php if(!empty($tareas_culminadas)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Título</th>
                                <th>Descripción</th>
                                <th>Asignado a</th>
                                <th>Departamento</th>
                                <th>Fecha Creación</th>
                                <th>Fecha Finalización</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($tareas_culminadas as $tarea): ?>
                            <tr>
                                <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                                <td><?= htmlspecialchars(substr($tarea['descripcion'], 0, 50)) . (strlen($tarea['descripcion']) > 50 ? '...' : '') ?></td>
                                <td><?= htmlspecialchars($tarea['asignado']) ?></td>
                                <td><?= htmlspecialchars($tarea['departamento']) ?></td>
                                <td><?= date('d/m/Y', strtotime($tarea['fecha_creacion'])) ?></td>
                                <td><?= date('d/m/Y', strtotime($tarea['fecha_finalizacion'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">No hay tareas culminadas para el periodo seleccionado.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Script para gráficos -->
<script src="vendor/chart.js/Chart.min.js"></script>
<script>
// Gráfica de Alcance
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('alcanceChart');
    var myChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Culminadas', 'En Progreso', 'Pendientes'],
            datasets: [{
                data: [
                    <?= $estadisticas['culminadas'] ?? 0 ?>,
                    <?= $estadisticas['en_progreso'] ?? 0 ?>,
                    <?= $estadisticas['pendientes'] ?? 0 ?>
                ],
                backgroundColor: [
                    'rgba(40, 167, 69, 0.8)',
                    'rgba(23, 162, 184, 0.8)',
                    'rgba(255, 193, 7, 0.8)'
                ],
                borderColor: [
                    'rgba(40, 167, 69, 1)',
                    'rgba(23, 162, 184, 1)',
                    'rgba(255, 193, 7, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>