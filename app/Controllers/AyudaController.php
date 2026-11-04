<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;

/** Guías de uso dentro del sistema. /ayuda es la del registrador: las fichas enlazan a sus secciones. */
class AyudaController extends Controller
{
    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');

        $this->vista('ayuda/registrador', [
            'tituloVista' => 'Guía del registrador',
            'rutaActual'  => 'ayuda',
            'vistaAncha'  => true,
            'guiaActual'  => 'registrador',
        ]);
    }

    public function administrador(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('ayuda/administrador', [
            'tituloVista' => 'Guía del administrador',
            'rutaActual'  => 'ayuda',
            'vistaAncha'  => true,
            'guiaActual'  => 'administrador',
        ]);
    }
}
