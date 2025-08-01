<?php
require_once 'config.php';
include 'header.php';

// Filtros
$filtros = [
    'departamento' => $_GET['departamento'] ?? null,
];

// Construir consulta con filtros
$query = "SELECT u.*, d.nombre as departamento, r.nombre as rol
          FROM usuarios u
          LEFT JOIN departamentos d ON u.id_departamento = d.id_departamento
          LEFT JOIN roles r ON u.id_rol = r.id_rol
          WHERE 1=1";

if ($filtros['departamento']) {
    $query .= " AND d.id_departamento = ?";
    $params[] = $filtros['departamento'];
    $types = 'i';
}

$stmt = $conexion->prepare($query);
if (isset($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$tareas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Obtener departamentos para el filtro
$departamentos = [];
$query_deps = $conexion->query("SELECT id_departamento, nombre FROM departamentos ORDER BY nombre");
if ($query_deps) {
    $departamentos = $query_deps->fetch_all(MYSQLI_ASSOC);
}

// Obtener roles para el modal de edición
$roles = [];
$query_roles = $conexion->query("SELECT id_rol, nombre FROM roles ORDER BY nombre");
if ($query_roles) {
    $roles = $query_roles->fetch_all(MYSQLI_ASSOC);
}
?>

<div class="container-fluid">
    <h1 class="h3 mb-4 text-gray-800">Lista de Usuarios</h1>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Usuarios Registrados</h6>
            <a href="crear.php" class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle"></i> Crear Usuario
            </a>
        </div>
        <div class="card-body">
            <form method="get" class="mb-3">
                <div class="row">
                    <div class="col-md-4">
                        <select name="departamento" class="form-select">
                            <option value="">Todos los departamentos</option>
                            <?php foreach ($departamentos as $dep): ?>
                                <option value="<?= $dep['id_departamento'] ?>" <?= ($filtros['departamento'] == $dep['id_departamento']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($dep['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                    </div>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Usuario</th>
                            <th>Departamento</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tareas as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['departamento']) ?></td>
                                <td><?= htmlspecialchars($usuario['rol']) ?></td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Acciones usuario">
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editarUsuarioModal" 
                                            data-id="<?= $usuario['id_usuario'] ?>"
                                            data-nombre="<?= htmlspecialchars($usuario['nombre']) ?>"
                                            data-usuario="<?= htmlspecialchars($usuario['usuario']) ?>"
                                            data-departamento="<?= $usuario['id_departamento'] ?>"
                                            data-rol="<?= $usuario['id_rol'] ?>">
                                            <i class="bi bi-person-fill-gear"></i> Editar
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#eliminarUsuarioModal" 
                                            data-id="<?= $usuario['id_usuario'] ?>"
                                            data-nombre="<?= htmlspecialchars($usuario['nombre']) ?>">
                                            <i class="bi bi-person-fill-slash"></i> Eliminar
                                        </button>
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

<!-- Modal para Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarUsuario" action="editar_usuario.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="editIdUsuario">
                    <div class="mb-3">
                        <label for="editNombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="editNombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="editUsuario" class="form-label">Usuario</label>
                        <input type="text" class="form-control" id="editUsuario" name="usuario" required>
                    </div>
                    <div class="mb-3">
                        <label for="editDepartamento" class="form-label">Departamento</label>
                        <select class="form-select" id="editDepartamento" name="id_departamento">
                            <option value="">Seleccione un departamento</option>
                            <?php foreach ($departamentos as $dep): ?>
                                <option value="<?= $dep['id_departamento'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="editRol" class="form-label">Rol</label>
                        <select class="form-select" id="editRol" name="id_rol" required>
                            <?php foreach ($roles as $rol): ?>
                                <option value="<?= $rol['id_rol'] ?>"><?= htmlspecialchars($rol['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
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

<!-- Modal para Eliminar Usuario -->
<div class="modal fade" id="eliminarUsuarioModal" tabindex="-1" aria-labelledby="eliminarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="eliminarUsuarioModalLabel">Eliminar usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEliminarUsuario" action="eliminar_usuario.php" method="post">
                <div class="modal-body">
                    <input type="hidden" name="id_usuario" id="deleteIdUsuario">
                    <p>¿Está seguro que desea eliminar al usuario <strong id="deleteNombreUsuario"></strong>?</p>
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
// Script para manejar los modales
document.addEventListener('DOMContentLoaded', function() {
    // Modal de edición
    var editarModal = document.getElementById('editarUsuarioModal');
    editarModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        var usuario = button.getAttribute('data-usuario');
        var departamento = button.getAttribute('data-departamento');
        var rol = button.getAttribute('data-rol');
        
        document.getElementById('editIdUsuario').value = id;
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editUsuario').value = usuario;
        document.getElementById('editDepartamento').value = departamento;
        document.getElementById('editRol').value = rol;
    });
    
    // Modal de eliminación
    var eliminarModal = document.getElementById('eliminarUsuarioModal');
    eliminarModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        
        document.getElementById('deleteIdUsuario').value = id;
        document.getElementById('deleteNombreUsuario').textContent = nombre;
    });
});
</script>

<?php include 'footer.php'; ?>