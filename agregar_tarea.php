<?php
session_start();
require_once '../config.php';

$es_admin = ($_SESSION['rol'] ?? '') === '1';

// Lista de usuarios para asignación (solo si es admin)
$usuarios = [];
if ($es_admin) {
    $query = $conexion->query("SELECT id_usuario, nombre FROM usuarios ORDER BY nombre");
    if ($query) {
        $usuarios = $query->fetch_all(MYSQLI_ASSOC);
    }
}

// Campos adicionales disponibles
$campos_adicionales = [];
$query_campos = $conexion->query("SELECT id_campo, id_tarea, id_usuario, nombre, descripcion FROM campos_adicionales");
if ($query_campos) {
    $campos_adicionales = $query_campos->fetch_all(MYSQLI_ASSOC);
}

$mensaje = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $id_usuario = trim($_POST['id_usuario'] ?? '');
    $id_estatus = 1; // Por defecto
   
    // Determinar a quién se asigna la tarea
    if ($es_admin && isset($_POST['id_usuario_asignado'])) {
        $id_usuario_asignado = isset($_POST['id_usuario_asignado']) ? (int)$_POST['id_usuario_asignado'] : (int)$_SESSION['id_usuario'];
    } else {
        $id_usuario_asignado = $_SESSION['id_usuario'];
    }
   
    // Validaciones
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
           
            // Campos adicionales
            if (!empty($_POST['campos_adicionales']) && !empty($_POST['campos_adicionales_valor'])) {
                foreach ($_POST['campos_adicionales'] as $idx => $id_campo) {
                    $valor = $_POST['campos_adicionales_valor'][$idx] ?? '';
                    if (!empty($valor)) {
                        // Buscar datos del campo adicional
                        $campo = array_filter($campos_adicionales, function($c) use ($id_campo) {
                            return $c['id_campo'] == $id_campo;
                        });
                        $campo = reset($campo);
                        $id_usuario = $_SESSION['id_usuario'];
                        $nombre = $campo['nombre'] ?? '';
                        $descripcion = $campo['descripcion'] ?? '';
                        $stmt_campo = $conexion->prepare("INSERT INTO campos_adicionales (id_tarea, id_campo, id_usuario, nombre, descripcion, valor) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt_campo->bind_param("iiisss", $id_tarea, $id_campo, $id_usuario, $nombre, $descripcion, $valor);
                        if (!$stmt_campo->execute()) {
                            throw new Exception("Error al insertar campo adicional: " . $stmt_campo->error);
                        }
                    }
                }
            }
           
            // Confirmar transacción
            $conexion->commit();
            $mensaje = '<div class="alert alert-success">Tarea creada exitosamente!</div>';
           
           
        } catch (Exception $e) {
            $conexion->rollback();
            $mensaje = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
        }
    }
}

include '../includes/header.php';
?>

<div class="container mt-4">
    <h2><i class="bi bi-plus-circle"></i> Crear Nueva Tarea</h2>
   
    <?= $mensaje ?>
   
    <form method="post" class="needs-validation" novalidate>
        <!-- Sección básica de la tarea -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                Información Básica
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
               
                <?php if ($es_admin && !empty($usuarios)): ?>
                <?php
                    // dentro del formulario
                ?>
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
                <?php endif; ?>
            </div>
        </div>
       
        <!-- Campos adicionales -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                <span>Campos Adicionales</span>
                <button type="button" class="btn btn-sm btn-light" id="agregarCampo">
                    <i class="bi bi-plus"></i> Agregar Campo
                </button>
            </div>
            <div class="card-body" id="camposAdicionalesContainer">
                <!-- Los campos adicionales se agregarán aquí dinámicamente -->
            </div>
        </div>
       
        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
            <a href="todas.php" class="btn btn-secondary me-md-2">
                <i class="bi bi-arrow-left"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Guardar Tarea
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
                    inputMostrar = contenedorValor.querySelector('input[type="text"].campo-valor');
                    break;
                case 'textarea':
                    inputMostrar = contenedorValor.querySelector('textarea.campo-valor');
                    break;
                case 'fecha':
                    inputMostrar = contenedorValor.querySelector('input[type="date"].campo-valor');
                    break;
                case 'booleano':
                    inputMostrar = contenedorValor.querySelector('select.campo-valor');
                    break;
                default:
                    inputMostrar = contenedorValor.querySelector('input[type="text"].campo-valor');
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

<?php include '../includes/footer.php'; ?>
