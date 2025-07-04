<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'lista_to_do';

$conexion = new mysqli($host, $user, $password, $database);

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar si el usuario está logueado
function usuario_logueado() {
    return isset($_SESSION['id_usuario']);
}

// Redirigir si no está logueado
function requerir_login() {
    if (!usuario_logueado()) {
        header('Location: inicio_sesion.php');
        exit;
    }
}
?>