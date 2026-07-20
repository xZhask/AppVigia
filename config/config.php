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
    'mail' => [
        // Remitente del correo de restablecimiento de contraseña. Vacío =
        // envío apagado: el token se genera igual y el flujo se comporta
        // idéntico exista o no el correo, pero el enlace solo queda en el
        // log de errores en vez de llegar a la bandeja de entrada. Configurar
        // para producción (requiere un MTA configurado en el servidor).
        'desde' => '',
    ],
    'reniec' => [
        // URL del proveedor de consulta RENIEC, con {dni} como marcador de
        // reemplazo. Vacío = integración apagada (los campos se digitan a
        // mano, sin error). Nunca exponer el token al navegador: la llamada
        // sale siempre del servidor, vía ReniecService.
        'url'     => 'https://apiperu.dev/api/dni/{dni}',
        'token'   => '941c6ff9a8023744ab41617d1b90add48a7b55847bae8b4a3f0a21ac0571e849',
        'timeout' => 5,
    ],
];
