<?php
/**
 * Campos específicos de notificación e investigación para Síndrome de Rubéola Congénita (P35.0).
 * Se muestran en la tarjeta superior 1. Notificación.
 */
// Resuelve contra P35.0 explícita, no la $enfermedad activa en esta carga
// de página: mismo motivo que notificacion-fechas-b26.php (ver ese
// archivo). $resolvedorPara la expone campos-por-clave.php.
$campoP35 = $resolvedorPara('P35.0');

$codigoReg       = $campoP35('p35_0_codigo_de_registro_n');
$fechaConoc      = $campoP35('p35_0_fecha_de_conocimiento_local_del_caso');
$fechaNotifEess  = $campoP35('p35_0_fecha_de_notificacion_eess_a_red_microred');
$fechaNotifRed   = $campoP35('p35_0_fecha_notif_red_microred_a_direccion_salud');
$fechaNotifCdc   = $campoP35('p35_0_fecha_notif_direccion_salud_a_cdc');
$fechaInvest     = $campoP35('p35_0_fecha_de_investigacion_visita_domiciliaria');
$casoCaptadoEn   = $campoP35('p35_0_caso_captado_en');
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
