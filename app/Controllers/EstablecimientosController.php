<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Establecimiento;
use App\Models\RedSalud;

class EstablecimientosController extends Controller
{
    private const INSTITUCIONES = ['MINSA', 'ESSALUD', 'FFAA_SANIDAD', 'PRIVADO'];
    private const RUTA = 'catalogos/establecimientos';

    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/establecimientos/index', [
            'tituloVista'     => 'Establecimientos',
            'rutaActual'      => self::RUTA,
            'establecimientos' => Establecimiento::conRedYUbicacion(),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/establecimientos/formulario', [
            'tituloVista'   => 'Nuevo establecimiento',
            'rutaActual'    => self::RUTA,
            'establecimiento' => [
                'id' => null, 'cod_renipress' => '', 'nombre' => '', 'red_id' => '',
                'institucion' => 'FFAA_SANIDAD', 'distrito_id' => null, 'activo' => 1,
            ],
            'redes'    => RedSalud::todos('nombre'),
            'errores'  => [],
        ] + contextoUbigeo(null));
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/establecimientos/formulario', [
                'tituloVista'   => 'Nuevo establecimiento',
                'rutaActual'    => self::RUTA,
                'establecimiento' => $datos + ['id' => null],
                'redes'    => RedSalud::todos('nombre'),
                'errores'  => $errores,
            ] + contextoUbigeo($datos['distrito_id']));
            return;
        }

        Establecimiento::crear($datos);
        Flash::set('Establecimiento registrado: ' . $datos['nombre']);
        header('Location: /catalogos/establecimientos');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $establecimiento = Establecimiento::buscar((int) $id);
        if (!$establecimiento) {
            Flash::set('El establecimiento solicitado no existe.');
            header('Location: /catalogos/establecimientos');
            exit;
        }

        $this->vista('catalogos/establecimientos/formulario', [
            'tituloVista'   => 'Editar establecimiento',
            'rutaActual'    => self::RUTA,
            'establecimiento' => $establecimiento,
            'redes'    => RedSalud::todos('nombre'),
            'errores'  => [],
        ] + contextoUbigeo($establecimiento['distrito_id']));
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/establecimientos/formulario', [
                'tituloVista'   => 'Editar establecimiento',
                'rutaActual'    => self::RUTA,
                'establecimiento' => $datos + ['id' => $id],
                'redes'    => RedSalud::todos('nombre'),
                'errores'  => $errores,
            ] + contextoUbigeo($datos['distrito_id']));
            return;
        }

        Establecimiento::actualizar((int) $id, $datos);
        Flash::set('Establecimiento actualizado: ' . $datos['nombre']);
        header('Location: /catalogos/establecimientos');
        exit;
    }

    public function alternarActivo(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        $establecimiento = Establecimiento::buscar((int) $id);
        if ($establecimiento) {
            Establecimiento::actualizar((int) $id, ['activo' => $establecimiento['activo'] ? 0 : 1]);
            Flash::set($establecimiento['activo'] ? 'Establecimiento desactivado.' : 'Establecimiento activado.');
        }

        header('Location: /catalogos/establecimientos');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'cod_renipress' => trim($entrada['cod_renipress'] ?? '') ?: null,
            'nombre'        => trim($entrada['nombre'] ?? ''),
            'red_id'        => $entrada['red_id'] !== '' ? (int) $entrada['red_id'] : null,
            'institucion'   => $entrada['institucion'] ?? '',
            'distrito_id'   => $entrada['distrito_id'] !== '' ? $entrada['distrito_id'] : null,
            'activo'        => isset($entrada['activo']) ? 1 : 0,
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if (!in_array($datos['institucion'], self::INSTITUCIONES, true)) {
            $errores['institucion'] = 'Selecciona una institución válida.';
        }

        return [$datos, $errores];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /catalogos/establecimientos');
            exit;
        }
    }
}
