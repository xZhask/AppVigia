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
                if (Usuario::existePersona($persona['id'], (int) $usuario['id'])) {
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

                    Flash::set('Perfil completado exitosamente. Bienvenido/a, ' . $usuario['nombre']);
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
