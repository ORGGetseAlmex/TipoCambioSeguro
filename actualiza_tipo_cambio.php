<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

$token = $_ENV['BANXICO_TOKEN'];
$fechaFin = date("Y-m-d");
$hoy = date("Y-m-d");

$conn->query("CREATE TABLE IF NOT EXISTS tblTipoCambio (
    Id INT AUTO_INCREMENT PRIMARY KEY,
    Valor DECIMAL(10,4) NOT NULL,
    FechaValor DATE NOT NULL,
    FechaEmision DATE NOT NULL,
    FechaLiquidacion DATE NOT NULL,
    Moneda VARCHAR(3) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

$conn->query("CREATE TABLE IF NOT EXISTS tblTipoCambioStatus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ultima_actualizacion DATE
)");

?>