<?php
namespace App\Controllers;

use App\Models\Database;
use App\Models\Festivo;

class FestivosController
{
    public function index(): void
    {
        $db = new Database();
        $model = new Festivo($db);
        $anioActual = date('Y');
        $mensaje = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar') {
            $dia = (int)$_POST['dia'];
            $mes = (int)$_POST['mes'];
            $anio = (int)$_POST['anio'];
            $descripcion = trim($_POST['descripcion']);
            $recurrente = isset($_POST['recurrente']);
            $insertadas = $model->add($dia, $mes, $anio, $descripcion, $recurrente);
            $mensaje = "Festivos registrados: $insertadas";
        }

        if (isset($_GET['delete'])) {
            $id = (int)$_GET['delete'];
            $rec = isset($_GET['recurrente']);
            $model->delete($id, $rec);
            $mensaje = $rec ? 'Fechas eliminadas en todos los años.' : 'Fecha eliminada.';
        }

        $fechas = $model->all((int)$anioActual);
        $db->getConnection()->close();
        require __DIR__ . '/../Views/festivos.php';
    }
}
