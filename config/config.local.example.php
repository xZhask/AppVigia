<?php
// Plantilla de config.local.php: copiarla como config/config.local.php en
// cada estación y servidor y completar. config.local.php no se versiona
// (.gitignore); solo trae lo que cambia respecto de config.php, que se
// combina por clave (lo que no se escribe aquí queda como en config.php).

return [
    'reniec' => [
        // Token de apiperu.dev (panel del proveedor). Vacío = sin RENIEC.
        'token' => '',
    ],
    // 'db' => ['clave' => ''],
    // 'app' => ['debug' => false],
];
