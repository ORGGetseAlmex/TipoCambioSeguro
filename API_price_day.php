<?php
header('Content-Type: text/plain');

define('APP_RUNNING', true);
require 'db.php'; // Asegúrate de que este archivo conecte correctamente a tu base de datos MySQL

$hoy = date("Y-m-d");

$stmt = $conn->prepare("SELECT Valor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor = ? ORDER BY Id DESC LIMIT 1");
$stmt->bind_param("s", $hoy);
$stmt->execute();
$stmt->bind_result($valor);

if ($stmt->fetch()) {
    echo number_format($valor, 4, '.', '');
} else {
    echo "No disponible";
}

$stmt->close();
$conn->close();
?>
