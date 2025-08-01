<?php
require_once 'config.php';
require_once 'auth.php';
include 'header.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Verificar autenticación y permisos
verificarAutenticacion();
$es_admin = esAdmin();
$usuario_actual = obtenerUsuario();

// Clase para manejar reportes
class ReporteTareas {
    private $conexion;
    
    public function __construct($conexion) {
        $this->conexion = $conexion;
    }
    
    // Obtener filtros con validación
    public function obtenerFiltros($es_admin, $usuario_actual) {
        return [
            'mes' => filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT, [
                'options' => ['default' => date('m'), 'min_range' => 1, 'max_range' => 12]
            ]),
            'ano' => filter_input(INPUT_GET, 'ano', FILTER_VALIDATE_INT, [
                'options' => ['default' => date('Y'), 'min_range' => 2020, 'max_range' => date('Y')]
            ]),
            'usuario' => $es_admin ? filter_input(INPUT_GET, 'usuario', FILTER_VALIDATE_INT) : $usuario_actual['id'],
            'departamento' => filter_input(INPUT_GET, 'departamento', FILTER_VALIDATE_INT)
        ];
    }
    
    // Obtener tareas culminadas
    public function obtenerTareasCulminadas($filtros) {
        $query = "SELECT t.*, u.nombre as asignado, d.nombre as departamento 
                 FROM tareas t
                 JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
                 JOIN departamentos d ON u.id_departamento = d.id_departamento
                 WHERE t.id_estatus = 3
                 AND MONTH(t.fecha_finalizacion) = ?
                 AND YEAR(t.fecha_finalizacion) = ?";
        
        $params = [$filtros['mes'], $filtros['ano']];
        $types = 'ii';
        
        if ($filtros['usuario']) {
            $query .= " AND t.id_usuario_asignado = ?";
            $params[] = $filtros['usuario'];
            $types .= 'i';
        }
        
        if ($filtros['departamento']) {
            $query .= " AND d.id_departamento = ?";
            $params[] = $filtros['departamento'];
            $types .= 'i';
        }
        
        $query .= " ORDER BY t.fecha_finalizacion DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtener tareas pendientes
    public function obtenerTareasPendientes($filtros) {
        $query = "SELECT t.*, u.nombre as asignado, d.nombre as departamento 
                 FROM tareas t
                 JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
                 JOIN departamentos d ON u.id_departamento = d.id_departamento
                 WHERE t.id_estatus = 1
                 AND MONTH(t.fecha_creacion) = ?
                 AND YEAR(t.fecha_creacion) = ?";
        
        $params = [$filtros['mes'], $filtros['ano']];
        $types = 'ii';
        
        if ($filtros['usuario']) {
            $query .= " AND t.id_usuario_asignado = ?";
            $params[] = $filtros['usuario'];
            $types .= 'i';
        }
        
        if ($filtros['departamento']) {
            $query .= " AND d.id_departamento = ?";
            $params[] = $filtros['departamento'];
            $types .= 'i';
        }
        
        $query .= " ORDER BY t.fecha_creacion DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtener resumen por departamento
    public function obtenerResumenDepartamentos($filtros) {
        $query = "SELECT d.nombre as departamento, 
                         COUNT(CASE WHEN t.id_estatus = 3 THEN 1 END) as culminadas,
                         COUNT(CASE WHEN t.id_estatus = 1 THEN 1 END) as pendientes
                  FROM tareas t
                  JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
                  JOIN departamentos d ON u.id_departamento = d.id_departamento
                  WHERE MONTH(t.fecha_creacion) = ? AND YEAR(t.fecha_creacion) = ?";
        
        $params = [$filtros['mes'], $filtros['ano']];
        
        if ($filtros['departamento']) {
            $query .= " AND d.id_departamento = ?";
            $params[] = $filtros['departamento'];
        }
        
        $query .= " GROUP BY d.nombre ORDER BY culminadas DESC";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtener datos para gráfica mensual
    public function obtenerDatosGraficaMensual($ano) {
        $query = "SELECT 
                    MONTH(fecha_finalizacion) as mes,
                    COUNT(*) as completadas
                  FROM tareas
                  WHERE id_estatus = 3
                  AND YEAR(fecha_finalizacion) = ?
                  GROUP BY MONTH(fecha_finalizacion)
                  ORDER BY mes";
        
        $stmt = $this->conexion->prepare($query);
        $stmt->bind_param('i', $ano);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    // Obtener datos para filtros
    public function obtenerUsuarios() {
        $result = $this->conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    public function obtenerDepartamentos() {
        $result = $this->conexion->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

// Inicializar reporte
$reporte = new ReporteTareas($conexion);
$filtros = $reporte->obtenerFiltros($es_admin, $usuario_actual);
$usuarios = $reporte->obtenerUsuarios();
$departamentos = $reporte->obtenerDepartamentos();

// Obtener datos
$tareas_culminadas = $reporte->obtenerTareasCulminadas($filtros);
$tareas_pendientes = $reporte->obtenerTareasPendientes($filtros);
$resumen_departamentos = $reporte->obtenerResumenDepartamentos($filtros);
$datos_grafica_mensual = $reporte->obtenerDatosGraficaMensual($filtros['ano']);

if (isset($_GET['exportar']) && $_GET['exportar'] == 'excel') {
    // 1. Crear nuevo documento
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    // 2. Obtener campos adicionales (código original)
    $campos_adicionales = [];
    $result_campos = $conexion->query("SELECT DISTINCT nombre FROM campos_adicionales ORDER BY nombre");
    if ($result_campos) {
        $campos_adicionales = $result_campos->fetch_all(MYSQLI_ASSOC);
    }

    // 3. Consulta para tareas culminadas (código original)
    $query = "SELECT 
                t.id_tarea, 
                t.titulo, 
                t.categoria, 
                t.descripcion, 
                t.herramientas,
                (SELECT GROUP_CONCAT(titulo SEPARATOR ' | ') 
                 FROM tareas WHERE id_tarea_padre = t.id_tarea) as subtareas
              FROM tareas t
              WHERE t.id_estatus = 3
              AND MONTH(t.fecha_finalizacion) = ?
              AND YEAR(t.fecha_finalizacion) = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('ii', $filtros['mes'], $filtros['ano']);
    $stmt->execute();
    $tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // 4. Configurar encabezados
    $columnas = [
        'A' => 'Título',
        'B' => 'Subtareas', 
        'C' => 'Categoría',
        'D' => 'Descripción',
        'E' => 'Herramientas'
    ];
    
    $columna_actual = 'F';
    foreach ($campos_adicionales as $campo) {
        $columnas[$columna_actual] = $campo['nombre'];
        $columna_actual++;
    }
    
    foreach ($columnas as $col => $titulo) {
        $sheet->setCellValue($col.'1', $titulo);
    }

    // 5. Llenar datos
    $fila = 2;
    foreach ($tareas as $tarea) {
        $sheet->setCellValue('A'.$fila, $tarea['titulo']);
        $sheet->setCellValue('B'.$fila, $tarea['subtareas'] ?? '');
        $sheet->setCellValue('C'.$fila, $tarea['categoria']);
        $sheet->setCellValue('D'.$fila, $tarea['descripcion']);
        $sheet->setCellValue('E'.$fila, $tarea['herramientas']);
        
        // Obtener campos adicionales
        $res_campos = $conexion->prepare("SELECT nombre, valor FROM campos_adicionales WHERE id_tarea = ?");
        $res_campos->bind_param('i', $tarea['id_tarea']);
        $res_campos->execute();
        $resultado = $res_campos->get_result();
        $valores_campos = [];
        while ($campo = $resultado->fetch_assoc()) {
            $valores_campos[$campo['nombre']] = $campo['valor'];
        }
        
        // Llenar campos adicionales
        $columna_actual = 'F';
        foreach ($campos_adicionales as $campo) {
            $sheet->setCellValue($columna_actual.$fila, $valores_campos[$campo['nombre']] ?? '');
            $columna_actual++;
        }
        
        $fila++;
    }

    // 6. Configurar respuesta para descarga
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="reporte_tareas_'.date('Y-m-d').'.xlsx"');
    header('Cache-Control: max-age=0');
    
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

$datosGrafica = [
    'departamentos' => array_column($resumen_departamentos, 'departamento'),
    'culminadas' => array_column($resumen_departamentos, 'culminadas'),
    'pendientes' => array_column($resumen_departamentos, 'pendientes'),
    'mensual' => $datos_grafica_mensual
];
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Reporte Mensual de Tareas</h1>
    
    <!-- Filtros mejorados en línea -->
    <div class="card shadow mb-4">
        <div class="card-body p-4">
            <form method="get" class="form">
                <div class="row">
                    <div class="col-md-2">
                        <label>Mes</label>
                        <select name="mes" class="form-control">
                            <?php for($i=1; $i<=12; $i++): ?>
                                <option value="<?= $i ?>" <?= $filtros['mes'] == $i ? 'selected' : '' ?>>
                                    <?= DateTime::createFromFormat('!m', $i)->format('M') ?>
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
                    <?php if($es_admin): ?>
                    <div class="col-md-3">
                        <label>Usuario</label>
                        <select name="usuario" class="form-control">
                            <option value="">Todos los usuarios</option>
                            <?php foreach($usuarios as $u): ?>
                                <option value="<?= $u['id_usuario'] ?>" <?= $filtros['usuario'] == $u['id_usuario'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label>Departamento</label>
                        <select name="departamento" class="form-control">
                            <option value="">Todos los departamentos</option>
                            <?php foreach($departamentos as $d): ?>
                                <option value="<?= $d['id_departamento'] ?>" <?= $filtros['departamento'] == $d['id_departamento'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($d['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-1">
                            Filtrar
                        </button>
                        <a href="reportes.php" class="btn btn-secondary">
                            Limpiar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row mb-4">
        <!-- Gráfico de desempeño por departamento -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Desempeño por Departamento</h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar pt-4">
                        <canvas id="departamentosChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráfico de tareas completadas por mes -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tareas Completadas en <?= $filtros['ano'] ?></h6>
                </div>
                <div class="card-body">
                    <div class="chart-bar pt-4">
                        <canvas id="mensualChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tareas culminadas con opción de exportar -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                Tareas Culminadas (<?= count($tareas_culminadas) ?>)
                <small class="text-muted ml-2">
                <?= date('F Y', mktime(0, 0, 0, $filtros['mes'], 1, $filtros['ano'])) ?>
                </small>
            </h6>
            <div>
                <a href="reportes.php?<?= http_build_query(array_merge($_GET, ['exportar' => 'excel'])) ?>" 
                   class="btn btn-success btn-sm mr-2">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </a>
            </div>
        </div>
        <div class="collapse show" id="detalleCulminadas">
            <div class="card-body">
                <?php if(!empty($tareas_culminadas)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Asignado</th>
                                    <th>Departamento</th>
                                    <th>Creación</th>
                                    <th>Finalización</th>
                                    <th>Duración</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tareas_culminadas as $tarea): 
                                    $dias = (strtotime($tarea['fecha_finalizacion']) - strtotime($tarea['fecha_creacion'])) / (60 * 60 * 24);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                                    <td><?= htmlspecialchars($tarea['asignado']) ?></td>
                                    <td><?= htmlspecialchars($tarea['departamento']) ?></td>
                                    <td><?= date('d/m/y', strtotime($tarea['fecha_creacion'])) ?></td>
                                    <td><?= date('d/m/y', strtotime($tarea['fecha_finalizacion'])) ?></td>
                                    <td><?= round($dias) ?> días</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No hay tareas culminadas para este periodo.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tareas pendientes -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-warning">
                Tareas Pendientes (<?= count($tareas_pendientes) ?>)
                <small class="text-muted ml-2">
                <?= date('F Y', mktime(0, 0, 0, $filtros['mes'], 1, $filtros['ano'])) ?>
                </small>
            </h6>
            <div>
                <button class="btn btn-sm btn-warning" data-toggle="collapse" data-target="#detallePendientes">
                    <i class="fas fa-chevron-down"></i> Ver/Ocultar
                </button>
            </div>
        </div>
        <div class="collapse" id="detallePendientes">
            <div class="card-body">
                <?php if(!empty($tareas_pendientes)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead>
                                <tr>
                                    <th>Título</th>
                                    <th>Asignado</th>
                                    <th>Departamento</th>
                                    <th>Creación</th>
                                    <th>Días pendiente</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($tareas_pendientes as $tarea): 
                                    $dias = (time() - strtotime($tarea['fecha_creacion'])) / (60 * 60 * 24);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($tarea['titulo']) ?></td>
                                    <td><?= htmlspecialchars($tarea['asignado']) ?></td>
                                    <td><?= htmlspecialchars($tarea['departamento']) ?></td>
                                    <td><?= date('d/m/y', strtotime($tarea['fecha_creacion'])) ?></td>
                                    <td class="<?= $dias > 30 ? 'text-danger font-weight-bold' : '' ?>">
                                        <?= round($dias) ?> días
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">¡No hay tareas pendientes para este periodo!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Script para gráficos -->
<script src="vendor/chart.js/Chart.min.js"></script>
<script>
// Espera a que jQuery esté disponible
function whenJQueryReady() {
    if (window.jQuery) {
        // Tu código que usa jQuery aquí
        $(document).ready(function() {
            // Inicialización de DataTables
            $('.table-datatable').DataTable();
        });
    } else {
        setTimeout(whenJQueryReady, 100);
    }
}
whenJQueryReady();

$(document).ready(function() {
    // Verifica que los elementos del gráfico existan
    const ctxDepartamentos = document.getElementById('departamentosChart')?.addEventListener(...)
    const ctxMensual = document.getElementById('mensualChart');
    
    if (!ctxDepartamentos || !ctxMensual) {
        console.error("No se encontraron los elementos del gráfico");
        return;
    }

    // Datos desde PHP (usa json_encode para seguridad)
    const datosDepartamentos = {
        labels: <?= json_encode(array_column($resumen_departamentos, 'departamento')) ?>,
        datasets: [
            {
                label: 'Tareas Culminadas',
                data: <?= json_encode(array_column($resumen_departamentos, 'culminadas')) ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.7)'
            },
            {
                label: 'Tareas Pendientes',
                data: <?= json_encode(array_column($resumen_departamentos, 'pendientes')) ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.7)'
            }
        ]
    };

    // Gráfico de Departamentos
    new Chart(ctxDepartamentos, {
        type: 'bar',
        data: datosDepartamentos,
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true } }
        }
    });

    // Gráfico Mensual (datos de ejemplo)
    const datosMensuales = <?= json_encode($datos_grafica_mensual) ?>;
    
    new Chart(ctxMensual, {
        type: 'line',
        data: {
            labels: ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            datasets: [{
                label: 'Tareas Completadas',
                data: datosMensuales,
                borderColor: 'rgba(54, 162, 235, 1)'
            }]
        }
    });
});
</script>

<?php include 'footer.php'; ?>