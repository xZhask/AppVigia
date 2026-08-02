<?php
/**
 * Cargador de campos adicionales de B05 (Filiación y Tutor).
 */
use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$b05 = [
    'puebloEtnico'  => ['name' => ''],
    'ocupacion'     => ['name' => ''],
    'lugarParto'    => ['name' => ''],
    'tipoLocalidad' => ['name' => ''],
    'esMenorEdad'   => ['name' => ''],
    'nombreTutor'   => ['name' => ''],
    'telefonoTutor' => ['name' => ''],
    'docTutor'      => ['name' => ''],
];

$enfB05Obj = Enfermedad::buscarPorCie10('B05');
if ($enfB05Obj) {
    $seccionesB05 = SeccionDef::porEnfermedad((int) $enfB05Obj['id']);
    $secFiliacion = null;
    foreach ($seccionesB05 as $s) {
        if (trim($s['nombre']) === 'Datos de filiación y tutor') {
            $secFiliacion = $s;
            break;
        }
    }
    if ($secFiliacion) {
        $camposFil = CampoDef::porSeccion((int) $secFiliacion['id']);
        $campoMapFil = [];
        foreach ($camposFil as $c) {
            $campoMapFil[trim($c['etiqueta'])] = $c;
        }
        $getCampoFilInput = function(string $etiqueta) use ($campoMapFil, $valoresCampos, $erroresCampos) {
            $c = $campoMapFil[$etiqueta] ?? null;
            if (!$c) return ['id' => null, 'name' => '', 'val' => '', 'err' => null, 'opciones' => []];
            $val = $valoresCampos[$c['id']] ?? '';
            $err = $erroresCampos[$c['id']] ?? null;
            $opciones = [];
            if ($c['catalogo_id']) {
                $opciones = CatalogoItem::porCatalogo((int) $c['catalogo_id']);
            }
            return [
                'id' => $c['id'],
                'name' => 'campo_' . $c['id'],
                'val' => $val,
                'err' => $err,
                'opciones' => $opciones
            ];
        };

        $b05['puebloEtnico']  = $getCampoFilInput('Pueblo étnico o etnia');
        $b05['ocupacion']     = $getCampoFilInput('Ocupación');
        $b05['lugarParto']    = $getCampoFilInput('Lugar probable de parto');
        $b05['tipoLocalidad'] = $getCampoFilInput('Tipo de localidad');
        $b05['esMenorEdad']   = $getCampoFilInput('¿Es menor de edad?');
        $b05['nombreTutor']   = $getCampoFilInput('Nombre de madre o tutor');
        $b05['telefonoTutor'] = $getCampoFilInput('Teléfono de madre o tutor');
        $b05['docTutor']      = $getCampoFilInput('N.° Doc. Identidad de madre o tutor');
    }
}
