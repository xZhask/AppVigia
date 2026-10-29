<?php
// Configuración del entorno. Este archivo está en git (repositorio público):
// NUNCA escribir aquí tokens ni claves. Lo propio de cada estación o
// servidor (token de RENIEC, clave de la BD...) va en config.local.php, que
// git ignora y se combina al final sobre estos valores. Plantilla:
// config.local.example.php.

$config = [
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
        // Zona horaria por defecto para Perú.
        'timezone' => 'America/Lima',
    ],
    'reportes' => [
        // Años previos con datos necesarios para usar el canal histórico
        // (percentiles) en la curva epidemiológica; por debajo de esto se
        // usa el respaldo de media móvil, etiquetado como provisional.
        'anios_minimos_corredor' => 2,
    ],
    'notificacion_semanal' => [
        // Plazo para cerrar una semana epidemiológica (domingo a sábado):
        // días después del sábado en que termina y hora de ese día. 2 y
        // '12:00' = el lunes siguiente a las 12:00. Cerrar después cuenta
        // como cierre tardío; no cerrar, como "no notificó".
        'plazo_dias' => 2,
        'plazo_hora' => '12:00',
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
        // sale siempre del servidor, vía ReniecService. El token va en
        // config.local.php.
        'url'     => 'https://apiperu.dev/api/dni/{dni}',
        'token'   => '',
        'timeout' => 5,
        // Cuidado del plan (las consultas tienen tope mensual). 0 = apagado.
        //   limite_diario_usuario: consultas reales por usuario y día.
        //   recordar_no_encontrado_dias: no repetir un DNI que RENIEC no
        //     tenía durante estos días (se digita a mano).
        //   pausa_rechazo_horas: tras un rechazo del proveedor (cuota
        //     agotada o token inválido) nadie consulta durante estas horas.
        'limite_diario_usuario'       => 20,
        'recordar_no_encontrado_dias' => 30,
        'pausa_rechazo_horas'         => 6,
    ],
];

$local = __DIR__ . '/config.local.php';

return is_file($local) ? array_replace_recursive($config, require $local) : $config;
