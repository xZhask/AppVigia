<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Persona extends Model
{
    protected static string $tabla = 'persona';

    public static function buscarPorDocumento(string $tipoDoc, string $numDoc): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT * FROM persona WHERE tipo_doc = :tipo AND num_doc = :num'
        );
        $consulta->execute(['tipo' => $tipoDoc, 'num' => $numDoc]);
        $fila = $consulta->fetch();

        return $fila ?: null;
    }

    /**
     * Arma el nombre para mostrar en listados, reportes y exportaciones:
     * "apellido_paterno apellido_materno, nombres". Único punto donde se
     * concatena, para no repetirlo en cada vista. Acepta cualquier arreglo
     * que traiga esas tres claves (fila de `paciente` o de un JOIN con `caso`).
     */
    public static function nombreCompleto(array $datos): string
    {
        $apellidos = trim(trim($datos['apellido_paterno'] ?? '') . ' ' . trim($datos['apellido_materno'] ?? ''));
        $nombres = trim($datos['nombres'] ?? '');

        if ($apellidos === '') {
            return $nombres;
        }
        if ($nombres === '') {
            return $apellidos;
        }

        return $apellidos . ', ' . $nombres;
    }

    /**
     * Arma el nombre para personal PNP: "Abrev. Apellidos, Nombres"
     */
    public static function nombreCompletoPnp(array $datos): string
    {
        $nombre = self::nombreCompleto($datos);
        $abreviatura = trim($datos['grado_abreviatura'] ?? '');
        return $abreviatura !== '' ? $abreviatura . ' ' . $nombre : $nombre;
    }

    private const VINCULOS_TITULAR = [
        'CONYUGE' => 'cónyuge', 'CONVIVIENTE' => 'conviviente', 'HIJO' => 'hijo(a)',
        'PADRE' => 'padre', 'MADRE' => 'madre', 'OTRO' => 'familiar',
    ];

    /**
     * Línea secundaria bajo el nombre según la condición del paciente:
     * - EFECTIVO: "Capitán de servicios · Situación: Actividad · CIP 12345678"
     * - DERECHOHABIENTE: "Derechohabiente (hijo) de Cap. Pérez Sosa, Luis"
     * - PARTICULAR: cadena vacía (sin línea institucional)
     *
     * Para DERECHOHABIENTE, $datos debe traer las claves `titular_*` (de un
     * JOIN con la persona titular), si se conocen.
     */
    public static function descripcionPnp(array $datos): string
    {
        $condicion = $datos['condicion'] ?? 'PARTICULAR';

        if ($condicion === 'EFECTIVO') {
            $grado = trim($datos['grado_nombre'] ?? '');
            $categoria = trim($datos['categoria_pnp'] ?? '');

            $partes = [];

            if ($grado !== '') {
                $gradoTexto = $grado;
                if ($categoria !== '') {
                    $gradoTexto .= ' de ' . strtolower($categoria);
                }
                $partes[] = $gradoTexto;
            }

            if (!empty($datos['situacion_pnp'])) {
                $partes[] = 'Situación: ' . ucfirst(strtolower($datos['situacion_pnp']));
            }

            if (!empty($datos['cip'])) {
                $partes[] = 'CIP ' . $datos['cip'];
            }

            return implode(' · ', $partes);
        }

        if ($condicion === 'DERECHOHABIENTE') {
            if (empty($datos['titular_apellido_paterno']) && empty($datos['titular_nombres'])) {
                $vinculo = self::VINCULOS_TITULAR[$datos['vinculo_titular'] ?? ''] ?? null;
                return $vinculo ? 'Derechohabiente (' . $vinculo . ')' : 'Derechohabiente';
            }

            $nombreTitular = self::nombreCompletoPnp([
                'apellido_paterno' => $datos['titular_apellido_paterno'] ?? '',
                'apellido_materno' => $datos['titular_apellido_materno'] ?? '',
                'nombres'          => $datos['titular_nombres'] ?? '',
                'grado_abreviatura'=> $datos['titular_grado_abreviatura'] ?? '',
            ]);
            $vinculo = self::VINCULOS_TITULAR[$datos['vinculo_titular'] ?? ''] ?? null;

            return 'Derechohabiente' . ($vinculo ? ' (' . $vinculo . ')' : '') . ' de ' . $nombreTitular;
        }

        return '';
    }
}
