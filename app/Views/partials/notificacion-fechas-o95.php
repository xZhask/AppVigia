<?php
/**
 * Campos específicos de notificación para Muerte Materna (O95):
 * - Hora de la notificación (hora_notificacion)
 * - Identificado por (identificado_por: Vigilancia activa / Vigilancia pasiva)
 */
$esO95 = ($enfermedad['cie10'] ?? '') === 'O95';
$valHoraNotif = $valoresFijos['hora_notificacion'] ?? $valoresCampos[14301] ?? date('H:i');
if (strlen($valHoraNotif) > 5) {
    $valHoraNotif = substr($valHoraNotif, 11, 5);
}
$valIdentificadoPor = $valoresFijos['identificado_por'] ?? $valoresFijos['tipo_captacion'] ?? $valoresCampos[14302] ?? 'ACTIVA';
?>
<div id="notificacionFechasO95Wrap" class="o95-elem" style="margin-top: 14px;" <?= !$esO95 ? 'hidden' : '' ?>>
  <div class="fields thirds">
    <div class="field">
      <label class="fl">Hora de la notificación <span class="req">*</span></label>
      <div class="control mono">
        <input type="time" id="horaNotificacionO95" name="hora_notificacion" value="<?= e($valHoraNotif) ?>">
      </div>
      <span class="hint">Formato 24 horas (HH:MM)</span>
    </div>
    <div class="field" style="grid-column: span 2;">
      <label class="fl">Identificado por <span class="req">*</span></label>
      <div class="control">
        <select name="identificado_por" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="ACTIVA" <?= seleccionado($valIdentificadoPor, 'ACTIVA') ?>>Vigilancia activa</option>
          <option value="PASIVA" <?= seleccionado($valIdentificadoPor, 'PASIVA') ?>>Vigilancia pasiva</option>
        </select>
      </div>
    </div>
  </div>
</div>
