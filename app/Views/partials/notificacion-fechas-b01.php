<?php
/**
 * Campos específicos de notificación e investigación para Varicela con
 * complicaciones (B01). Se muestran en la tarjeta superior 1. Notificación
 * en lugar de la sección clínica inferior.
 */
// Resuelve contra B01 explícita, no la $enfermedad activa en esta carga de
// página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/b26/p350/a35/a33/a370 -- sus campos tienen que estar en el
// DOM (ocultos) incluso cuando B01 no es la ficha activa, para que el
// cambio de enfermedad en el formulario (que solo reemplaza la sección
// clínica vía AJAX, no esta tarjeta) los pueda mostrar sin recargar la
// página.
$campoB01 = $resolvedorPara('B01');

$codigoRegistro = $campoB01('b01_codigo_de_registro_n');
$fechaInvest     = $campoB01('b01_fecha_de_investigacion_visita_domiciliaria');
$fechaNotifEess  = $campoB01('b01_fecha_de_notificacion_ee_ss_a_red_microred');
$fechaNotifRed   = $campoB01('b01_fecha_de_notificacion_red_microred_a_direccion_de_sa');
$fechaNotifCdc   = $campoB01('b01_fecha_de_notificacion_de_direccion_de_salud_a_cdc');

// La cabecera del PDF (pág. 3) también trae "Fecha de Hospitalización"
// entre Código de registro y Fecha de Investigación -- por decisión
// explícita del usuario (2026-08-08) NO se duplica ni se mueve acá: esa
// fecha sigue viviendo solo en "Hospitalización y egreso"
// (b01_fecha_de_hospitalizacion, ítem 32, gateada por Hospitalización=Sí).
?>

<div id="notificacionFechasB01Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B01' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($codigoRegistro['name']): ?>
    <div class="field">
      <label class="fl">Código de registro N.°</label>
      <div class="control">
        <input type="text" name="<?= $codigoRegistro['name'] ?>" value="<?= e($codigoRegistro['val']) ?>" placeholder="Código de registro…">
      </div>
      <?php if ($codigoRegistro['err']): ?><span class="hint err"><?= e($codigoRegistro['err']) ?></span><?php endif; ?>
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
      <label class="fl">Fecha de notificación EE.SS a Red/Microred</label>
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
      <label class="fl">Fecha de notificación de Dirección de Salud a CDC</label>
      <div class="control mono <?= $fechaNotifCdc['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifCdc['name'] ?>" value="<?= e($fechaNotifCdc['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifCdc['err']): ?><span class="hint err"><?= e($fechaNotifCdc['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
