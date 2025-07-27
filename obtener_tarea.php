<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID de tarea no proporcionado']);
    exit();
}

$id_tarea = intval($_GET['id']);

try {
    $query = "SELECT t.*, 
                     ua.id_departamento, 
                     e.nombre AS estatus_nombre
              FROM tareas t
              JOIN usuarios ua ON t.id_usuario_asignado = ua.id_usuario
              JOIN estatus e ON t.id_estatus = e.id_estatus
              WHERE t.id_tarea = ?";

    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $id_tarea);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Tarea no encontrada");
    }
    
    $tarea = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'id_tarea' => $tarea['id_tarea'],
        'titulo' => $tarea['titulo'],
        'descripcion' => $tarea['descripcion'],
        'herramientas' => $tarea['herramientas'],
        'id_estatus' => $tarea['id_estatus'],
        'id_usuario_asignado' => $tarea['id_usuario_asignado']
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}