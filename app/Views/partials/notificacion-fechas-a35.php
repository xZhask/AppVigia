<?php
/**
 * Campos específicos de notificación e investigación para Tétanos (A35).
 * Se muestran en la tarjeta superior 1. Notificación en lugar de la sección clínica inferior.
 */
// Resuelve contra A35 explícita, no la $enfermedad activa en esta carga de
// página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/b26/p350 -- sus campos tienen que estar en el DOM (ocultos)
// incluso cuando A35 no es la ficha activa, para que el cambio de
// enfermedad en el formulario (que solo reemplaza la sección clínica vía
// AJAX, no esta tarjeta) los pueda mostrar sin recargar la página.
$campoA35 = $resolvedorPara('A35');

$casoN           = $campoA35('a35_caso_n');
$fechaConoc      = $campoA35('a35_fecha_de_conocimiento_local');
$fechaInvest     = $campoA35('a35_fecha_de_investigacion_visita_domiciliaria');
$fechaNotifEess  = $campoA35('a35_fecha_de_notificacion_ee_ss_a_red_microrred');
$fechaNotifRed   = $campoA35('a35_fecha_de_notificacion_red_microrred_a_disa');
?>

<div id="notificacionFechasA35Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A35' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($casoN['name']): ?>
    <div class="field">
      <label class="fl">Caso N.°</label>
      <div class="control">
        <input type="text" name="<?= $casoN['name'] ?>" value="<?= e($casoN['val']) ?>" placeholder="Caso N.°…">
      </div>
      <?php if ($casoN['err']): ?><span class="hint err"><?= e($casoN['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaConoc['name']): ?>
    <div class="field">
      <label class="fl">Fecha de conocimiento local</label>
      <div class="control mono <?= $fechaConoc['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaConoc['name'] ?>" value="<?= e($fechaConoc['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaConoc['err']): ?><span class="hint err"><?= e($fechaConoc['err']) ?></span><?php endif; ?>
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

    <?php if ($fechaNotifEess['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación EE SS a Red/Microrred</label>
      <div class="control mono <?= $fechaNotifEess['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifEess['name'] ?>" value="<?= e($fechaNotifEess['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifEess['err']): ?><span class="hint err"><?= e($fechaNotifEess['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaNotifRed['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación Red/Microrred a DISA</label>
      <div class="control mono <?= $fechaNotifRed['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifRed['name'] ?>" value="<?= e($fechaNotifRed['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifRed['err']): ?><span class="hint err"><?= e($fechaNotifRed['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
