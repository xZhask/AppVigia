<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\Establecimiento;
use App\Models\RedSalud;
use App\Models\ResumenCatalogo;

class EstablecimientosController extends Controller
{
    private const INSTITUCIONES = ['MINSA', 'ESSALUD', 'FFAA_SANIDAD', 'PRIVADO'];
    private const RUTA = 'catalogos/establecimientos';

    public function index(): void
    {
        Auth::exigirRol('ADMIN');

        // Rediseño 2026-10-19: registradores, cumplimiento del cierre semanal
        // y última notificación por establecimiento (ResumenCatalogo).
        $establecimientos = Establecimiento::paraCatalogo();
        $semanas = ResumenCatalogo::semanas();
        $this->vista('catalogos/establecimientos/index', [
            'tituloVista'      => 'Establecimientos',
            'rutaActual'       => self::RUTA,
            'vistaAncha'       => true,
            'establecimientos' => $establecimientos,
            'semanas'          => $semanas,
            'cumplimiento'     => ResumenCatalogo::cumplimiento(array_map(fn($e) => (int) $e['id'], $establecimientos), $semanas),
            'usuarios'         => ResumenCatalogo::usuariosPorEstablecimiento(),
            'ultimaNotif'      => ResumenCatalogo::ultimaNotificacion(),
            'redes'            => RedSalud::todos('nombre'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN');

        $this->vista('catalogos/establecimientos/formulario', [
            'tituloVista'   => 'Nuevo establecimiento',
            'rutaActual'    => self::RUTA,
            'establecimiento' => [
                'id' => null, 'cod_renipress' => '', 'nombre' => '', 'red_id' => '',
                'institucion' => 'FFAA_SANIDAD', 'categoria' => null, 'distrito_id' => null, 'activo' => 1,
            ],
            'redes'    => RedSalud::todos('nombre'),
            'errores'  => [],
        ] + contextoUbigeo(null));
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN');
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
        Auth::exigirRol('ADMIN');

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
        Auth::exigirRol('ADMIN');
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
        Auth::exigirRol('ADMIN');
        $this->exigirCsrf();

        $establecimiento = Establecimiento::buscar((int) $id);
        $motivo = mb_substr(trim((string) ($_POST['motivo'] ?? '')), 0, 255);
        if ($establecimiento && $establecimiento['activo'] && $motivo === '') {
            Flash::set('Indica el motivo para desactivar el establecimiento.');
        } elseif ($establecimiento) {
            // Desactivar pide motivo (2026-10-19); reactivar lo borra.
            Establecimiento::actualizar((int) $id, $establecimiento['activo'] ? ['activo' => 0, 'motivo_inactivo' => $motivo] : ['activo' => 1, 'motivo_inactivo' => null]);
            Flash::set(nombreEnOracion($establecimiento['nombre']) . ($establecimiento['activo'] ? ' desactivado.' : ' reactivado.'));
        }

        header('Location: /catalogos/establecimientos');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'cod_renipress' => trim($entrada['cod_renipress'] ?? '') ?: null,
            'nombre'        => trim($entrada['nombre'] ?? ''),
            'nombre_corto'  => mb_substr(trim($entrada['nombre_corto'] ?? ''), 0, 80) ?: null,
            'red_id'        => $entrada['red_id'] !== '' ? (int) $entrada['red_id'] : null,
            'institucion'   => $entrada['institucion'] ?? '',
            'categoria'     => ($entrada['categoria'] ?? '') !== '' ? $entrada['categoria'] : null,
            'distrito_id'   => $entrada['distrito_id'] !== '' ? $entrada['distrito_id'] : null,
        ];

        $errores = [];
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if (!in_array($datos['institucion'], self::INSTITUCIONES, true)) {
            $errores['institucion'] = 'Selecciona una institución válida.';
        }
        if ($datos['categoria'] !== null && !array_key_exists($datos['categoria'], CATEGORIAS_ESTABLECIMIENTO)) {
            $errores['categoria'] = 'Selecciona una categoría válida.';
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
