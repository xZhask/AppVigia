<?php
namespace App\Services;

use App\Models\Persona;
use App\Core\ReniecService;
use App\Core\Auth;
use App\Models\ReniecConsulta;

class PersonaService
{
    /**
     * Busca una persona por documento. Si no existe y es DNI, consulta a RENIEC
     * y, si hay éxito, crea el registro en la base de datos y lo devuelve.
     */
    public static function buscarOCrear(string $tipoDoc, string $numDoc): ?array
    {
        $persona = Persona::buscarPorDocumento($tipoDoc, $numDoc);
        
        if ($persona) {
            return $persona;
        }

        if ($tipoDoc === 'DNI' && preg_match('/^\d{8}$/', $numDoc)) {
            $datosReniec = ReniecService::buscarPorDni($numDoc);

            $usuario = Auth::usuario();
            $usuarioId = $usuario ? (int) $usuario['id'] : 0;
            if ($usuarioId > 0) {
                ReniecConsulta::registrar($usuarioId, $numDoc, $datosReniec !== null);
            }

            if ($datosReniec) {
                $id = Persona::crear([
                    'tipo_doc'         => 'DNI',
                    'num_doc'          => $numDoc,
                    'apellido_paterno' => $datosReniec['apellido_paterno'],
                    'apellido_materno' => $datosReniec['apellido_materno'],
                    'nombres'          => $datosReniec['nombres'],
                    'es_pnp'           => 0
                ]);
                return Persona::buscar($id);
            }
        }

        return null;
    }

    /**
     * Crea una persona con datos digitados a mano, para cuando el documento
     * no aparece en RENIEC ni en el padrón local (paso 3 de la búsqueda
     * unificada). Vuelve a verificar el documento antes de crear, por si
     * apareció entre la búsqueda inicial y el envío de este formulario.
     */
    public static function crearManual(string $tipoDoc, string $numDoc, array $datos): array
    {
        $existente = Persona::buscarPorDocumento($tipoDoc, $numDoc);
        if ($existente) {
            return $existente;
        }

        $id = Persona::crear([
            'tipo_doc'         => $tipoDoc,
            'num_doc'          => $numDoc,
            'apellido_paterno' => $datos['apellido_paterno'],
            'apellido_materno' => $datos['apellido_materno'] !== '' ? $datos['apellido_materno'] : null,
            'nombres'          => $datos['nombres'],
            'es_pnp'           => 0,
        ]);

        return Persona::buscar($id);
    }
}
