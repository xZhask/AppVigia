<?php
/**
 * Selector encadenado departamento → provincia → distrito.
 * Variables esperadas:
 *   $prefijo (string, único en la página -- permite más de un selector por
 *     página, ej. domicilio del paciente + residencia de la madre en P96)
 *   $departamentos (array de departamento)
 *   $provinciasIniciales (array de provincia ya filtradas por el departamento seleccionado)
 *   $distritosIniciales (array de distrito ya filtradas por la provincia seleccionada)
 *   $departamentoSeleccionado, $provinciaSeleccionada, $distritoSeleccionado (ids o '')
 *   $errorDistrito (?string, opcional)
 *   $nombreCampoDistrito (string, opcional, default 'distrito_id' -- el
 *     `name` del <select> final; hay que cambiarlo si se usa más de una vez
 *     en la misma página, o los dos selectores mandan el mismo campo POST)
 *   $distritoRequerido (bool, opcional, default true)
 */
$errorDistrito ??= null;
$nombreCampoDistrito ??= 'distrito_id';
$distritoRequerido ??= true;
?>
<div class="fields thirds">
  <div class="field">
    <label class="fl">Departamento</label>
    <div class="control">
      <select id="<?= e($prefijo) ?>-departamento">
        <option value="">Seleccionar…</option>
        <?php foreach ($departamentos as $dep): ?>
          <option value="<?= e($dep['id']) ?>" <?= seleccionado($departamentoSeleccionado, $dep['id']) ?>><?= e($dep['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field">
    <label class="fl">Provincia</label>
    <div class="control">
      <select id="<?= e($prefijo) ?>-provincia">
        <option value="">Seleccionar…</option>
        <?php foreach ($provinciasIniciales as $prov): ?>
          <option value="<?= e($prov['id']) ?>" <?= seleccionado($provinciaSeleccionada, $prov['id']) ?>><?= e($prov['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>
  <div class="field">
    <label class="fl">Distrito <?php if ($distritoRequerido): ?><span class="req">*</span><?php endif; ?></label>
    <div class="control <?= $errorDistrito ? 'err' : '' ?>">
      <select name="<?= e($nombreCampoDistrito) ?>" id="<?= e($prefijo) ?>-distrito" <?= $distritoRequerido ? 'required' : '' ?>>
        <option value="">Seleccionar…</option>
        <?php foreach ($distritosIniciales as $dist): ?>
          <option value="<?= e($dist['id']) ?>" <?= seleccionado($distritoSeleccionado, $dist['id']) ?>><?= e($dist['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <?php if ($errorDistrito): ?><span class="hint err"><?= e($errorDistrito) ?></span><?php endif; ?>
  </div>
</div>
<script>document.addEventListener('DOMContentLoaded', function () { inicializarUbigeo('<?= e($prefijo) ?>'); });</script>
