<?php
/**
 * Campos específicos de notificación e investigación para Parotiditis con complicaciones (B26).
 * Se muestran en la tarjeta superior 1. Notificación en lugar de la sección clínica inferior.
 */
use App\Models\CampoDef;
use App\Models\Enfermedad;
use App\Models\SeccionDef;

$enfB26Obj = Enfermedad::buscarPorCie10('B26');
$seccionesB26 = $enfB26Obj ? SeccionDef::porEnfermedad((int) $enfB26Obj['id']) : [];
$secNotifB26 = null;
foreach ($seccionesB26 as $s) {
    if (trim($s['nombre']) === 'Datos de notificación e investigación del caso') {
        $secNotifB26 = $s;
        break;
    }
}

$camposNotifB26 = $secNotifB26 ? CampoDef::porSeccion((int) $secNotifB26['id']) : [];
$campoMapB26 = [];
foreach ($camposNotifB26 as $c) {
    $campoMapB26[trim($c['etiqueta'])] = $c;
}

$getCampoInputB26 = function(string $etiqueta) use ($campoMapB26, $valoresCampos, $erroresCampos) {
    $c = $campoMapB26[$etiqueta] ?? null;
    if (!$c) return ['id' => null, 'name' => '', 'val' => '', 'err' => null];
    $val = $valoresCampos[$c['id']] ?? '';
    $err = $erroresCampos[$c['id']] ?? null;
    return [
        'id' => $c['id'],
        'name' => 'campo_' . $c['id'],
        'val' => $val,
        'err' => $err
    ];
};

$codigoReg       = $getCampoInputB26('Código de registro N.°');
$fechaConsulta   = $getCampoInputB26('Fecha de consulta');
$fechaConoc      = $getCampoInputB26('Fecha de conocimiento local del caso');
$fechaInvest     = $getCampoInputB26('Fecha de investigación (visita domiciliaria)');
$fechaNotifEess  = $getCampoInputB26('Fecha de notificación EE.SS. a Red/Microred');
$fechaNotifRed   = $getCampoInputB26('Fecha de notificación Red/Microred a Dirección de Salud');
$fechaNotifCdc   = $getCampoInputB26('Fecha de notificación Dirección de Salud a CDC');
?>

<div id="notificacionFechasB26Wrap" <?= ($enfermedad['cie10'] ?? null) === 'B26' ? '' : 'hidden' ?>>
  <div class="fields thirds" style="margin-top:14px">
    <?php if ($codigoReg['name']): ?>
    <div class="field">
      <label class="fl">Código de registro N.°</label>
      <div class="control">
        <input type="text" name="<?= $codigoReg['name'] ?>" value="<?= e($codigoReg['val']) ?>" placeholder="Código de registro N.°…">
      </div>
      <?php if ($codigoReg['err']): ?><span class="hint err"><?= e($codigoReg['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($fechaConsulta['name']): ?>
    <div class="field">
      <label class="fl">Fecha de consulta</label>
      <div class="control mono <?= $fechaConsulta['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaConsulta['name'] ?>" value="<?= e($fechaConsulta['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaConsulta['err']): ?><span class="hint err"><?= e($fechaConsulta['err']) ?></span><?php endif; ?>
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
      <label class="fl">Fecha de notificación EE.SS. a Red/Microred</label>
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
      <label class="fl">Fecha de notificación Dirección de Salud a CDC</label>
      <div class="control mono <?= $fechaNotifCdc['err'] ? 'err' : '' ?>">
        <input type="date" name="<?= $fechaNotifCdc['name'] ?>" value="<?= e($fechaNotifCdc['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <?php if ($fechaNotifCdc['err']): ?><span class="hint err"><?= e($fechaNotifCdc['err']) ?></span><?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
