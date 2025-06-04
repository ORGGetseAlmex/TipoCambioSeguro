<?php
define('APP_RUNNING', true);
require 'db.php';

$hoy = date("Y-m-d");

// Elimina el registro de hoy si existe
$stmt = $conn->prepare("DELETE FROM tblTipoCambioStatus WHERE ultima_actualizacion = ?");
$stmt->bind_param("s", $hoy);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo "Registro de actualización del día $hoy eliminado correctamente.\n";
} else {
    echo "No se encontró ningún registro para el día $hoy.\n";
}

$stmt->close();
$conn->close();
?>
