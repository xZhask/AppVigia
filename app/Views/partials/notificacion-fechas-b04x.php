<?php
/**
 * Campos específicos de notificación para Viruela del mono / Mpox
 * (B04X). Se muestran en la tarjeta superior 1. Notificación en lugar
 * de la sección clínica inferior.
 */
// Resuelve contra B04X explícita, no la $enfermedad activa en esta carga
// de página: este partial se incluye sin condición junto a sus hermanos de
// pfa/b05/o95/b26/p350/a35/a33/a370/b01/a97/b57/a95 -- sus campos tienen
// que estar en el DOM (ocultos) incluso cuando B04X no es la ficha activa,
// para que el cambio de enfermedad en el formulario los pueda mostrar sin
// recargar la página.
$campoB04X = $resolvedorPara('B04X');

$servicioIngreso = $campoB04X('b04x_servicio_de_ingreso_cerits');

// "I. DATOS GENERALES DE LA NOTIFICACIÓN" del PDF (págs. 5-6): GERESA/
// DIRESA/DIRIS, RSS/RIS (Red de Salud), EESS e Inst. Adm. (MINSA/EsSalud/
// FF.AA-Sanidad/Privado) NO se capturan como campo_def -- son datos
// constantes del establecimiento ya elegido en "Establecimiento (EESS)"
// (establecimiento.institucion, red_salud.nombre/diresa), mismo criterio
// que A370/A35/A33. "Microred" no tiene equivalente en el sistema
// (bloqueado, ver PENDIENTES.md ítem 13). "Fecha de investigación" (ítem
// 2) no se duplica como campo_def propio: el PDF no trae una segunda
// fecha de investigación distinta más adelante (a diferencia de A370/A35/
// A33/B01/PFA, que sí separan una "visita domiciliaria" de la fecha de
// cierre) -- decisión explícita del usuario (2026-08-27): se reutiliza el
// campo núcleo `fecha_investigacion` que ya vive en la sección "Investigador"
// (investigador.php, común a las 24 fichas).
?>

<div id="notificacionFechasB04xWrap" <?= ($enfermedad['cie10'] ?? null) === 'B04X' ? '' : 'hidden style="display:none;"' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($servicioIngreso['name']): ?>
    <div class="field">
      <label class="fl">Servicio de ingreso/CERITS donde se identificó el caso</label>
      <div class="control">
        <input type="text" name="<?= $servicioIngreso['name'] ?>" value="<?= e($servicioIngreso['val']) ?>">
      </div>
      <?php if ($servicioIngreso['err']): ?><span class="hint err"><?= e($servicioIngreso['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
