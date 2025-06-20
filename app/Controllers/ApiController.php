<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\TipoCambio;

class ApiController
{
    public function priceToday(): void
    {
        header('Content-Type: text/plain');
        $db = new Database();
        $tc = new TipoCambio($db);
        $valor = $tc->valorHoy();
        if ($valor) {
            echo number_format($valor['valor'], 4, '.', '');
        } else {
            echo 'No disponible';
        }
        $db->getConnection()->close();
    }
}
