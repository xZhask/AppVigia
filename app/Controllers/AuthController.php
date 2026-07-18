<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;

class AuthController
{
    public function mostrarLogin(): void
    {
        if (Auth::estaAutenticado()) {
            header('Location: /');
            exit;
        }

        $error = $_SESSION['_error_login'] ?? null;
        unset($_SESSION['_error_login']);

        require __DIR__ . '/../Views/auth/login.php';
    }

    public function login(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $_SESSION['_error_login'] = 'La sesión del formulario expiró. Vuelve a intentarlo.';
            header('Location: /login');
            exit;
        }

        $email = trim($_POST['email'] ?? '');
        $clave = (string) ($_POST['clave'] ?? '');

        if ($email === '' || $clave === '' || !Auth::intentarLogin($email, $clave)) {
            $_SESSION['_error_login'] = 'Correo o contraseña incorrectos.';
            header('Location: /login');
            exit;
        }

        header('Location: /');
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
}
