<?php
/**
 * Fila dinámica de contactos del caso (caso_contacto). Variable esperada:
 * $filasContactos (array de ['nombres','parentesco','edad','sexo','vacunado',
 * 'fecha_vacunacion','profilaxis','doc','celular','fecha_contacto',
 * 'lugar_contacto','fecha_inicio_erupcion','vacunado_72h']). Las columnas
 * clínicas se agregaron para el censo de contactos domiciliarios (difteria y
 * otras fichas similares — AUDITORIA_FICHA_DIFTERIA.md, punto 6); las
 * últimas 4 son de la cadena de transmisión de sarampión
 * (CIERRE_RECARGA_Y_FASE5.md Parte 1.4).
 *
 * $columnasContacto (PENDIENTES_POST_FASE5.md punto 3): lista de claves de
 * columna que esta ficha debe mostrar (viene de
 * enfermedad.columnas_contacto, cargado desde el manifiesto). "nombres"
 * siempre se muestra -- una fila de contacto sin nombre no tiene sentido.
 */
// Mismo mínimo que CasosController::COLUMNAS_HIJA_DEFECTO['contacto'] --
// no debería usarse en la práctica, ya que el controlador siempre pasa
// $columnasContacto, pero evita un aviso de PHP si alguna vista nueva
// llegara a incluir este parcial sin pasar la variable.
$columnasContacto = $columnasContacto ?? ['parentesco', 'doc', 'celular'];
$muestra = fn(string $col) => in_array($col, $columnasContacto, true);

$filaContacto = function (array $fila = [
    'nombres' => '', 'parentesco' => '', 'edad' => '', 'sexo' => '', 'vacunado' => '',
    'fecha_vacunacion' => '', 'profilaxis' => '', 'doc' => '', 'celular' => '',
    'fecha_contacto' => '', 'lugar_contacto' => '', 'fecha_inicio_erupcion' => '', 'vacunado_72h' => '',
]) use ($muestra): void { ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field wide">
        <label class="fl">Nombres y apellidos</label>
        <div class="control"><input type="text" name="contacto_nombres[]" value="<?= e($fila['nombres']) ?>"></div>
      </div>
      <?php if ($muestra('parentesco')): ?>
      <div class="field">
        <label class="fl">Parentesco / relación</label>
        <div class="control"><input type="text" name="contacto_parentesco[]" value="<?= e($fila['parentesco']) ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('edad')): ?>
      <div class="field">
        <label class="fl">Edad</label>
        <div class="control mono"><input type="number" min="0" max="120" name="contacto_edad[]" value="<?= e($fila['edad'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('sexo')): ?>
      <div class="field">
        <label class="fl">Sexo</label>
        <div class="control">
          <select name="contacto_sexo[]" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="M" <?= seleccionado($fila['sexo'] ?? '', 'M') ?>>Masculino</option>
            <option value="F" <?= seleccionado($fila['sexo'] ?? '', 'F') ?>>Femenino</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('vacunado')): ?>
      <div class="field">
        <label class="fl">Vacunado</label>
        <div class="control">
          <select name="contacto_vacunado[]" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="SI" <?= seleccionado($fila['vacunado'] ?? '', 'SI') ?>>Sí</option>
            <option value="NO" <?= seleccionado($fila['vacunado'] ?? '', 'NO') ?>>No</option>
            <option value="IGNORADO" <?= seleccionado($fila['vacunado'] ?? '', 'IGNORADO') ?>>Ignorado</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_vacunacion')): ?>
      <div class="field">
        <label class="fl">Fecha de vacunación</label>
        <div class="control mono"><input type="date" name="contacto_fecha_vacunacion[]" value="<?= e($fila['fecha_vacunacion'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('profilaxis')): ?>
      <div class="field">
        <label class="fl">Profilaxis</label>
        <div class="control">
          <select name="contacto_profilaxis[]" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="SI" <?= seleccionado($fila['profilaxis'] ?? '', 'SI') ?>>Sí</option>
            <option value="NO" <?= seleccionado($fila['profilaxis'] ?? '', 'NO') ?>>No</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('doc')): ?>
      <div class="field">
        <label class="fl">Documento</label>
        <div class="control mono"><input type="text" name="contacto_doc[]" value="<?= e($fila['doc']) ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('celular')): ?>
      <div class="field">
        <label class="fl">Celular</label>
        <div class="control mono"><input type="text" name="contacto_celular[]" value="<?= e($fila['celular']) ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_contacto')): ?>
      <div class="field">
        <label class="fl">Fecha de contacto</label>
        <div class="control mono"><input type="date" name="contacto_fecha_contacto[]" value="<?= e($fila['fecha_contacto'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('lugar_contacto')): ?>
      <div class="field">
        <label class="fl">Lugar de contacto / exposición</label>
        <div class="control"><input type="text" name="contacto_lugar_contacto[]" value="<?= e($fila['lugar_contacto'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_inicio_erupcion')): ?>
      <div class="field">
        <label class="fl">Fecha de inicio de erupción</label>
        <div class="control mono"><input type="date" name="contacto_fecha_inicio_erupcion[]" value="<?= e($fila['fecha_inicio_erupcion'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('vacunado_72h')): ?>
      <div class="field">
        <label class="fl">Vacunado dentro de 72h del contacto</label>
        <div class="control">
          <select name="contacto_vacunado_72h[]" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="SI" <?= seleccionado($fila['vacunado_72h'] ?? '', 'SI') ?>>Sí</option>
            <option value="NO" <?= seleccionado($fila['vacunado_72h'] ?? '', 'NO') ?>>No</option>
            <option value="DESCONOCIDO" <?= seleccionado($fila['vacunado_72h'] ?? '', 'DESCONOCIDO') ?>>Desconocido</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
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
