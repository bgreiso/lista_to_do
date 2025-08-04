<?php
session_start();     

// Verificar si el usuario está logueado
function verificarAutenticacion() {
    if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
        // Guardar la página actual para redirigir después del login
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: inicio_sesion.php');
        exit;
    }
}

// Función para verificar si es admin
function esAdmin() {
    return ($_SESSION['id_rol'] ?? 0) == 1;
}

// Función para obtener datos del usuario
function obtenerUsuario() {
    return [
        'id' => $_SESSION['id_usuario'] ?? null,
        'nombre' => $_SESSION['nombre'] ?? '',
        'usuario' => $_SESSION['usuario'] ?? '',
        'rol' => $_SESSION['id_rol'] ?? 0
    ];
}
?>