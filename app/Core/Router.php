<?php
namespace App\Core;

class Router
{
    private array $rutas = [];

    public function get(string $ruta, callable $accion): void
    {
        $this->registrar('GET', $ruta, $accion);
    }

    public function post(string $ruta, callable $accion): void
    {
        $this->registrar('POST', $ruta, $accion);
    }

    private function registrar(string $metodo, string $ruta, callable $accion): void
    {
        $patron = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', trim($ruta, '/'));
        $this->rutas[$metodo][] = [
            'patron' => '#^' . $patron . '$#',
            'accion' => $accion,
        ];
    }

    public function despachar(string $metodo, string $uriSolicitada): void
    {
        $ruta = parse_url($uriSolicitada, PHP_URL_PATH) ?? '/';
        $ruta = trim($ruta, '/');

        foreach ($this->rutas[$metodo] ?? [] as $definicion) {
            if (preg_match($definicion['patron'], $ruta, $coincidencias)) {
                array_shift($coincidencias);
                call_user_func_array($definicion['accion'], $coincidencias);
                return;
            }
        }

        http_response_code(404);
        $rutaActual = '404';
        $tituloVista = 'Página no encontrada';
        require __DIR__ . '/../Views/404.php';
    }
}
