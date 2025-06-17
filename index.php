<?php
date_default_timezone_set('America/Mexico_City');
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

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

function fechaMesLargoEspañol($ym) {
    $fecha = DateTime::createFromFormat('Y-m', $ym);
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

// Hardcodeo de días festivos
$diasFestivosHardcoded = [
    '2025-01-01', // Año Nuevo
    '2025-02-05', // Constitución
    '2025-03-21', // Benito Juárez
    '2025-05-01', // Día del Trabajo
    '2025-09-16', // Independencia
    '2025-11-20', // Revolución
    '2025-12-25', // Navidad
];

$excluirFestivos = isset($_GET['excluirFestivos']);

$fechaFin = date("Y-m-d");

if (isset($_GET['mesInicio']) && isset($_GET['anioInicio']) && isset($_GET['mesFin']) && isset($_GET['anioFin'])) {
    $desde = date("Y-m-d", strtotime($_GET['anioInicio'] . '-' . $_GET['mesInicio'] . '-01'));
    $hasta = date("Y-m-t", strtotime($_GET['anioFin'] . '-' . $_GET['mesFin'] . '-01'));
} else {
    $rango = $_GET['rango'] ?? '3meses';
    switch ($rango) {
        case 'semana':
            $desde = date("Y-m-d", strtotime("-7 days"));
            break;
        case 'mes':
            $desde = date("Y-m-d", strtotime("-1 month"));
            break;
        case '3meses':
            $desde = date("Y-m-d", strtotime("-3 months"));
            break;
        case 'anio':
            $desde = date("Y-m-d", strtotime("-1 year"));
            break;
        case 'todo':
            $desde = "1991-11-21";
            break;
        default:
            $desde = date("Y-m-d", strtotime("-3 months"));
    }
    $hasta = $_GET['hasta'] ?? $fechaFin;
}

$query = $conn->prepare("SELECT Valor, FechaValor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor BETWEEN ? AND ? ORDER BY FechaValor DESC");
$query->bind_param("ss", $desde, $hasta);
$query->execute();
$result = $query->get_result();

$meses = [];
$valorHoy = null;
$fechaHoy = null;
$fechaActual = date("Y-m-d");

while ($row = $result->fetch_assoc()) {
    $fecha = $row['FechaValor'];

    if ($excluirFestivos && in_array($fecha, $diasFestivosHardcoded)) {
        continue;
    }

    $claveMes = date('Y-m', strtotime($fecha));
    if (!isset($meses[$claveMes])) {
        $meses[$claveMes] = ['registros' => [], 'suma' => 0, 'n' => 0];
    }

    $meses[$claveMes]['registros'][] = [
        'fecha_iso' => $fecha,
        'valor' => number_format($row['Valor'], 4)
    ];
    $meses[$claveMes]['suma'] += $row['Valor'];
    $meses[$claveMes]['n']++;

    if ($fecha === $fechaActual && !$valorHoy) {
        $valorHoy = number_format($row['Valor'], 4);
        $fechaHoy = fechaFormateadaEspañol($fecha);
    }
}

if (!$valorHoy) {
    reset($meses);
    $primerMes = current($meses);
    $primerRegistro = $primerMes['registros'][0];
    $valorHoy = $primerRegistro['valor'];
    $fechaHoy = fechaFormateadaEspañol($primerRegistro['fecha_iso']);
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Tipo de Cambio Dólar</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: url('https://img.freepik.com/fotos-premium/mazorcas-maiz-mesa-madera-telon-fondo-campo-maiz-al-atardecer_159938-2894.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #f1f1f1;
            margin: 0;
            padding: 0;
            display: flex;
        }
        .logo-fijo {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        .logo-fijo img {
            height: 240px;
            width: auto;
            filter: drop-shadow(2px 2px 5px rgba(0,0,0,0.7));
        }
        .sidebar {
            background-color: rgba(0, 0, 0, 0.85);
            padding: 2rem 1rem;
            width: 260px;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            overflow-y: auto;
        }
        .sidebar h3, .sidebar h4 {
            color: #ffee58;
            margin-bottom: 1rem;
            text-align: center;
        }
        .sidebar form {
            width: 100%;
        }
        .sidebar button, .sidebar select, .sidebar input[type="number"] {
            width: 100%;
            padding: 0.6rem;
            margin-bottom: 0.5rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
        }
        .sidebar button {
            background-color: #37474f;
            color: #fff176;
            cursor: pointer;
        }
        .sidebar button:hover {
            background-color: #455a64;
        }
        .main {
            margin-left: 280px;
            padding: 3rem 2rem;
            width: 100%;
        }
        .container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 2rem 3rem;
            border-radius: 20px;
            max-width: 1000px;
            margin: auto;
            text-align: center;
            box-shadow: 0 0 25px rgba(0,0,0,0.4);
        }
        h1 { color: #ffd54f; font-size: 2.5rem; }
        h2 { font-weight: 400; margin: 1rem 0; }
        .highlight { font-weight: bold; font-size: 1.8rem; color: #00e676; }
        table {
            width: 100%; margin: 1rem auto;
            border-collapse: collapse;
            background-color: #212121;
            color: #e0e0e0;
            border-radius: 10px;
        }
        th, td {
            padding: 14px;
            border-bottom: 1px solid #424242;
        }
        th { background-color: #37474f; color: #fff176; }
        h3 { color: #ffee58; margin-top: 2rem; }
        .checkbox-label {
            color: #fff; font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="logo-fijo"><img src="logo-almex.png" alt="Logo ALMEX"></div>

<div class="sidebar">
    <h3>Rango</h3>
    <?php
    $rangoLinks = ['semana' => 'Semana', 'mes' => 'Mes', '3meses' => '3 Meses', 'anio' => 'Año', 'todo' => 'Todo'];
    foreach ($rangoLinks as $clave => $texto):
    ?>
    <form method="get">
        <input type="hidden" name="rango" value="<?= $clave ?>">
        <?php if ($excluirFestivos): ?>
            <input type="hidden" name="excluirFestivos" value="1">
        <?php endif; ?>
        <button type="submit"><?= $texto ?></button>
    </form>
    <?php endforeach; ?>

    <h4>Buscar por Rango</h4>
    <form method="get">
        <?php
        $meses_es = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        ?>
        <label>Mes inicio:
            <select name="mesInicio">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $meses_es[$i] ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>Año inicio:
            <input type="number" name="anioInicio" value="<?= date('Y') ?>">
        </label>
        <label>Mes fin:
            <select name="mesFin">
                <?php for ($i = 1; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $meses_es[$i] ?></option>
                <?php endfor; ?>
            </select>
        </label>
        <label>Año fin:
            <input type="number" name="anioFin" value="<?= date('Y') ?>">
        </label>
        <label class="checkbox-label">
            <input type="checkbox" name="excluirFestivos" value="1" <?= $excluirFestivos ? 'checked' : '' ?>>
            Excluir días festivos
        </label>
        <button type="submit" style="background-color:#00c853; color:white;">Buscar</button>
    </form>
</div>

<div class="main">
    <div class="container">
        <h1>Tipo de Cambio del Dólar</h1>
        <h2>Fecha: <?= $fechaHoy ?> | Valor Actual: <span class="highlight">$<?= $valorHoy ?></span></h2>

        <?php foreach ($meses as $ym => $info): ?>
            <h3><?= fechaMesLargoEspañol($ym) ?></h3>
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
</div>
</body>
</html>
