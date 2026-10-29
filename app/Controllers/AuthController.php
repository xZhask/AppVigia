<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\MailService;
use App\Models\LoginIntento;
use App\Models\PasswordResetToken;
use App\Models\Usuario;

class AuthController
{
    public function mostrarLogin(): void
    {
        if (Auth::estaAutenticado()) {
            header('Location: /');
            exit;
        }

        $error = $_SESSION['_error_login'] ?? null;
        $intentosRestantes = $_SESSION['_intentos_restantes_login'] ?? null;
        $mensaje = $_SESSION['_mensaje_login'] ?? null;
        // Lo escrito en el intento anterior, para no volver a digitarlo.
        $documento = $_SESSION['_documento_login'] ?? '';
        unset($_SESSION['_error_login'], $_SESSION['_intentos_restantes_login'], $_SESSION['_mensaje_login'], $_SESSION['_documento_login']);

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        // Se limpia en cada intento nuevo: si no, un contador de un intento
        // anterior queda "pegado" en sesión y se muestra junto al mensaje
        // de un intento posterior que no pasó por esa misma rama (p. ej. un
        // bloqueo por IP que no vuelve a calcular el contador).
        unset($_SESSION['_intentos_restantes_login']);

        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $_SESSION['_error_login'] = 'La sesión del formulario expiró. Vuelve a intentarlo.';
            header('Location: /login');
            exit;
        }

        // Documento (DNI) con que se entra; sin espacios, que a veces se
        // cuelan al copiar el número.
        $documento = preg_replace('/\s+/', '', (string) ($_POST['documento'] ?? ''));
        $clave = (string) ($_POST['clave'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['_documento_login'] = mb_substr($documento, 0, 120);

        if ($documento !== '' && LoginIntento::estaBloqueado($documento, $ip)) {
            $_SESSION['_error_login'] = 'Demasiados intentos fallidos. Espera unos minutos antes de volver a intentarlo.';
            header('Location: /login');
            exit;
        }

        // Auth::intentarLogin() compara siempre contra un hash (real o
        // señuelo) para que el tiempo de respuesta no delate si el documento
        // tiene cuenta; por eso se llama igual aunque venga vacío.
        $exito = $documento !== '' && $clave !== '' && Auth::intentarLogin($documento, $clave);

        if ($documento !== '') {
            LoginIntento::registrar($documento, $ip, $exito);
        }

        if (!$exito) {
            $_SESSION['_error_login'] = 'DNI o contraseña incorrectos.';

            if ($documento !== '') {
                $restantes = LoginIntento::intentosRestantes($documento, $ip);
                $intentosUsados = LoginIntento::MAX_INTENTOS - $restantes;
                // "Solo después del primer fallo": con 1 solo fallo no se
                // muestra el contador todavía.
                if ($intentosUsados >= 2) {
                    $_SESSION['_intentos_restantes_login'] = $restantes;
                }
            }

            header('Location: /login');
            exit;
        }

        $destino = $_SESSION['_url_deseada'] ?? '/';
        unset($_SESSION['_url_deseada'], $_SESSION['_documento_login']);
        header('Location: ' . $destino);
        exit;
    }

    public function logout(): void
    {
        if (Csrf::valido($_POST['csrf_token'] ?? null)) {
            Auth::cerrarSesion();
        }

        header('Location: /login');
        exit;
    }

    public function mostrarOlvide(): void
    {
        if (Auth::estaAutenticado()) {
            header('Location: /');
            exit;
        }

        $enviado = (bool) ($_SESSION['_olvide_enviado'] ?? false);
        unset($_SESSION['_olvide_enviado']);

        require __DIR__ . '/../Views/auth/olvide.php';
    }

    public function procesarOlvide(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            header('Location: /login/olvide');
            exit;
        }

        $documento = preg_replace('/\s+/', '', (string) ($_POST['documento'] ?? ''));

        if ($documento !== '') {
            $usuario = Usuario::buscarPorDocumento($documento);

            // Sin correo registrado no hay a dónde enviar el enlace: el ADMIN
            // le asigna una contraseña nueva desde Catálogos > Usuarios.
            if ($usuario && (int) $usuario['activo'] === 1 && !empty($usuario['email'])) {
                $token = PasswordResetToken::crear((int) $usuario['id']);
                $enlace = $this->urlBase() . '/login/restablecer?token=' . $token;

                MailService::enviar(
                    $usuario['email'],
                    'VIGÍA PNP · Restablecer contraseña',
                    "Solicitaste restablecer tu contraseña en VIGÍA PNP.\n\n"
                        . "Usa este enlace (válido por 1 hora):\n$enlace\n\n"
                        . "Si no fuiste tú, ignora este correo: tu contraseña sigue igual."
                );
            }
            // Si el documento no tiene cuenta, está inactiva o no tiene
            // correo, no se hace nada más: ni se crea token ni se envía
            // correo, pero la respuesta al usuario es idéntica en todos los
            // casos (ver _olvide_enviado abajo).
        }

        $_SESSION['_olvide_enviado'] = true;
        header('Location: /login/olvide');
        exit;
    }

    public function mostrarRestablecer(): void
    {
        $token = trim($_GET['token'] ?? '');
        $valido = $token !== '' && PasswordResetToken::validar($token) !== null;
        $errores = [];

        require __DIR__ . '/../Views/auth/restablecer.php';
    }

    public function procesarRestablecer(): void
    {
        $token = trim($_POST['token'] ?? '');
        $fila = $token !== '' ? PasswordResetToken::validar($token) : null;
        $valido = $fila !== null;
        $errores = [];

        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $errores['general'] = 'La sesión del formulario expiró. Vuelve a intentarlo.';
        } elseif ($valido) {
            $clave = (string) ($_POST['clave'] ?? '');
            $claveConfirmar = (string) ($_POST['clave_confirmar'] ?? '');

            if (strlen($clave) < 8) {
                $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif ($clave !== $claveConfirmar) {
                $errores['clave_confirmar'] = 'Las contraseñas no coinciden.';
            } else {
                Usuario::actualizar((int) $fila['usuario_id'], [
                    'password_hash' => password_hash($clave, PASSWORD_BCRYPT),
                ]);
                PasswordResetToken::marcarUsado((int) $fila['id']);

                $_SESSION['_mensaje_login'] = 'Contraseña actualizada. Ya puedes iniciar sesión.';
                header('Location: /login');
                exit;
            }
        }

        require __DIR__ . '/../Views/auth/restablecer.php';
    }

    private function urlBase(): string
    {
        $config = require __DIR__ . '/../../config/config.php';
        $prefijo = rtrim($config['app']['base_url'] ?? '', '/');
        $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

        return $esquema . '://' . $host . $prefijo;
    }
}
