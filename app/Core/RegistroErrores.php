<?php
namespace App\Core;

use PDO;

/**
 * Registro de errores (2026-10-27): guarda en error_registro cada excepción
 * no capturada, aviso de PHP (warning, notice, deprecated), caída grave
 * (fatal) y error del navegador (/errores/js), con usuario, establecimiento y
 * pantalla. El id es el código que ve el usuario (E-00042) para reportarlo;
 * el ADMIN los revisa en /sistema/errores.
 *
 * Usa una conexión propia: si el error ocurrió dentro de una transacción que
 * nadie deshizo, el INSERT en la conexión compartida se perdería con ella.
 * Si la base no responde, queda solo el log de PHP, como antes.
 */
class RegistroErrores
{
    /** Tope por petición: un aviso dentro de un bucle no llena la tabla. */
    private const MAX_POR_PETICION = 20;
    /** Tope de errores del navegador por sesión y hora. */
    public const MAX_JS_POR_HORA = 30;
    /** Parámetros de la URL que pueden traer datos personales: se guarda solo el nombre. */
    private const PARAMETROS_SENSIBLES = ['q', 'dni', 'num_doc', 'documento', 'nombre', 'nombres', 'apellido', 'email', 'correo', 'token', 'clave'];
    private const FATALES = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR];

    private static bool $registrando = false;
    private static int $enEstaPeticion = 0;
    private static ?PDO $conexion = null;

    public static function instalar(): void
    {
        set_error_handler([self::class, 'alError']);
        set_exception_handler([self::class, 'alExcepcion']);
        register_shutdown_function([self::class, 'alTerminar']);
    }

    /** Avisos de PHP: se registran y siguen su curso normal (log y, con debug, pantalla). */
    public static function alError(int $nivel, string $mensaje, string $archivo = '', int $linea = 0): bool
    {
        if (error_reporting() & $nivel) {
            $traza = (new \Exception())->getTraceAsString();
            self::registrar('PHP', self::nombreNivel($nivel), $mensaje, $archivo, $linea, $traza);
        }
        return false;
    }

    public static function alExcepcion(\Throwable $e): void
    {
        error_log('Excepción no capturada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
        self::mostrarError(self::registrarExcepcion($e));
    }

    /** Caídas graves (memoria, tiempo, errores de sintaxis): no pasan por los manejadores. */
    public static function alTerminar(): void
    {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], self::FATALES, true)) {
            return;
        }
        $id = self::registrar('PHP', 'FATAL', $error['message'], $error['file'], $error['line'], null);
        self::mostrarError($id);
    }

    /** Para los catch que muestran su propio mensaje: registra y devuelve el id. */
    public static function registrarExcepcion(\Throwable $e): ?int
    {
        $clase = substr(strrchr('\\' . get_class($e), '\\'), 1);
        return self::registrar('PHP', 'EXCEPCION', $clase . ': ' . $e->getMessage(), $e->getFile(), $e->getLine(), $e->getTraceAsString());
    }

    /** Error enviado por public/js/errores.js. La ruta es la de la página, no la del POST. */
    public static function registrarDelNavegador(string $mensaje, ?string $archivo, ?int $linea, ?string $traza, ?string $pagina): ?int
    {
        $archivo = $archivo ? (parse_url($archivo, PHP_URL_PATH) ?: $archivo) : null;
        return self::registrar('JS', 'JS', $mensaje, $archivo, $linea, $traza, $pagina ? self::rutaSegura($pagina) : null);
    }

    public static function codigo(int $id): string
    {
        return sprintf('E-%05d', $id);
    }

    /** "E-00042", "e42" o "42" → 42. */
    public static function idDeCodigo(string $texto): ?int
    {
        return preg_match('/^\s*(?:E-?)?0*(\d{1,9})\s*$/i', $texto, $m) ? (int) $m[1] : null;
    }

    /** " (código E-00042)" para los mensajes al usuario; vacío si no se pudo registrar. */
    public static function sufijoCodigo(?int $id): string
    {
        return $id ? ' (código ' . self::codigo($id) . ')' : '';
    }

    private static function registrar(string $origen, string $nivel, string $mensaje, ?string $archivo, ?int $linea, ?string $traza, ?string $ruta = null): ?int
    {
        if (self::$registrando || self::$enEstaPeticion >= self::MAX_POR_PETICION) {
            return null;
        }
        self::$registrando = true;
        self::$enEstaPeticion++;
        try {
            $mensaje = mb_substr(trim($mensaje) ?: '(sin mensaje)', 0, 1000);
            $archivo = $archivo ? mb_substr(self::rutaRelativa($archivo), 0, 255) : null;
            $usuario = $_SESSION['usuario'] ?? null;
            $esPhp = $origen === 'PHP';
            $campos = $esPhp && !empty($_POST) ? array_diff(array_keys($_POST), ['csrf_token']) : [];

            $consulta = self::conexion()->prepare(
                'INSERT INTO error_registro (huella, origen, nivel, mensaje, archivo, linea, traza, metodo, ruta, campos_enviados, usuario_id, establecimiento_id, navegador, ip)
                 VALUES (:huella, :origen, :nivel, :mensaje, :archivo, :linea, :traza, :metodo, :ruta, :campos, :usuario, :est, :navegador, :ip)'
            );
            $consulta->execute([
                // Mismo error = mismo origen, mensaje (sin números), archivo y línea.
                'huella'    => sha1(implode('|', [$origen, $nivel, preg_replace('/\d+/', '#', $mensaje), $archivo, $linea])),
                'origen'    => $origen,
                'nivel'     => $nivel,
                'mensaje'   => $mensaje,
                'archivo'   => $archivo,
                'linea'     => $linea ?: null,
                'traza'     => $traza !== null ? mb_substr(self::rutaRelativa($traza), 0, 20000) : null,
                'metodo'    => $esPhp ? ($_SERVER['REQUEST_METHOD'] ?? null) : null,
                'ruta'      => $ruta ?? (isset($_SERVER['REQUEST_URI']) ? self::rutaSegura($_SERVER['REQUEST_URI']) : 'consola'),
                'campos'    => $campos ? mb_substr(implode(', ', $campos), 0, 2000) : null,
                'usuario'   => $usuario['id'] ?? null,
                'est'       => $usuario['establecimiento_id'] ?? null,
                'navegador' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr($_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
                'ip'        => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
            return (int) self::conexion()->lastInsertId();
        } catch (\Throwable $e) {
            error_log('RegistroErrores: no se pudo guardar "' . $mensaje . '" (' . $e->getMessage() . ').');
            return null;
        } finally {
            self::$registrando = false;
        }
    }

    private static function conexion(): PDO
    {
        if (self::$conexion === null) {
            $db = (require __DIR__ . '/../../config/config.php')['db'];
            self::$conexion = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $db['host'], $db['puerto'], $db['nombre'], $db['charset']),
                $db['usuario'],
                $db['clave'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
            );
            self::$conexion->exec("SET time_zone = '-05:00'");
        }
        return self::$conexion;
    }

    /** Página 500, o JSON si la pidió un fetch (iaas.js muestra "mensaje"). */
    private static function mostrarError(?int $id): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        // En una caída grave PHP ya fijó el 500; volver a fijarlo deja un aviso.
        if (!headers_sent() && http_response_code() !== 500) {
            http_response_code(500);
        }
        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch') {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=UTF-8');
            }
            echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error inesperado' . self::sufijoCodigo($id) . '. Vuelve a intentarlo; si se repite, comunícalo al Área de Epidemiología.'], JSON_UNESCAPED_UNICODE);
            return;
        }
        $codigoError = $id ? self::codigo($id) : null;
        require __DIR__ . '/../Views/500.php';
    }

    /** Ruta y parámetros de la URL, con los valores sensibles ocultos. */
    private static function rutaSegura(string $url): string
    {
        $ruta = parse_url($url, PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($url, PHP_URL_QUERY), $parametros);
        foreach ($parametros as $clave => $valor) {
            if (in_array(strtolower((string) $clave), self::PARAMETROS_SENSIBLES, true) && $valor !== '') {
                $parametros[$clave] = '…';
            }
        }
        return mb_substr($ruta . ($parametros ? '?' . urldecode(http_build_query($parametros)) : ''), 0, 500);
    }

    /** Quita la carpeta del proyecto de rutas y trazas: "app/Models/Caso.php". */
    private static function rutaRelativa(string $texto): string
    {
        $raiz = dirname(__DIR__, 2);
        return str_replace([$raiz . DIRECTORY_SEPARATOR, $raiz . '/', str_replace('\\', '/', $raiz) . '/'], '', $texto);
    }

    private static function nombreNivel(int $nivel): string
    {
        return match ($nivel) {
            E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => 'WARNING',
            E_NOTICE, E_USER_NOTICE => 'NOTICE',
            E_DEPRECATED, E_USER_DEPRECATED => 'DEPRECATED',
            default => 'ERROR',
        };
    }
}
