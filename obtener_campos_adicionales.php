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
    $query = "SELECT * FROM campos_adicionales WHERE id_tarea = ? ORDER BY id_campo ASC";
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('i', $id_tarea);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $campos = [];
    while ($row = $result->fetch_assoc()) {
        $campos[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'campos' => $campos
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}