<?php
require_once 'config.php';
include 'includes/header.php';

// Datos para widgets del dashboard
$stats = [
    'total_usuarios' => $conexion->query("SELECT COUNT(*) FROM usuarios")->fetch_row()[0],
    'tareas_pendientes' => $conexion->query("SELECT COUNT(*) FROM tareas WHERE id_estatus = 1")->fetch_row()[0],
    'tareas_completadas' => $conexion->query("SELECT COUNT(*) FROM tareas WHERE id_estatus = 3")->fetch_row()[0]
];

// Datos para gráficos (puedes personalizar)
$usuarios_por_depto = $conexion->query("
    SELECT d.nombre as departamento, COUNT(u.id_usuario) as total
    FROM departamentos d
    LEFT JOIN usuarios u ON d.id_departamento = u.id_departamento
    GROUP BY d.id_departamento
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- Contenido de la página -->
<div class="container-fluid">

    <!-- Encabezado -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Panel de Control</h1>
        <a href="reportes/generar.php" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="bi bi-download"></i> Generar Reporte
        </a>
    </div>

    <!-- Widgets -->
    <div class="row">
        <!-- Total Usuarios -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Usuarios Registrados</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_usuarios'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas Pendientes -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tareas Pendientes</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['tareas_pendientes'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-list-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tareas Completadas -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Tareas Completadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['tareas_completadas'] ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Otro Widget -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Progreso Total</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                        <?php
                                        $total_tareas = $stats['tareas_pendientes'] + $stats['tareas_completadas'];
                                        $progreso = ($total_tareas > 0) ? round(($stats['tareas_completadas'] / $total_tareas) * 100) : 0;
                                        echo $progreso . '%';
                                        ?>
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?= $progreso ?>%" 
                                             aria-valuenow="<?= $progreso ?>" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-clipboard-data fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos y Tablas -->
    <div class="row">
        <!-- Gráfico de usuarios por departamento -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Distribución de Usuarios por Departamento</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar">
                        <canvas id="usuariosDepartamentoChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimas tareas -->
        <div class="col-xl-4 col-lg-5">
            <?php include 'includes/ultimas_tareas.php'; ?>
        </div>
    </div>

    <!-- Tabla de usuarios recientes -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Usuarios Recientes</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Departamento</th>
                                    <th>Fecha Registro</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $usuarios = $conexion->query("
                                    SELECT u.id_usuario, u.nombre, u.usuario, u.fecha_registro, d.nombre as departamento
                                    FROM usuarios u
                                    LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
                                    ORDER BY u.fecha_registro DESC
                                    LIMIT 5
                                ");
                                
                                while ($usuario = $usuarios->fetch_assoc()):
                                ?>
                                <tr>
                                    <td><?= $usuario['id_usuario'] ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                    <td><?= htmlspecialchars($usuario['departamento']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></td>
                                    <td>
                                        <a href="usuarios/editar.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <a href="usuarios/eliminar.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Script para gráficos -->
<script>
// Gráfico de barras - Usuarios por departamento
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('usuariosDepartamentoChart').getContext('2d');
    const departamentos = <?= json_encode(array_column($usuarios_por_depto, 'departamento')) ?>;
    const totales = <?= json_encode(array_column($usuarios_por_depto, 'total')) ?>;
    
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: departamentos,
            datasets: [{
                label: 'Usuarios',
                data: totales,
                backgroundColor: '#4e73df',
                hoverBackgroundColor: '#2e59d9',
                borderColor: '#4e73df',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>