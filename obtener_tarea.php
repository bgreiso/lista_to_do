<?php
require_once 'config.php';

if (!isset($_GET['id'])) {
    die("ID de tarea no proporcionado");
}

$id_tarea = intval($_GET['id']);

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
$tarea = $result->fetch_assoc();

if (!$tarea) {
    die("Tarea no encontrada");
}

header('Content-Type: application/json');
echo json_encode($tarea);