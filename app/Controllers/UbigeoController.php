<?php
namespace App\Controllers;

use App\Models\Distrito;
use App\Models\Provincia;

class UbigeoController
{
    public function provincias(): void
    {
        $departamentoId = $_GET['departamento'] ?? '';

        header('Content-Type: application/json; charset=utf-8');

        if ($departamentoId === '') {
            echo json_encode([]);
            return;
        }

        echo json_encode(Provincia::porDepartamento($departamentoId));
    }

    public function distritos(): void
    {
        $provinciaId = $_GET['provincia'] ?? '';

        header('Content-Type: application/json; charset=utf-8');

        if ($provinciaId === '') {
            echo json_encode([]);
            return;
        }

        echo json_encode(Distrito::porProvincia($provinciaId));
    }
}
