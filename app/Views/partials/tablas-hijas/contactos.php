<?php
/**
 * Fila dinámica de contactos del caso (caso_contacto). Variable esperada:
 * $filasContactos (array de ['nombres','parentesco','doc','celular']).
 */
$filaContacto = function (array $fila = ['nombres' => '', 'parentesco' => '', 'doc' => '', 'celular' => '']): void { ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field wide">
        <label class="fl">Nombres y apellidos</label>
        <div class="control"><input type="text" name="contacto_nombres[]" value="<?= e($fila['nombres']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Parentesco / relación</label>
        <div class="control"><input type="text" name="contacto_parentesco[]" value="<?= e($fila['parentesco']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Documento</label>
        <div class="control mono"><input type="text" name="contacto_doc[]" value="<?= e($fila['doc']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Celular</label>
        <div class="control mono"><input type="text" name="contacto_celular[]" value="<?= e($fila['celular']) ?>"></div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar contacto" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="contactos">
  <?php foreach ($filasContactos as $fila): $filaContacto($fila); endforeach; ?>
</div>
<template id="plantilla-contactos"><?php $filaContacto(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-contactos" data-lista="contactos" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar contacto
</button>
