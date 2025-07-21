<?php
require_once 'auth.php';
require_once 'config.php';

// Verificar rol y cargar usuarios si es admin
$usuarios = [];
if (esAdmin()) {
    $query = $conexion->query("SELECT id_usuario, nombre FROM usuarios WHERE id_rol = 2 ORDER BY nombre"); // Solo usuarios normales
    if ($query) {
        $usuarios = $query->fetch_all(MYSQLI_ASSOC);
    }
}

// Obtener campos adicionales disponibles
$campos_adicionales = [];
$query_campos = $conexion->query("SELECT id_campo, nombre, tipo FROM campos_adicionales");
if ($query_campos) {
    $campos_adicionales = $query_campos->fetch_all(MYSQLI_ASSOC);
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $id_estatus = 1; // Por defecto, pendiente
    
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
            
            // Insertar la tarea principal
            $stmt = $conexion->prepare("INSERT INTO tareas (titulo, descripcion, herramientas, id_usuario, id_usuario_asignado, id_estatus, fecha_creacion) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("sssiii", $titulo, $descripcion, $herramientas, $_SESSION['id_usuario'], $id_usuario_asignado, $id_estatus);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al insertar tarea: " . $stmt->error);
            }
            
            $id_tarea = $conexion->insert_id;
            
            // Procesar campos adicionales
            if (!empty($_POST['campos_adicionales'])) {
                foreach ($_POST['campos_adicionales'] as $id_campo => $valor) {
                    if (!empty($valor)) {
                        $stmt_campo = $conexion->prepare("INSERT INTO tareas_campos (id_tarea, id_campo, valor) VALUES (?, ?, ?)");
                        $stmt_campo->bind_param("iis", $id_tarea, $id_campo, $valor);
                        
                        if (!$stmt_campo->execute()) {
                            throw new Exception("Error al insertar campo adicional: " . $stmt_campo->error);
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
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                </div>
                
                <div class="mb-3">
                    <label for="herramientas" class="form-label">Herramientas/Recursos Necesarios</label>
                    <textarea class="form-control" id="herramientas" name="herramientas" rows="2"></textarea>
                </div>
                
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
        
        <!-- Campos adicionales -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check"></i> Campos Adicionales</span>
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
            <select class="form-select campo-select" name="campo_seleccionado">
                <option value="">Seleccione un campo...</option>
                <?php foreach ($campos_adicionales as $campo): ?>
                    <option value="<?= $campo['id_campo'] ?>" data-tipo="<?= $campo['tipo'] ?>">
                        <?= htmlspecialchars($campo['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <input type="text" class="form-control campo-valor" name="campo_valor" disabled>
            <textarea class="form-control campo-valor" name="campo_valor" rows="2" style="display: none;" disabled></textarea>
            <input type="date" class="form-control campo-valor" name="campo_valor" style="display: none;" disabled>
            <select class="form-select campo-valor" name="campo_valor" style="display: none;" disabled>
                <option value="1">Sí</option>
                <option value="0">No</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="button" class="btn btn-danger btn-eliminar-campo">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Contador para campos adicionales
    let contadorCampos = 0;
    
    // Agregar nuevo campo
    document.getElementById('agregarCampo').addEventListener('click', function() {
        const plantilla = document.getElementById('plantillaCampo');
        const nuevoCampo = plantilla.cloneNode(true);
        nuevoCampo.style.display = 'block';
        nuevoCampo.id = '';
        
        // Actualizar nombres de los campos para el formulario
        const selects = nuevoCampo.querySelectorAll('select[name="campo_seleccionado"]');
        const inputs = nuevoCampo.querySelectorAll('.campo-valor');
        
        selects.forEach(select => {
            select.name = `campos_adicionales[${contadorCampos}]`;
        });
        
        inputs.forEach(input => {
            input.name = `campos_adicionales_valor[${contadorCampos}]`;
            input.disabled = false;
        });
        
        // Agregar al contenedor
        document.getElementById('camposAdicionalesContainer').appendChild(nuevoCampo);
        contadorCampos++;
        
        // Configurar evento para cambiar tipo de campo
        configurarEventosCampo(nuevoCampo);
    });
    
    // Eliminar campo
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-eliminar-campo')) {
            e.target.closest('.campo-adicional').remove();
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
    
    // Función para configurar eventos de cambio de tipo de campo
    function configurarEventosCampo(campoElement) {
        const selectCampo = campoElement.querySelector('.campo-select');
        const contenedorValor = campoElement.querySelector('.col-md-6');
        
        selectCampo.addEventListener('change', function() {
            const tipo = this.options[this.selectedIndex].dataset.tipo;
            const inputs = contenedorValor.querySelectorAll('.campo-valor');
            
            // Ocultar todos los inputs primero
            inputs.forEach(input => {
                input.style.display = 'none';
                input.disabled = true;
            });
            
            // Mostrar el input correspondiente al tipo
            let inputMostrar;
            switch(tipo) {
                case 'texto':
                    inputMostrar = contenedorValor.querySelector('.campo-valor[type="text"]');
                    break;
                case 'textarea':
                    inputMostrar = contenedorValor.querySelector('.campo-valor[type="textarea"]');
                    break;
                case 'fecha':
                    inputMostrar = contenedorValor.querySelector('.campo-valor[type="date"]');
                    break;
                case 'booleano':
                    inputMostrar = contenedorValor.querySelector('.campo-valor[type="select"]');
                    break;
                default:
                    inputMostrar = contenedorValor.querySelector('.campo-valor[type="text"]');
            }
            
            if (inputMostrar) {
                inputMostrar.style.display = 'block';
                inputMostrar.disabled = false;
                inputMostrar.required = selectCampo.required;
            }
        });
    }
});
</script>

<?php include 'footer.php'; ?>