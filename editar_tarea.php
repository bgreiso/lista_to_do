<?php
require_once 'config.php';
require_once 'auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: tablero.php');
    exit();
}

$id_tarea = intval($_GET['id']);

// Obtener información de la tarea
$tarea = [];
$query = $conexion->prepare("
    SELECT t.*, u.nombre as asignado, e.nombre as estado, u_creador.nombre as creador
    FROM tareas t
    JOIN usuarios u ON t.id_usuario_asignado = u.id_usuario
    JOIN usuarios u_creador ON t.id_usuario = u_creador.id_usuario
    JOIN estatus e ON t.id_estatus = e.id_estatus
    WHERE t.id_tarea = ?
");
$query->bind_param("i", $id_tarea);
$query->execute();
$resultado = $query->get_result();

if ($resultado->num_rows === 0) {
    header('Location: tablero.php');
    exit();
}

$tarea = $resultado->fetch_assoc();

// Obtener campos adicionales
$campos_adicionales = [];
$query_campos = $conexion->prepare("
    SELECT * FROM campos_adicionales 
    WHERE id_tarea = ?
    ORDER BY id_campo ASC
");
$query_campos->bind_param("i", $id_tarea);
$query_campos->execute();
$resultado_campos = $query_campos->get_result();
$campos_adicionales = $resultado_campos->fetch_all(MYSQLI_ASSOC);

// Obtener subtareas
$subtareas = [];
$query_subtareas = $conexion->prepare("
    SELECT * FROM tareas 
    WHERE id_tarea_padre = ?
    ORDER BY fecha_creacion ASC
");
$query_subtareas->bind_param("i", $id_tarea);
$query_subtareas->execute();
$resultado_subtareas = $query_subtareas->get_result();
$subtareas = $resultado_subtareas->fetch_all(MYSQLI_ASSOC);

// Cargar usuarios para asignación (si es admin)
$usuarios = [];
if (esAdmin()) {
    $query_usuarios = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_rol = 2 ORDER BY nombre");
    if ($query_usuarios) {
        $usuarios = $query_usuarios->fetch_all(MYSQLI_ASSOC);
    }
}

// Cargar estatus disponibles
$estatus = [];
$query_estatus = $conexion->query("SELECT * FROM estatus ORDER BY id_estatus");
if ($query_estatus) {
    $estatus = $query_estatus->fetch_all(MYSQLI_ASSOC);
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-pencil-square"></i> Editar Tarea</h2>
        <a href="ver.php?id=<?= $id_tarea ?>" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la tarea
        </a>
    </div>
    
    <?php if (isset($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success"><?= $_SESSION['mensaje_exito']; unset($_SESSION['mensaje_exito']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['mensaje_error']; unset($_SESSION['mensaje_error']); ?></div>
    <?php endif; ?>

    <form method="post" action="actualizar_tarea.php" class="needs-validation" novalidate>
        <input type="hidden" name="id_tarea" value="<?= $id_tarea ?>">
        
        <!-- Sección básica de la tarea -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-card-heading"></i> Información Básica
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="<?= htmlspecialchars($tarea['titulo']) ?>" required>
                    <div class="invalid-feedback">
                        Por favor ingresa un título para la tarea.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <input type="text" class="form-control" id="categoria" name="categoria" 
                           value="<?= htmlspecialchars($tarea['categoria']) ?>" placeholder="Ej: Desarrollo, Diseño, Marketing...">
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($tarea['descripcion']) ?></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="herramientas" class="form-label">Herramientas/Recursos Necesarios</label>
                    <textarea class="form-control" id="herramientas" name="herramientas" rows="2"><?= htmlspecialchars($tarea['herramientas']) ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="id_estatus" class="form-label">Estado</label>
                        <select class="form-select" id="id_estatus" name="id_estatus" required>
                            <?php foreach ($estatus as $estado): ?>
                                <option value="<?= $estado['id_estatus'] ?>" <?= $estado['id_estatus'] == $tarea['id_estatus'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($estado['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="id_usuario_asignado" class="form-label">Asignar a</label>
                        <?php if (esAdmin() && !empty($usuarios)): ?>
                            <select class="form-select" id="id_usuario_asignado" name="id_usuario_asignado" required>
                                <?php foreach ($usuarios as $usuario): ?>
                                    <option value="<?= $usuario['id_usuario'] ?>" <?= $usuario['id_usuario'] == $tarea['id_usuario_asignado'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($usuario['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($tarea['asignado']) ?>" readonly>
                            <input type="hidden" name="id_usuario_asignado" value="<?= $tarea['id_usuario_asignado'] ?>">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sección de subtareas -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-task"></i> Subtareas</span>
                <button type="button" class="btn btn-sm btn-light" id="agregarSubtarea">
                    <i class="bi bi-plus"></i> Agregar Subtarea
                </button>
            </div>
            <div class="card-body" id="subtareasContainer">
                <?php foreach ($subtareas as $index => $subtarea): ?>
                    <div class="mb-3 subtarea">
                        <div class="card">
                            <div class="card-body">
                                <div class="row g-3 align-items-center">
                                    <div class="col-md-5">
                                        <input type="hidden" name="subtareas[<?= $index ?>][id_subtarea]" value="<?= $subtarea['id_tarea'] ?>">
                                        <input type="text" class="form-control" name="subtareas[<?= $index ?>][titulo]" 
                                               value="<?= htmlspecialchars($subtarea['titulo']) ?>" placeholder="Título de la subtarea" required>
                                    </div>
                                    <div class="col-md-5">
                                        <textarea class="form-control" name="subtareas[<?= $index ?>][descripcion]" rows="1" 
                                                  placeholder="Descripción (opcional)"><?= htmlspecialchars($subtarea['descripcion']) ?></textarea>
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
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Campos adicionales personalizados -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Campos Adicionales</span>
                <button type="button" class="btn btn-sm btn-light" id="agregarCampo">
                    <i class="bi bi-plus"></i> Agregar Campo
                </button>
            </div>
            <div class="card-body" id="camposAdicionalesContainer">
                <?php foreach ($campos_adicionales as $index => $campo): ?>
                    <div class="mb-3 campo-adicional">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <input type="hidden" name="campos_adicionales[<?= $index ?>][id_campo]" value="<?= $campo['id_campo'] ?>">
                                <input type="text" class="form-control" name="campos_adicionales[<?= $index ?>][nombre]" 
                                       value="<?= htmlspecialchars($campo['nombre']) ?>" placeholder="Nombre del campo">
                            </div>
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="campos_adicionales[<?= $index ?>][valor]" 
                                       value="<?= htmlspecialchars($campo['valor']) ?>" placeholder="Valor">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger btn-eliminar-campo">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="reset" class="btn btn-outline-secondary me-md-2">
                <i class="bi bi-x-circle"></i> Restablecer
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar Cambios
            </button>
        </div>
    </form>
</div>

<!-- Plantilla para campos adicionales (hidden) -->
<div id="plantillaCampo" class="mb-3 campo-adicional" style="display: none;">
    <div class="row g-3 align-items-center">
        <div class="col-md-4">
            <input type="hidden" name="campos_adicionales[{{index}}][id_campo]" value="0">
            <input type="text" class="form-control" name="campos_adicionales[{{index}}][nombre]" placeholder="Nombre del campo">
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control" name="campos_adicionales[{{index}}][valor]" placeholder="Valor">
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-eliminar-campo">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>

<!-- Plantilla para subtareas (hidden) -->
<div id="plantillaSubtarea" class="mb-3 subtarea" style="display: none;">
    <div class="card">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <input type="hidden" name="subtareas[{{index}}][id_subtarea]" value="0">
                    <input type="text" class="form-control" name="subtareas[{{index}}][titulo]" placeholder="Título de la subtarea" required>
                </div>
                <div class="col-md-5">
                    <textarea class="form-control" name="subtareas[{{index}}][descripcion]" rows="1" placeholder="Descripción (opcional)"></textarea>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contadores para campos y subtareas
    let contadorCampos = <?= count($campos_adicionales) ?>;
    let contadorSubtareas = <?= count($subtareas) ?>;
    
    // Agregar nuevo campo adicional
    document.getElementById('agregarCampo').addEventListener('click', function() {
        const plantilla = document.getElementById('plantillaCampo');
        const nuevoCampo = plantilla.cloneNode(true);
        nuevoCampo.style.display = 'block';
        nuevoCampo.id = '';
        
        // Reemplazar marcadores de posición
        const html = nuevoCampo.innerHTML.replace(/{{index}}/g, contadorCampos);
        nuevoCampo.innerHTML = html;
        
        // Agregar al contenedor
        document.getElementById('camposAdicionalesContainer').appendChild(nuevoCampo);
        contadorCampos++;
    });
    
    // Agregar nueva subtarea
    document.getElementById('agregarSubtarea').addEventListener('click', function() {
        const plantilla = document.getElementById('plantillaSubtarea');
        const nuevaSubtarea = plantilla.cloneNode(true);
        nuevaSubtarea.style.display = 'block';
        nuevaSubtarea.id = '';
        
        // Reemplazar marcadores de posición
        const html = nuevaSubtarea.innerHTML.replace(/{{index}}/g, contadorSubtareas);
        nuevaSubtarea.innerHTML = html;
        
        // Agregar al contenedor
        document.getElementById('subtareasContainer').appendChild(nuevaSubtarea);
        contadorSubtareas++;
    });
    
    // Eliminar campo adicional
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-eliminar-campo')) {
            e.target.closest('.campo-adicional').remove();
        }
        
        if (e.target.classList.contains('btn-eliminar-subtarea')) {
            e.target.closest('.subtarea').remove();
        }
    });
    
    // Validación de formulario
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
});
</script>

<?php include 'footer.php'; ?>