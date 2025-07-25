<?php
// Configuración para mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión y conexión a BD
session_start();
require_once 'config.php';

// Verificar conexión
if (!$conexion) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Error de conexión a la base de datos'
    ];
    header('Location: listar.php');
    exit;
}

// Procesar solo solicitudes POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_usuario'])) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Solicitud inválida'
    ];
    header('Location: listar.php');
    exit;
}

// Obtener y validar ID
$id_usuario = intval($_POST['id_usuario']);

if ($id_usuario <= 0) {
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'ID de usuario inválido'
    ];
    header('Location: listar.php');
    exit;
}

try {
    // Desactivar restricciones de clave foránea temporalmente
    $conexion->query("SET FOREIGN_KEY_CHECKS = 0");
    
    // 1. Primero eliminar todas las tareas del usuario
    $sql_eliminar_tareas = "DELETE FROM tareas WHERE id_usuario_asignado = ?";
    $stmt_tareas = $conexion->prepare($sql_eliminar_tareas);
    $stmt_tareas->bind_param('i', $id_usuario);
    $stmt_tareas->execute();
    $tareas_eliminadas = $stmt_tareas->affected_rows;
    
    // 2. Luego eliminar el usuario
    $sql_eliminar_usuario = "DELETE FROM usuarios WHERE id_usuario = ?";
    $stmt_usuario = $conexion->prepare($sql_eliminar_usuario);
    $stmt_usuario->bind_param('i', $id_usuario);
    $stmt_usuario->execute();
    $usuario_eliminado = $stmt_usuario->affected_rows;
    
    // Reactivar restricciones de clave foránea
    $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Preparar mensaje de resultado
    if ($usuario_eliminado > 0) {
        $_SESSION['mensaje'] = [
            'tipo' => 'success',
            'texto' => "Usuario eliminado correctamente. Tareas eliminadas: $tareas_eliminadas"
        ];
    } else {
        $_SESSION['mensaje'] = [
            'tipo' => 'warning',
            'texto' => 'No se encontró el usuario para eliminar'
        ];
    }
    
} catch (Exception $e) {
    // Asegurarse de reactivar las restricciones si hay error
    $conexion->query("SET FOREIGN_KEY_CHECKS = 1");
    
    $_SESSION['mensaje'] = [
        'tipo' => 'error',
        'texto' => 'Error al eliminar: ' . $e->getMessage()
    ];
}

// Redireccionar
header('Location: listar.php');
exit;