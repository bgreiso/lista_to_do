<?php 
include 'config.php';
requerir_login();

$id_usuario = 
$_SESSION['id_usuario'];

    $sql = "SELECT id, tarea, fecha_creacion, completada, fecha_completada
        FROM tareas
        WHERE id_usuario = ?
        ORDER BY completada ASC, fecha_creacion DESC";

$stmt = $conexion->prepare($sql);

if (!$stmt) {
    die("Error en la consulta: " . $conexion->error);
}

$stmt->bind_param("i", $id_usuario);

if (!$stmt->execute()) {
    die("Error al ejecutar: " . $stmt->error);
}

$resultado = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Panel de Control</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="estilos.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body class="d-flex flex-column min-vh-100">
    <nav style="background-color: #0d6efd; color: white; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; width: 100%;">
        <div>
            <i class="bi bi-check2-circle me-2"></i>
                <span>Gestor de Tareas</span>
        </div>
        <div>
            <span style="margin-right: 15px;">
                <i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($_SESSION['usuario']) ?>
            </span>
                <a href="cerrar_sesion.php" style="color: white; text-decoration: none; border: 1px solid white; padding: 5px 10px; border-radius: 4px;">
                    <i class="bi bi-box-arrow-right me-1"></i> Salir
                </a>
        </div>
    </nav>

    <!-- Contenido principal -->
    <main class="container my-4 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Card para agregar tareas -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <form action="agregar_tarea.php" method="POST" class="row g-2">
                            <div class="col-md-8">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0"><i class="bi bi-pencil-square text-primary"></i></span>
                                    <input type="text" name="nueva_tarea" class="form-control border-start-0" 
                                           placeholder="Escribe una nueva tarea..." required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100 h-100">
                                    <i class="bi bi-plus-circle me-1"></i> Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card de lista de tareas -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="bi bi-list-task text-primary me-2"></i>
                                Mis Tareas
                            </h5>
                            <span class="badge bg-primary rounded-pill">
                            </span>
                        </div>
                    </div>
                    
                    <div class="card-body p-0">
                        <?php if ($resultado->num_rows > 0): ?>
                            <ul class="list-group list-group-flush">
                                <?php while ($fila = $resultado->fetch_assoc()): ?>
                                <li class="list-group-item <?= $fila['completada'] ? 'bg-light' : '' ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 me-3">
                                            <div class="d-flex align-items-center">
                                                <?php if ($fila['completada']): ?>
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                <?php else: ?>
                                                    <i class="bi bi-circle text-muted me-2"></i>
                                                <?php endif; ?>
                                                
                                                <span class="<?= $fila['completada'] ? 'text-decoration-line-through text-muted' : 'fw-medium' ?>">
                                                    <?= htmlspecialchars($fila['tarea']) ?>
                                                </span>
                                            </div>
                                            <small class="text-muted ms-4">
                                                <i class="bi bi-clock me-1"></i>
                                                <?= date('d/m/Y H:i', strtotime($fila['fecha_creacion'])) ?>
                                                <?php if ($fila['completada'] && !empty($fila['fecha_completada'])): ?>
|                                                   <i class="bi bi-check2-all me-1"></i>
                                                    <?= date('d/m/Y', strtotime($fila['fecha_completada'])) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        
                                        <div class="btn-group" role="group">
                                            <?php if (!$fila['completada']): ?>
                                                <a href="completar_tarea.php?id=<?= $fila['id'] ?>" 
                                                   class="btn btn-sm btn-outline-success"
                                                   data-bs-toggle="tooltip" title="Marcar como completada">
                                                    <i class="bi bi-check2"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="eliminar_tarea.php?id=<?= $fila['id'] ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               data-bs-toggle="tooltip" title="Eliminar tarea">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-check2-all display-5 text-muted mb-3"></i>
                                <h5 class="text-muted">No hay tareas pendientes</h5>
                                <p class="text-muted">¡Agrega tu primera tarea usando el formulario arriba!</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer class="bg-light mt-auto py-3 border-top">
        <div class="container text-center text-muted small">
            <div>Sistema de Tareas v1.0 &copy; <?= date('Y') ?></div>
        </div>
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>