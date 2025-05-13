<?php
if (!defined('APP_RUNNING')) {
    die('Acceso denegado.');
}

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];
$usuario = $_ENV['DB_USER'];
$contrasena = $_ENV['DB_PASS'];
$nombreBD = $_ENV['DB_NAME'];

$conn = new mysqli($host, $usuario, $contrasena);
if ($conn->connect_error) {
    error_log("Conexión fallida: " . $conn->connect_error);
    die("Error al conectar a la base de datos.");
}

$conn->query("CREATE DATABASE IF NOT EXISTS $nombreBD");
$conn->select_db($nombreBD);
