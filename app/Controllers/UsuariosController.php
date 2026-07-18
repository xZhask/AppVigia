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
    private const ROLES = ['ADMIN', 'EPIDEMIOLOGO', 'REGISTRADOR', 'LECTOR'];
    private const RUTA = 'catalogos/usuarios';

    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/usuarios/index', [
            'tituloVista' => 'Usuarios',
            'rutaActual'  => self::RUTA,
            'usuarios'    => Usuario::conEstablecimiento(),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

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
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
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
        unset($datos['clave']);

        Usuario::crear($datos);
        Flash::set('Usuario registrado: ' . $datos['nombre']);
        header('Location: /catalogos/usuarios');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $usuario = Usuario::buscar((int) $id);
        if (!$usuario) {
            Flash::set('El usuario solicitado no existe.');
            header('Location: /catalogos/usuarios');
            exit;
        }

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
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
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
        unset($datos['clave']);

        Usuario::actualizar((int) $id, $datos);
        Flash::set('Usuario actualizado: ' . $datos['nombre']);
        header('Location: /catalogos/usuarios');
        exit;
    }

    public function alternarActivo(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
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
            'nombre'             => trim($entrada['nombre'] ?? ''),
            'email'              => trim($entrada['email'] ?? ''),
            'rol'                => $entrada['rol'] ?? '',
            'establecimiento_id' => $entrada['establecimiento_id'] !== '' ? (int) $entrada['establecimiento_id'] : null,
            'activo'             => isset($entrada['activo']) ? 1 : 0,
            'clave'              => (string) ($entrada['clave'] ?? ''),
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
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
