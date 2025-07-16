<?php
include 'config.php';
requerir_login();

$conexion->query("UPDATE tareas SET completada = 1, fecha_completada = NOW() WHERE id = $id_tarea AND id_usuario = $id_usuario");

if (isset($_GET['id'])) {
    $id_tarea = $_GET['id'];
    $id_usuario = $_SESSION['id_usuario'];
    
    $stmt = $conexion->prepare("UPDATE tareas SET completada = 1 WHERE id = ? AND id_usuario = ?");
    $stmt->bind_param("ii", $id_tarea, $id_usuario);
    $stmt->execute();
}

header('Location: panel_control.php');
exit();
?>