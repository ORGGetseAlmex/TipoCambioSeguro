<?php
namespace App\Models;

use mysqli;

class Festivo
{
    protected mysqli $conn;

    public function __construct(Database $db)
    {
        $this->conn = $db->getConnection();
    }

    public function all(int $anio): array
    {
        $stmt = $this->conn->prepare("SELECT * FROM tblDiasFestivos WHERE YEAR(fecha) = ? ORDER BY fecha ASC");
        $stmt->bind_param('i', $anio);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function add(int $dia, int $mes, int $anio, string $descripcion, bool $recurrente): int
    {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO tblDiasFestivos (fecha, descripcion, recurrente) VALUES (?, ?, ?)");
        $insertadas = 0;
        $rangoInicio = $recurrente ? 1991 : $anio;
        $rangoFin = $recurrente ? 2100 : $anio;
        for ($y = $rangoInicio; $y <= $rangoFin; $y++) {
            $fecha = sprintf('%04d-%02d-%02d', $y, $mes, $dia);
            $stmt->bind_param('ssi', $fecha, $descripcion, $recurrente);
            if ($stmt->execute()) $insertadas++;
        }
        return $insertadas;
    }

    public function delete(int $id, bool $recurrente): void
    {
        $sql = $recurrente ?
            "DELETE FROM tblDiasFestivos WHERE DAY(fecha) = DAY((SELECT fecha FROM tblDiasFestivos WHERE id = ?)) AND MONTH(fecha) = MONTH((SELECT fecha FROM tblDiasFestivos WHERE id = ?))" :
            "DELETE FROM tblDiasFestivos WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($recurrente) {
            $stmt->bind_param('ii', $id, $id);
        } else {
            $stmt->bind_param('i', $id);
        }
        $stmt->execute();
    }
}
