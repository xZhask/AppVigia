<?php
namespace App\Core;

class View
{
    public static function render(string $vista, array $datos = []): void
    {
        extract($datos);

        $rutaVista = __DIR__ . '/../Views/' . $vista . '.php';
        if (!file_exists($rutaVista)) {
            throw new \RuntimeException("Vista no encontrada: {$vista}");
        }

        ob_start();
        require $rutaVista;
        $contenido = ob_get_clean();

        require __DIR__ . '/../Views/layouts/shell.php';
    }
}
