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
        // En producción debe quedar en false: los errores se registran en el
        // log de PHP, nunca se muestran en pantalla (detalle técnico ≠ usuario).
        'debug' => false,
    ],
    'reportes' => [
        // Años previos con datos necesarios para usar el canal histórico
        // (percentiles) en la curva epidemiológica; por debajo de esto se
        // usa el respaldo de media móvil, etiquetado como provisional.
        'anios_minimos_corredor' => 2,
    ],
];
