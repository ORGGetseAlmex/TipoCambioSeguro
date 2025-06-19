<?php
define('APP_RUNNING', true);
date_default_timezone_set('America/Mexico_City');
require 'db.php';

$mensaje = "";

// Alta de festivo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'agregar') {
    $dia = str_pad($_POST['dia'], 2, '0', STR_PAD_LEFT);
    $mes = str_pad($_POST['mes'], 2, '0', STR_PAD_LEFT);
    $anio = intval($_POST['anio']);
    $descripcion = trim($_POST['descripcion']);
    $recurrente = isset($_POST['recurrente']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT IGNORE INTO tblDiasFestivos (fecha, descripcion, recurrente) VALUES (?, ?, ?)");
    $insertadas = 0;

    if ($recurrente) {
        for ($y = $anio; $y <= 2100; $y++) {
            $fecha = "$y-$mes-$dia";
            $stmt->bind_param("ssi", $fecha, $descripcion, $recurrente);
            if ($stmt->execute()) $insertadas++;
        }
    } else {
        $fecha = "$anio-$mes-$dia";
        $stmt->bind_param("ssi", $fecha, $descripcion, $recurrente);
        if ($stmt->execute()) $insertadas++;
    }

    $mensaje = "Festivos registrados: $insertadas";
}

// Eliminación
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $recurrente = isset($_GET['recurrente']);
    $sql = $recurrente
        ? "DELETE FROM tblDiasFestivos WHERE DAY(fecha) = DAY((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND MONTH(fecha) = MONTH((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND recurrente = 1"
        : "DELETE FROM tblDiasFestivos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $recurrente ? $stmt->bind_param("ii", $id, $id) : $stmt->bind_param("i", $id);
    $stmt->execute();
    $mensaje = $recurrente ? "Fechas recurrentes eliminadas." : "Fecha eliminada.";
}

// Actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'actualizar') {
    $id = intval($_POST['id']);
    $desc = trim($_POST['nueva_descripcion']);
    $recurrente = isset($_POST['actualizar_recurrente']);

    if ($recurrente) {
        $sql = "UPDATE tblDiasFestivos SET descripcion = ? WHERE DAY(fecha) = DAY((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND MONTH(fecha) = MONTH((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND recurrente = 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $desc, $id, $id);
    } else {
        $sql = "UPDATE tblDiasFestivos SET descripcion = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $desc, $id);
    }

    $stmt->execute();
    $mensaje = "Actualización completada.";
}

// Obtener todos los registros
$fechas = $conn->query("SELECT * FROM tblDiasFestivos ORDER BY fecha ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestión de Días Festivos</title>
    <style>
        body {
            font-family: "Segoe UI", Tahoma, sans-serif;
            background: url('https://img.freepik.com/fotos-premium/mazorcas-maiz-mesa-madera-telon-fondo-campo-maiz-al-atardecer_159938-2894.jpg') no-repeat center center fixed;
            background-size: cover;
            color: #f1f1f1;
            margin: 0;
            padding: 0;
            display: flex;
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
            max-width: 900px;
            margin: auto;
            text-align: center;
        }
        h1, h2 { color: #ffd54f; }
        form { margin: 1rem 0; }
        select, input[type="number"], input[type="text"], button {
            padding: 0.6rem;
            font-size: 1rem;
            border-radius: 10px;
            border: none;
            margin: 0.2rem;
        }
        button { background-color: #00c853; color: white; cursor: pointer; }
        button:hover { background-color: #00e676; }
        .mensaje { margin: 1rem; font-weight: bold; color: #00e676; }
        table {
            width: 100%;
            margin: 1rem auto;
            border-collapse: collapse;
            background-color: #212121;
            color: #e0e0e0;
            border-radius: 10px;
        }
        th, td {
            padding: 10px;
            border-bottom: 1px solid #424242;
        }
        th { background-color: #37474f; color: #fff176; }
        label { color: #ffee58; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h3>Menú</h3>
        <a href="index.php"><button>⟵ Volver</button></a>
    </div>
    <div class="main">
        <div class="container">
            <h1>Agregar Día Festivo</h1>
            <form method="POST">
                <input type="hidden" name="accion" value="agregar">
                <label>Descripción:
                    <input type="text" name="descripcion" required>
                </label>
                <label>Día:
                    <select name="dia"><?php for ($i = 1; $i <= 31; $i++) echo "<option>$i</option>"; ?></select>
                </label>
                <label>Mes:
                    <select name="mes"><?php foreach (['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'] as $i => $m) echo "<option value='".($i+1)."'>$m</option>"; ?></select>
                </label>
                <label>Año:
                    <input type="number" name="anio" value="<?= date('Y') ?>" required>
                </label>
                <label>
                    <input type="checkbox" name="recurrente"> Aplicar para todos los años
                </label>
                <button type="submit">Guardar</button>
            </form>
            <?php if ($mensaje): ?>
                <div class="mensaje"><?= $mensaje ?></div>
            <?php endif; ?>

            <h2>Días Festivos Registrados</h2>
            <table>
                <tr><th>Fecha</th><th>Descripción</th><th>Recurrente</th><th>Acciones</th></tr>
                <?php foreach ($fechas as $f): ?>
                    <tr>
                        <td><?= date("d/m/Y", strtotime($f['fecha'])) ?></td>
                        <td><?= htmlspecialchars($f['descripcion']) ?></td>
                        <td><?= $f['recurrente'] ? '✔' : '✖' ?></td>
                        <td>
                            <form method="get" style="display:inline;">
                                <input type="hidden" name="delete" value="<?= $f['id'] ?>">
                                <button type="submit">🗑</button>
                                <label><input type="checkbox" name="recurrente"> Recurrente</label>
                            </form>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="accion" value="actualizar">
                                <input type="hidden" name="id" value="<?= $f['id'] ?>">
                                <input type="text" name="nueva_descripcion" placeholder="Nueva descripción" required>
                                <label><input type="checkbox" name="actualizar_recurrente"> Recurrente</label>
                                <button type="submit">✏</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>
