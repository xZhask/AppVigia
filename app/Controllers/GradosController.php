<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\GradoPnp;
use PDOException;

class GradosController extends Controller
{
    private const CATEGORIAS = ['OFICIAL_GENERAL', 'OFICIAL_SUPERIOR', 'OFICIAL_SUBALTERNO', 'SUBOFICIAL', 'EMPLEADO_CIVIL'];
    private const RUTA = 'catalogos/grados';

    public function index(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/grados/index', [
            'tituloVista' => 'Grados PNP',
            'rutaActual'  => self::RUTA,
            'grados'      => GradoPnp::todos('jerarquia'),
        ]);
    }

    public function nuevo(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $this->vista('catalogos/grados/formulario', [
            'tituloVista' => 'Nuevo grado PNP',
            'rutaActual'  => self::RUTA,
            'grado'       => ['id' => null, 'abreviatura' => '', 'nombre' => '', 'categoria' => 'SUBOFICIAL', 'jerarquia' => ''],
            'errores'     => [],
        ]);
    }

    public function crear(): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/grados/formulario', [
                'tituloVista' => 'Nuevo grado PNP',
                'rutaActual'  => self::RUTA,
                'grado'       => $datos + ['id' => null],
                'errores'     => $errores,
            ]);
            return;
        }

        GradoPnp::crear($datos);
        Flash::set('Grado registrado: ' . $datos['nombre']);
        header('Location: /catalogos/grados');
        exit;
    }

    public function editar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');

        $grado = GradoPnp::buscar((int) $id);
        if (!$grado) {
            Flash::set('El grado solicitado no existe.');
            header('Location: /catalogos/grados');
            exit;
        }

        $this->vista('catalogos/grados/formulario', [
            'tituloVista' => 'Editar grado PNP',
            'rutaActual'  => self::RUTA,
            'grado'       => $grado,
            'errores'     => [],
        ]);
    }

    public function actualizar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        [$datos, $errores] = $this->validar($_POST);

        if (!empty($errores)) {
            $this->vista('catalogos/grados/formulario', [
                'tituloVista' => 'Editar grado PNP',
                'rutaActual'  => self::RUTA,
                'grado'       => $datos + ['id' => $id],
                'errores'     => $errores,
            ]);
            return;
        }

        GradoPnp::actualizar((int) $id, $datos);
        Flash::set('Grado actualizado: ' . $datos['nombre']);
        header('Location: /catalogos/grados');
        exit;
    }

    public function eliminar(string $id): void
    {
        Auth::exigirRol('ADMIN', 'EPIDEMIOLOGO');
        $this->exigirCsrf();

        try {
            GradoPnp::eliminar((int) $id);
            Flash::set('Grado eliminado.');
        } catch (PDOException $e) {
            Flash::set('No se puede eliminar: hay personas registradas con este grado.');
        }

        header('Location: /catalogos/grados');
        exit;
    }

    private function validar(array $entrada): array
    {
        $datos = [
            'abreviatura' => trim($entrada['abreviatura'] ?? ''),
            'nombre'      => trim($entrada['nombre'] ?? ''),
            'categoria'   => $entrada['categoria'] ?? '',
            'jerarquia'   => (int) ($entrada['jerarquia'] ?? 0),
        ];

        $errores = [];
        if ($datos['abreviatura'] === '') {
            $errores['abreviatura'] = 'La abreviatura es obligatoria.';
        }
        if ($datos['nombre'] === '') {
            $errores['nombre'] = 'El nombre es obligatorio.';
        }
        if (!in_array($datos['categoria'], self::CATEGORIAS, true)) {
            $errores['categoria'] = 'Selecciona una categoría válida.';
        }
        if ($datos['jerarquia'] < 1) {
            $errores['jerarquia'] = 'La jerarquía debe ser un número mayor a 0 (1 = mayor rango).';
        }

        return [$datos, $errores];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /catalogos/grados');
            exit;
        }
    }
}
