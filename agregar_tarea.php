<?php
include 'config.php';
requerir_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tarea = $_POST['nueva_tarea'];
    $id_usuario = $_SESSION['id_usuario'];
    
    $stmt = $conexion->prepare("INSERT INTO tareas (tarea, id_usuario) VALUES (?, ?)");
    $stmt->bind_param("si", $tarea, $id_usuario);
    $stmt->execute();
}

header('Location: panel_control.php');
exit();
?>