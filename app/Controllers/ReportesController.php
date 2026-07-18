<?php
namespace App\Controllers;

use App\Core\Controller;

class ReportesController extends Controller
{
    public function index(): void
    {
        $this->vista('reportes/index', [
            'tituloVista' => 'Reportes',
            'rutaActual'  => 'reportes',
        ]);
    }
}
