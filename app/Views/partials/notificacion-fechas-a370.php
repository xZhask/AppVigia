<?php
/**
 * Campos específicos de notificación e investigación para Tos ferina
 * (A37.0). Se muestran en la tarjeta superior 1. Notificación en lugar de
 * la sección clínica inferior.
 */
// Resuelve contra A37.0 explícita, no la $enfermedad activa en esta carga
// de página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/b26/p350/a35/a33 -- sus campos tienen que estar en el DOM
// (ocultos) incluso cuando A37.0 no es la ficha activa, para que el cambio
// de enfermedad en el formulario (que solo reemplaza la sección clínica
// vía AJAX, no esta tarjeta) los pueda mostrar sin recargar la página.
$campoA370 = $resolvedorPara('A37.0');

$codigoRegistro  = $campoA370('a37_0_codigo_de_registro_n');
$fechaConoc      = $campoA370('a37_0_fecha_de_conocimiento_local_del_caso');
$fechaNotifEess  = $campoA370('a37_0_fecha_de_notificacion_ee_ss_a_red_microred');
$fechaNotifRed   = $campoA370('a37_0_fecha_de_notificacion_red_microrred_a_direccion_r');
$fechaNotifCdc   = $campoA370('a37_0_fecha_de_notificacion_de_direccion_de_salud_a_cdc');
$fechaInvest     = $campoA370('a37_0_fecha_de_investigacion_visita_domiciliaria');

// "I. DATOS DEL ESTABLECIMIENTO NOTIFICANTE" del PDF (pág. 1): GERESA/
// DIRESA/DIRIS, Red de Salud, EESS notificante e Inst. Adm. NO se capturan
// como campo_def -- son datos constantes del establecimiento ya elegido en
// "Establecimiento (EESS)" (establecimiento.nombre/institucion,
// red_salud.nombre/diresa), mismo criterio que "Institución informante" de
// A35/A33. "Micro red" no tiene equivalente en el sistema (bloqueado, ver
// PENDIENTES.md ítem 13). "Caso captado en" sí es dato propio del caso.
$casoCaptadoEn = $campoA370('a37_0_caso_captado_en');
?>

<div id="notificacionFechasA370Wrap" <?= ($enfermedad['cie10'] ?? null) === 'A37.0' ? '' : 'hidden style="display:none;"' ?>>
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
      <label class="fl">Fecha de notificación EE.SS a Red/Microred</label>
      <div class="control mono <?= $fechaNotifEess['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifEess['name'] ?>" value="<?= e($fechaNotifEess['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifEess['err']): ?><span class="hint err"><?= e($fechaNotifEess['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaNotifRed['name']): ?>
    <div class="field">
      <label class="fl">Fecha de notificación Red/Microrred a Dirección Regional de Salud</label>
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

    <?php if ($fechaInvest['name']): ?>
    <div class="field">
      <label class="fl">Fecha de investigación (visita domiciliaria)</label>
      <div class="control mono <?= $fechaInvest['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaInvest['name'] ?>" value="<?= e($fechaInvest['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaInvest['err']): ?><span class="hint err"><?= e($fechaInvest['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($casoCaptadoEn['name']): ?>
    <div class="field">
      <label class="fl">Caso captado en</label>
      <div class="control">
        <select name="<?= $casoCaptadoEn['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($casoCaptadoEn['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($casoCaptadoEn['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($casoCaptadoEn['err']): ?><span class="hint err"><?= e($casoCaptadoEn['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
