<?php
/**
 * Fila dinámica de antecedentes vacunales del caso (caso_vacuna). Variables
 * esperadas: $filasVacunas (array de ['vacuna','dosis','fecha','fabricante',
 * 'lote','via','sitio','fecha_vencimiento','establecimiento','adyuvante']),
 * $opcionesVacuna/$opcionesDosis/$opcionesViaVacuna/$opcionesSitio/
 * $opcionesAdyuvante (catalogo_item, PENDIENTES_POST_FASE5.md punto 2).
 *
 * "Vacuna"/"Dosis"/"Vía"/"Sitio"/"Adyuvante" son <select> respaldados por
 * catálogo (mismo patrón que muestras.php: se guarda el `valor` del
 * catalogo_item, no texto libre, para que "Pentavalente"/"pentavalente"/
 * "Penta" no dejen de agruparse en reportes). Solo "Vacuna" tiene además un
 * campo de texto para "20 Otro (especificar)", porque es la única opción
 * del PDF que lo pide -- si se completa, reemplaza al código elegido.
 *
 * $columnasVacuna (punto 3): columnas que esta ficha debe mostrar, además
 * de "Vacuna"/"Otro (especificar)"/"Fecha de aplicación", que son fijas.
 */
$erroresVacunas = $erroresVacunas ?? [];
$opcionesVacuna = $opcionesVacuna ?? [];
$opcionesDosis = $opcionesDosis ?? [];
$opcionesViaVacuna = $opcionesViaVacuna ?? [];
$opcionesSitio = $opcionesSitio ?? [];
$opcionesAdyuvante = $opcionesAdyuvante ?? [];
$columnasVacuna = $columnasVacuna ?? ['dosis'];
$muestra = fn(string $col) => in_array($col, $columnasVacuna, true);

$filaVacuna = function (array $fila = [
    'vacuna' => '', 'vacuna_otro' => '', 'dosis' => '', 'fecha' => '', 'fabricante' => '', 'lote' => '',
    'via' => '', 'sitio' => '', 'adyuvante' => '', 'fecha_vencimiento' => '', 'establecimiento' => '',
], ?array $error = null) use ($opcionesVacuna, $opcionesDosis, $opcionesViaVacuna, $opcionesSitio, $opcionesAdyuvante, $muestra): void {
    $errorFecha = $error['fecha'] ?? null;
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Vacuna</label>
        <div class="control">
          <select name="vacuna_nombre[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesVacuna as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['vacuna'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="field">
        <label class="fl">Si es "Otro", especificar</label>
        <div class="control"><input type="text" name="vacuna_otro[]" value="<?= e($fila['vacuna_otro'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Fecha de aplicación</label>
        <div class="control mono <?= $errorFecha ? 'err' : '' ?>"><input type="date" name="vacuna_fecha[]" value="<?= e($fila['fecha'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorFecha): ?><span class="hint err"><?= e($errorFecha) ?></span><?php endif; ?>
      </div>
      <?php if ($muestra('dosis')): ?>
      <div class="field">
        <label class="fl">Dosis</label>
        <div class="control">
          <select name="vacuna_dosis[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesDosis as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['dosis'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('via')): ?>
      <div class="field">
        <label class="fl">Vía</label>
        <div class="control">
          <select name="vacuna_via[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesViaVacuna as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['via'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('sitio')): ?>
      <div class="field">
        <label class="fl">Sitio</label>
        <div class="control">
          <select name="vacuna_sitio[]">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesSitio as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['sitio'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('adyuvante')): ?>
      <div class="field">
        <label class="fl">Adyuvante</label>
        <div class="control">
          <select name="vacuna_adyuvante[]" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <?php foreach ($opcionesAdyuvante as $op): ?>
              <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['adyuvante'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('fabricante')): ?>
      <div class="field">
        <label class="fl">Fabricante</label>
        <div class="control"><input type="text" name="vacuna_fabricante[]" value="<?= e($fila['fabricante'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('lote')): ?>
      <div class="field">
        <label class="fl">Lote</label>
        <div class="control mono"><input type="text" name="vacuna_lote[]" value="<?= e($fila['lote'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('fecha_vencimiento')): ?>
      <div class="field">
        <label class="fl">Fecha de vencimiento</label>
        <div class="control mono"><input type="date" name="vacuna_fecha_vencimiento[]" value="<?= e($fila['fecha_vencimiento'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($muestra('establecimiento')): ?>
      <div class="field">
        <label class="fl">EE.SS. que vacunó</label>
        <div class="control"><input type="text" name="vacuna_establecimiento[]" value="<?= e($fila['establecimiento'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar vacuna" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="vacunas">
  <?php foreach ($filasVacunas as $i => $fila): $filaVacuna($fila, $erroresVacunas[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-vacunas"><?php $filaVacuna(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-vacunas" data-lista="vacunas" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar antecedente vacunal
</button>
