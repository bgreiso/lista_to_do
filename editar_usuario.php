<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar datos recibidos
    $id_usuario = isset($_POST['id_usuario']) ? intval($_POST['id_usuario']) : 0;
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $id_departamento = isset($_POST['id_departamento']) ? intval($_POST['id_departamento']) : null;
    $id_rol = isset($_POST['id_rol']) ? intval($_POST['id_rol']) : 0;

    if ($id_usuario <= 0 || empty($nombre) || empty($usuario) || $id_rol <= 0) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Datos incompletos o inválidos.'
        ];
        header('Location: listar.php');
        exit;
    }

    try {
        // Actualizar el usuario en la base de datos
        $query = "UPDATE usuarios SET 
                  nombre = ?, 
                  usuario = ?, 
                  id_departamento = ?, 
                  id_rol = ? 
                  WHERE id_usuario = ?";
        
        $stmt = $conexion->prepare($query);
        $stmt->bind_param('ssiii', $nombre, $usuario, $id_departamento, $id_rol, $id_usuario);
        
        if ($stmt->execute()) {
            $_SESSION['mensaje'] = [
                'tipo' => 'success',
                'texto' => 'Usuario actualizado correctamente.'
            ];
        } else {
            throw new Exception("Error al actualizar el usuario.");
        }
    } catch (Exception $e) {
        $_SESSION['mensaje'] = [
            'tipo' => 'error',
            'texto' => 'Error al actualizar el usuario: ' . $e->getMessage()
        ];
    }

    header('Location: listar.php');
    exit;
} else {
    // Si no es POST, redirigir
    header('Location: index.php');
    exit;
}