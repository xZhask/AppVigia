<?php
/**
 * Campos específicos de notificación para Muerte Materna (O95):
 * - Hora de la notificación (hora_notificacion)
 * - Identificado por (identificado_por: Vigilancia activa / Vigilancia pasiva)
 * - Etapa de la ficha (o95_tipo_ficha: ANEXO_1 Notificación inmediata / ANEXO_2 Investigación epidemiológica)
 */
$esO95 = ($enfermedad['cie10'] ?? '') === 'O95';
$valHoraNotif = $valoresFijos['hora_notificacion'] ?? $valoresCampos[14301] ?? date('H:i');
if (strlen($valHoraNotif) > 5) {
    $valHoraNotif = substr($valHoraNotif, 11, 5);
}
$valIdentificadoPor = $valoresFijos['identificado_por'] ?? $valoresFijos['tipo_captacion'] ?? $valoresCampos[14302] ?? 'ACTIVA';
$valTipoFicha = $valoresFijos['o95_tipo_ficha'] ?? $valoresCampos[14300] ?? $_POST['o95_tipo_ficha'] ?? 'ANEXO_1';
?>
<div id="notificacionFechasO95Wrap" class="o95-elem" style="margin-top: 14px;" <?= !$esO95 ? 'hidden' : '' ?>>
  <div class="fields thirds" style="margin-bottom:14px;">
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

  <div class="field" style="width:100%; border-top:1px solid var(--line-2); padding-top:14px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">Etapa de la ficha (Muerte Materna) <span class="req">*</span></label>
    <div class="control-radio-group" style="display:flex; gap:16px; align-items:center; background:var(--surface-2, #18222d); padding:12px 16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line-2);">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="o95_tipo_ficha" value="ANEXO_1" <?= $valTipoFicha !== 'ANEXO_2' ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
        <span>Anexo 1: Notificación inmediata</span>
      </label>
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
        <input type="radio" name="o95_tipo_ficha" value="ANEXO_2" <?= $valTipoFicha === 'ANEXO_2' ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
        <span>Anexo 2: Investigación epidemiológica</span>
      </label>
    </div>
    <span class="hint" style="margin-top:6px;">Al seleccionar "Anexo 2: Investigación epidemiológica", se habilitan los campos ampliados de antecedentes, APN, complicaciones, 4 demoras, etc.</span>
  </div>
</div>
