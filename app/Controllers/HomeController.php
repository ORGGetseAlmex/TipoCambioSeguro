<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\TipoCambio;
use App\Models\Festivo;

class HomeController
{
    public function index(): void
    {
        $db = new Database();
        $tc = new TipoCambio($db);
        $festivoModel = new Festivo($db);

        $result = $db->getConnection()->query("SELECT fecha FROM tblDiasFestivos ORDER BY fecha ASC");
        $diasFestivos = [];
        while ($row = $result->fetch_assoc()) {
            $diasFestivos[] = $row['fecha'];
        }

        $excluirFestivos = isset($_GET['excluirFestivos']);
        $excluirInhabiles = isset($_GET['excluirInhabiles']);
        $fechaFin = date('Y-m-d');

        if (isset($_GET['mesInicio'], $_GET['anioInicio'], $_GET['mesFin'], $_GET['anioFin'])) {
            $desde = date('Y-m-d', strtotime($_GET['anioInicio'] . '-' . $_GET['mesInicio'] . '-01'));
            $hasta = date('Y-m-t', strtotime($_GET['anioFin'] . '-' . $_GET['mesFin'] . '-01'));
        } else {
            $rango = $_GET['rango'] ?? '3meses';
            switch ($rango) {
                case 'semana': $desde = date('Y-m-d', strtotime('-7 days')); break;
                case 'mes': $desde = date('Y-m-d', strtotime('-1 month')); break;
                case '3meses': $desde = date('Y-m-d', strtotime('-3 months')); break;
                case 'anio': $desde = date('Y-m-d', strtotime('-1 year')); break;
                case 'todo': $desde = '1991-11-21'; break;
                default: $desde = date('Y-m-d', strtotime('-3 months'));
            }
            $hasta = $_GET['hasta'] ?? $fechaFin;
        }

        $datos = $tc->obtenerRango($desde, $hasta);
        $meses = [];
        $valorHoy = null;
        $fechaHoy = null;
        $fechaActual = date('Y-m-d');

        foreach ($datos as $row) {
            $fecha = $row['FechaValor'];
            $esFestivo = in_array($fecha, $diasFestivos);
            $esFinDeSemana = in_array(date('N', strtotime($fecha)), [6,7]);
            if (($excluirFestivos && $esFestivo) || ($excluirInhabiles && ($esFestivo || $esFinDeSemana))) {
                continue;
            }
            $claveMes = date('Y-m', strtotime($fecha));
            if (!isset($meses[$claveMes])) {
                $meses[$claveMes] = ['registros' => [], 'suma' => 0, 'n' => 0];
            }
            $meses[$claveMes]['registros'][] = ['fecha_iso' => $fecha, 'valor' => $row['Valor']];
            $meses[$claveMes]['suma'] += $row['Valor'];
            $meses[$claveMes]['n']++;

            if ($fecha === $fechaActual && !$valorHoy) {
                $valorHoy = number_format($row['Valor'], 4);
                $fechaHoy = fechaFormateadaEspañol($fecha);
            }
        }

        if (!$valorHoy && !empty($meses)) {
            $primerMes = current($meses);
            $primerRegistro = $primerMes['registros'][0];
            $valorHoy = $primerRegistro['valor'];
            $fechaHoy = fechaFormateadaEspañol($primerRegistro['fecha_iso']);
        }

        $db->getConnection()->close();
        require __DIR__ . '/../Views/home.php';
    }
}
