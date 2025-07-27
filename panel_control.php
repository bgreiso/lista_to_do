<?php
require_once 'auth.php';
require_once 'config.php';
verificarAutenticacion();
$usuario = obtenerUsuario();
include 'header.php';

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
        <h2>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>
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
            <?php include 'ultimas_tareas.php'; ?>
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
                                    <th>Nombre</th>
                                    <th>Usuario</th>
                                    <th>Departamento</th>
                                    <th>Último acceso</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $usuarios = $conexion->query("
                                    SELECT u.nombre, u.usuario, u.ultimo_acceso, d.nombre as departamento
                                    FROM usuarios u
                                    LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
                                    ORDER BY u.ultimo_acceso DESC
                                    LIMIT 5
                                ");
                                
                                if ($usuarios === false) {
                                    die("Error en la consulta: " . $conexion->error);
                                }
                                
                                while ($usuario = $usuarios->fetch_assoc()):
                                    $fechaMostrar = !empty($usuario['ultimo_acceso']) ? 
                                        date('d/m/Y H:i', strtotime($usuario['ultimo_acceso'])) : 
                                        'Nunca ha accedido';
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                    <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                    <td><?= htmlspecialchars($usuario['departamento']) ?></td>
                                    <td><?= $fechaMostrar ?></td>
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
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Verificar si el canvas existe
    const ctx = document.getElementById('usuariosDepartamentoChart');
    if (!ctx) {
        console.error('No se encontró el elemento canvas para el gráfico');
        return;
    }

    // Obtener datos desde PHP
    const departamentos = <?= json_encode(array_column($usuarios_por_depto, 'departamento')) ?>;
    const totales = <?= json_encode(array_column($usuarios_por_depto, 'total')) ?>;
    
    // Verificar que hay datos
    if (departamentos.length === 0 || totales.length === 0) {
        console.error('No hay datos para mostrar en el gráfico');
        // Mostrar mensaje en lugar del gráfico
        document.querySelector('.chart-bar').innerHTML = 
            '<div class="alert alert-warning">No hay datos disponibles para mostrar el gráfico</div>';
        return;
    }
    try {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: departamentos,
                datasets: [{
                    label: 'Usuarios por Departamento',
                    data: totales,
                    backgroundColor: [
                        'rgba(78, 115, 223, 0.8)',
                        'rgba(54, 185, 204, 0.8)',
                        'rgba(255, 159, 64, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)'
                    ],
                    borderColor: [
                        'rgba(78, 115, 223, 1)',
                        'rgba(54, 185, 204, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                return `${context.dataset.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0 // Para mostrar números enteros
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error al crear el gráfico:', error);
        document.querySelector('.chart-bar').innerHTML = 
            '<div class="alert alert-danger">Error al cargar el gráfico</div>';
    }
});
</script>

<?php include 'footer.php'; ?>