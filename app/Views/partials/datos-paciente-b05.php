<?php
/**
 * Campos adicionales de filiación y tutor para Sarampión / Rubéola (B05).
 * Se muestran dentro de la tarjeta 2. Datos de la persona (Datos Personales)
 * agregando únicamente los campos propios de B05 en la posición correcta.
 */
use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enfB05Obj = Enfermedad::buscarPorCie10('B05');
$seccionesB05 = $enfB05Obj ? SeccionDef::porEnfermedad((int) $enfB05Obj['id']) : [];
$secFiliacion = null;
foreach ($seccionesB05 as $s) {
    if (trim($s['nombre']) === 'Datos de filiación y tutor') {
        $secFiliacion = $s;
        break;
    }
}

$camposFil = $secFiliacion ? CampoDef::porSeccion((int) $secFiliacion['id']) : [];
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

$puebloEtnico    = $getCampoFilInput('Pueblo étnico o etnia');
$ocupacion       = $getCampoFilInput('Ocupación');
$lugarParto      = $getCampoFilInput('Lugar probable de parto');
$tipoLocalidad   = $getCampoFilInput('Tipo de localidad');
$referenciaLoc   = $getCampoFilInput('Referencia para localizar (cerca de iglesia, fundo, comercio, etc.)');
$esMenorEdad     = $getCampoFilInput('¿Es menor de edad?');
$nombreTutor     = $getCampoFilInput('Nombre de madre o tutor');
$telefonoTutor   = $getCampoFilInput('Teléfono de madre o tutor');
$docTutor        = $getCampoFilInput('N.° Doc. Identidad de madre o tutor');
?>

<div id="datosPacienteB05Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B05' ? '' : 'hidden' ?> style="margin-top:14px">
  <!-- 1. Debajo de Domicilio actual: Referencia para localizar y Tipo de localidad -->
  <div class="fields" style="display:flex;gap:16px;align-items:flex-start">
    <?php if ($referenciaLoc['name']): ?>
    <div class="field" style="flex:2">
      <label class="fl">Referencia para localizar <span class="hint">(a la altura de o cerca de: Iglesia, fundo, comercio, etc.)</span></label>
      <div class="control">
        <input type="text" name="<?= $referenciaLoc['name'] ?>" value="<?= e($referenciaLoc['val']) ?>" placeholder="Referencia para localizar…">
      </div>
    </div>
    <?php endif; ?>

    <?php if ($tipoLocalidad['name']): ?>
    <div class="field" style="flex:1">
      <label class="fl">Tipo de localidad</label>
      <div class="control">
        <select name="<?= $tipoLocalidad['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($tipoLocalidad['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($tipoLocalidad['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- 2. Pueblo étnico o etnia + Ocupación (se alinean al lado de Etnia / raza de datos-paciente-nucleo) -->
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($puebloEtnico['name']): ?>
    <div class="field">
      <label class="fl">Pueblo étnico o etnia</label>
      <div class="control">
        <input type="text" name="<?= $puebloEtnico['name'] ?>" value="<?= e($puebloEtnico['val']) ?>" placeholder="Pueblo étnico / etnia…">
      </div>
    </div>
    <?php endif; ?>

    <?php if ($ocupacion['name']): ?>
    <div class="field">
      <label class="fl">Ocupación</label>
      <div class="control">
        <input type="text" name="<?= $ocupacion['name'] ?>" value="<?= e($ocupacion['val']) ?>" placeholder="Ocupación…">
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- 3. Debajo de Gestante / Semanas de gestación: Lugar probable de parto (solo para gestantes) -->
  <?php if ($lugarParto['name']): ?>
  <div class="field wide" id="wrapLugarPartoB05" style="margin-top:12px" <?= ($valoresFijos['gestante'] ?? '') === '1' ? '' : 'hidden' ?>>
    <label class="fl" style="color:var(--accent-deep, #0284c7)">Lugar probable de parto <span class="hint">(solo para gestantes)</span></label>
    <div class="control">
      <input type="text" name="<?= $lugarParto['name'] ?>" value="<?= e($lugarParto['val']) ?>" placeholder="Lugar probable de parto…">
    </div>
  </div>
  <?php endif; ?>

  <!-- 4. ¿Es menor de edad? + Datos del tutor -->
  <?php if ($esMenorEdad['name']): ?>
  <div style="margin-top:16px;padding-top:14px;border-top:1px dashed var(--line)">
    <label class="sym" style="font-weight:600;color:var(--ink-1)">
      <input type="checkbox" id="chkEsMenorEdadB05" name="<?= $esMenorEdad['name'] ?>" value="1" <?= marcado($esMenorEdad['val'] === '1') ?>>
      ¿Es menor de edad? (Indicar datos de la madre o tutor)
    </label>

    <div id="wrapTutorB05" class="fields thirds" style="margin-top:12px" <?= $esMenorEdad['val'] === '1' ? '' : 'hidden' ?>>
      <?php if ($nombreTutor['name']): ?>
      <div class="field">
        <label class="fl">Nombre de madre o tutor</label>
        <div class="control">
          <input type="text" name="<?= $nombreTutor['name'] ?>" value="<?= e($nombreTutor['val']) ?>" placeholder="Nombre completo…">
        </div>
      </div>
      <?php endif; ?>

      <?php if ($telefonoTutor['name']): ?>
      <div class="field">
        <label class="fl">Teléfono de madre o tutor</label>
        <div class="control mono">
          <input type="text" name="<?= $telefonoTutor['name'] ?>" value="<?= e($telefonoTutor['val']) ?>" placeholder="Teléfono de contacto…">
        </div>
      </div>
      <?php endif; ?>

      <?php if ($docTutor['name']): ?>
      <div class="field">
        <label class="fl">N.° Doc. Identidad de madre o tutor</label>
        <div class="control mono">
          <input type="text" name="<?= $docTutor['name'] ?>" value="<?= e($docTutor['val']) ?>" placeholder="Documento de identidad…">
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>
