<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('America/Mexico_City');
define('APP_RUNNING', true);
require 'db.php';

// Verifica si la tabla ya existe y tiene la restricción UNIQUE en 'fecha'
$verificaTabla = $conn->query("SHOW CREATE TABLE tblDiasFestivos");
if ($verificaTabla) {
    $row = $verificaTabla->fetch_assoc();
    if (strpos($row['Create Table'], 'UNIQUE KEY `fecha` (`fecha`)') !== false) {
        // Reconstruir tabla eliminando UNIQUE en 'fecha'
        $conn->query("RENAME TABLE tblDiasFestivos TO tblDiasFestivos_old");

        $conn->query("
            CREATE TABLE tblDiasFestivos (
                id INT AUTO_INCREMENT PRIMARY KEY,
                fecha DATE NOT NULL,
                descripcion VARCHAR(100),
                recurrente BOOLEAN DEFAULT 0
            )
        ");

        $conn->query("
            INSERT INTO tblDiasFestivos (fecha, descripcion, recurrente)
            SELECT fecha, descripcion, recurrente FROM tblDiasFestivos_old
        ");

        // Opcional: elimina la tabla antigua si ya se migró correctamente
        // $conn->query("DROP TABLE tblDiasFestivos_old");
    }
} else {
    // Si la tabla no existe, la creamos por primera vez
    $conn->query("
        CREATE TABLE IF NOT EXISTS tblDiasFestivos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fecha DATE NOT NULL,
            descripcion VARCHAR(100),
            recurrente BOOLEAN DEFAULT 0
        )
    ");
}

$mensaje = "";
$anioActual = date('Y');


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['accion'] === 'agregar') {
    $dia = str_pad($_POST['dia'], 2, '0', STR_PAD_LEFT);
    $mes = str_pad($_POST['mes'], 2, '0', STR_PAD_LEFT);
    $anio = intval($_POST['anio']);
    $descripcion = trim($_POST['descripcion']);
    $recurrente = isset($_POST['recurrente']) ? 1 : 0;

    $stmt = $conn->prepare("INSERT IGNORE INTO tblDiasFestivos (fecha, descripcion, recurrente) VALUES (?, ?, ?)");
    $insertadas = 0;

    $rangoInicio = $recurrente ? 1991 : $anio;
    $rangoFin = $recurrente ? 2100 : $anio;

    for ($y = $rangoInicio; $y <= $rangoFin; $y++) {
        $fecha = "$y-$mes-$dia";
        $stmt->bind_param("ssi", $fecha, $descripcion, $recurrente);
        if ($stmt->execute()) $insertadas++;
    }

    $mensaje = "Festivos registrados: $insertadas";
}


if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $recurrente = isset($_GET['recurrente']);

    $sql = $recurrente
        ? "DELETE FROM tblDiasFestivos WHERE DAY(fecha) = DAY((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND MONTH(fecha) = MONTH((SELECT fecha FROM tblDiasFestivos WHERE id = ?))"
        : "DELETE FROM tblDiasFestivos WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $recurrente ? $stmt->bind_param("ii", $id, $id) : $stmt->bind_param("i", $id);
    $stmt->execute();
    $mensaje = $recurrente ? "Fechas eliminadas en todos los años." : "Fecha eliminada.";
}


$fechas = $conn->query("SELECT * FROM tblDiasFestivos WHERE YEAR(fecha) = $anioActual ORDER BY fecha ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestor de Días Festivos</title>
    <style>
        body { font-family: sans-serif; background: #111; color: #eee; margin: 0; display: flex; }
        .sidebar { background: #222; width: 260px; padding: 2rem; height: 100vh; }
        .main { flex-grow: 1; padding: 2rem; }
        .container { background: #333; padding: 2rem; border-radius: 12px; }
        table { width: 100%; background: #222; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 10px; border: 1px solid #444; text-align: center; }
        th { background: #555; color: #ffd54f; }
        input, select, button { padding: 0.5rem; margin: 0.3rem; border-radius: 8px; border: none; }
        button { background: #00c853; color: white; }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>Menú</h2>
        <a href="index.php"><button>Volver</button></a>
    </div>
    <div class="main">
        <div class="container">
            <h1>Agregar Día Festivo</h1>
            <form method="POST">
                <input type="hidden" name="accion" value="agregar">
                <input type="text" name="descripcion" placeholder="Descripción" required>
                <select name="dia"><?php for ($i = 1; $i <= 31; $i++) echo "<option>$i</option>"; ?></select>
                <select name="mes"><?php foreach (range(1, 12) as $m) echo "<option value='$m'>".date('F', mktime(0,0,0,$m,1))."</option>"; ?></select>
                <input type="number" name="anio" value="<?= $anioActual ?>" required>
                <label><input type="checkbox" name="recurrente"> Registrar de 1991 a 2100</label>
                <button type="submit">Guardar</button>
            </form>
            <?php if ($mensaje): ?><p><strong><?= $mensaje ?></strong></p><?php endif; ?>

            <h2>Festivos en <?= $anioActual ?></h2>
            <table>
                <tr><th>Fecha</th><th>Descripción</th><th>Acciones</th></tr>
                <?php foreach ($fechas as $f): ?>
                <tr>
                    <td><?= date("d/m/Y", strtotime($f['fecha'])) ?></td>
                    <td><?= htmlspecialchars($f['descripcion']) ?></td>
                    <td>
                        <form method="get" style="display:inline;">
                            <input type="hidden" name="delete" value="<?= $f['id'] ?>">
                            <button type="submit">Eliminar</button>
                            <label><input type="checkbox" name="recurrente"> Todos los años</label>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</body>
</html>