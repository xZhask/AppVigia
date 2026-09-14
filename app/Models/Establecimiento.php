<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Establecimiento extends Model
{
    protected static string $tabla = 'establecimiento';

    public static function conRedYUbicacion(): array
    {
        $sql = 'SELECT es.*, r.nombre AS red_nombre, d.nombre AS distrito_nombre
                  FROM establecimiento es
             LEFT JOIN red_salud r ON r.id = es.red_id
             LEFT JOIN distrito d  ON d.id = es.distrito_id
              ORDER BY es.nombre';

        return Database::conexion()->query($sql)->fetchAll();
    }

    /**
     * Encabezado de un formulario del PDF para un establecimiento: DISA/DIRESA,
     * red, institución y ubicación (departamento, provincia, distrito). Todo
     * vacío sin establecimiento: el formulario abarca varios. Lo usan el
     * registro de búsqueda activa de Z21 y el listado de muerte fetal y
     * neonatal (P96).
     *
     * @return array{diresa: string, red: string, institucion: string, establecimiento: string, departamento: string, provincia: string, distrito: string}
     */
    public static function encabezadoFormulario(?int $establecimientoId): array
    {
        $vacio = ['diresa' => '', 'red' => '', 'institucion' => '', 'establecimiento' => '', 'departamento' => '', 'provincia' => '', 'distrito' => ''];
        if (!$establecimientoId) {
            return $vacio;
        }
        $consulta = Database::conexion()->prepare(
            'SELECT es.nombre AS establecimiento, es.institucion, r.nombre AS red, r.diresa,
                    d.nombre AS distrito, pr.nombre AS provincia, dep.nombre AS departamento
               FROM establecimiento es
          LEFT JOIN red_salud r      ON r.id = es.red_id
          LEFT JOIN distrito d       ON d.id = es.distrito_id
          LEFT JOIN provincia pr     ON pr.id = d.provincia_id
          LEFT JOIN departamento dep ON dep.id = d.departamento_id
              WHERE es.id = :id'
        );
        $consulta->execute(['id' => $establecimientoId]);

        return ($consulta->fetch() ?: []) + $vacio;
    }
}
