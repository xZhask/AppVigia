<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Flash;
use App\Models\IaasCatalogo;
use App\Models\IaasNorma;

/**
 * Catálogos > Parámetros IAAS (solo ADMIN): horas t del índice de riesgo de
 * ISQ, antimicrobianos del antibiograma y microorganismos (Anexo 11). Se
 * cargan literales de la norma; aquí se ajusta un ítem si el área lo pide.
 */
class IaasParametrosController extends Controller
{
    private const RUTA = '/catalogos/iaas';

    public function index(): void
    {
        Auth::exigirRol('ADMIN');
        $this->vista('iaas/parametros', [
            'tituloVista'     => 'Parámetros IAAS',
            'rutaActual'      => 'catalogos/iaas',
            'horasT'          => IaasCatalogo::horasTConDetalle(),
            'antimicrobianos' => IaasCatalogo::antimicrobianos(false),
            'microorganismos' => IaasCatalogo::microorganismos(false),
        ]);
    }

    private function exigir(): void
    {
        Auth::exigirRol('ADMIN');
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $this->volver('La sesión del formulario expiró. Vuelve a intentarlo.');
        }
    }

    private function volver(string $mensaje, string $ancla = ''): never
    {
        Flash::set($mensaje);
        header('Location: ' . self::RUTA . $ancla);
        exit;
    }

    public function guardarHoras(): void
    {
        $this->exigir();
        $usuarioId = (int) Auth::usuario()['id'];
        foreach ((array) ($_POST['horas'] ?? []) as $procedimiento => $valor) {
            $valor = str_replace(',', '.', trim((string) $valor));
            if (!isset(IaasNorma::PROCEDIMIENTOS[$procedimiento]) || $procedimiento === 'PARTO_VAGINAL') {
                continue;
            }
            if (!is_numeric($valor) || (float) $valor <= 0 || (float) $valor > 24) {
                $this->volver('Las horas t deben ser un número mayor que 0 y hasta 24.', '#horas');
            }
            IaasCatalogo::guardarHorasT($procedimiento, round((float) $valor, 2), $usuarioId);
        }
        $this->volver('Horas t guardadas. El índice de riesgo de ISQ se recalcula con estos valores.', '#horas');
    }

    public function guardarAntimicrobiano(): void
    {
        $this->exigir();
        $nombre = mb_substr(trim((string) ($_POST['nombre'] ?? '')), 0, 80);
        if ($nombre === '') {
            $this->volver('Escribe el nombre del antimicrobiano.', '#antimicrobianos');
        }
        $id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : null;
        IaasCatalogo::guardarAntimicrobiano($id, $nombre, !empty($_POST['activo']) || !$id);
        $this->volver($id ? 'Antimicrobiano actualizado.' : 'Antimicrobiano agregado al final del antibiograma.', '#antimicrobianos');
    }

    public function guardarMicroorganismo(): void
    {
        $this->exigir();
        $nombre = mb_substr(trim((string) ($_POST['nombre'] ?? '')), 0, 120);
        $abreviatura = mb_substr(trim((string) ($_POST['abreviatura'] ?? '')), 0, 40);
        $familia = (int) ($_POST['familia'] ?? 0);
        if ($nombre === '' || $abreviatura === '' || !isset(IaasNorma::FAMILIAS_MICROORGANISMO[$familia])) {
            $this->volver('Completa la familia, el nombre y la abreviatura del microorganismo.', '#microorganismos');
        }
        $id = ctype_digit((string) ($_POST['id'] ?? '')) ? (int) $_POST['id'] : null;
        IaasCatalogo::guardarMicroorganismo($id, $familia, $nombre, $abreviatura, !empty($_POST['activo']) || !$id);
        $this->volver($id ? 'Microorganismo actualizado.' : 'Microorganismo agregado.', '#microorganismos');
    }
}
