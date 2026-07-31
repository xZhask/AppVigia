<?php
/**
 * Campos específicos de notificación para Sarampión / Rubéola / Febriles eruptivas (B05).
 * Se muestran en la tarjeta superior 1. Notificación en lugar de la sección clínica inferior.
 */
// Petición 2, cotejo B05 (2026-07-30): este partial tenía su PROPIO
// resolvedor local, por etiqueta en vez de por clave -- un tercer
// mecanismo aparte de $campo() y $resolvedorPara(). Se auditaron las 6
// etiquetas contra campo_def real: ninguna estaba desincronizada (a
// diferencia de las 62 claves de O95), pero se migra igual para no dejar
// un tercer resolvedor local vivo (ver campos-por-clave.php). Este
// partial se incluye sin condición aunque B05 no sea la ficha activa
// (mismo motivo que notificacion-fechas-b26.php): $campoB05 resuelve
// siempre contra la B05 real, no contra $enfermedad.
$campoB05 = $resolvedorPara('B05');
$enfermedadNotif = $campoB05('b05_enfermedad_notificada');
$codigoReg       = $campoB05('b05_codigo_de_registro');
$fechaIdentif    = $campoB05('b05_fecha_de_identificacion_local_del_caso_o_consulta');
$fechaInvest     = $campoB05('b05_fecha_de_investigacion_visita_domiciliaria');
$personalSalud   = $campoB05('b05_nombre_de_personal_de_salud_que_atiende_el_caso');
$telefonoPers    = $campoB05('b05_telefono_del_personal_de_salud');
?>

<div id="notificacionFechasB05Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B05' ? '' : 'hidden' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($enfermedadNotif['name']): ?>
    <div class="field">
      <label class="fl">Enfermedad notificada</label>
      <div class="control">
        <select name="<?= $enfermedadNotif['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($enfermedadNotif['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($enfermedadNotif['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($enfermedadNotif['err']): ?><span class="hint err"><?= e($enfermedadNotif['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($codigoReg['name']): ?>
    <div class="field">
      <label class="fl">Código de registro</label>
      <div class="control">
        <input type="text" name="<?= $codigoReg['name'] ?>" value="<?= e($codigoReg['val']) ?>" placeholder="Código de registro…">
      </div>
      <?php if ($codigoReg['err']): ?><span class="hint err"><?= e($codigoReg['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaIdentif['name']): ?>
    <div class="field">
      <label class="fl">Fecha de identificación local del caso (o consulta)</label>
      <div class="control mono <?= $fechaIdentif['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaIdentif['name'] ?>" value="<?= e($fechaIdentif['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaIdentif['err']): ?><span class="hint err"><?= e($fechaIdentif['err']) ?></span><?php endif; ?>
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

    <?php if ($personalSalud['name']): ?>
    <div class="field">
      <label class="fl">Nombre de personal de salud que atiende el caso</label>
      <div class="control">
        <input type="text" name="<?= $personalSalud['name'] ?>" value="<?= e($personalSalud['val']) ?>" placeholder="Nombre completo del personal…">
      </div>
      <?php if ($personalSalud['err']): ?><span class="hint err"><?= e($personalSalud['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($telefonoPers['name']): ?>
    <div class="field">
      <label class="fl">Teléfono del personal de salud</label>
      <div class="control">
        <input type="text" name="<?= $telefonoPers['name'] ?>" value="<?= e($telefonoPers['val']) ?>" placeholder="Teléfono de contacto…">
      </div>
      <?php if ($telefonoPers['err']): ?><span class="hint err"><?= e($telefonoPers['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
