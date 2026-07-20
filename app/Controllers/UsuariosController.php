<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Establecimiento;
use App\Models\Usuario;

class UsuariosController extends Controller
{
    private const ROLES = ['ADMIN', 'REGISTRADOR'];
    private const RUTA = 'catalogos/usuarios';

    public function index(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('catalogos/usuarios/index', [
            'tituloVista' => 'Usuarios',
            'rutaActual'  => self::RUTA,
            'usuarios'    => Usuario::conEstablecimiento(),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('catalogos/usuarios/formulario', [
            'tituloVista' => 'Nuevo usuario',
            'rutaActual'  => self::RUTA,
            'usuario'     => ['id' => null, 'nombre' => '', 'email' => '', 'rol' => 'REGISTRADOR', 'establecimiento_id' => '', 'activo' => 1],
            'establecimientos' => Establecimiento::todos('nombre'),
            'errores'     => [],
        ]);
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST, null);

        if (!empty($errores)) {
            $this->vista('catalogos/usuarios/formulario', [
                'tituloVista' => 'Nuevo usuario',
                'rutaActual'  => self::RUTA,
                'usuario'     => $datos + ['id' => null],
                'establecimientos' => Establecimiento::todos('nombre'),
                'errores'     => $errores,
            ]);
            return;
        }

        $datos['password_hash'] = password_hash($datos['clave'], PASSWORD_BCRYPT);
        unset($datos['clave'], $datos['tipo_doc'], $datos['num_doc'], $datos['apellido_paterno'], $datos['apellido_materno'], $datos['nombres'], $datos['mostrar_manual']);

        Usuario::crear($datos);
        Flash::set('Usuario registrado.');
        header('Location: /catalogos/usuarios');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN');

        $usuario = Usuario::buscar((int) $id);
        if (!$usuario) {
            Flash::set('El usuario solicitado no existe.');
            header('Location: /catalogos/usuarios');
            exit;
        }
        
        $persona = \App\Models\Persona::buscar((int)$usuario['persona_id']);
        $usuario['tipo_doc'] = $persona['tipo_doc'] ?? '';
        $usuario['num_doc'] = $persona['num_doc'] ?? '';

        $this->vista('catalogos/usuarios/formulario', [
            'tituloVista' => 'Editar usuario',
            'rutaActual'  => self::RUTA,
            'usuario'     => $usuario,
            'establecimientos' => Establecimiento::todos('nombre'),
            'errores'     => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST, (int) $id);

        if (!empty($errores)) {
            $this->vista('catalogos/usuarios/formulario', [
                'tituloVista' => 'Editar usuario',
                'rutaActual'  => self::RUTA,
                'usuario'     => $datos + ['id' => $id],
                'establecimientos' => Establecimiento::todos('nombre'),
                'errores'     => $errores,
            ]);
            return;
        }

        if (!empty($datos['clave'])) {
            $datos['password_hash'] = password_hash($datos['clave'], PASSWORD_BCRYPT);
        }
        unset($datos['clave'], $datos['tipo_doc'], $datos['num_doc'], $datos['apellido_paterno'], $datos['apellido_materno'], $datos['nombres'], $datos['mostrar_manual']);

        Usuario::actualizar((int) $id, $datos);
        Flash::set('Usuario actualizado.');
        header('Location: /catalogos/usuarios');
        exit;
    }

    public function alternarActivo(string $id): void
    {
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        if ((int) $id === Auth::usuario()['id']) {
            Flash::set('No puedes desactivar tu propia cuenta.');
            header('Location: /catalogos/usuarios');
            exit;
        }

        $usuario = Usuario::buscar((int) $id);
        if ($usuario) {
            Usuario::actualizar((int) $id, ['activo' => $usuario['activo'] ? 0 : 1]);
            Flash::set($usuario['activo'] ? 'Usuario desactivado.' : 'Usuario activado.');
        }

        header('Location: /catalogos/usuarios');
        exit;
    }

    private function validar(array $entrada, ?int $idActual): array
    {
        $datos = [
            'tipo_doc'           => trim($entrada['tipo_doc'] ?? ''),
            'num_doc'            => trim($entrada['num_doc'] ?? ''),
            'email'              => trim($entrada['email'] ?? ''),
            'rol'                => $entrada['rol'] ?? '',
            'establecimiento_id' => $entrada['establecimiento_id'] !== '' ? (int) $entrada['establecimiento_id'] : null,
            'activo'             => isset($entrada['activo']) ? 1 : 0,
            'clave'              => (string) ($entrada['clave'] ?? ''),
        ];

        $datos['apellido_paterno'] = trim($entrada['apellido_paterno'] ?? '');
        $datos['apellido_materno'] = trim($entrada['apellido_materno'] ?? '');
        $datos['nombres'] = trim($entrada['nombres'] ?? '');
        $datos['mostrar_manual'] = false;

        $errores = [];
        if ($datos['tipo_doc'] === '' || $datos['num_doc'] === '') {
            $errores['documento'] = 'El documento es obligatorio.';
        } else {
            $persona = \App\Services\PersonaService::buscarOCrear($datos['tipo_doc'], $datos['num_doc']);

            if (!$persona && $datos['apellido_paterno'] !== '' && $datos['nombres'] !== '') {
                $persona = \App\Services\PersonaService::crearManual($datos['tipo_doc'], $datos['num_doc'], [
                    'apellido_paterno' => $datos['apellido_paterno'],
                    'apellido_materno' => $datos['apellido_materno'],
                    'nombres'          => $datos['nombres'],
                ]);
            }

            if (!$persona) {
                $errores['documento'] = 'El documento no existe en RENIEC ni en el padrón local. Completa los apellidos y nombres para registrarlo manualmente.';
                $datos['mostrar_manual'] = true;
            } else {
                $datos['persona_id'] = $persona['id'];
                if (Usuario::existePersona($datos['persona_id'], $idActual)) {
                    $errores['documento'] = 'Ya existe un usuario asociado a este documento.';
                }
            }
        }

        if ($datos['email'] === '' || !filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $errores['email'] = 'Ingresa un correo electrónico válido.';
        } elseif (Usuario::existeEmail($datos['email'], $idActual)) {
            $errores['email'] = 'Ya existe un usuario con este correo.';
        }

        if (!in_array($datos['rol'], self::ROLES, true)) {
            $errores['rol'] = 'Selecciona un rol válido.';
        }

        if ($datos['rol'] === 'REGISTRADOR' && $datos['establecimiento_id'] === null) {
            $errores['establecimiento_id'] = 'El rol Registrador debe tener un establecimiento asignado.';
        }

        if ($idActual === null && strlen($datos['clave']) < 8) {
            $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($idActual !== null && $datos['clave'] !== '' && strlen($datos['clave']) < 8) {
            $errores['clave'] = 'La contraseña debe tener al menos 8 caracteres.';
        }

        return [$datos, $errores];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /catalogos/usuarios');
            exit;
        }
    }
}
