<?php
require_once 'config.php';
require_once 'auth.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Validar y obtener datos básicos
    $id_tarea = isset($_POST['id_tarea']) ? intval($_POST['id_tarea']) : 0;
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $herramientas = trim($_POST['herramientas'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $id_estatus = isset($_POST['id_estatus']) ? intval($_POST['id_estatus']) : 1;
    $id_usuario_asignado = isset($_POST['id_usuario_asignado']) ? intval($_POST['id_usuario_asignado']) : 0;

    // Validaciones básicas
    if (empty($titulo)) {
        throw new Exception('El título es obligatorio');
    }

    if ($id_tarea <= 0) {
        throw new Exception('ID de tarea inválido');
    }

    $conexion->begin_transaction();

    // 1. Actualizar tarea principal
    $query = "UPDATE tareas SET 
              titulo = ?,
              descripcion = ?,
              herramientas = ?,
              categoria = ?,
              id_estatus = ?,
              id_usuario_asignado = ?,
              fecha_actualizacion = NOW()
              WHERE id_tarea = ?";
    
    $stmt = $conexion->prepare($query);
    $stmt->bind_param('ssssiii', $titulo, $descripcion, $herramientas, $categoria, $id_estatus, $id_usuario_asignado, $id_tarea);
    
    if (!$stmt->execute()) {
        throw new Exception("Error al actualizar tarea: " . $stmt->error);
    }

    // 2. Procesar campos adicionales
    if (!empty($_POST['campos_adicionales'])) {
        foreach ($_POST['campos_adicionales'] as $campo) {
            if (empty($campo['nombre'])) continue;
            
            $id_campo = isset($campo['id_campo']) ? intval($campo['id_campo']) : 0;
            $nombre = trim($campo['nombre']);
            $valor = trim($campo['valor'] ?? '');

            if ($id_campo > 0) {
                // Actualizar campo existente
                $stmt_campo = $conexion->prepare("UPDATE campos_adicionales SET 
                                                 nombre = ?, 
                                                 valor = ? 
                                                 WHERE id_campo = ? AND id_tarea = ?");
                $stmt_campo->bind_param("ssii", $nombre, $valor, $id_campo, $id_tarea);
            } else {
                // Insertar nuevo campo
                $stmt_campo = $conexion->prepare("INSERT INTO campos_adicionales 
                                                 (id_tarea, id_usuario, nombre, valor) 
                                                 VALUES (?, ?, ?, ?)");
                $stmt_campo->bind_param("iiss", $id_tarea, $_SESSION['id_usuario'], $nombre, $valor);
            }
            
            if (!$stmt_campo->execute()) {
                throw new Exception("Error al procesar campo adicional: " . $stmt_campo->error);
            }
        }
    }

    // 3. Procesar subtareas
    if (!empty($_POST['subtareas'])) {
        foreach ($_POST['subtareas'] as $subtarea) {
            if (empty($subtarea['titulo'])) continue;
            
            $id_subtarea = isset($subtarea['id_subtarea']) ? intval($subtarea['id_subtarea']) : 0;
            $titulo_sub = trim($subtarea['titulo']);
            $descripcion_sub = trim($subtarea['descripcion'] ?? '');
            $id_estatus_sub = isset($subtarea['id_estatus']) ? intval($subtarea['id_estatus']) : 1;

            if ($id_subtarea > 0) {
                // Actualizar subtarea existente
                $stmt_sub = $conexion->prepare("UPDATE tareas SET 
                                               titulo = ?, 
                                               descripcion = ?, 
                                               id_estatus = ?,
                                               fecha_actualizacion = NOW()
                                               WHERE id_tarea = ? AND id_tarea_padre = ?");
                $stmt_sub->bind_param("ssiii", $titulo_sub, $descripcion_sub, $id_estatus_sub, $id_subtarea, $id_tarea);
            } else {
                // Insertar nueva subtarea
                $stmt_sub = $conexion->prepare("INSERT INTO tareas 
                                              (titulo, descripcion, id_usuario, id_usuario_asignado, 
                                               id_estatus, fecha_creacion, id_tarea_padre) 
                                              VALUES (?, ?, ?, ?, ?, NOW(), ?)");
                $stmt_sub->bind_param("ssiiii", $titulo_sub, $descripcion_sub, $_SESSION['id_usuario'], $id_usuario_asignado, $id_estatus_sub, $id_tarea);
            }
            
            if (!$stmt_sub->execute()) {
                throw new Exception("Error al procesar subtarea: " . $stmt_sub->error);
            }
        }
    }

    $conexion->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tarea actualizada correctamente',
        'redirect' => 'ver.php?id='.$id_tarea
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