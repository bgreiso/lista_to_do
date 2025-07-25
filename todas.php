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
                        <div class="btn-group" role="group" aria-label="Acciones tarea">
                                <!-- Botón Editar con modal -->
                                <button type="button" 
                                        class="btn btn-primary btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editarTareaModal"
                                        data-id="<?= $tarea['id_tarea'] ?>"
                                        title="Editar tarea">
                                    <i class="bi bi-pencil-fill"></i>
                                </button>
                                
                                <button type="button" 
                                        class="btn btn-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#eliminarTareaModal"
                                        data-id="<?= $tarea['id_tarea'] ?>" 
                                        data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>"
                                        title="Eliminar tarea">
                                    <i class="bi bi-trash-fill"></i>
                                </button>
                                
                                <!-- Botón Ver Detalles -->
                                <a href="ver.php?id=<?= $tarea['id_tarea'] ?>" 
                                class="btn btn-success btn-sm" 
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

<!-- Modal para Editar Tarea -->
<div class="modal fade" id="editarTareaModal" tabindex="-1" aria-labelledby="editarTareaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarTareaModalLabel">Editar Tarea</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarTarea" action="editar_tarea.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_tarea" id="editIdTarea">
                    
                    <div class="mb-3">
                        <label for="editTitulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="editTitulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editDescripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="editDescripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editHerramientas" class="form-label">Herramientas/Recursos</label>
                        <textarea class="form-control" id="editHerramientas" name="herramientas" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editAsignado" class="form-label">Asignar a *</label>
                            <select class="form-select" id="editAsignado" name="id_usuario_asignado" required>
                                <?php 
                                $usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
                                while ($usuario = $usuarios->fetch_assoc()): ?>
                                    <option value="<?= $usuario['id_usuario'] ?>"><?= htmlspecialchars($usuario['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="editEstatus" class="form-label">Estatus *</label>
                            <select class="form-select" id="editEstatus" name="id_estatus" required>
                                <?php 
                                $estatus = $conexion->query("SELECT id_estatus, nombre FROM estatus ORDER BY id_estatus");
                                while ($est = $estatus->fetch_assoc()): ?>
                                    <option value="<?= $est['id_estatus'] ?>"><?= htmlspecialchars($est['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Eliminar Tarea -->
<div class="modal fade" id="eliminarTareaModal" tabindex="-1" aria-labelledby="eliminarTareaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="eliminarTareaModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEliminarTarea" action="eliminar_tarea.php" method="post">
                <input type="hidden" name="id_tarea" id="idTareaEliminar">
                <div class="modal-body">
                    <p>¿Está seguro que desea eliminar la tarea <strong id="tituloTareaEliminar"></strong>?</p>
                    <p class="text-danger">Esta acción no se puede deshacer.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Manejar modal de edición
document.addEventListener('DOMContentLoaded', function() {
    // Modal de edición
    var editarModal = document.getElementById('editarTareaModal');
    editarModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var idTarea = button.getAttribute('data-id');
        
        // Obtener datos de la tarea via fetch API
        fetch('obtener_tarea.php?id=' + idTarea)
            .then(response => response.json())
            .then(data => {
                document.getElementById('editIdTarea').value = data.id_tarea;
                document.getElementById('editTitulo').value = data.titulo;
                document.getElementById('editDescripcion').value = data.descripcion;
                document.getElementById('editHerramientas').value = data.herramientas;
                document.getElementById('editAsignado').value = data.id_usuario_asignado;
                document.getElementById('editEstatus').value = data.id_estatus;
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar los datos de la tarea');
            });
    });
    
    // Configurar el formulario de edición para evitar recarga de página
    document.getElementById('formEditarTarea').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const buttonSubmit = this.querySelector('button[type="submit"]');
        buttonSubmit.disabled = true;
        buttonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

        fetch('editar_tarea.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
        })
        .then(data => {
            // Recargar la página después de 1 segundo
            setTimeout(() => window.location.reload(), 1000);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al guardar: ' + error.message);
        })
        .finally(() => {
            buttonSubmit.disabled = false;
            buttonSubmit.innerHTML = 'Guardar Cambios';
        });
    });

    // Modal de eliminación
    const eliminarModal = document.getElementById('eliminarTareaModal');
    eliminarModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const idTarea = button.getAttribute('data-id');
        const tituloTarea = button.getAttribute('data-titulo');
        
        document.getElementById('idTareaEliminar').value = idTarea;
        document.getElementById('tituloTareaEliminar').textContent = tituloTarea;
    });

    // Configurar el formulario de eliminación
    document.getElementById('formEliminarTarea').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const buttonSubmit = this.querySelector('button[type="submit"]');
        buttonSubmit.disabled = true;
        buttonSubmit.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Eliminando...';

        fetch('eliminar_tarea.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recargar la página después de 1 segundo
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar: ' + error.message);
        })
        .finally(() => {
            buttonSubmit.disabled = false;
            buttonSubmit.innerHTML = 'Eliminar';
        });
    });
});
</script>

<?php include 'footer.php'; ?>