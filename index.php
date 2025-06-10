<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

// Función para formatear una fecha completa en español
function fechaFormateadaEspañol($fechaISO) {
    $fecha = new DateTime($fechaISO);
    $formatter = new IntlDateFormatter(
        'es_MX',
        IntlDateFormatter::FULL,
        IntlDateFormatter::NONE,
        'America/Mexico_City',
        IntlDateFormatter::GREGORIAN,
        "EEEE d 'de' MMMM 'del' yyyy"
    );
    return ucfirst($formatter->format($fecha));
}


function encabezadoMesEspañol($fechaISO) {
    $fecha = new DateTime($fechaISO);
    $formatter = new IntlDateFormatter(
        'es_MX',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'America/Mexico_City',
        IntlDateFormatter::GREGORIAN,
        "LLLL 'de' yyyy"
    );
    return ucfirst($formatter->format($fecha));
}

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

$fechaFin = date("Y-m-d");
$desde = $_GET['desde'] ?? "1991-11-21";
$hasta = $_GET['hasta'] ?? $fechaFin;

$query = $conn->prepare("SELECT Valor, FechaValor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor BETWEEN ? AND ? ORDER BY FechaValor DESC");
$query->bind_param("ss", $desde, $hasta);
$query->execute();
$result = $query->get_result();

$meses = [];
while ($row = $result->fetch_assoc()) {
    $fecha = $row['FechaValor'];
    $claveMes = encabezadoMesEspañol($fecha);  

    if (!isset($meses[$claveMes])) {
        $meses[$claveMes] = ['registros' => [], 'suma' => 0, 'n' => 0];
    }

    $meses[$claveMes]['registros'][] = [
        'fecha_iso' => $fecha,
        'valor' => number_format($row['Valor'], 4)
    ];
    $meses[$claveMes]['suma'] += $row['Valor'];
    $meses[$claveMes]['n']++;
}
reset($meses);
$ultimoMes = current($meses);
$valorHoy = $ultimoMes['registros'][0]['valor'];
$fechaHoy = fechaFormateadaEspañol($ultimoMes['registros'][0]['fecha_iso']);

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
                <tr>
                    <td><?= fechaFormateadaEspañol($r['fecha_iso']) ?></td>
                    <td>$<?= $r['valor'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr><th>Promedio:</th><th>$<?= number_format($info['suma'] / $info['n'], 4) ?></th></tr>
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
