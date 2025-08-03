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
                            <td><?= htmlspecialchars($tarea['creador_nombre']) ?></td>
                            <td><?= htmlspecialchars($tarea['asignado_nombre']) ?></td>
                            <td><?= htmlspecialchars($tarea['departamento_nombre']) ?></td>
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
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <!-- Botón Editar -->
                                    <button type="button" 
                                            class="btn btn-primary btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editarTareaModal"
                                            data-id="<?= $tarea['id_tarea'] ?>"
                                            onclick="cargarDatosTarea(<?= $tarea['id_tarea'] ?>)">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    
                                    <!-- Botón Eliminar -->
                                    <button type="button" 
                                            class="btn btn-danger btn-sm" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#eliminarTareaModal"
                                            data-id="<?= $tarea['id_tarea'] ?>" 
                                            data-titulo="<?= htmlspecialchars($tarea['titulo']) ?>">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                    
                                    <!-- Botón Ver Detalles -->
                                    <a href="ver.php?id=<?= $tarea['id_tarea'] ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="bi bi-eye"></i>
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
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="editarTareaModalLabel">Editar Tarea</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarTarea" action="actualizar_tarea.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_tarea" id="editIdTarea">
                    
                    <div class="mb-3">
                        <label for="editTitulo" class="form-label">Título *</label>
                        <input type="text" class="form-control" id="editTitulo" name="titulo" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editCategoria" class="form-label">Categoría</label>
                        <input type="text" class="form-control" id="editCategoria" name="categoria">
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
                            <label for="editEstatus" class="form-label">Estatus *</label>
                            <select class="form-select" id="editEstatus" name="id_estatus" required>
                                <?php 
                                $estatus = $conexion->query("SELECT * FROM estatus ORDER BY id_estatus");
                                while ($est = $estatus->fetch_assoc()): ?>
                                    <option value="<?= $est['id_estatus'] ?>"><?= htmlspecialchars($est['nombre']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
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
                    </div>
                    
                    <!-- Sección para campos adicionales -->
                    <div class="card mb-3">
                        <div class="card-header bg-secondary text-white">
                            Campos Adicionales
                        </div>
                        <div class="card-body" id="camposAdicionalesContainer">
                            <!-- Los campos se cargarán dinámicamente -->
                        </div>
                    </div>
                    
                    <!-- Sección para subtareas -->
                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            Subtareas
                        </div>
                        <div class="card-body" id="subtareasContainer">
                            <!-- Las subtareas se cargarán dinámicamente -->
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
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="eliminar_tarea.php">
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
// Configurar modal de eliminación
document.getElementById('eliminarTareaModal').addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    const idTarea = button.getAttribute('data-id');
    const tituloTarea = button.getAttribute('data-titulo');
    
    const modal = this;
    modal.querySelector('#idTareaEliminar').value = idTarea;
    modal.querySelector('#tituloTareaEliminar').textContent = tituloTarea;
});

// Función para cargar datos de la tarea en el modal de edición
function cargarDatosTarea(idTarea) {
    fetch(`obtener_tarea.php?id=${idTarea}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Llenar campos básicos
                document.getElementById('editIdTarea').value = data.id_tarea;
                document.getElementById('editTitulo').value = data.titulo;
                document.getElementById('editDescripcion').value = data.descripcion || '';
                document.getElementById('editHerramientas').value = data.herramientas || '';
                document.getElementById('editEstatus').value = data.id_estatus;
                document.getElementById('editAsignado').value = data.id_usuario_asignado;
                
                // Cargar campos adicionales
                cargarCamposAdicionales(idTarea);
                
                // Cargar subtareas
                cargarSubtareas(idTarea);
            } else {
                alert('Error al cargar los datos de la tarea: ' + (data.error || 'Desconocido'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos de la tarea');
        });
}

function cargarCamposAdicionales(idTarea) {
    fetch(`obtener_campos_adicionales.php?id_tarea=${idTarea}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('camposAdicionalesContainer');
            container.innerHTML = '';
            
            if (data.success && data.campos.length > 0) {
                data.campos.forEach((campo, index) => {
                    const campoHTML = `
                        <div class="mb-3 campo-adicional">
                            <div class="row g-3 align-items-center">
                                <div class="col-md-4">
                                    <input type="hidden" name="campos_adicionales[${index}][id_campo]" value="${campo.id_campo}">
                                    <input type="text" class="form-control" name="campos_adicionales[${index}][nombre]" 
                                           value="${campo.nombre}" placeholder="Nombre del campo">
                                </div>
                                <div class="col-md-6">
                                    <input type="text" class="form-control" name="campos_adicionales[${index}][valor]" 
                                           value="${campo.valor}" placeholder="Valor">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-eliminar-campo">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                    container.innerHTML += campoHTML;
                });
            } else {
                container.innerHTML = '<p class="text-muted">No hay campos adicionales para esta tarea.</p>';
            }
        });
}

function cargarSubtareas(idTarea) {
    fetch(`obtener_subtareas.php?id_tarea=${idTarea}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('subtareasContainer');
            container.innerHTML = '';
            
            if (data.success && data.subtareas.length > 0) {
                data.subtareas.forEach((subtarea, index) => {
                    const subtareaHTML = `
                        <div class="mb-3 subtarea">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row g-3 align-items-center">
                                        <div class="col-md-5">
                                            <input type="hidden" name="subtareas[${index}][id_subtarea]" value="${subtarea.id_tarea}">
                                            <input type="text" class="form-control" name="subtareas[${index}][titulo]" 
                                                   value="${subtarea.titulo}" placeholder="Título de la subtarea" required>
                                        </div>
                                        <div class="col-md-5">
                                            <textarea class="form-control" name="subtareas[${index}][descripcion]" rows="1" 
                                                      placeholder="Descripción (opcional)">${subtarea.descripcion || ''}</textarea>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="button" class="btn btn-danger btn-eliminar-subtarea">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.innerHTML += subtareaHTML;
                });
            } else {
                container.innerHTML = '<p class="text-muted">No hay subtareas para esta tarea.</p>';
            }
        });
}

// Manejar el envío del formulario de edición
document.getElementById('formEditarTarea').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            window.location.href = data.redirect;
        } else {
            throw new Error(data.message || 'Error al guardar cambios');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Guardar Cambios';
    });
});

// Manejar el envío del formulario de eliminación
document.querySelector('#eliminarTareaModal form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const form = this;
    const formData = new FormData(form);
    const submitBtn = form.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Eliminando...';

    fetch(form.action, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Error en la respuesta del servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('eliminarTareaModal')).hide();
            window.location.href = data.redirect;
        } else {
            throw new Error(data.message || 'Error al eliminar la tarea');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Eliminar';
    });
});

// Manejar eliminación de campos y subtareas
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('btn-eliminar-campo')) {
        e.target.closest('.campo-adicional').remove();
    }
    
    if (e.target.classList.contains('btn-eliminar-subtarea')) {
        e.target.closest('.subtarea').remove();
    }
});
</script>

<?php include 'footer.php'; ?>