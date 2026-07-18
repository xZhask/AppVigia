<?php
spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    if (!str_starts_with($clase, $prefijo)) {
        return;
    }

    $rutaRelativa = str_replace('\\', '/', substr($clase, strlen($prefijo)));
    $rutaArchivo = __DIR__ . '/../../app/' . $rutaRelativa . '.php';

    if (file_exists($rutaArchivo)) {
        require $rutaArchivo;
    }
});
