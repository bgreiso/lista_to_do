<?php

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'lista_to_do';

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

$conexion->set_charset('utf8mb4');

function usuario_logueado() {
    return isset($_SESSION['id_usuario']);
}

function requerir_login() {
    if (!usuario_logueado()) {
        header('Location: inicio_sesion.php');
        exit;
    }
}

function requiere_rol($rol_requerido) {
    session_start(); // Asegurar que la sesión esté iniciada
    
    // Redirigir si no tiene el rol requerido
    if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== $rol_requerido) {
        header('Location: inicio_sesion.php');
        exit();
    }
}