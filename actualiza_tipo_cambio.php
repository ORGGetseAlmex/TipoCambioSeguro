<?php
define('APP_RUNNING', true);
require 'db.php';
require 'helpers.php';

date_default_timezone_set('America/Mexico_City');

$token = $_ENV['BANXICO_TOKEN'] ?? null;
if (!$token) {
    echo "Error: BANXICO_TOKEN no está definido en variables de entorno.\n";
    exit(1);
}

$fechaFin = date("Y-m-d");

try {
    
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

   
    $checkIndex = $conn->query("SHOW INDEX FROM tblTipoCambio WHERE Key_name = 'uniq_fecha_moneda'");
    if ($checkIndex->num_rows === 0) {
        $conn->query("ALTER TABLE tblTipoCambio ADD UNIQUE KEY uniq_fecha_moneda (FechaValor, Moneda)");
    }

    
    $conn->query("DELETE t1 FROM tblTipoCambio t1 JOIN tblTipoCambio t2
        ON t1.FechaValor = t2.FechaValor AND t1.Moneda = t2.Moneda AND t1.Id > t2.Id");

    
    $result = $conn->query("SELECT MAX(FechaValor) AS ultimaFecha FROM tblTipoCambio WHERE Moneda = '02'");
    $row = $result->fetch_assoc();

    $fechaInicio = $row['ultimaFecha'] ?? null;

    if (!$fechaInicio) {
        
        $fechaInicio = "1991-11-21";
        echo "No se encontraron datos anteriores. Descargando todo desde 1991...\n";
    } else {
        
        $fechaInicio = date("Y-m-d", strtotime($fechaInicio . " +1 day"));
        echo "Última fecha encontrada: {$row['ultimaFecha']}. Iniciando desde $fechaInicio hasta $fechaFin...\n";
    }

    if ($fechaInicio > $fechaFin) {
        echo "No hay fechas nuevas para actualizar. Última fecha registrada es mayor o igual al día de hoy.\n";
        exit(0);
    }

    
    $url = "https://www.banxico.org.mx/SieAPIRest/service/v1/series/SF60653/datos/$fechaInicio/$fechaFin?token=$token";
    $response = @file_get_contents($url);

    if (!$response) {
        echo "Error: No se pudo conectar con Banxico o respuesta vacía.\n";
        exit(1);
    }

    $data = json_decode($response, true);
    if (!isset($data['bmx']['series'][0]['datos'])) {
        echo "Error: La respuesta de Banxico no contiene datos válidos.\n";
        exit(1);
    }

    $registros = $data['bmx']['series'][0]['datos'];
    if (count($registros) === 0) {
        echo "No se encontraron nuevos registros para actualizar.\n";
        exit(0);
    }

    
    $stmt = $conn->prepare("INSERT IGNORE INTO tblTipoCambio (Valor, FechaValor, FechaEmision, FechaLiquidacion, Moneda) VALUES (?, ?, ?, ?, '02')");

    foreach ($registros as $item) {
        $fecha = DateTime::createFromFormat('d/m/Y', $item['fecha'])->format('Y-m-d');
        $valor = floatval($item['dato']);
        $stmt->bind_param("dsss", $valor, $fecha, $fecha, $fecha);
        $stmt->execute();
    }

    $stmt->close();

   
    $hoy = date("Y-m-d");
    $conn->query("INSERT INTO tblTipoCambioStatus (ultima_actualizacion) VALUES ('$hoy')");

    echo "Actualización completada con éxito el $hoy. Registros nuevos insertados: " . count($registros) . "\n";

} catch (Exception $e) {
    echo "Error durante la actualización: " . $e->getMessage() . "\n";
    exit(1);
}

$conn->close();
?>
