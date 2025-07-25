<?php
require_once 'config.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    $id_tarea = isset($_POST['id_tarea']) ? intval($_POST['id_tarea']) : 0;
    
    if ($id_tarea <= 0) {
        throw new Exception('ID de tarea inválido: ' . $_POST['id_tarea']);
    }

    if (!$conexion) {
        throw new Exception('Error de conexión a la base de datos');
    }

    // Desactivar restricciones de clave foránea
    $conexion->query("SET FOREIGN_KEY_CHECKS = 0");

    // Primero eliminar campos adicionales
    $conexion->query("DELETE FROM campos_adicionales WHERE id_tarea = $id_tarea");

    // Luego eliminar la tarea
    $sql = "DELETE FROM tareas WHERE id_tarea = ?";
    $stmt = $conexion->prepare($sql);
    
    if (!$stmt) {
        throw new Exception('Error al preparar consulta: ' . $conexion->error);
    }

    $stmt->bind_param('i', $id_tarea);
    $resultado = $stmt->execute();

    // Reactivar restricciones
    $conexion->query("SET FOREIGN_KEY_CHECKS = 1");

    if (!$resultado) {
        throw new Exception('Error al ejecutar consulta: ' . $stmt->error);
    }

    if ($stmt->affected_rows === 0) {
        throw new Exception('No se encontró la tarea con ID: ' . $id_tarea);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Tarea eliminada correctamente'
    ]);

} catch (Exception $e) {
    if (isset($conexion)) {
        $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}