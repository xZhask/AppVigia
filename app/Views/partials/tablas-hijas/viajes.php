<?php
/**
 * Fila dinámica de viajes del caso (caso_viaje). Variable esperada:
 * $filasViajes (array de ['pais','fecha_salida','fecha_retorno']).
 * `pais` se usa como "lugar visitado" libre (nacional o internacional);
 * no se normaliza contra distrito_id salvo que la ficha pida esa columna
 * (ver "distrito_id" más abajo).
 *
 * $columnasViaje (PETICION_P35_RUBEOLA_CONGENITA.md Fase 5.1 + ítem Z.2):
 * "pais"/"fecha_salida"/"fecha_retorno" se muestran siempre (identidad
 * mínima de la fila). "localidad"/"semana_gestacion" son opt-in, propias
 * de P35.0 (ítem 33 del PDF de SRC: País y Localidad/ciudad son columnas
 * separadas). "transporte_ida"/"transporte_retorno" son opt-OUT: se
 * muestran salvo que la ficha declare columnas_tablas_hija.caso_viaje
 * sin incluirlas -- P35.0 no las pide en su PDF, las demás 7 fichas (que
 * no declaran esta clave) siguen viéndolas vía COLUMNAS_HIJA_DEFECTO.
 * "distrito_id"/"direccion" (A97, pág. 49, ítem 21 "¿Dónde estuvo en las
 * últimas dos semanas?") son opt-in, igual que "localidad": Departamento/
 * Provincia/Distrito real (mismo selector encadenado que
 * lugar-infeccion.php, un prefijo único por fila) + dirección libre.
 */
$erroresViajes = $erroresViajes ?? [];
$columnasViaje = $columnasViaje ?? ['pais', 'fecha_salida', 'fecha_retorno', 'transporte_ida', 'transporte_retorno'];
$mostrarColViaje = fn(string $col) => in_array($col, $columnasViaje, true);
$etiquetaPais = $mostrarColViaje('localidad') ? 'País' : 'Lugar visitado (país o ciudad)';
$esA370Viaje = (($enfermedad['cie10'] ?? '') === 'A37.0');
// A44 (cotejo 2026-08-18, pág. 42 del PDF): "Viaje a localidades o
// comunidades vecinas" solo trae Fecha de viaje/Lugar/Tiempo de
// permanencia -- una sola fecha, no el par fecha_salida/fecha_retorno de
// las demás fichas (columnas_tablas_hija.caso_viaje de A44 omite
// 'fecha_retorno', ver $mostrarColViaje('fecha_retorno') más abajo).
$esA44Viaje = (($enfermedad['cie10'] ?? '') === 'A44');
$etiquetaFechaSalida = $esA44Viaje ? 'Fecha de viaje' : 'Fecha de ingreso';
$filaViaje = function (
    array $fila = ['pais' => '', 'localidad' => '', 'distrito_id' => '', 'direccion' => '', 'fecha_salida' => '', 'fecha_retorno' => '', 'tiempo_permanencia' => '', 'semana_gestacion' => ''],
    ?array $error = null,
    ?int $indice = null
) use ($mostrarColViaje, $etiquetaPais, $esA370Viaje, $etiquetaFechaSalida): void {
    $errorSalida = $error['fecha_salida'] ?? null;
    $errorRetorno = $error['fecha_retorno'] ?? null;
    $prefijoUbigeo = $indice !== null ? 'viaje-ubigeo-' . $indice : 'viaje-ubigeo-nueva';
    ?>
  <div class="subrow">
    <div class="fields" style="flex:1; display:flex; flex-wrap:wrap; gap:12px">
      <!-- 1. Lugar visitado -->
      <div class="field" style="flex:<?= $mostrarColViaje('localidad') ? '1' : '2' ?>; min-width:160px">
        <label class="fl"><?= e($etiquetaPais) ?></label>
        <div class="control"><input type="text" name="viaje_pais[]" value="<?= e($fila['pais'] ?? '') ?>" placeholder="<?= $mostrarColViaje('localidad') ? 'País…' : 'País o ciudad…' ?>"></div>
      </div>
      <?php if ($mostrarColViaje('localidad')): ?>
      <!-- Localidad/ciudad (columna extra, propia de fichas que la declaren).
      A37.0 reusa esta misma columna de texto libre como "Departamento"
      (ítem 59 del PDF: País/Departamento/Fecha de salida/Fecha de retorno)
      -- no se creó una columna nueva en caso_viaje para esto, solo cambia
      la etiqueta visible. -->
      <div class="field" style="flex:1; min-width:160px">
        <label class="fl"><?= $esA370Viaje ? 'Departamento' : 'Localidad/ciudad' ?></label>
        <div class="control"><input type="text" name="viaje_localidad[]" value="<?= e($fila['localidad'] ?? '') ?>" placeholder="<?= $esA370Viaje ? 'Departamento…' : 'Localidad o ciudad…' ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('distrito_id')): ?>
      <!-- Departamento/Provincia/Distrito real (A97: ítems 23-25 del PDF) -->
      <div class="field wide" style="flex-basis:100%">
        <?php
        (function (string $prefijo, ?string $distritoId): void {
            $nombreCampoDepartamento = 'viaje_departamento_id[]';
            $nombreCampoProvincia = 'viaje_provincia_id[]';
            $nombreCampoDistrito = 'viaje_distrito_id[]';
            $distritoRequerido = false;
            $errorDistrito = null;
            extract(contextoUbigeo($distritoId));
            require __DIR__ . '/../selector-ubigeo.php';
        })($prefijoUbigeo, $fila['distrito_id'] ?? null);
        ?>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('direccion')): ?>
      <!-- Dirección (A97: ítem 27 del PDF) -->
      <div class="field" style="flex:2; min-width:200px">
        <label class="fl">Dirección</label>
        <div class="control"><input type="text" name="viaje_direccion[]" value="<?= e($fila['direccion'] ?? '') ?>" placeholder="Dirección…"></div>
      </div>
      <?php endif; ?>
      <!-- 2. Fecha de ingreso / Fecha de viaje (A44) -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl"><?= e($etiquetaFechaSalida) ?></label>
        <div class="control mono <?= $errorSalida ? 'err' : '' ?>"><input type="date" name="viaje_fecha_salida[]" value="<?= e($fila['fecha_salida'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorSalida): ?><span class="hint err"><?= e($errorSalida) ?></span><?php endif; ?>
      </div>
      <?php if ($mostrarColViaje('transporte_ida')): ?>
      <!-- 3. Transporte ida -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Transporte ida</label>
        <div class="control">
          <select name="viaje_transporte_ida[]">
            <option value="">Seleccionar…</option>
            <option value="AEREO" <?= seleccionado($fila['transporte_ida'] ?? '', 'AEREO') ?>>Aéreo</option>
            <option value="TERRESTRE" <?= seleccionado($fila['transporte_ida'] ?? '', 'TERRESTRE') ?>>Terrestre</option>
            <option value="MARITIMO" <?= seleccionado($fila['transporte_ida'] ?? '', 'MARITIMO') ?>>Marítimo</option>
            <option value="OTRO" <?= seleccionado($fila['transporte_ida'] ?? '', 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('fecha_retorno')): ?>
      <!-- 4. Fecha de salida -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Fecha de salida</label>
        <div class="control mono <?= $errorRetorno ? 'err' : '' ?>"><input type="date" name="viaje_fecha_retorno[]" value="<?= e($fila['fecha_retorno'] ?? '') ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
        <?php if ($errorRetorno): ?><span class="hint err"><?= e($errorRetorno) ?></span><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('tiempo_permanencia')): ?>
      <!-- Tiempo de permanencia (A44, pág. 42 del PDF: texto libre, ej. "3 días") -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Tiempo de permanencia</label>
        <div class="control"><input type="text" name="viaje_tiempo_permanencia[]" value="<?= e($fila['tiempo_permanencia'] ?? '') ?>" placeholder="Ej. 3 días…"></div>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('semana_gestacion')): ?>
      <!-- Semana de gestación (columna extra, propia de fichas que la declaren) -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Semana de gestación</label>
        <div class="control mono"><input type="number" min="0" max="45" name="viaje_semana_gestacion[]" value="<?= e($fila['semana_gestacion'] ?? '') ?>"></div>
      </div>
      <?php endif; ?>
      <?php if ($mostrarColViaje('transporte_retorno')): ?>
      <!-- 5. Transporte retorno -->
      <div class="field" style="flex:1; min-width:130px">
        <label class="fl">Transporte retorno</label>
        <div class="control">
          <select name="viaje_transporte_retorno[]">
            <option value="">Seleccionar…</option>
            <option value="AEREO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'AEREO') ?>>Aéreo</option>
            <option value="TERRESTRE" <?= seleccionado($fila['transporte_retorno'] ?? '', 'TERRESTRE') ?>>Terrestre</option>
            <option value="MARITIMO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'MARITIMO') ?>>Marítimo</option>
            <option value="OTRO" <?= seleccionado($fila['transporte_retorno'] ?? '', 'OTRO') ?>>Otro</option>
          </select>
        </div>
      </div>
      <?php endif; ?>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar viaje" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="viajes">
  <?php foreach ($filasViajes as $i => $fila): $filaViaje($fila, $erroresViajes[$i] ?? null, $i); endforeach; ?>
</div>
<template id="plantilla-viajes"><?php $filaViaje(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-viajes" data-lista="viajes" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar viaje
</button>
