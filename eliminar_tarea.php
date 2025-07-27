<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar ID de tarea
    if (!isset($_POST['id_tarea']) || !is_numeric($_POST['id_tarea'])) {
        throw new Exception('ID de tarea no proporcionado o inválido');
    }

    $id_tarea = intval($_POST['id_tarea']);

    if ($id_tarea <= 0) {
        throw new Exception('ID de tarea debe ser mayor que cero');
    }

    $conexion->begin_transaction();

    // 1. Eliminar campos adicionales
    $stmt_campos = $conexion->prepare("DELETE FROM campos_adicionales WHERE id_tarea = ?");
    $stmt_campos->bind_param('i', $id_tarea);
    
    if (!$stmt_campos->execute()) {
        throw new Exception("Error al eliminar campos adicionales: " . $stmt_campos->error);
    }

    // 2. Eliminar subtareas (si existen)
    $stmt_subtareas = $conexion->prepare("DELETE FROM tareas WHERE id_tarea_padre = ?");
    $stmt_subtareas->bind_param('i', $id_tarea);
    
    if (!$stmt_subtareas->execute()) {
        throw new Exception("Error al eliminar subtareas: " . $stmt_subtareas->error);
    }

    // 3. Eliminar la tarea principal
    $stmt_tarea = $conexion->prepare("DELETE FROM tareas WHERE id_tarea = ?");
    $stmt_tarea->bind_param('i', $id_tarea);
    
    if (!$stmt_tarea->execute()) {
        throw new Exception("Error al eliminar tarea: " . $stmt_tarea->error);
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tarea eliminada correctamente',
        'redirect' => 'todas.php'
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