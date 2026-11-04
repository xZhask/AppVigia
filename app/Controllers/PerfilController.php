<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Usuario;
use App\Models\Persona;
use App\Services\PersonaService;

class PerfilController extends Controller
{
    /** Intentos con la contraseña actual equivocada antes de cerrar la sesión. */
    private const MAX_FALLOS_CLAVE = 5;

    public function mostrarContrasena(): void
    {
        $this->vista('perfil/contrasena', [
            'tituloVista' => 'Cambiar contraseña',
            'rutaActual'  => 'perfil/contrasena',
            'errores'     => [],
        ]);
    }

    public function procesarContrasena(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /perfil/contrasena');
            exit;
        }

        $sesion = Auth::usuario();
        $usuario = Usuario::buscar((int) $sesion['id']);
        $actual = (string) ($_POST['clave_actual'] ?? '');
        $nueva = (string) ($_POST['clave'] ?? '');
        $confirmar = (string) ($_POST['clave_confirmar'] ?? '');

        $errores = [];
        if (!password_verify($actual, (string) $usuario['password_hash'])) {
            // El contador va en la sesión y no en login_intento, que bloquea
            // por IP: un error aquí no debe dejar sin ingreso a toda la IPRESS
            // detrás de la misma conexión. Quien usa una sesión ajena la pierde.
            $_SESSION['_fallos_clave'] = (int) ($_SESSION['_fallos_clave'] ?? 0) + 1;
            if ($_SESSION['_fallos_clave'] >= self::MAX_FALLOS_CLAVE) {
                Auth::expulsar('Por seguridad se cerró la sesión tras varios intentos con la contraseña actual equivocada.');
            }
            $errores['clave_actual'] = 'La contraseña actual no es correcta.';
        } else {
            $persona = $usuario['persona_id'] ? Persona::buscar((int) $usuario['persona_id']) : null;
            $documento = trim((string) ($persona['num_doc'] ?? ''));

            if (strlen($nueva) < 8) {
                $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
            } elseif (strlen($nueva) > 72) {
                // bcrypt solo considera los primeros 72 bytes.
                $errores['clave'] = 'La contraseña no puede tener más de 72 caracteres.';
            } elseif ($nueva === $actual) {
                $errores['clave'] = 'La contraseña nueva debe ser distinta de la actual.';
            } elseif ($documento !== '' && $nueva === $documento) {
                $errores['clave'] = 'La contraseña no puede ser tu número de documento.';
            } elseif ($nueva !== $confirmar) {
                $errores['clave_confirmar'] = 'Las contraseñas no coinciden.';
            }
        }

        if ($errores) {
            $this->vista('perfil/contrasena', [
                'tituloVista' => 'Cambiar contraseña',
                'rutaActual'  => 'perfil/contrasena',
                'errores'     => $errores,
            ]);
            return;
        }

        Usuario::actualizar((int) $usuario['id'], ['password_hash' => password_hash($nueva, PASSWORD_BCRYPT)]);
        Auth::renovarHuella();
        unset($_SESSION['_fallos_clave']);
        session_regenerate_id(true);

        Flash::set('Contraseña actualizada. Si tenías la sesión abierta en otro equipo, allí se cerró.');
        header('Location: /');
        exit;
    }

    public function mostrarCompletar(): void
    {
        $usuario = Auth::usuario();

        if (!$usuario || !$usuario['perfil_incompleto']) {
            header('Location: /');
            exit;
        }

        $this->vista('perfil/completar', [
            'tituloVista'   => 'Completar perfil',
            'rutaActual'    => 'perfil/completar',
            'errores'       => [],
            'valores'       => [],
            'mostrarManual' => false,
        ]);
    }

    public function procesarCompletar(): void
    {
        $usuario = Auth::usuario();

        if (!$usuario || !$usuario['perfil_incompleto']) {
            header('Location: /');
            exit;
        }

        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /perfil/completar');
            exit;
        }

        $tipoDoc = trim($_POST['tipo_doc'] ?? '');
        $numDoc = trim($_POST['num_doc'] ?? '');
        $apellidoPaterno = trim($_POST['apellido_paterno'] ?? '');
        $apellidoMaterno = trim($_POST['apellido_materno'] ?? '');
        $nombres = trim($_POST['nombres'] ?? '');
        $mostrarManual = false;

        $errores = [];
        if ($tipoDoc === '' || $numDoc === '') {
            $errores['documento'] = 'Debes ingresar tu tipo y número de documento.';
        } else {
            $persona = PersonaService::buscarOCrear($tipoDoc, $numDoc);

            if (!$persona && $apellidoPaterno !== '' && $nombres !== '') {
                $persona = PersonaService::crearManual($tipoDoc, $numDoc, [
                    'apellido_paterno' => $apellidoPaterno,
                    'apellido_materno' => $apellidoMaterno,
                    'nombres'          => $nombres,
                ]);
            }

            if (!$persona) {
                $errores['documento'] = 'No se encontraron datos para este documento en RENIEC ni en el padrón local. Completa tus apellidos y nombres para registrarte manualmente.';
                $mostrarManual = true;
            } else {
                if (Usuario::existePersona($persona['id'], (int) $usuario['id'])
                    || Usuario::existeDocumento($numDoc, (int) $usuario['id'])) {
                    $errores['documento'] = 'Este documento ya está asociado a otra cuenta de usuario.';
                } else {
                    // Guardar referencia a la persona placeholder antes de sobrescribir
                    $oldPersonaId = $usuario['persona_id'] ?? null;

                    // Actualizar usuario en BD
                    Usuario::actualizar((int) $usuario['id'], [
                        'persona_id' => $persona['id'],
                        'perfil_incompleto' => 0
                    ]);

                    // Actualizar sesión
                    $usuario['persona_id'] = $persona['id'];
                    $usuario['perfil_incompleto'] = 0;
                    $usuario['nombre'] = Persona::nombreCompleto($persona);
                    $_SESSION['usuario'] = $usuario;
                    
                    // Limpiar persona placeholder si existe y es distinta
                    if ($oldPersonaId && $oldPersonaId != $persona['id']) {
                        $oldPersona = Persona::buscar((int) $oldPersonaId);
                        if ($oldPersona && $oldPersona['tipo_doc'] === 'SIN_DOCUMENTO') {
                            Persona::eliminar((int) $oldPersonaId);
                        }
                    }

                    Flash::set('Perfil completado. Bienvenido/a, ' . $usuario['nombre'] . '. Desde ahora entras con tu número de documento (' . $numDoc . ') y tu contraseña.');
                    header('Location: /');
                    exit;
                }
            }
        }

        $this->vista('perfil/completar', [
            'tituloVista'   => 'Completar perfil',
            'rutaActual'    => 'perfil/completar',
            'errores'       => $errores,
            'valores'       => [
                'tipo_doc'         => $tipoDoc,
                'num_doc'          => $numDoc,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
                'nombres'          => $nombres,
            ],
            'mostrarManual' => $mostrarManual,
        ]);
    }
}
