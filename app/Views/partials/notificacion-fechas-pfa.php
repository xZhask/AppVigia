<?php
/**
 * Campos específicos de notificación para PFA (A80):
 * N.° de ficha y las 5 fechas del flujo de notificación MINSA/CDC.
 */
?>
<div id="notificacionFechasPfaWrap" <?= ($enfermedad['cie10'] ?? null) === 'A80' ? '' : 'hidden' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <div class="field">
      <label class="fl">N.° de ficha</label>
      <div class="control">
        <input type="text" name="nro_ficha_pfa" value="<?= e($valoresFijos['nro_ficha_pfa'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Fecha de conocimiento local</label>
      <div class="control mono">
        <input type="date" name="fecha_conocimiento_local" value="<?= e($valoresFijos['fecha_conocimiento_local'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Fecha de investigación</label>
      <div class="control mono">
        <input type="date" name="fecha_investigacion" value="<?= e($valoresFijos['fecha_investigacion'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Fecha de notificación EE.SS. a Red/Microred</label>
      <div class="control mono">
        <input type="date" name="fecha_notif_eess_red" value="<?= e($valoresFijos['fecha_notif_eess_red'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Fecha de notificación Red/Microred a DISA</label>
      <div class="control mono">
        <input type="date" name="fecha_notif_red_disa" value="<?= e($valoresFijos['fecha_notif_red_disa'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Fecha de notificación de DISA a DGE</label>
      <div class="control mono">
        <input type="date" name="fecha_notif_disa_dge" value="<?= e($valoresFijos['fecha_notif_disa_dge'] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
  </div>
</div>
