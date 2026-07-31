<?php
/**
 * Campos específicos de notificación e investigación para Parotiditis con complicaciones (B26).
 * Se muestran en la tarjeta superior 1. Notificación en lugar de la sección clínica inferior.
 */
// Resuelve contra B26 explícita, no la $enfermedad activa en esta carga de
// página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/p350 -- sus campos tienen que estar en el DOM (ocultos)
// incluso cuando B26 no es la ficha activa, para que el cambio de
// enfermedad en el formulario (que solo reemplaza la sección clínica vía
// AJAX, no esta tarjeta) los pueda mostrar sin recargar la página.
// $resolvedorPara la expone campos-por-clave.php (ver ese archivo).
$campoB26 = $resolvedorPara('B26');

$codigoReg       = $campoB26('b26_codigo_de_registro_n');
$fechaConsulta   = $campoB26('b26_fecha_de_consulta');
$fechaConoc      = $campoB26('b26_fecha_de_conocimiento_local_del_caso');
$fechaInvest     = $campoB26('b26_fecha_de_investigacion_visita_domiciliaria');
$fechaNotifEess  = $campoB26('b26_fecha_de_notificacion_ee_ss_a_red_microred');
$fechaNotifRed   = $campoB26('b26_fecha_de_notificacion_red_microred_a_direccion_de_s');
$fechaNotifCdc   = $campoB26('b26_fecha_de_notificacion_direccion_de_salud_a_cdc');
?>

<div id="notificacionFechasB26Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B26' ? '' : 'hidden style="display:none;"' ?>>
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

    <?php if ($fechaConsulta['name']): ?>
    <div class="field">
      <label class="fl">Fecha de consulta</label>
      <div class="control mono <?= $fechaConsulta['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaConsulta['name'] ?>" value="<?= e($fechaConsulta['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaConsulta['err']): ?><span class="hint err"><?= e($fechaConsulta['err']) ?></span><?php endif; ?>
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
  </div>
</div>
