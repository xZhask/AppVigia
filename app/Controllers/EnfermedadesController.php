<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Enfermedad;

class EnfermedadesController extends Controller
{
    private const TIPOS_NOTIF = ['INMEDIATA', 'SEMANAL'];
    private const RUTA = 'catalogos/enfermedades';

    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/enfermedades/index', [
            'tituloVista' => 'Enfermedades',
            'rutaActual'  => self::RUTA,
            'enfermedades' => Enfermedad::todos('nombre'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/enfermedades/formulario', [
            'tituloVista' => 'Nueva enfermedad',
            'rutaActual'  => self::RUTA,
            'enfermedad'  => ['id' => null, 'nombre' => '', 'cie10' => '', 'tipo_notif' => 'SEMANAL', 'grupo' => '', 'activo' => 1],
            'errores'     => [],
        ]);
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/enfermedades/formulario', [
                'tituloVista' => 'Nueva enfermedad',
                'rutaActual'  => self::RUTA,
                'enfermedad'  => $datos + ['id' => null],
                'errores'     => $errores,
            ]);
            return;
        }

        Enfermedad::crear($datos);
        Flash::set('Enfermedad registrada: ' . $datos['nombre']);
        header('Location: /catalogos/enfermedades');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $enfermedad = Enfermedad::buscar((int) $id);
        if (!$enfermedad) {
            Flash::set('La enfermedad solicitada no existe.');
            header('Location: /catalogos/enfermedades');
            exit;
        }

        $this->vista('catalogos/enfermedades/formulario', [
            'tituloVista' => 'Editar enfermedad',
            'rutaActual'  => self::RUTA,
            'enfermedad'  => $enfermedad,
            'errores'     => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/enfermedades/formulario', [
                'tituloVista' => 'Editar enfermedad',
                'rutaActual'  => self::RUTA,
                'enfermedad'  => $datos + ['id' => $id],
                'errores'     => $errores,
            ]);
            return;
        }

        Enfermedad::actualizar((int) $id, $datos);
        Flash::set('Enfermedad actualizada: ' . $datos['nombre']);
        header('Location: /catalogos/enfermedades');
        exit;
    }

    public function alternarActivo(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        $enfermedad = Enfermedad::buscar((int) $id);
        if ($enfermedad) {
            Enfermedad::actualizar((int) $id, ['activo' => $enfermedad['activo'] ? 0 : 1]);
            Flash::set($enfermedad['activo'] ? 'Enfermedad desactivada.' : 'Enfermedad activada.');
        }

        header('Location: /catalogos/enfermedades');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'nombre'     => trim($entrada['nombre'] ?? ''),
            'cie10'      => trim($entrada['cie10'] ?? '') ?: null,
            'tipo_notif' => $entrada['tipo_notif'] ?? '',
            'grupo'      => trim($entrada['grupo'] ?? '') ?: null,
            'activo'     => isset($entrada['activo']) ? 1 : 0,
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if (!in_array($datos['tipo_notif'], self::TIPOS_NOTIF, true)) {
            $errores['tipo_notif'] = 'Selecciona un tipo de notificación válido.';
        }

        return [$datos, $errores];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /catalogos/enfermedades');
            exit;
        }
    }
}
