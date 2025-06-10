<?php
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

$fechaFin = date("Y-m-d");
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
            width: 220px;
            height: 100vh;
            position: fixed;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }
        .sidebar h3 {
            color: #ffee58;
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.4rem;
        }
        .sidebar form {
            width: 100%;
        }
        .sidebar button {
            width: 100%;
            padding: 0.8rem;
            background-color: #37474f;
            color: #fff176;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
            margin-bottom: 0.5rem;
            font-size: 1rem;
        }
        .sidebar button:hover {
            background-color: #455a64;
        }

        .main {
            margin-left: 240px;
            padding: 3rem 2rem;
            width: 100%;
        }

        .main .container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 2rem 3rem;
            border-radius: 20px;
            max-width: 1000px;
            margin: auto;
            text-align: center;
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
            font-size: 1.8rem;
            color: #00e676;
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

        .busqueda-box {
            margin-top: 2rem;
            background-color: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            text-align: left;
            color: #fff;
        }
        .busqueda-box label {
            display: inline-block;
            margin: 0.5rem 0;
        }
        .busqueda-box input,
        .busqueda-box select,
        .busqueda-box button {
            margin: 0.3rem 0;
            padding: 0.4rem;
            border-radius: 5px;
            border: none;
            font-size: 1rem;
        }
        .busqueda-box button {
            background-color: #00bfa5;
            color: #fff;
            cursor: pointer;
        }
        .busqueda-box button:hover {
            background-color: #008e76;
        }
        .highlighted {
            background-color: #4caf50 !important;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="logo-fijo">
    <img src="logo-almex.png" alt="Logo ALMEX">
</div>

<div class="sidebar">
    <h3>Rango</h3>
    <form method="get"><input type="hidden" name="rango" value="semana"><button type="submit">Semana</button></form>
    <form method="get"><input type="hidden" name="rango" value="mes"><button type="submit">Mes</button></form>
    <form method="get"><input type="hidden" name="rango" value="3meses"><button type="submit">3 Meses</button></form>
    <form method="get"><input type="hidden" name="rango" value="anio"><button type="submit">Año</button></form>
    <form method="get"><input type="hidden" name="rango" value="todo"><button type="submit">Todo</button></form>
</div>

<div class="main">
    <div class="container">
        <h1>Tipo de Cambio del Dólar</h1>
        <h2>Fecha: <?= $fechaHoy ?> | Valor Actual: <span class="highlight">$<?= $valorHoy ?></span></h2>

        <div class="busqueda-box">
            <h3>Búsqueda por rango</h3>
            <label>Mes inicio:
                <select id="mesInicio">
                    <?php for($i=1;$i<=12;$i++): ?>
                        <option value="<?= $i ?>"><?= DateTime::createFromFormat('!m', $i)->format('F') ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Año inicio:
                <input type="number" id="anioInicio" value="<?= date('Y') ?>">
            </label><br>
            <label>Mes fin:
                <select id="mesFin">
                    <?php for($i=1;$i<=12;$i++): ?>
                        <option value="<?= $i ?>"><?= DateTime::createFromFormat('!m', $i)->format('F') ?></option>
                    <?php endfor; ?>
                </select>
            </label>
            <label>Año fin:
                <input type="number" id="anioFin" value="<?= date('Y') ?>">
            </label>
            <button onclick="buscarPorRango()">Buscar</button>
        </div>

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
</div>

<script>
function buscarPorRango() {
    const mesInicio = parseInt(document.getElementById('mesInicio').value);
    const anioInicio = parseInt(document.getElementById('anioInicio').value);
    const mesFin = parseInt(document.getElementById('mesFin').value);
    const anioFin = parseInt(document.getElementById('anioFin').value);

    const filas = document.querySelectorAll("table tr");
    filas.forEach(fila => fila.classList.remove("highlighted"));

    filas.forEach(fila => {
        const celdaFecha = fila.querySelector("td");
        if (!celdaFecha) return;

        const texto = celdaFecha.textContent.trim();
        const partes = texto.split(" ");
        if (partes.length < 5) return;

        const dia = parseInt(partes[1]);
        const mesTexto = partes[3];
        const anio = parseInt(partes[5]);

        const meses = ["enero","febrero","marzo","abril","mayo","junio","julio","agosto","septiembre","octubre","noviembre","diciembre"];
        const mesNum = meses.indexOf(mesTexto.toLowerCase()) + 1;

        const fechaActual = new Date(anio, mesNum - 1, dia);
        const desde = new Date(anioInicio, mesInicio - 1, 1);
        const hasta = new Date(anioFin, mesFin, 0);

        if (fechaActual >= desde && fechaActual <= hasta) {
            fila.classList.add("highlighted");
        }
    });
}
</script>
</body>
</html>
