<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Caso;

class PanelController extends Controller
{
    public function index(): void
    {
        $this->vista('panel/index', [
            'tituloVista' => 'Panel de vigilancia',
            'rutaActual'  => '',
            'totalFichas' => Caso::contarTodos(),
        ]);
    }
}
