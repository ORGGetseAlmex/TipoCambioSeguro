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
        <a href="/"><button>Volver</button></a>
    </div>
    <div class="main">
        <div class="container">
            <h1>Agregar Día Festivo</h1>
            <form method="POST" action="/festivos">
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
                        <form method="get" action="/festivos" style="display:inline;">
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
