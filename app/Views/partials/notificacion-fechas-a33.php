<?php
/**
 * Campos específicos de notificación e investigación para Tétanos neonatal
 * (A33). Se muestran en la tarjeta superior 1. Notificación en lugar de la
 * sección clínica inferior.
 */
// Resuelve contra A33 explícita, no la $enfermedad activa en esta carga de
// página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/b26/p350/a35 -- sus campos tienen que estar en el DOM
// (ocultos) incluso cuando A33 no es la ficha activa, para que el cambio de
// enfermedad en el formulario (que solo reemplaza la sección clínica vía
// AJAX, no esta tarjeta) los pueda mostrar sin recargar la página.
$campoA33 = $resolvedorPara('A33');

$casoN           = $campoA33('a33_caso_n');
$fechaConoc      = $campoA33('a33_fecha_de_conocimiento_local');
$fechaInvest     = $campoA33('a33_fecha_de_investigacion_visita_domiciliaria');
$fechaNotifEess  = $campoA33('a33_fecha_de_notificacion_ee_ss_a_red_microrred');
$fechaNotifRed   = $campoA33('a33_fecha_de_notificacion_red_microrred_a_disa');
$fechaNotifDge   = $campoA33('a33_fecha_de_notificacion_dge');

// "I. DATOS DEL ESTABLECIMIENTO NOTIFICANTE" del PDF (pág. 21): DISA, RED y
// nombre del establecimiento NO se capturan como campo_def -- son datos
// constantes del establecimiento ya elegido en "Establecimiento (EESS)"
// (red_salud.nombre/diresa), mismo criterio que "Institución informante" de
// A35 (A35.1). "Captación del caso" sí es dato propio del caso (distinto
// del genérico tipo/lugar de captación de notificacion-captacion.php,
// oculto para A33) -- sus 3 opciones son literales del PDF.
$captacion = $campoA33('a33_captacion_del_caso');
?>

<div id="notificacionFechasA33Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A33' ? '' : 'hidden style="display:none;"' ?>>
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

    <?php if ($fechaNotifDge['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación DGE</label>
      <div class="control mono <?= $fechaNotifDge['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifDge['name'] ?>" value="<?= e($fechaNotifDge['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifDge['err']): ?><span class="hint err"><?= e($fechaNotifDge['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($captacion['name']): ?>
    <div class="field">
      <label class="fl">Captación del caso</label>
      <div class="control">
        <select name="<?= $captacion['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($captacion['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($captacion['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($captacion['err']): ?><span class="hint err"><?= e($captacion['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
