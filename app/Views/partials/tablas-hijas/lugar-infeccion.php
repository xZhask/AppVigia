<?php
/**
 * Lugar probable de infección (caso_lugar_infeccion), reutilizable entre
 * fichas (difteria, fiebre amarilla, ...). Variable esperada: $filasLugarInfeccion
 * (array de ['lugar_institucion','localidad_texto','permanencia_dias']).
 * Al igual que viajes.php, la localidad se captura como texto libre por ahora
 * (no se normaliza contra distrito_id en esta fase).
 */
$erroresLugarInfeccion = $erroresLugarInfeccion ?? [];
$filaLugarInfeccion = function (array $fila = ['lugar_institucion' => '', 'localidad_texto' => '', 'permanencia_dias' => ''], ?array $error = null): void {
    $errorDias = $error['permanencia_dias'] ?? null;
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <div class="field">
        <label class="fl">Lugar o institución</label>
        <div class="control"><input type="text" name="lugarinf_institucion[]" value="<?= e($fila['lugar_institucion']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Localidad / distrito / provincia / departamento</label>
        <div class="control"><input type="text" name="lugarinf_localidad[]" value="<?= e($fila['localidad_texto'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Permanencia (días)</label>
        <div class="control mono <?= $errorDias ? 'err' : '' ?>"><input type="number" min="0" name="lugarinf_permanencia[]" value="<?= e($fila['permanencia_dias']) ?>"></div>
        <?php if ($errorDias): ?><span class="hint err"><?= e($errorDias) ?></span><?php endif; ?>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar lugar" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="lugar_infeccion">
  <?php foreach ($filasLugarInfeccion as $i => $fila): $filaLugarInfeccion($fila, $erroresLugarInfeccion[$i] ?? null); endforeach; ?>
</div>
<template id="plantilla-lugar_infeccion"><?php $filaLugarInfeccion(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-lugar_infeccion" data-lista="lugar_infeccion" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar lugar
</button>
