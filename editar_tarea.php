<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id_tarea = intval($_POST['id_tarea'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $id_usuario_asignado = intval($_POST['id_usuario_asignado'] ?? 0);
    $id_estatus = intval($_POST['id_estatus'] ?? 1);

    if (empty($titulo)) {
        throw new Exception('El título es obligatorio');
    }

    if ($id_tarea <= 0) {
        throw new Exception('ID de tarea inválido');
    }

    $conexion->begin_transaction();

    $query = "UPDATE tareas SET 
              titulo = ?,
              descripcion = ?,
              herramientas = ?,
              id_usuario_asignado = ?,
              id_estatus = ?,
              fecha_actualizacion = NOW()
              WHERE id_tarea = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('sssiii', $titulo, $descripcion, $herramientas, $id_usuario_asignado, $id_estatus, $id_tarea);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar tarea: " . $stmt->error);
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tarea actualizada correctamente'
    ]);

} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}