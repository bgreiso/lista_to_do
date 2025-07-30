<?php
session_start();
require_once 'config.php';
require_once 'auth.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje_error'] = 'Método de solicitud no válido';
    header('Location: tablero.php');
    exit();
}

// Validar ID de la tarea
if (!isset($_POST['id_tarea']) || !is_numeric($_POST['id_tarea'])) {
    $_SESSION['mensaje_error'] = 'ID de tarea no válido';
    header('Location: tablero.php');
    exit();
}

$id_tarea = intval($_POST['id_tarea']);

try {
    // Iniciar transacción para asegurar la integridad de los datos
    $conexion->begin_transaction();

    // 1. Actualizar información básica de la tarea
    $query = $conexion->prepare("
        UPDATE tareas 
        SET titulo = ?, descripcion = ?, categoria = ?, herramientas = ?, id_estatus = ?, id_usuario_asignado = ?
        WHERE id_tarea = ?
    ");
    
    $query->bind_param(
        "ssssiii",
        $_POST['titulo'],
        $_POST['descripcion'],
        $_POST['categoria'],
        $_POST['herramientas'],
        $_POST['id_estatus'],
        $_POST['id_usuario_asignado'],
        $id_tarea
    );
    
    $query->execute();

    // 2. Procesar campos adicionales
    if (isset($_POST['campos_adicionales'])) {
        foreach ($_POST['campos_adicionales'] as $campo) {
            if (empty($campo['nombre'])) continue;

            if ($campo['id_campo'] > 0) {
                // Actualizar campo existente
                $query = $conexion->prepare("
                    UPDATE campos_adicionales 
                    SET nombre = ?, valor = ? 
                    WHERE id_campo = ? AND id_tarea = ?
                ");
                $query->bind_param("ssii", $campo['nombre'], $campo['valor'], $campo['id_campo'], $id_tarea);
            } else {
                // Insertar nuevo campo
                $query = $conexion->prepare("
                    INSERT INTO campos_adicionales (id_tarea, nombre, valor)
                    VALUES (?, ?, ?)
                ");
                $query->bind_param("iss", $id_tarea, $campo['nombre'], $campo['valor']);
            }
            $query->execute();
        }
    }

    // 3. Procesar subtareas
    if (isset($_POST['subtareas'])) {
        foreach ($_POST['subtareas'] as $subtarea) {
            if (empty($subtarea['titulo'])) continue;

            if ($subtarea['id_subtarea'] > 0) {
                // Actualizar subtarea existente
                $query = $conexion->prepare("
                    UPDATE tareas 
                    SET titulo = ?, descripcion = ? 
                    WHERE id_tarea = ? AND id_tarea_padre = ?
                ");
                $query->bind_param("ssii", $subtarea['titulo'], $subtarea['descripcion'], $subtarea['id_subtarea'], $id_tarea);
            } else {
                // Insertar nueva subtarea
                $query = $conexion->prepare("
                    INSERT INTO tareas (titulo, descripcion, id_usuario, id_usuario_asignado, id_estatus, id_tarea_padre)
                    VALUES (?, ?, ?, ?, 1, ?)
                ");
                $query->bind_param(
                    "ssiii",
                    $subtarea['titulo'],
                    $subtarea['descripcion'],
                    $_SESSION['id_usuario'], // Usuario que crea la subtarea
                    $_POST['id_usuario_asignado'], // Mismo asignado que la tarea padre
                    $id_tarea
                );
            }
            $query->execute();
        }
    }

    // Confirmar todos los cambios
    $conexion->commit();

    // Redirigir con mensaje de éxito
    $_SESSION['mensaje_exito'] = 'Tarea actualizada correctamente';
    header("Location: ver.php?id=$id_tarea");
    exit();

} catch (Exception $e) {
    // Revertir cambios en caso de error
    $conexion->rollback();
    
    $_SESSION['mensaje_error'] = 'Error al actualizar la tarea: ' . $e->getMessage();
    header("Location: editar_tarea.php?id=$id_tarea");
    exit();
}
?>