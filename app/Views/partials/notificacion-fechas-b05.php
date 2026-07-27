<?php
/**
 * Campos específicos de notificación para Sarampión / Rubéola / Febriles eruptivas (B05).
 * Se muestran en la tarjeta superior 1. Notificación en lugar de la sección clínica inferior.
 */
use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enfB05Obj = Enfermedad::buscarPorCie10('B05');
$seccionesB05 = $enfB05Obj ? SeccionDef::porEnfermedad((int) $enfB05Obj['id']) : [];
$secNotif = null;
foreach ($seccionesB05 as $s) {
    if (trim($s['nombre']) === 'Datos de notificación e identificación del caso') {
        $secNotif = $s;
        break;
    }
}

$camposNotif = $secNotif ? CampoDef::porSeccion((int) $secNotif['id']) : [];
$campoMap = [];
foreach ($camposNotif as $c) {
    $campoMap[trim($c['etiqueta'])] = $c;
}

$getCampoInput = function(string $etiqueta) use ($campoMap, $valoresCampos, $erroresCampos) {
    $c = $campoMap[$etiqueta] ?? null;
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

$enfermedadNotif = $getCampoInput('Enfermedad notificada');
$codigoReg       = $getCampoInput('Código de registro');
$fechaIdentif    = $getCampoInput('Fecha de identificación local del caso (o consulta)');
$fechaInvest     = $getCampoInput('Fecha de investigación (visita domiciliaria)');
$personalSalud   = $getCampoInput('Nombre de personal de salud que atiende el caso');
$telefonoPers    = $getCampoInput('Teléfono del personal de salud');
?>

<div id="notificacionFechasB05Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B05' ? '' : 'hidden' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($enfermedadNotif['name']): ?>
    <div class="field">
      <label class="fl">Enfermedad notificada</label>
      <div class="control">
        <select name="<?= $enfermedadNotif['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($enfermedadNotif['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($enfermedadNotif['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($enfermedadNotif['err']): ?><span class="hint err"><?= e($enfermedadNotif['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($codigoReg['name']): ?>
    <div class="field">
      <label class="fl">Código de registro</label>
      <div class="control">
        <input type="text" name="<?= $codigoReg['name'] ?>" value="<?= e($codigoReg['val']) ?>" placeholder="Código de registro…">
      </div>
      <?php if ($codigoReg['err']): ?><span class="hint err"><?= e($codigoReg['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaIdentif['name']): ?>
    <div class="field">
      <label class="fl">Fecha de identificación local del caso (o consulta)</label>
      <div class="control mono <?= $fechaIdentif['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaIdentif['name'] ?>" value="<?= e($fechaIdentif['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaIdentif['err']): ?><span class="hint err"><?= e($fechaIdentif['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaInvest['name']): ?>
    <div class="field">
      <label class="fl">Fecha de investigación (visita domiciliaria)</label>
      <div class="control mono <?= $fechaInvest['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaInvest['name'] ?>" value="<?= e($fechaInvest['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaInvest['err']): ?><span class="hint err"><?= e($fechaInvest['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($personalSalud['name']): ?>
    <div class="field">
      <label class="fl">Nombre de personal de salud que atiende el caso</label>
      <div class="control">
        <input type="text" name="<?= $personalSalud['name'] ?>" value="<?= e($personalSalud['val']) ?>" placeholder="Nombre completo del personal…">
      </div>
      <?php if ($personalSalud['err']): ?><span class="hint err"><?= e($personalSalud['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($telefonoPers['name']): ?>
    <div class="field">
      <label class="fl">Teléfono del personal de salud</label>
      <div class="control">
        <input type="text" name="<?= $telefonoPers['name'] ?>" value="<?= e($telefonoPers['val']) ?>" placeholder="Teléfono de contacto…">
      </div>
      <?php if ($telefonoPers['err']): ?><span class="hint err"><?= e($telefonoPers['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
