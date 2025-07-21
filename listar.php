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
                            <th>ID</th>
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
                                <td><?= htmlspecialchars($usuario['id_usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['departamento']) ?></td>
                                <td><?= htmlspecialchars($usuario['rol']) ?></td>
                                <td>
                                    <a href="usuarios/editar.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-primary">
                                        <i class="bi bi-pencil"></i> Editar
                                    </a>
                                    <a href="usuarios/eliminar.php?id=<?= $usuario['id_usuario'] ?>" class="btn btn-danger btn-sm">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>  
</div>
<?php include 'footer.php'; ?>