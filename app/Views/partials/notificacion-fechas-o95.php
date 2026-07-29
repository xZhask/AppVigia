<?php
/**
 * Campos específicos de notificación para Muerte Materna (O95):
 * - Hora de la notificación (hora_notificacion)
 * - Identificado por (identificado_por: Vigilancia activa / Vigilancia pasiva)
 * - Etapa de la ficha (o95_tipo_ficha: ANEXO_1 Notificación inmediata / ANEXO_2 Investigación epidemiológica)
 */
$esO95 = ($enfermedad['cie10'] ?? '') === 'O95';

// Peticion 2, Fase 5: hora_notificacion/identificado_por/o95_tipo_ficha son
// "casos especiales" que CasosController.php guarda por clave (no por
// campo_NNNN, para que ficha.js los pueda seguir seleccionando por name=
// literal). Antes de esto ninguno de los tres se guardaba nunca: 14301 y
// 14302 no existían en campo_def (ver MAPA_IDS_CAMPOS.md, "codigo
// inalcanzable"), y 14300 (o95_tipo_ficha) era v99_aseguradora de otra
// ficha. $campo(...)['val'] nunca es null, así que hay que comparar contra
// '' antes de seguir la cadena de fallback.
$campoHoraNotifO95 = $campo('o95_hora_de_la_notificacion');
$campoIdentificadoPorO95 = $campo('o95_identificado_por');
$campoTipoFichaO95Notif = $campo('o95_tipo_de_ficha');

$valHoraNotif = $valoresFijos['hora_notificacion']
    ?? ($campoHoraNotifO95['val'] !== '' ? $campoHoraNotifO95['val'] : null)
    ?? date('H:i');
if (strlen($valHoraNotif) > 5) {
    $valHoraNotif = substr($valHoraNotif, 11, 5);
}
$valIdentificadoPor = $valoresFijos['identificado_por']
    ?? $valoresFijos['tipo_captacion']
    ?? ($campoIdentificadoPorO95['val'] !== '' ? $campoIdentificadoPorO95['val'] : null)
    ?? 'ACTIVA';
$valTipoFicha = $valoresFijos['o95_tipo_ficha']
    ?? ($campoTipoFichaO95Notif['val'] !== '' ? $campoTipoFichaO95Notif['val'] : null)
    ?? $_POST['o95_tipo_ficha']
    ?? 'ANEXO_1';
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
    <div class="control-radio-group">
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
