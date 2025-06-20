<?php
namespace App\Models;

use mysqli;
use DateTime;

class TipoCambio
{
    protected mysqli $conn;

    public function __construct(Database $db)
    {
        $this->conn = $db->getConnection();
    }

    public function obtenerRango(string $desde, string $hasta): array
    {
        $stmt = $this->conn->prepare("SELECT Valor, FechaValor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor BETWEEN ? AND ? ORDER BY FechaValor DESC");
        $stmt->bind_param("ss", $desde, $hasta);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res->fetch_all(MYSQLI_ASSOC);
    }

    public function valorHoy(): ?array
    {
        $hoy = date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT Valor FROM tblTipoCambio WHERE Moneda = '02' AND FechaValor = ? ORDER BY Id DESC LIMIT 1");
        $stmt->bind_param('s', $hoy);
        $stmt->execute();
        $stmt->bind_result($valor);
        if ($stmt->fetch()) {
            return ['valor' => $valor, 'fecha' => $hoy];
        }
        return null;
    }
}
