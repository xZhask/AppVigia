<?php
namespace App\Core;

abstract class Controller
{
    protected function vista(string $vista, array $datos = []): void
    {
        View::render($vista, $datos);
    }
}
