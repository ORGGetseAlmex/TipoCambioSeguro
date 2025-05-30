<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

$token = $_ENV['BANXICO_TOKEN'];
$fechaFin = date("Y-m-d");
$hoy = date("Y-m-d");



// Verifica si ya se actualizó hoy
$estado = $conn->query("SELECT ultima_actualizacion FROM tblTipoCambioStatus ORDER BY id DESC LIMIT 1");
$yaActualizadoHoy = false;

if ($estado && $row = $estado->fetch_assoc()) {
    $yaActualizadoHoy = $row['ultima_actualizacion'] === $hoy;
}


$conn->query("DELETE t1 FROM tblTipoCambio t1 JOIN tblTipoCambio t2 
    ON t1.FechaValor = t2.FechaValor AND t1.Moneda = t2.Moneda AND t1.Id > t2.Id");

if (!$yaActualizadoHoy) {
    $result = $conn->query("SELECT MAX(FechaValor) AS ultimaFecha FROM tblTipoCambio WHERE Moneda = '02'");
    $row = $result->fetch_assoc();
    $fechaInicio = $row['ultimaFecha'] ?? "1991-11-21";
    $fechaInicio = date("Y-m-d", strtotime($fechaInicio . " +1 day"));

    if ($fechaInicio <= $fechaFin) {
        $url = "https://www.banxico.org.mx/SieAPIRest/service/v1/series/SF60653/datos/$fechaInicio/$fechaFin?token=$token";
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if ($data && isset($data['bmx']['series'][0]['datos'])) {
            $registros = $data['bmx']['series'][0]['datos'];
            $stmt = $conn->prepare("INSERT IGNORE INTO tblTipoCambio (Valor, FechaValor, FechaEmision, FechaLiquidacion, Moneda) VALUES (?, ?, ?, ?, '02')");
            foreach ($registros as $item) {
                $fecha = DateTime::createFromFormat('d/m/Y', $item['fecha'])->format('Y-m-d');
                $valor = floatval($item['dato']);
                $stmt->bind_param("dsss", $valor, $fecha, $fecha, $fecha);
                $stmt->execute();
            }
            $stmt->close();
            $conn->query("INSERT INTO tblTipoCambioStatus (ultima_actualizacion) VALUES ('$hoy')");
        }
    }
}

$conn->close();
?>
