<?php
/**
 * Bloque condicional de una tabla hija (capacidad 6,
 * PETICION_HC_Y_LABORATORIO.md Parte 2, ítem 43 de P35.0): un SEGUNDO
 * conjunto de filas de la misma tabla (hoy solo caso_muestra), separado del
 * bloque principal por la columna `contexto`, visible solo cuando la
 * Clasificación del caso (núcleo) toma uno de sus "valores_activadores".
 *
 * Variables esperadas: $bloqueMuestra (['tabla','contexto','titulo',
 * 'columnas','depende_de','valores_activadores'] -- una entrada de
 * bloques_condicionales), $filasBloque (filas ya filtradas a este
 * contexto), $erroresBloque, $clasificacionActualBloque (valor actual de
 * "clasificacion" para computar la visibilidad inicial en servidor),
 * $opcionesResultado (catalogo_item, reutilizado de datosMuestrasCatalogo()
 * si el bloque declara la columna "resultado").
 */
$contexto = $bloqueMuestra['contexto'];
$columnasBloque = $bloqueMuestra['columnas'];
$tieneColumna = fn(string $col): bool => in_array($col, $columnasBloque, true);
$idLista = 'muestras_' . $contexto;
$idPlantilla = 'plantilla-muestras-' . $contexto;
$nombreCampo = fn(string $col): string => 'muestra_' . $contexto . '_' . $col . '[]';

$visible = in_array($clasificacionActualBloque, $bloqueMuestra['valores_activadores'], true);

$filaBloque = function (array $fila = ['fecha_toma' => '', 'fecha_result' => '', 'resultado' => '']) use ($tieneColumna, $nombreCampo, $opcionesResultado): void {
    ?>
  <div class="subrow">
    <div class="fields thirds" style="flex:1">
      <?php if ($tieneColumna('fecha_toma')): ?>
        <div class="field">
          <label class="fl">Fecha de obtención</label>
          <div class="control mono"><input type="date" name="<?= e($nombreCampo('fecha_toma')) ?>" value="<?= e($fila['fecha_toma'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>
      <?php endif; ?>
      <?php if ($tieneColumna('fecha_result')): ?>
        <div class="field">
          <label class="fl">Fecha de resultado</label>
          <div class="control mono"><input type="date" name="<?= e($nombreCampo('fecha_result')) ?>" value="<?= e($fila['fecha_result'] ?? '') ?>" max="<?= date('Y-m-d') ?>"></div>
        </div>
      <?php endif; ?>
      <?php if ($tieneColumna('resultado')): ?>
        <div class="field">
          <label class="fl">Resultado</label>
          <div class="control">
            <select name="<?= e($nombreCampo('resultado')) ?>">
              <option value="">Pendiente</option>
              <?php foreach ($opcionesResultado as $op): ?>
                <option value="<?= e($op['valor']) ?>" <?= seleccionado($fila['resultado'] ?? '', $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar muestra" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="card section dep-wrap bloque-condicional-muestra" data-contexto="<?= e($contexto) ?>" data-depende-de="<?= e($bloqueMuestra['depende_de']) ?>" data-valor-activador="<?= e(implode(',', $bloqueMuestra['valores_activadores'])) ?>" <?= $visible ? '' : 'hidden' ?>>
  <div class="section-head">
    <?php if (isset($numeroSeccion)): ?><span class="section-num"><?= $numeroSeccion ?></span><?php endif; ?>
    <h3><?= e($bloqueMuestra['titulo']) ?></h3>
  </div>
  <div class="section-body">
    <div class="subrows" data-lista="<?= e($idLista) ?>">
      <?php foreach ($filasBloque as $i => $fila): $filaBloque($fila); endforeach; ?>
    </div>
    <template id="<?= e($idPlantilla) ?>"><?php $filaBloque(); ?></template>
    <button type="button" class="btn btn-ghost agregar-fila" data-plantilla="<?= e($idPlantilla) ?>" data-lista="<?= e($idLista) ?>" style="margin-top:12px">
      <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
      Agregar muestra
    </button>
  </div>
</div>
