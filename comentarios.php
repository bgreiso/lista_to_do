<?php
require_once 'config.php';
require_once 'auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea = intval($_POST['id_tarea']);
    $comentario = trim($_POST['comentario']);
    $id_usuario = $_SESSION['id_usuario'];

    try {
        // Validar datos
        if (empty($comentario)) {
            throw new Exception("El comentario no puede estar vacío");
        }

        // Verificar que la tarea existe y el usuario tiene permiso
        $stmt = $conexion->prepare("SELECT 1 FROM tareas WHERE id_tarea = ?");
        $stmt->bind_param("i", $id_tarea);
        $stmt->execute();
        
        if (!$stmt->get_result()->num_rows) {
            throw new Exception("La tarea no existe");
        }

        // Insertar comentario
        $stmt = $conexion->prepare("INSERT INTO comentarios (comentario, id_tarea, id_usuario, fecha_creacion) 
                                   VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sii", $comentario, $id_tarea, $id_usuario);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje_exito'] = "Comentario agregado correctamente";
        } else {
            throw new Exception("Error al guardar el comentario: " . $stmt->error);
        }
    } catch (Exception $e) {
        $_SESSION['mensaje_error'] = $e->getMessage();
    }

    // Redirigir de vuelta a la vista de la tarea
    header("Location: ver.php?id=" . $id_tarea);
    exit();
}

header("Location: tablero.php");
exit();
?>