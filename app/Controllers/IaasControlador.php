<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Flash;
use App\Models\Establecimiento;
use App\Models\IaasSala;
use App\Models\Persona;

/**
 * Base de los controladores del módulo IAAS. El REGISTRADOR trabaja sobre su
 * establecimiento; el ADMIN elige uno (?est=, recordado en la sesión). Las
 * acciones de la ronda responden JSON si las pide fetch (cabecera
 * X-Requested-With) y, si no, redirigen con un aviso: el formulario funciona
 * igual sin JavaScript.
 */
abstract class IaasControlador extends Controller
{
    protected array $usuario;
    protected ?array $establecimiento = null;
    protected ?array $configuracion = null;
    protected array $salas = [];

    public function __construct()
    {
        Auth::exigirRol('ADMIN', 'REGISTRADOR');
        $this->usuario = Auth::usuario();
        $id = $this->establecimientoElegido();
        if ($id) {
            $this->establecimiento = Establecimiento::buscar($id);
            $this->configuracion = IaasSala::configuracion($id);
            $this->salas = IaasSala::delEstablecimiento($id);
        }
    }

    private function establecimientoElegido(): ?int
    {
        if ($this->usuario['rol'] !== 'ADMIN') {
            return $this->usuario['establecimiento_id'];
        }
        if (isset($_GET['est']) && ctype_digit((string) $_GET['est'])) {
            $_SESSION['iaas_est'] = (int) $_GET['est'];
        }
        if (!empty($_SESSION['iaas_est'])) {
            return (int) $_SESSION['iaas_est'];
        }
        $configurados = IaasSala::establecimientosConfigurados();
        return $configurados ? (int) $configurados[0]['id'] : $this->usuario['establecimiento_id'];
    }

    protected function estId(): int
    {
        return (int) $this->establecimiento['id'];
    }

    protected function esAdmin(): bool
    {
        return $this->usuario['rol'] === 'ADMIN';
    }

    /** Datos comunes de todas las vistas del módulo (cabecera y selector del ADMIN). */
    protected function datosVista(string $titulo, string $ruta, array $datos = []): array
    {
        return [
            'tituloVista'      => $titulo,
            'rutaActual'       => $ruta,
            'establecimiento'  => $this->establecimiento,
            'configuracion'    => $this->configuracion,
            'salas'            => $this->salas,
            'esAdmin'          => $this->esAdmin(),
            'establecimientos' => $this->esAdmin() ? Database::conexion()->query('SELECT id, nombre, nombre_corto FROM establecimiento WHERE activo = 1 ORDER BY nombre')->fetchAll() : [],
        ] + $datos;
    }

    /** Sin establecimiento (ADMIN sin elegir o usuario sin IPRESS) no hay nada que mostrar. */
    protected function exigirEstablecimiento(): void
    {
        if ($this->establecimiento) {
            return;
        }
        $this->vista('iaas/sin-establecimiento', $this->datosVista('IAAS', 'iaas'));
        exit;
    }

    /** Las pantallas con pacientes piden haber respondido qué servicios hay. */
    protected function exigirConfiguracion(): void
    {
        $this->exigirEstablecimiento();
        if (!$this->configuracion) {
            header('Location: /iaas/servicios');
            exit;
        }
    }

    protected function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            $this->responder(false, 'La sesión del formulario expiró. Vuelve a cargar la página.', '/iaas/ronda');
        }
    }

    protected function esFetch(): bool
    {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch';
    }

    /** Responde y termina: JSON para fetch, aviso + redirección para un formulario normal. */
    protected function responder(bool $ok, string $mensaje, string $volver, array $extra = []): never
    {
        if ($this->esFetch()) {
            header('Content-Type: application/json; charset=utf-8');
            if (!$ok) {
                http_response_code(422);
            }
            echo json_encode(['ok' => $ok, 'mensaje' => $mensaje] + $extra, JSON_UNESCAPED_UNICODE);
            exit;
        }
        Flash::set($mensaje);
        header('Location: ' . $volver);
        exit;
    }

    /** Fecha ISO válida y no futura, o null. */
    protected function fechaPasada(mixed $valor): ?string
    {
        $fecha = fechaIsoValida(is_string($valor) ? trim($valor) : null);
        return $fecha !== null && $fecha <= date('Y-m-d') ? $fecha : null;
    }

    protected function texto(string $clave, int $max = 255): ?string
    {
        $valor = trim((string) ($_POST[$clave] ?? ''));
        return $valor === '' ? null : mb_substr($valor, 0, $max);
    }

    protected function entero(string $clave, int $min = 0, int $max = 65535): ?int
    {
        $valor = trim((string) ($_POST[$clave] ?? ''));
        if ($valor === '' || !ctype_digit($valor)) {
            return null;
        }
        $n = (int) $valor;
        return $n >= $min && $n <= $max ? $n : null;
    }

    public const TIPOS_DOC = ['DNI', 'CE', 'PTP', 'PAS', 'OTRO', 'SIN_DOCUMENTO'];

    /**
     * Paciente del formulario (tipo_doc, num_doc, apellidos, nombres, sexo,
     * fecha_nac, historia_clinica): el del padrón si el documento ya existe,
     * completando solo lo que le falta (persona compartida: no se pisa lo que
     * pidió otra ficha); si no, uno nuevo. "Sin documento" siempre crea uno
     * (neonatos sin DNI). Devuelve [persona_id|null, errores].
     *
     * @return array{0: ?int, 1: string[]}
     */
    protected function personaDelFormulario(): array
    {
        $errores = [];
        $tipoDoc = $this->opcion('tipo_doc', self::TIPOS_DOC) ?? 'DNI';
        $numDoc = $this->texto('num_doc', 20);
        if ($tipoDoc !== 'SIN_DOCUMENTO' && !$numDoc) {
            $errores[] = 'el número de documento del paciente (o elige «Sin documento»)';
        }
        $persona = ($tipoDoc !== 'SIN_DOCUMENTO' && $numDoc) ? Persona::buscarPorDocumento($tipoDoc, $numDoc) : null;
        $apPaterno = $this->texto('apellido_paterno', 60);
        $nombres = $this->texto('nombres', 80);
        if (!$persona && (!$apPaterno || !$nombres)) {
            $errores[] = 'el apellido paterno y los nombres del paciente';
        }
        if ($errores) {
            return [null, $errores];
        }
        $sexo = $this->opcion('sexo', ['M', 'F']);
        $fechaNac = $this->fechaPasada($_POST['fecha_nac'] ?? '');
        $hc = $this->texto('historia_clinica', 30);
        if ($persona) {
            $completar = array_filter([
                'sexo' => $persona['sexo'] ? null : $sexo,
                'fecha_nac' => $persona['fecha_nac'] ? null : $fechaNac,
                'n_historia_clinica' => $persona['n_historia_clinica'] ? null : $hc,
            ]);
            if ($completar) {
                Persona::actualizar((int) $persona['id'], $completar);
            }
            return [(int) $persona['id'], []];
        }
        return [Persona::crear([
            'tipo_doc' => $tipoDoc, 'num_doc' => $tipoDoc === 'SIN_DOCUMENTO' ? null : $numDoc,
            'apellido_paterno' => $apPaterno, 'apellido_materno' => $this->texto('apellido_materno', 60), 'nombres' => $nombres,
            'sexo' => $sexo, 'fecha_nac' => $fechaNac, 'n_historia_clinica' => $hc, 'condicion' => 'PARTICULAR',
        ]), []];
    }

    /** Valor de una lista cerrada, o null. */
    protected function opcion(string $clave, array $permitidas): ?string
    {
        $valor = (string) ($_POST[$clave] ?? '');
        return in_array($valor, array_map('strval', $permitidas), true) ? $valor : null;
    }
}
