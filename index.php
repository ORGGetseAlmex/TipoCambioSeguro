<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

$cache_file = __DIR__ . '/tipo_cambio_cache.html';
$cache_lifetime = 60; 


if (isset($_GET['nocache']) && $_GET['nocache'] === '1') {
    @unlink($cache_file);
}


if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_lifetime) {
    readfile($cache_file);
    exit;
}


ob_start();

$token = $_ENV['BANXICO_TOKEN'];
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


$conn->query("CREATE TABLE IF NOT EXISTS tblTipoCambioStatus (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ultima_actualizacion DATE
)");


$estado = $conn->query("SELECT ultima_actualizacion FROM tblTipoCambioStatus ORDER BY id DESC LIMIT 1");
$hoy = date("Y-m-d");
$yaActualizadoHoy = false;

if ($estado && $row = $estado->fetch_assoc()) {
    $yaActualizadoHoy = $row['ultima_actualizacion'] === $hoy;
}


$checkIndex = $conn->query("SHOW INDEX FROM tblTipoCambio WHERE Key_name = 'uniq_fecha_moneda'");
if ($checkIndex->num_rows === 0) {
    $conn->query("ALTER TABLE tblTipoCambio ADD UNIQUE KEY uniq_fecha_moneda (FechaValor, Moneda)");
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


$desde = $_GET['desde'] ?? "1991-11-21";
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
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

    body {
        font-family: 'Inter', sans-serif;
        background: url('https://img.freepik.com/fotos-premium/mazorcas-maiz-mesa-madera-telon-fondo-campo-maiz-al-atardecer_159938-2894.jpg') no-repeat center center fixed;
        background-size: cover;
        margin: 0;
        padding: 0;
        color: #ffffff;
        backdrop-filter: blur(6px);
    }

    .container {
        background-color: rgba(0, 28, 48, 0.85);
        margin: 3rem auto;
        padding: 2.5rem 3rem;
        border-radius: 20px;
        width: 95%;
        max-width: 1100px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
        text-align: center;
    }

    h1 {
        color: #fdd835;
        font-size: 2.8rem;
        margin-bottom: 0.5rem;
        font-weight: 700;
    }

    h2 {
        font-weight: 500;
        font-size: 1.4rem;
        margin-bottom: 1.5rem;
        color: #cfd8dc;
    }

    .highlight {
        font-size: 2rem;
        color: #00e5ff;
        font-weight: 700;
    }

    form {
        margin: 2rem 0;
    }

    input[type="date"], button {
        padding: 0.8rem 1rem;
        border: none;
        border-radius: 10px;
        margin: 0.5rem;
        font-size: 1rem;
        font-family: 'Inter', sans-serif;
    }

    input[type="date"] {
        background-color: #ffffff;
        color: #263238;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    button {
        background-color: #00bcd4;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
        box-shadow: 0 4px 12px rgba(0, 188, 212, 0.3);
    }

    button:hover {
        background-color: #008ba3;
    }

    table {
        width: 100%;
        margin: 1.5rem auto;
        border-collapse: collapse;
        background-color: rgba(255, 255, 255, 0.05);
        border-radius: 12px;
        overflow: hidden;
        color: #ffffff;
    }

    th, td {
        padding: 14px 20px;
        text-align: center;
    }

    th {
        background-color: rgba(0, 96, 100, 0.9);
        color: #ffeb3b;
        font-size: 1rem;
        letter-spacing: 0.5px;
    }

    td {
        border-bottom: 1px solid rgba(255,255,255,0.1);
        font-size: 0.95rem;
    }

    h3 {
        margin-top: 3rem;
        font-size: 1.3rem;
        font-weight: 600;
        color: #ffe082;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    @media (max-width: 768px) {
        .container {
            padding: 1.5rem;
        }

        table, th, td {
            font-size: 0.9rem;
        }

        h1 {
            font-size: 2rem;
        }

        h2 {
            font-size: 1.2rem;
        }

        .highlight {
            font-size: 1.5rem;
        }
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
            <tr><th>Promedio</th><th>$<?= number_format($info['suma'] / $info['n'], 4) ?></th></tr>
        </table>
    <?php endforeach; ?>
</div>
</body>
</html>

<?php

$contenido = ob_get_contents();
ob_end_clean();
file_put_contents($cache_file, $contenido);
echo $contenido;
?>
