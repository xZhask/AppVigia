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

// II. FUENTE DE NOTIFICACION del PDF (págs. 23-24): tipo/institución
// informante/fuente + trabajador que diagnostica + EESS que notifica.
// No es el genérico tipo/lugar de captación (notificacion-captacion.php,
// oculto para A35 -- ver nueva/index.php) -- ese concepto no existe en
// esta ficha, el PDF de A35 pide otra cosa en su lugar.
$tipo               = $campoA35('a35_tipo');
$institucionInf     = $campoA35('a35_institucion_informante');
$fuente             = $campoA35('a35_fuente');
$fuenteOtra         = $campoA35('a35_fuente_otra');
$trabajadorDx       = $campoA35('a35_trabajador_diagnostico_inicial');
$establecimientoNot = $campoA35('a35_establecimiento_que_notifica');
$fuenteOtraOculto   = !campoVisiblePorDependencia($fuenteOtra['campo'], $valoresCampos);
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

  <div class="fields thirds" style="margin-top:14px">
    <?php if ($tipo['name']): ?>
    <div class="field">
      <label class="fl">Tipo</label>
      <div class="control">
        <select name="<?= $tipo['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($tipo['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($tipo['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($tipo['err']): ?><span class="hint err"><?= e($tipo['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($institucionInf['name']): ?>
    <div class="field">
      <label class="fl">Institución informante</label>
      <div class="control">
        <select name="<?= $institucionInf['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($institucionInf['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($institucionInf['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($institucionInf['err']): ?><span class="hint err"><?= e($institucionInf['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fuente['name']): ?>
    <div class="field">
      <label class="fl">Fuente</label>
      <div class="control">
        <select name="<?= $fuente['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($fuente['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($fuente['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($fuente['err']): ?><span class="hint err"><?= e($fuente['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fuenteOtra['name']): ?>
    <div class="dep-wrap" data-depende-de="<?= $fuente['name'] ?>" data-valor-activador="OTRO" <?= $fuenteOtraOculto ? 'hidden' : '' ?>>
      <div class="field">
        <label class="fl">Fuente (especificar)</label>
        <div class="control">
          <input type="text" name="<?= $fuenteOtra['name'] ?>" value="<?= e($fuenteOtra['val']) ?>" placeholder="Especificar la fuente…">
        </div>
        <?php if ($fuenteOtra['err']): ?><span class="hint err"><?= e($fuenteOtra['err']) ?></span><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="fields halves" style="margin-top:14px">
    <?php if ($trabajadorDx['name']): ?>
    <div class="field">
      <label class="fl">Trabajador de salud que hace el diagnóstico inicial</label>
      <div class="control">
        <input type="text" name="<?= $trabajadorDx['name'] ?>" value="<?= e($trabajadorDx['val']) ?>" placeholder="Nombre completo…">
      </div>
      <?php if ($trabajadorDx['err']): ?><span class="hint err"><?= e($trabajadorDx['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($establecimientoNot['name']): ?>
    <div class="field">
      <label class="fl">Establecimiento de salud que notifica</label>
      <div class="control">
        <input type="text" name="<?= $establecimientoNot['name'] ?>" value="<?= e($establecimientoNot['val']) ?>" placeholder="Nombre del establecimiento…">
      </div>
      <?php if ($establecimientoNot['err']): ?><span class="hint err"><?= e($establecimientoNot['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
