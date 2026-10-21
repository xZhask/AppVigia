<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

/**
 * Listado de enfermedades sujetas a vigilancia del Área de Epidemiología PNP
 * (hoja "LISTADO DE ENF SUJ A VIG", tabla cie10_vigilancia, 2026-10-21; ver
 * sql/migraciones/add_registro_pnp.php). Cada código dice qué ficha MINSA lo
 * cubre; los que no tienen ficha se registran en el registro ligero
 * "Otras enfermedades" (REG-PNP).
 */
class Cie10Vigilancia extends Model
{
    protected static string $tabla = 'cie10_vigilancia';

    /** CIE-10 de la enfermedad del registro ligero de otras enfermedades. */
    public const REGISTRO_OTRAS = 'REG-PNP';

    /** CIE-10 de la enfermedad del registro diario de febriles. */
    public const REGISTRO_FEBRILES = 'REG-FEB';

    /** Códigos sin ficha MINSA, en el orden de la hoja (ventana "Cambiar enfermedad"). */
    public static function sinFicha(): array
    {
        return Database::conexion()->query(
            'SELECT codigo, prefijos, descripcion, notificacion, par_cie10, indicacion FROM cie10_vigilancia WHERE enfermedad_cie10 IS NULL ORDER BY orden'
        )->fetchAll();
    }

    /**
     * Ficha MINSA activa que cubre un código CIE-10 ("A97.1", "b059"), o
     * null si no hay ninguna. Gana el prefijo más largo, de la hoja o del
     * CIE-10 de la propia enfermedad: B26.9 (sin complicaciones) es un
     * registro aunque B26 tenga ficha, y A97.9 cae en la ficha A97 aunque la
     * hoja no lo liste.
     */
    public static function fichaQueCubre(string $codigo): ?array
    {
        $clave = Cie10::clave($codigo);
        if ($clave === '') {
            return null;
        }
        $fichas = [];
        foreach (Enfermedad::activas() as $enfermedad) {
            if (($enfermedad['modalidad'] ?? 'FICHA') === 'FICHA' && !empty($enfermedad['cie10'])) {
                $fichas[$enfermedad['cie10']] = $enfermedad;
            }
        }

        $candidatos = [];
        foreach ($fichas as $cie10 => $enfermedad) {
            $candidatos[] = [Cie10::clave($cie10), $cie10];
        }
        foreach (Database::conexion()->query('SELECT prefijos, enfermedad_cie10 FROM cie10_vigilancia')->fetchAll() as $fila) {
            foreach (explode(',', $fila['prefijos']) as $prefijo) {
                $candidatos[] = [trim($prefijo), $fila['enfermedad_cie10']];
            }
        }

        $mejor = null;
        foreach ($candidatos as [$prefijo, $cie10]) {
            if ($prefijo !== '' && str_starts_with($clave, $prefijo) && ($mejor === null || strlen($prefijo) > strlen($mejor[0]))) {
                $mejor = [$prefijo, $cie10];
            }
        }

        return $mejor && $mejor[1] !== null ? ($fichas[$mejor[1]] ?? null) : null;
    }

    /**
     * Ficha MINSA con la que se puede confundir un código del registro
     * (cie10_vigilancia.par_cie10, 2026-10-21: diarrea aguda y "EDA grave /
     * cólera", varicela sin y con complicaciones...), con su criterio de uso
     * (enfermedad.criterio). null si el código no tiene par.
     */
    public static function parDe(string $codigo): ?array
    {
        $clave = Cie10::clave($codigo);
        $mejor = null;
        foreach (Database::conexion()->query('SELECT prefijos, par_cie10 FROM cie10_vigilancia WHERE enfermedad_cie10 IS NULL')->fetchAll() as $fila) {
            foreach (explode(',', $fila['prefijos']) as $prefijo) {
                $prefijo = trim($prefijo);
                if ($prefijo !== '' && str_starts_with($clave, $prefijo) && ($mejor === null || strlen($prefijo) > strlen($mejor[0]))) {
                    $mejor = [$prefijo, $fila['par_cie10']];
                }
            }
        }
        $ficha = !empty($mejor[1]) ? Enfermedad::buscarPorCie10($mejor[1]) : null;

        return $ficha && $ficha['activo'] && !empty($ficha['criterio']) ? $ficha : null;
    }

    /**
     * Nombre de un diagnóstico para mostrarlo como título de su registro
     * (2026-10-21): el del listado PNP que lo contiene (A27.0 ->
     * "Leptospirosis"), o la descripción del catálogo CIE-10, o null.
     */
    public static function nombreDiagnostico(string $codigo): ?string
    {
        static $cache = [];
        if ($codigo === '') {
            return null;
        }
        if (!array_key_exists($codigo, $cache)) {
            $clave = Cie10::clave($codigo);
            $mejor = null;
            foreach (Database::conexion()->query('SELECT prefijos, descripcion FROM cie10_vigilancia')->fetchAll() as $fila) {
                foreach (explode(',', $fila['prefijos']) as $prefijo) {
                    $prefijo = trim($prefijo);
                    if ($prefijo !== '' && str_starts_with($clave, $prefijo) && ($mejor === null || strlen($prefijo) > strlen($mejor[0]))) {
                        $mejor = [$prefijo, $fila['descripcion']];
                    }
                }
            }
            $cache[$codigo] = $mejor[1] ?? descripcionCie10($codigo);
        }

        return $cache[$codigo];
    }

    /**
     * Grupo del consolidado semanal en que cuenta un código (EDA, COLERA,
     * DISENTERIA, IRA, NEUMONIA, SOBA, ASMA; cie10_vigilancia.consolidado), o
     * null.
     */
    public static function grupoConsolidado(string $codigo): ?string
    {
        static $filas = null;
        $filas ??= Database::conexion()->query('SELECT prefijos, consolidado FROM cie10_vigilancia WHERE consolidado IS NOT NULL')->fetchAll();
        $clave = Cie10::clave($codigo);
        $mejor = null;
        foreach ($filas as $fila) {
            foreach (explode(',', $fila['prefijos']) as $prefijo) {
                $prefijo = trim($prefijo);
                if ($prefijo !== '' && str_starts_with($clave, $prefijo) && ($mejor === null || strlen($prefijo) > strlen($mejor[0]))) {
                    $mejor = [$prefijo, $fila['consolidado']];
                }
            }
        }

        return $mejor[1] ?? null;
    }

    /** Tipo de notificación del listado PNP para un código (INMEDIATA/SEMANAL/COLECTIVA), o null. */
    public static function notificacionDe(string $codigo): ?string
    {
        $clave = Cie10::clave($codigo);
        $mejor = null;
        foreach (Database::conexion()->query('SELECT prefijos, notificacion FROM cie10_vigilancia')->fetchAll() as $fila) {
            foreach (explode(',', $fila['prefijos']) as $prefijo) {
                $prefijo = trim($prefijo);
                if ($prefijo !== '' && str_starts_with($clave, $prefijo) && ($mejor === null || strlen($prefijo) > strlen($mejor[0]))) {
                    $mejor = [$prefijo, $fila['notificacion']];
                }
            }
        }

        return $mejor[1] ?? null;
    }
}
