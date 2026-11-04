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

    public function jefeRed(): void
    {
        Auth::exigirRol('ADMIN', 'JEFE_RED');

        $this->vista('ayuda/jefe-red', [
            'tituloVista' => 'Guía del jefe de red',
            'rutaActual'  => 'ayuda',
            'vistaAncha'  => true,
            'guiaActual'  => 'jefe-red',
        ]);
    }

    public function iaas(): void
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');

        $this->vista('ayuda/iaas', [
            'tituloVista' => 'Guía de IAAS',
            'rutaActual'  => 'ayuda',
            'vistaAncha'  => true,
            'guiaActual'  => 'iaas',
        ]);
    }
}
