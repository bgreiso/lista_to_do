<?php
require_once 'auth.php';
require_once 'config.php';

// Verificar rol y cargar usuarios si es admin
$usuarios = [];
if (esAdmin()) {
    $query = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_rol = 2 ORDER BY nombre");
    if ($query) {
        $usuarios = $query->fetch_all(MYSQLI_ASSOC);
    }
}

// Cargar tareas existentes para asignar como padre (si es necesario)
$tareasExistentes = [];
$queryTareas = $conexion->query("SELECT id_tarea, titulo FROM tareas ORDER BY titulo");
if ($queryTareas) {
    $tareasExistentes = $queryTareas->fetch_all(MYSQLI_ASSOC);
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $id_estatus = 1; // Por defecto, pendiente
    $id_tarea_padre = isset($_POST['id_tarea_padre']) ? (int)$_POST['id_tarea_padre'] : null;
    
    // Determinar a quién se asigna la tarea
    $id_usuario_asignado = esAdmin() && isset($_POST['id_usuario_asignado']) 
        ? (int)$_POST['id_usuario_asignado'] 
        : $_SESSION['id_usuario'];
    
    // Validaciones básicas
    if (empty($titulo)) {
        $mensaje = '<div class="alert alert-danger">El título es obligatorio.</div>';
    } elseif (!$id_usuario_asignado) {
        $mensaje = '<div class="alert alert-danger">No se ha especificado un usuario asignado.</div>';
    } else {
        try {
            // Iniciar transacción
            $conexion->begin_transaction();

            if ($id_tarea_padre !== null) {
                // Verificar que la tarea padre existe
                $stmt_verificar = $conexion->prepare("SELECT id_tarea FROM tareas WHERE id_tarea = ?");
                $stmt_verificar->bind_param("i", $id_tarea_padre);
                $stmt_verificar->execute();
                $stmt_verificar->store_result();
                
                if ($stmt_verificar->num_rows === 0) {
                    $mensaje = '<div class="alert alert-danger">La tarea padre seleccionada no existe.</div>';
                    $id_tarea_padre = null; // Ignorar la relación si no existe
                }
            }
            
            // Insertar la tarea principal
            $stmt = $conexion->prepare("INSERT INTO tareas (titulo, descripcion, herramientas, categoria, id_usuario, id_usuario_asignado, id_estatus, fecha_creacion, id_tarea_padre) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?)");
            
            if (!$stmt) {
                throw new Exception("Error al preparar la consulta: " . $conexion->error);
            }
            
            $stmt->bind_param("ssssiiii", $titulo, $descripcion, $herramientas, $categoria, $_SESSION['id_usuario'], $id_usuario_asignado, $id_estatus, $id_tarea_padre);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al insertar tarea: " . $stmt->error);
            }
            
            $id_tarea = $conexion->insert_id;
            
            // Procesar campos adicionales (opcionales)
            if (!empty($_POST['campos_adicionales'])) {
                foreach ($_POST['campos_adicionales'] as $campo) {
                    if (!empty($campo['nombre']) && !empty($campo['valor'])) {
                        $stmt_campo = $conexion->prepare("INSERT INTO campos_adicionales (id_tarea, id_usuario, nombre, valor) VALUES (?, ?, ?, ?)");
                        
                        if (!$stmt_campo) {
                            throw new Exception("Error al preparar consulta de campo: " . $conexion->error);
                        }
                        
                        $stmt_campo->bind_param("iiss", $id_tarea, $_SESSION['id_usuario'], $campo['nombre'], $campo['valor']);
                        
                        if (!$stmt_campo->execute()) {
                            throw new Exception("Error al insertar campo adicional: " . $stmt_campo->error);
                        }
                    }
                }
            }
            
            // Procesar subtareas (si existen)
            if (!empty($_POST['subtareas'])) {
                foreach ($_POST['subtareas'] as $subtarea) {
                    if (!empty($subtarea['titulo'])) {
                        $stmt_subtarea = $conexion->prepare("INSERT INTO tareas (titulo, descripcion, id_usuario, id_usuario_asignado, id_estatus, fecha_creacion, id_tarea_padre) 
                                                             VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                        
                        if (!$stmt_subtarea) {
                            throw new Exception("Error al preparar consulta de subtarea: " . $conexion->error);
                        }
                        
                        $desc_subtarea = $subtarea['descripcion'] ?? '';
                        $stmt_subtarea->bind_param("sssiii", $subtarea['titulo'], $desc_subtarea, $_SESSION['id_usuario'], $id_usuario_asignado, $id_estatus, $id_tarea);
                        
                        if (!$stmt_subtarea->execute()) {
                            throw new Exception("Error al insertar subtarea: " . $stmt_subtarea->error);
                        }
                    }
                }
            }
            
            // Confirmar transacción
            $conexion->commit();
            $mensaje = '<div class="alert alert-success">Tarea creada exitosamente!</div>';
            
            // Redirigir después de 2 segundos
            header("Refresh: 2; url=ver.php?id=$id_tarea");
            
        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

include 'header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-plus-circle"></i> Crear Nueva Tarea</h2>
        <a href="tablero.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al tablero
        </a>
    </div>
    
    <?= $mensaje ?>
    
    <form method="post" class="needs-validation" novalidate>
        <!-- Sección básica de la tarea -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <i class="bi bi-card-heading"></i> Información Básica
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required>
                    <div class="invalid-feedback">
                        Por favor ingresa un título para la tarea.
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <input type="text" class="form-control" id="categoria" name="categoria" placeholder="Ej: Desarrollo, Diseño, Marketing...">
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="herramientas" class="form-label">Herramientas/Recursos Necesarios</label>
                    <textarea class="form-control" id="herramientas" name="herramientas" rows="2"></textarea>
                </div>
                
                <?php if (!empty($tareasExistentes)): ?>
                <div class="mb-3">
                    <label for="id_tarea_padre" class="form-label">Tarea padre (opcional)</label>
                    <select class="form-select" id="id_tarea_padre" name="id_tarea_padre">
                        <option value="">-- Ninguna (tarea principal) --</option>
                        <?php foreach ($tareasExistentes as $tarea): ?>
                            <option value="<?= $tarea['id_tarea'] ?>"><?= htmlspecialchars($tarea['titulo']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if (esAdmin() && !empty($usuarios)): ?>
                <div class="mb-3">
                    <label for="id_usuario_asignado" class="form-label">Asignar a</label>
                    <select class="form-select" id="id_usuario_asignado" name="id_usuario_asignado">
                        <option value="<?= $_SESSION['id_usuario'] ?>">Yo mismo</option>
                        <?php foreach ($usuarios as $usuario): ?>
                            <?php if ($usuario['id_usuario'] != $_SESSION['id_usuario']): ?>
                                <option value="<?= $usuario['id_usuario'] ?>"><?= htmlspecialchars($usuario['nombre']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                    <input type="hidden" name="id_usuario_asignado" value="<?= $_SESSION['id_usuario'] ?>">
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sección de subtareas -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-task"></i> Subtareas (Opcionales)</span>
                <button type="button" class="btn btn-sm btn-light" id="agregarSubtarea">
                    <i class="bi bi-plus"></i> Agregar Subtarea
                </button>
            </div>
            <div class="card-body" id="subtareasContainer">
                <!-- Las subtareas se agregarán aquí dinámicamente -->
            </div>
        </div>
        
        <!-- Campos adicionales personalizados -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Campos Adicionales (Opcionales)</span>
                <button type="button" class="btn btn-sm btn-light" id="agregarCampo">
                    <i class="bi bi-plus"></i> Agregar Campo
                </button>
            </div>
            <div class="card-body" id="camposAdicionalesContainer">
                <!-- Los campos adicionales se agregarán aquí dinámicamente -->
            </div>
        </div>
        
        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
            <button type="reset" class="btn btn-outline-secondary me-md-2">
                <i class="bi bi-x-circle"></i> Limpiar
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-send-check"></i> Crear Tarea
            </button>
        </div>
    </form>
</div>

<!-- Plantilla para campos adicionales (hidden) -->
<div id="plantillaCampo" class="mb-3 campo-adicional" style="display: none;">
    <div class="row g-3 align-items-center">
        <div class="col-md-4">
            <input type="text" class="form-control campo-nombre" name="campo_nombre" placeholder="Nombre del campo">
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control campo-valor" name="campo_valor" placeholder="Valor">
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
    <div class="card mb-2">
        <div class="card-body">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <input type="text" class="form-control subtarea-titulo" name="subtarea_titulo" placeholder="Título de la subtarea" required>
                </div>
                <div class="col-md-5">
                    <textarea class="form-control subtarea-descripcion" name="subtarea_descripcion" rows="1" placeholder="Descripción (opcional)"></textarea>
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
    // Contador para campos adicionales
    let contadorCampos = 0;
    let contadorSubtareas = 0;
    
    // Agregar nuevo campo adicional
    document.getElementById('agregarCampo').addEventListener('click', function() {
        const plantilla = document.getElementById('plantillaCampo');
        const nuevoCampo = plantilla.cloneNode(true);
        nuevoCampo.style.display = 'block';
        nuevoCampo.id = '';
        
        // Actualizar nombres de los campos para el formulario
        const nombreCampo = nuevoCampo.querySelector('.campo-nombre');
        const valorCampo = nuevoCampo.querySelector('.campo-valor');
        
        nombreCampo.name = `campos_adicionales[${contadorCampos}][nombre]`;
        valorCampo.name = `campos_adicionales[${contadorCampos}][valor]`;
        
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
        
        // Actualizar nombres de los campos para el formulario
        const tituloSubtarea = nuevaSubtarea.querySelector('.subtarea-titulo');
        const descSubtarea = nuevaSubtarea.querySelector('.subtarea-descripcion');
        
        tituloSubtarea.name = `subtareas[${contadorSubtareas}][titulo]`;
        descSubtarea.name = `subtareas[${contadorSubtareas}][descripcion]`;
        
        // Agregar al contenedor
        document.getElementById('subtareasContainer').appendChild(nuevaSubtarea);
        contadorSubtareas++;
    });
    
    // Eliminar campo adicional
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-eliminar-campo')) {
            e.target.closest('.campo-adicional').remove();
        }
    });
    
    // Eliminar subtarea
    document.addEventListener('click', function(e) {
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