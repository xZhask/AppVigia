<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\CsvLector;
use App\Core\Database;
use App\Core\Flash;
use App\Core\XlsxEscritor;
use App\Core\XlsxLector;
use App\Models\CampoDef;
use App\Models\Caso;
use App\Models\CasoBitacora;
use App\Models\CasoValor;
use App\Models\CatalogoItem;
use App\Models\Distrito;
use App\Models\Enfermedad;
use App\Models\Establecimiento;
use App\Models\GradoPnp;
use App\Models\LoteImportacion;
use App\Models\Paciente;
use App\Models\UnidadPnp;
use DateTime;
use RuntimeException;
use Throwable;

class ImportacionController extends Controller
{
    private const ROLES_REGISTRO = ['ADMIN', 'REGISTRADOR'];

    /** Encabezados fijos de la plantilla, en orden. */
    private const COLUMNAS_FIJAS = [
        'fecha_notif', 'tipo_doc', 'num_doc', 'apellidos_nombres', 'sexo', 'fecha_nac',
        'ubigeo', 'fecha_inicio_sintomas', 'es_pnp', 'grado', 'situacion_pnp', 'cip',
        'unidad', 'tipo_beneficiario',
    ];

    /** Columnas fijas que deben forzarse a texto en el .xlsx (documentos/códigos/fechas). */
    private const COLUMNAS_FIJAS_TEXTO = ['fecha_notif', 'num_doc', 'fecha_nac', 'ubigeo', 'fecha_inicio_sintomas', 'cip'];

    public function formulario(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);

        $enfermedades = Enfermedad::activas();
        if (empty($enfermedades)) {
            Flash::set('No hay enfermedades activas para importar. Actívalas desde Catálogos › Enfermedades.');
            header('Location: /');
            exit;
        }

        $enfermedadId = isset($_GET['enfermedad_id']) ? (int) $_GET['enfermedad_id'] : (int) $enfermedades[0]['id'];
        $enfermedad = Enfermedad::buscar($enfermedadId) ?: $enfermedades[0];

        $this->vista('importacion/formulario', array_merge([
            'tituloVista'  => 'Importar desde Excel',
            'rutaActual'   => 'casos',
            'enfermedades' => $enfermedades,
            'enfermedad'   => $enfermedad,
            'camposDinamicos' => $this->camposConCatalogo((int) $enfermedad['id']),
        ], $this->datosEstablecimiento()));
    }

    public function plantilla(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);

        $enfermedadId = (int) ($_GET['enfermedad_id'] ?? 0);
        $enfermedad = Enfermedad::buscar($enfermedadId);
        if (!$enfermedad) {
            Flash::set('Selecciona una enfermedad válida para descargar la plantilla.');
            header('Location: /casos/importar');
            exit;
        }

        $encabezados = self::COLUMNAS_FIJAS;
        $ejemplo = [
            '15/07/2026', 'DNI', '76540319', 'Pérez García, Juan', 'M', '01/01/1990',
            '150101', '12/07/2026', 'NO', '', '', '', '', '',
        ];
        $indicesTexto = array_keys(array_intersect(self::COLUMNAS_FIJAS, self::COLUMNAS_FIJAS_TEXTO));

        foreach ($this->camposConCatalogo((int) $enfermedad['id']) as $campo) {
            $encabezados[] = $campo['clave'];
            $ejemplo[] = $this->ejemploParaCampo($campo);
            if ($campo['tipo'] === 'FECHA') {
                $indicesTexto[] = count($encabezados) - 1;
            }
        }

        $bytes = XlsxEscritor::generar($encabezados, $ejemplo, $indicesTexto);
        $nombreArchivo = 'plantilla_' . $this->normalizarNombreArchivo($enfermedad['nombre']) . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . strlen($bytes));
        echo $bytes;
        exit;
    }

    public function procesar(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);
        $this->exigirCsrf();

        $usuario = Auth::usuario();
        $enfermedadId = (int) ($_POST['enfermedad_id'] ?? 0);
        $enfermedad = Enfermedad::buscar($enfermedadId);

        if (!$enfermedad || !$enfermedad['activo']) {
            Flash::set('Selecciona una enfermedad válida.');
            header('Location: /casos/importar');
            exit;
        }

        $puedeElegirEstablecimiento = $usuario['rol'] === 'ADMIN';
        $establecimientoId = $puedeElegirEstablecimiento
            ? (int) ($_POST['establecimiento_id'] ?? 0)
            : (int) ($usuario['establecimiento_id'] ?? 0);
        $establecimiento = $establecimientoId ? Establecimiento::buscar($establecimientoId) : null;

        if (!$establecimiento) {
            Flash::set('Selecciona un establecimiento válido para el lote.');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            Flash::set('Selecciona un archivo .xlsx o .csv para importar.');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        $rutaTemporal = $_FILES['archivo']['tmp_name'];

        try {
            $filasCrudas = $this->leerArchivo($rutaTemporal);
        } catch (Throwable $e) {
            Flash::set('No se pudo leer el archivo (' . $e->getMessage() . '). Descarga la plantilla nuevamente e inténtalo de nuevo.');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        if (count($filasCrudas) < 3) {
            Flash::set('El archivo no tiene filas de datos (recuerda que la fila 1 es de encabezados y la fila 2 es de ejemplo).');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        $indiceColumna = [];
        foreach ($filasCrudas[0] as $i => $celda) {
            $indiceColumna[trim(mb_strtolower($celda['valor']))] = $i;
        }

        $camposDef = CampoDef::porEnfermedad($enfermedadId);
        $filasDatos = array_slice($filasCrudas, 2);

        $filasValidas = [];
        $filasConError = [];
        $aceptadasEnLote = [];

        foreach ($filasDatos as $i => $fila) {
            $numeroFilaExcel = $i + 3;
            $obtener = function (string $columna) use ($fila, $indiceColumna): array {
                $idx = $indiceColumna[$columna] ?? null;
                return $idx !== null ? ($fila[$idx] ?? ['valor' => '', 'numerico' => false]) : ['valor' => '', 'numerico' => false];
            };

            // Fila totalmente vacía (frecuente al final de una hoja): se ignora sin contar como error.
            $todoVacio = true;
            foreach ($fila as $celda) {
                if (trim($celda['valor']) !== '') {
                    $todoVacio = false;
                    break;
                }
            }
            if ($todoVacio) {
                continue;
            }

            [$datos, $errores] = $this->validarFila($obtener, $enfermedadId, $camposDef, $aceptadasEnLote, $numeroFilaExcel);

            if (!empty($errores)) {
                $filasConError[] = ['fila' => $numeroFilaExcel, 'errores' => $errores];
                continue;
            }

            $filasValidas[] = $datos;
            $aceptadasEnLote[] = [
                'fila' => $numeroFilaExcel,
                'tipo_doc' => $datos['tipo_doc'],
                'num_doc' => $datos['num_doc'],
                'fecha_notif' => $datos['fecha_notif_iso'],
            ];
        }

        if (empty($filasValidas) && empty($filasConError)) {
            Flash::set('El archivo no tiene filas de datos para importar.');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        $codigosImportados = [];
        $pdo = Database::conexion();

        try {
            $pdo->beginTransaction();

            $loteId = LoteImportacion::crear([
                'enfermedad_id'      => $enfermedadId,
                'establecimiento_id' => $establecimientoId,
                'usuario_id'         => (int) $usuario['id'],
                'nombre_archivo'     => $_FILES['archivo']['name'],
                'total_filas'        => count($filasValidas) + count($filasConError),
                'filas_importadas'   => count($filasValidas),
                'filas_error'        => count($filasConError),
            ]);

            foreach ($filasValidas as $datos) {
                $pacienteExistente = Paciente::buscarPorDocumento($datos['tipo_doc'], $datos['num_doc']);
                $datosPaciente = $datos['paciente'];

                if ($pacienteExistente) {
                    $pacienteId = (int) $pacienteExistente['id'];
                    Paciente::actualizar($pacienteId, $datosPaciente);
                } else {
                    $pacienteId = Paciente::crear($datosPaciente);
                }

                $semana = semanaEpidemiologica($datos['fecha_notif_iso']);

                $casoId = Caso::crearConCodigo([
                    'enfermedad_id'         => $enfermedadId,
                    'paciente_id'           => $pacienteId,
                    'establecimiento_id'    => $establecimientoId,
                    'usuario_id'            => (int) $usuario['id'],
                    'fecha_notif'           => $datos['fecha_notif_iso'],
                    'anio_epi'              => $semana['anio'],
                    'semana_epi'            => $semana['semana'],
                    'fecha_inicio_sintomas' => $datos['fecha_inicio_sintomas_iso'],
                ]);

                CasoValor::guardarTodos($casoId, $datos['para_guardar']);
                CasoBitacora::registrar($casoId, (int) $usuario['id'], 'CREACION', "Importado del lote #$loteId.");

                $codigosImportados[] = ['codigo' => sprintf('F-%05d', $casoId), 'nombre' => $datos['paciente']['apellidos_nombres']];
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Error al importar lote: ' . $e->getMessage());
            Flash::set('No se pudo completar la importación por un error interno. No se guardó ninguna fila.');
            header('Location: /casos/importar?enfermedad_id=' . $enfermedadId);
            exit;
        }

        $this->vista('importacion/resultado', [
            'tituloVista'   => 'Resultado de la importación',
            'rutaActual'    => 'casos',
            'enfermedad'    => $enfermedad,
            'totalFilas'    => count($filasValidas) + count($filasConError),
            'filasImportadas' => $codigosImportados,
            'filasConError' => $filasConError,
        ]);
    }

    public function lotes(): void
    {
        Auth::exigirRol(...self::ROLES_REGISTRO);

        $this->vista('importacion/lotes', [
            'tituloVista' => 'Lotes importados',
            'rutaActual'  => 'casos',
            'lotes'       => LoteImportacion::todosConDetalle(),
        ]);
    }

    /**
     * Detecta el formato por contenido (firma de zip) y delega al lector
     * correspondiente; ambos devuelven la misma forma de filas.
     */
    private function leerArchivo(string $ruta): array
    {
        $firma = file_get_contents($ruta, false, null, 0, 4);

        if ($firma === "PK\x03\x04") {
            return XlsxLector::leer($ruta);
        }

        return CsvLector::leer($ruta);
    }

    /**
     * @return array{0: array, 1: string[]} [datos normalizados listos para guardar, errores]
     */
    private function validarFila(callable $obtener, int $enfermedadId, array $camposDef, array $aceptadasEnLote, int $numeroFilaExcel): array
    {
        $errores = [];

        $tipoDoc = trim($obtener('tipo_doc')['valor']) ?: 'DNI';
        if (!in_array($tipoDoc, ['DNI', 'CE', 'PTP', 'PAS', 'OTRO'], true)) {
            $errores[] = 'tipo_doc: debe ser DNI, CE, PTP, PAS u OTRO.';
            $tipoDoc = 'DNI';
        }

        $numDoc = trim($obtener('num_doc')['valor']);
        if ($numDoc === '') {
            $errores[] = 'num_doc: obligatorio.';
        } elseif ($tipoDoc === 'DNI' && !preg_match('/^\d{8}$/', $numDoc)) {
            $errores[] = 'num_doc: el DNI debe tener 8 dígitos (revisa que la columna esté en formato Texto en Excel; parece haber perdido un cero inicial).';
        }

        $apellidosNombres = trim($obtener('apellidos_nombres')['valor']);
        if ($apellidosNombres === '') {
            $errores[] = 'apellidos_nombres: obligatorio.';
        }

        $sexoCelda = trim(mb_strtoupper($obtener('sexo')['valor']));
        $sexo = null;
        if ($sexoCelda !== '') {
            if (!in_array($sexoCelda, ['M', 'F'], true)) {
                $errores[] = 'sexo: debe ser M o F.';
            } else {
                $sexo = $sexoCelda;
            }
        }

        $fechaNac = $this->interpretarFecha($obtener('fecha_nac'));
        if (!$fechaNac['valida']) {
            $errores[] = 'fecha_nac: formato de fecha inválido (usa dd/mm/aaaa).';
        }

        $ubigeoCelda = $obtener('ubigeo');
        $ubigeoTexto = trim($ubigeoCelda['valor']);
        $distrito = null;
        if ($ubigeoTexto === '') {
            $errores[] = 'ubigeo: obligatorio (código INEI de distrito, 6 dígitos).';
        } elseif ($ubigeoCelda['numerico'] && strlen($ubigeoTexto) < 6) {
            $errores[] = 'ubigeo: quedó con menos de 6 dígitos (revisa que la columna esté en formato Texto en Excel; parece haber perdido un cero inicial).';
        } else {
            $distrito = Distrito::buscarPorId($ubigeoTexto);
            if (!$distrito) {
                $errores[] = 'ubigeo: código INEI de distrito no encontrado.';
            }
        }

        $fechaNotif = $this->interpretarFecha($obtener('fecha_notif'));
        if ($fechaNotif['vacio']) {
            $errores[] = 'fecha_notif: obligatorio (dd/mm/aaaa).';
        } elseif (!$fechaNotif['valida']) {
            $errores[] = 'fecha_notif: formato de fecha inválido (usa dd/mm/aaaa).';
        } elseif ($fechaNotif['iso'] > (new DateTime())->format('Y-m-d')) {
            $errores[] = 'fecha_notif: no puede ser una fecha futura.';
        }

        $fechaInicioSintomas = $this->interpretarFecha($obtener('fecha_inicio_sintomas'));
        if ($fechaInicioSintomas['vacio']) {
            $errores[] = 'fecha_inicio_sintomas: obligatorio (dd/mm/aaaa).';
        } elseif (!$fechaInicioSintomas['valida']) {
            $errores[] = 'fecha_inicio_sintomas: formato de fecha inválido (usa dd/mm/aaaa).';
        }

        // ---------- efectivo PNP (opcional) ----------
        $esPnpCelda = trim(mb_strtoupper($obtener('es_pnp')['valor']));
        $esPnp = false;
        if ($esPnpCelda !== '') {
            if (!in_array($esPnpCelda, ['SI', 'SÍ', 'NO'], true)) {
                $errores[] = 'es_pnp: debe ser SI o NO.';
            } else {
                $esPnp = in_array($esPnpCelda, ['SI', 'SÍ'], true);
            }
        }

        $grado = null;
        $gradoCelda = trim($obtener('grado')['valor']);
        if ($esPnp && $gradoCelda !== '') {
            $grado = GradoPnp::buscarPorAbreviatura($gradoCelda);
            if (!$grado) {
                $errores[] = 'grado: abreviatura no reconocida.';
            }
        }

        $situacionPnp = null;
        $situacionCelda = trim(mb_strtoupper($obtener('situacion_pnp')['valor']));
        if ($esPnp && $situacionCelda !== '') {
            if (!in_array($situacionCelda, ['ACTIVIDAD', 'RETIRO', 'DISPONIBILIDAD'], true)) {
                $errores[] = 'situacion_pnp: debe ser ACTIVIDAD, RETIRO o DISPONIBILIDAD.';
            } else {
                $situacionPnp = $situacionCelda;
            }
        }

        $cip = $esPnp ? (trim($obtener('cip')['valor']) ?: null) : null;

        $unidad = null;
        $unidadCelda = trim($obtener('unidad')['valor']);
        if ($esPnp && $unidadCelda !== '') {
            $unidad = UnidadPnp::buscarPorNombre($unidadCelda);
            if (!$unidad) {
                $errores[] = 'unidad: no encontrada (debe coincidir exactamente con el nombre registrado).';
            }
        }

        $tipoBeneficiario = null;
        $beneficiarioCelda = trim(mb_strtoupper($obtener('tipo_beneficiario')['valor']));
        if ($esPnp && $beneficiarioCelda !== '') {
            if (!in_array($beneficiarioCelda, ['TITULAR', 'DERECHOHABIENTE'], true)) {
                $errores[] = 'tipo_beneficiario: debe ser TITULAR o DERECHOHABIENTE.';
            } else {
                $tipoBeneficiario = $beneficiarioCelda;
            }
        }

        // ---------- campos dinámicos de la enfermedad ----------
        $paraGuardar = [];
        foreach ($camposDef as $campoId => $campo) {
            $celda = $obtener($campo['clave']);
            $texto = trim($celda['valor']);
            $obligatorio = (bool) $campo['obligatorio'];

            switch ($campo['tipo']) {
                case 'MULTISELECT':
                    $seleccion = $texto === '' ? [] : array_map('trim', explode(',', $texto));
                    if ($campo['catalogo_id'] && !empty($seleccion)) {
                        $validos = array_column(CatalogoItem::porCatalogo((int) $campo['catalogo_id']), 'valor');
                        $invalidos = array_diff($seleccion, $validos);
                        if (!empty($invalidos)) {
                            $errores[] = "{$campo['clave']}: valor(es) de catálogo no reconocido(s) (" . implode(', ', $invalidos) . ').';
                        } else {
                            $paraGuardar[$campoId] = implode(',', $seleccion);
                        }
                    } elseif ($obligatorio && empty($seleccion)) {
                        $errores[] = "{$campo['clave']}: obligatorio.";
                    }
                    break;
                case 'BOOLEANO':
                    $normalizado = mb_strtoupper($texto);
                    if ($normalizado !== '' && !in_array($normalizado, ['SI', 'SÍ', 'NO'], true)) {
                        $errores[] = "{$campo['clave']}: debe ser SI o NO.";
                    } else {
                        $paraGuardar[$campoId] = in_array($normalizado, ['SI', 'SÍ'], true) ? '1' : '0';
                    }
                    break;
                case 'NUMERO':
                    if ($texto === '') {
                        if ($obligatorio) {
                            $errores[] = "{$campo['clave']}: obligatorio.";
                        }
                    } elseif (!is_numeric($texto)) {
                        $errores[] = "{$campo['clave']}: debe ser un número.";
                    } else {
                        $paraGuardar[$campoId] = $texto;
                    }
                    break;
                case 'FECHA':
                    $r = $this->interpretarFecha($celda);
                    if ($r['vacio']) {
                        if ($obligatorio) {
                            $errores[] = "{$campo['clave']}: obligatorio (dd/mm/aaaa).";
                        }
                    } elseif (!$r['valida']) {
                        $errores[] = "{$campo['clave']}: formato de fecha inválido (usa dd/mm/aaaa).";
                    } else {
                        $paraGuardar[$campoId] = $r['iso'];
                    }
                    break;
                case 'SELECT':
                    if ($texto === '') {
                        if ($obligatorio) {
                            $errores[] = "{$campo['clave']}: obligatorio.";
                        }
                    } else {
                        $validos = $campo['catalogo_id']
                            ? array_column(CatalogoItem::porCatalogo((int) $campo['catalogo_id']), 'valor')
                            : [];
                        if (!in_array($texto, $validos, true)) {
                            $errores[] = "{$campo['clave']}: valor de catálogo no reconocido ({$texto}).";
                        } else {
                            $paraGuardar[$campoId] = $texto;
                        }
                    }
                    break;
                default: // TEXTO, TEXTAREA
                    if ($texto === '') {
                        if ($obligatorio) {
                            $errores[] = "{$campo['clave']}: obligatorio.";
                        }
                    } else {
                        $paraGuardar[$campoId] = $texto;
                    }
            }
        }

        // ---------- duplicados (BD + dentro del mismo archivo) ----------
        if (empty($errores)) {
            $dupBd = Caso::buscarDuplicado($enfermedadId, $tipoDoc, $numDoc, $fechaNotif['iso']);
            if ($dupBd) {
                $errores[] = "posible duplicado de la ficha {$dupBd['codigo']} ya registrada (mismo documento y enfermedad, dentro de 30 días).";
            } else {
                $filaDuplicada = $this->buscarDuplicadoEnLote($aceptadasEnLote, $tipoDoc, $numDoc, $fechaNotif['iso']);
                if ($filaDuplicada !== null) {
                    $errores[] = "documento repetido en este mismo archivo (fila {$filaDuplicada}).";
                }
            }
        }

        if (!empty($errores)) {
            return [[], $errores];
        }

        $datos = [
            'tipo_doc'         => $tipoDoc,
            'num_doc'          => $numDoc,
            'fecha_notif_iso'  => $fechaNotif['iso'],
            'fecha_inicio_sintomas_iso' => $fechaInicioSintomas['iso'],
            'para_guardar'     => $paraGuardar,
            'paciente'         => [
                'tipo_doc'          => $tipoDoc,
                'num_doc'           => $numDoc,
                'apellidos_nombres' => $apellidosNombres,
                'sexo'              => $sexo,
                'fecha_nac'         => $fechaNac['iso'],
                'distrito_id'       => $distrito['id'],
                'es_pnp'            => $esPnp ? 1 : 0,
                'cip'               => $cip,
                'situacion_pnp'     => $situacionPnp,
                'grado_id'          => $grado['id'] ?? null,
                'unidad_id'         => $unidad['id'] ?? null,
                'tipo_beneficiario' => $tipoBeneficiario,
            ],
        ];

        return [$datos, []];
    }

    private function buscarDuplicadoEnLote(array $aceptadas, string $tipoDoc, string $numDoc, string $fechaIso): ?int
    {
        $fecha = new DateTime($fechaIso);
        foreach ($aceptadas as $fila) {
            if ($fila['tipo_doc'] !== $tipoDoc || $fila['num_doc'] !== $numDoc) {
                continue;
            }
            $otraFecha = new DateTime($fila['fecha_notif']);
            if (abs($fecha->diff($otraFecha)->days) <= 30) {
                return $fila['fila'];
            }
        }

        return null;
    }

    /**
     * @return array{iso: ?string, vacio: bool, valida: bool}
     */
    private function interpretarFecha(array $celda): array
    {
        $texto = trim($celda['valor']);
        if ($texto === '') {
            return ['iso' => null, 'vacio' => true, 'valida' => true];
        }

        $iso = $celda['numerico'] ? XlsxLector::serialAFecha($texto) : fechaDmyAIso($texto);

        return ['iso' => $iso, 'vacio' => false, 'valida' => $iso !== null];
    }

    private function camposConCatalogo(int $enfermedadId): array
    {
        return array_values(CampoDef::porEnfermedad($enfermedadId));
    }

    private function ejemploParaCampo(array $campo): string
    {
        switch ($campo['tipo']) {
            case 'BOOLEANO':
                return 'NO';
            case 'FECHA':
            case 'NUMERO':
            case 'TEXTO':
            case 'TEXTAREA':
                return '';
            case 'SELECT':
                $opciones = $campo['catalogo_id'] ? CatalogoItem::porCatalogo((int) $campo['catalogo_id']) : [];
                return $opciones[0]['valor'] ?? '';
            case 'MULTISELECT':
                $opciones = $campo['catalogo_id'] ? CatalogoItem::porCatalogo((int) $campo['catalogo_id']) : [];
                return implode(',', array_column(array_slice($opciones, 0, 2), 'valor'));
            default:
                return '';
        }
    }

    private function normalizarNombreArchivo(string $texto): string
    {
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ñ' => 'N']);
        $texto = preg_replace('/[^a-zA-Z0-9]+/', '-', $texto);

        return mb_strtolower(trim($texto, '-'));
    }

    private function datosEstablecimiento(): array
    {
        $usuario = Auth::usuario();
        $puedeElegir = $usuario['rol'] === 'ADMIN';

        $establecimientoUsuarioNombre = '';
        if (!$puedeElegir) {
            $est = $usuario['establecimiento_id'] ? Establecimiento::buscar((int) $usuario['establecimiento_id']) : null;
            $establecimientoUsuarioNombre = $est['nombre'] ?? 'Sin establecimiento asignado';
        }

        return [
            'puedeElegirEstablecimiento'   => $puedeElegir,
            'establecimientos'             => $puedeElegir ? Establecimiento::todos('nombre') : [],
            'establecimientoUsuarioNombre' => $establecimientoUsuarioNombre,
        ];
    }

    private function exigirCsrf(): void
    {
        if (!Csrf::valido($_POST['csrf_token'] ?? null)) {
            Flash::set('La sesión del formulario expiró. Vuelve a intentarlo.');
            header('Location: /casos/importar');
            exit;
        }
    }
}
