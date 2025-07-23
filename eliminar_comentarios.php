<?php
require_once 'config.php';
require_once 'auth.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['id_tarea']) || !is_numeric($_GET['id_tarea'])) {
    header('Location: tablero.php');
    exit();
}

$id_comentario = intval($_GET['id']);
$id_tarea = intval($_GET['id_tarea']);

try {
    // Verificar permisos
    $stmt = $conexion->prepare("SELECT id_usuario FROM comentarios WHERE id_comentario = ?");
    $stmt->bind_param("i", $id_comentario);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($resultado->num_rows === 0) {
        throw new Exception("El comentario no existe");
    }
    
    $comentario = $resultado->fetch_assoc();
    
    // Solo el autor del comentario o un admin puede eliminarlo
    if ($comentario['id_usuario'] != $_SESSION['id_usuario'] && !esAdmin()) {
        throw new Exception("No tienes permiso para eliminar este comentario");
    }
    
    // Eliminar comentario
    $stmt = $conexion->prepare("DELETE FROM comentarios WHERE id_comentario = ?");
    $stmt->bind_param("i", $id_comentario);
    
    if ($stmt->execute()) {
        $_SESSION['mensaje_exito'] = "Comentario eliminado correctamente";
    } else {
        throw new Exception("Error al eliminar el comentario");
    }
} catch (Exception $e) {
    $_SESSION['mensaje_error'] = $e->getMessage();
}

header("Location: ver.php?id=" . $id_tarea);
exit();
?>