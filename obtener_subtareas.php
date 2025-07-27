<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id_tarea']) || !is_numeric($_GET['id_tarea'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de tarea no proporcionado']);
    exit();
}

$id_tarea = intval($_GET['id_tarea']);

try {
    $query = "SELECT t.*, e.nombre as estado 
              FROM tareas t
              JOIN estatus e ON t.id_estatus = e.id_estatus
              WHERE t.id_tarea_padre = ? 
              ORDER BY t.fecha_creacion ASC";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $id_tarea);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $subtareas = [];
    while ($row = $result->fetch_assoc()) {
        $subtareas[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'subtareas' => $subtareas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}