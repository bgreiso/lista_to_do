<?php
include 'config.php';
requerir_login();

if (isset($_GET['id'])) {
    $id_tarea = $_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];
    
    $stmt = $conexion->prepare("DELETE FROM tareas WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_tarea, $id_usuario);
    $stmt->execute();
}

header('Location: panel_control.php');
exit();
?>