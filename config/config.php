<?php
// Configuración del entorno. No versionar credenciales reales en producción.

return [
    'db' => [
        'host'    => '127.0.0.1',
        'puerto'  => '3306',
        'nombre'  => 'vigia',
        'usuario' => 'root',
        'clave'   => '',
        'charset' => 'utf8mb4',
    ],
    'app' => [
        // Ruta base de la app, útil si no corre en la raíz del host virtual.
        'base_url' => '',
    ],
];
