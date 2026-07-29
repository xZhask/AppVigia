<?php
/**
 * Campos específicos de notificación e investigación para Síndrome de Rubéola Congénita (P35.0).
 * Se muestran en la tarjeta superior 1. Notificación.
 */
use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enfP35Obj = Enfermedad::buscarPorCie10('P35.0');
$seccionesP35 = $enfP35Obj ? SeccionDef::porEnfermedad((int) $enfP35Obj['id']) : [];
$secNotifP35 = null;
foreach ($seccionesP35 as $s) {
    if (trim($s['nombre']) === 'Datos de notificación e investigación del caso') {
        $secNotifP35 = $s;
        break;
    }
}

$camposNotifP35 = $secNotifP35 ? CampoDef::porSeccion((int) $secNotifP35['id']) : [];
$campoMapP35 = [];
foreach ($camposNotifP35 as $c) {
    $campoMapP35[$c['clave']] = $c;
}

// No usa $campo() de campos-por-clave.php: mismo motivo que
// notificacion-fechas-b26.php (ver ese archivo) -- este partial se incluye
// sin condicion aunque P35.0 no sea la ficha activa, así que resuelve
// siempre contra la P35.0 real, no contra $enfermedad.
$getCampoInputP35 = function(string $clave) use ($campoMapP35, $valoresCampos, $erroresCampos) {
    $c = $campoMapP35[$clave] ?? null;
    if (!$c) return ['id' => null, 'name' => '', 'val' => '', 'err' => null];
    $val = $valoresCampos[$c['id']] ?? '';
    $err = $erroresCampos[$c['id']] ?? null;
    return [
        'id' => $c['id'],
        'name' => 'campo_' . $c['id'],
        'val' => $val,
        'err' => $err
    ];
};

$codigoReg       = $getCampoInputP35('p35_0_codigo_de_registro_n');
$fechaConoc      = $getCampoInputP35('p35_0_fecha_de_conocimiento_local_del_caso');
$fechaNotifEess  = $getCampoInputP35('p35_0_fecha_de_notificacion_eess_a_red_microred');
$fechaNotifRed   = $getCampoInputP35('p35_0_fecha_notif_red_microred_a_direccion_salud');
$fechaNotifCdc   = $getCampoInputP35('p35_0_fecha_notif_direccion_salud_a_cdc');
$fechaInvest     = $getCampoInputP35('p35_0_fecha_de_investigacion_visita_domiciliaria');
$casoCaptadoEn   = $getCampoInputP35('p35_0_caso_captado_en');
?>

<div id="notificacionFechasP35Wrap" <?= ($enfermedad['cie10'] ?? null) === 'P35.0' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($codigoReg['name']): ?>
    <div class="field">
      <label class="fl">Código de registro N.°</label>
      <div class="control">
        <input type="text" name="<?= $codigoReg['name'] ?>" value="<?= e($codigoReg['val']) ?>" placeholder="Código de registro N.°…">
      </div>
      <?php if ($codigoReg['err']): ?><span class="hint err"><?= e($codigoReg['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaConoc['name']): ?>
    <div class="field">
      <label class="fl">Fecha de conocimiento local del caso</label>
      <div class="control mono <?= $fechaConoc['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaConoc['name'] ?>" value="<?= e($fechaConoc['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaConoc['err']): ?><span class="hint err"><?= e($fechaConoc['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaNotifEess['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación EE.SS. a Red/Microred</label>
      <div class="control mono <?= $fechaNotifEess['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifEess['name'] ?>" value="<?= e($fechaNotifEess['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifEess['err']): ?><span class="hint err"><?= e($fechaNotifEess['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaNotifRed['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación Red/Microred a Dirección de Salud</label>
      <div class="control mono <?= $fechaNotifRed['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifRed['name'] ?>" value="<?= e($fechaNotifRed['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifRed['err']): ?><span class="hint err"><?= e($fechaNotifRed['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaNotifCdc['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación Dirección de Salud a CDC</label>
      <div class="control mono <?= $fechaNotifCdc['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifCdc['name'] ?>" value="<?= e($fechaNotifCdc['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifCdc['err']): ?><span class="hint err"><?= e($fechaNotifCdc['err']) ?></span><?php endif; ?>
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
  </div>

  <?php if ($casoCaptadoEn['name']): ?>
  <div style="margin-top:16px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 14px;">
    <label class="fl" style="font-weight:600; margin-bottom:10px; display:block;">Caso captado en:</label>
    <div style="display:flex; flex-wrap:wrap; gap:16px 24px; align-items:center;">
      <?php
      $optsCapt = [
          'Emergencia' => 'Emergencia',
          'Hospitalización' => 'Hospitalización',
          'Consultorio externo' => 'Consultorio externo',
          'Búsqueda institucional' => 'Búsqueda institucional'
      ];
      $valCapt = $casoCaptadoEn['val'];
      foreach ($optsCapt as $oVal => $oLabel):
          $chk = ($valCapt === $oVal || $valCapt === strtoupper(str_replace(' ', '_', $oVal))) ? 'checked' : '';
      ?>
        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:14px; color:var(--text-main, #e2e8f0);">
          <input type="radio" name="<?= $casoCaptadoEn['name'] ?>" value="<?= e($oVal) ?>" <?= $chk ?> style="accent-color:var(--accent, #00f2fe);">
          <span><?= e($oLabel) ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <?php if ($casoCaptadoEn['err']): ?><span class="hint err" style="margin-top:6px; display:block;"><?= e($casoCaptadoEn['err']) ?></span><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
