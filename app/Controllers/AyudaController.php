<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/** Guía de uso dentro del sistema. Por ahora, la del registrador. */
class AyudaController extends Controller
{
    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');

        $this->vista('ayuda/registrador', [
            'tituloVista' => 'Guía del registrador',
            'rutaActual'  => 'ayuda',
            'vistaAncha'  => true,
        ]);
    }
}
