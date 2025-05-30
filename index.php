<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

$token = $_ENV['BANXICO_TOKEN'];
$fechaInicio = "1991-11-21";
$fechaFin = date("Y-m-d");

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

$checkIndex = $conn->query("SHOW INDEX FROM tblTipoCambio WHERE Key_name = 'uniq_fecha_moneda'");
if ($checkIndex->num_rows === 0) {
    $conn->query("ALTER TABLE tblTipoCambio ADD UNIQUE KEY uniq_fecha_moneda (FechaValor, Moneda)");
}

$conn->query("DELETE t1 FROM tblTipoCambio t1 JOIN tblTipoCambio t2 
    ON t1.FechaValor = t2.FechaValor AND t1.Moneda = t2.Moneda AND t1.Id > t2.Id");

$url = "https://www.banxico.org.mx/SieAPIRest/service/v1/series/SF60653/datos/$fechaInicio/$fechaFin?token=$token";
$response = file_get_contents($url);
$data = json_decode($response, true);

if (!$data || !isset($data['bmx']['series'][0]['datos'])) {
    die("No se pudieron obtener datos desde Banxico.");
}

$registros = $data['bmx']['series'][0]['datos'];
$stmt = $conn->prepare("INSERT IGNORE INTO tblTipoCambio (Valor, FechaValor, FechaEmision, FechaLiquidacion, Moneda) VALUES (?, ?, ?, ?, '02')");
foreach ($registros as $item) {
    $fecha = DateTime::createFromFormat('d/m/Y', $item['fecha'])->format('Y-m-d');
    $valor = floatval($item['dato']);
    $stmt->bind_param("dsss", $valor, $fecha, $fecha, $fecha);
    $stmt->execute();
}
$stmt->close();

$desde = $_GET['desde'] ?? $fechaInicio;
$hasta = $_GET['hasta'] ?? $fechaFin;

$query = $conn->prepare("SELECT Valor, FechaValor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor BETWEEN ? AND ? ORDER BY FechaValor DESC");
$query->bind_param("ss", $desde, $hasta);
$query->execute();
$result = $query->get_result();

$meses = [];
while ($row = $result->fetch_assoc()) {
    $fecha = $row['FechaValor'];
    $mesAnio = date('F Y', strtotime($fecha));
    if (!isset($meses[$mesAnio])) {
        $meses[$mesAnio] = ['registros' => [], 'suma' => 0, 'n' => 0];
    }
    $meses[$mesAnio]['registros'][] = [
        'fecha' => date('d/m/Y', strtotime($fecha)),
        'valor' => number_format($row['Valor'], 4)
    ];
    $meses[$mesAnio]['suma'] += $row['Valor'];
    $meses[$mesAnio]['n']++;
}
reset($meses);
$ultimoMes = current($meses);
$valorHoy = $ultimoMes['registros'][0]['valor'];
$fechaHoy = $ultimoMes['registros'][0]['fecha'];

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tipo de Cambio Dólar</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: url('https://img.freepik.com/fotos-premium/mazorcas-maiz-mesa-madera-telon-fondo-campo-maiz-al-atardecer_159938-2894.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #e0e0e0;
            backdrop-filter: blur(5px);
            text-align: center;
            margin: 0;
            padding: 0;
        }
        .container {
            background-color: rgba(20, 20, 20, 0.85);
            margin: 2rem auto;
            padding: 2rem 3rem;
            border-radius: 20px;
            width: 95%;
            max-width: 1000px;
            box-shadow: 0 0 25px rgba(0,0,0,0.4);
        }
        h1 {
            color: #ffd54f;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        h2 {
            font-weight: 400;
            margin: 1rem 0;
        }
        .highlight {
            font-weight: bold;
            font-size: 2rem;
            color: #4db6ac;
        }
        form {
            margin: 1.5rem 0;
        }
        input[type="date"], button {
            padding: 0.6rem;
            border: none;
            border-radius: 8px;
            margin: 0.3rem;
            font-size: 1rem;
        }
        button {
            background-color: #4db6ac;
            color: white;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        button:hover {
            background-color: #009688;
        }
        table {
            width: 100%;
            margin: 1rem auto;
            border-collapse: collapse;
            background-color: #212121;
            color: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid #424242;
        }
        th {
            background-color: #37474f;
            color: #fff176;
        }
        h3 {
            color: #ffee58;
            margin-top: 2rem;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Tipo de Cambio del Dólar</h1>
    <h2>Fecha: <?= $fechaHoy ?> | Valor Actual: <span class="highlight">$<?= $valorHoy ?></span></h2>

    <?php foreach ($meses as $mes => $info): ?>
        <h3><?= $mes ?></h3>
        <table>
            <tr><th>Fecha</th><th>Valor</th></tr>
            <?php foreach ($info['registros'] as $r): ?>
                <tr><td><?= $r['fecha'] ?></td><td>$<?= $r['valor'] ?></td></tr>
            <?php endforeach; ?>
            <tr><th>Promedio:</th><th>$<?= number_format($info['suma'] / $info['n'], 4) ?></th></tr>
        </table>
    <?php endforeach; ?>
</div>
</body>
</html>
