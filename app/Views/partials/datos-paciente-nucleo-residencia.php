<?php
/**
 * Campos núcleo de "Datos del persona" relacionados a residencia: se
 * requiere DESPUÉS del selector de Departamento/Provincia/Distrito
 * ("Residencia habitual", pac-ubigeo) en el shell (nueva/index.php,
 * fichas/editar.php), para que ese trío quede junto con el resto de datos
 * de residencia -- Nacionalidad, Localidad, Domicilio actual, Referencia
 * para localizar, Tiempo de residencia y Tipo de localidad (B05).
 *
 * Extraído de datos-paciente-nucleo.php (2026-08-29, pedido del usuario,
 * cotejo B04X): antes estos campos estaban intercalados entre datos
 * genuinamente personales (Edad, Etnia, Gestante...), mezclando dos
 * conceptos distintos dentro del mismo núcleo compartido.
 *
 * Depende de variables/closures que ya están en scope porque
 * datos-paciente-nucleo.php se requiere ANTES en el mismo request (mismo
 * scope de función: los require anidados de PHP comparten variables locales
 * con quien los llama) -- $valoresFijos, $nucleoOmite, $detalleDomicilio,
 * $tieneDetalleDomicilio, $esB05, $b05. No recalcula nada de eso.
 */
?>
<!-- 1. Nacionalidad, Localidad, Domicilio actual, Referencia para localizar -->
<div class="fields thirds" style="margin-top:14px">
  <div class="field" data-nucleo-campo="nacionalidad" <?= $nucleoOmite('nacionalidad') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Nacionalidad</label>
    <div class="control"><input type="text" name="nacionalidad" value="<?= e($valoresFijos['nacionalidad'] ?? '') ?>"></div>
  </div>
  <div class="field" data-nucleo-campo="localidad" <?= $nucleoOmite('localidad') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Localidad</label>
    <div class="control"><input type="text" name="localidad" value="<?= e($valoresFijos['localidad'] ?? '') ?>"></div>
  </div>
  <div class="field wide" data-nucleo-campo="direccion" <?= $nucleoOmite('direccion') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Domicilio actual</label>
    <div class="control"><input type="text" name="direccion" value="<?= e($valoresFijos['direccion'] ?? '') ?>"></div>
  </div>
  <div class="field wide" data-nucleo-campo="referencia_localizar" <?= $nucleoOmite('referencia_localizar') ? 'hidden style="display:none;"' : '' ?>>
    <label class="fl">Referencia para localizar <span class="hint">(a la altura de o cerca de: Iglesia, fundo, comercio, etc.)</span></label>
    <div class="control"><input type="text" name="referencia_localizar" value="<?= e($valoresFijos['referencia_localizar'] ?? '') ?>" placeholder="Referencia para localizar…"></div>
  </div>
</div>

<!-- 1b. Detalle de domicilio (Entrada J acotada, opt-in vía detalle_domicilio).
     Extraído a un partial reusable (detalle-domicilio-campos.php) porque el
     cotejo de B57 necesita el mismo bloque de 6 sub-campos otra vez para
     "Domicilio anterior" (tarjeta propia "Migración", migracion-b57.php)
     -- mismo criterio de "hidden" (no if de PHP) documentado ahí. -->
<?php
$valoresDetalleActual = [
    'tipo_zona'   => $valoresFijos['tipo_zona'] ?? '',
    'nombre_zona' => $valoresFijos['nombre_zona'] ?? '',
    'tipo_via'    => $valoresFijos['tipo_via'] ?? '',
    'nombre_via'  => $valoresFijos['nombre_via'] ?? '',
    'numero'      => $valoresFijos['numero'] ?? '',
    'mz_lote'     => $valoresFijos['mz_lote'] ?? '',
];
(function (string $prefijoNombreCampo, array $valoresDetalle, array $detalleDomicilioPermitido) {
    require __DIR__ . '/detalle-domicilio-campos.php';
})('', $valoresDetalleActual, $detalleDomicilio);
?>
<div class="fields thirds" style="margin-top:14px">
  <div class="field" data-detalle-domicilio-campo="TIEMPO_RESIDENCIA" <?= $tieneDetalleDomicilio('TIEMPO_RESIDENCIA') ? '' : 'hidden style="display:none;"' ?>>
    <label class="fl">Tiempo de residencia</label>
    <div class="control"><input type="text" name="tiempo_residencia" value="<?= e($valoresFijos['tiempo_residencia'] ?? '') ?>"></div>
  </div>
</div>

<!-- 2. Debajo de Domicilio actual / Referencia para localizar: Tipo de localidad (B05) -->
<div class="b05-field-wrap" <?= $esB05 ? '' : 'hidden' ?> style="margin-top:14px">
  <?php if ($b05['tipoLocalidad']['name']): ?>
  <div class="fields thirds">
    <div class="field">
      <label class="fl">Tipo de localidad</label>
      <div class="control">
        <select name="<?= $b05['tipoLocalidad']['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach ($b05['tipoLocalidad']['opciones'] as $op): ?>
            <option value="<?= e($op['valor']) ?>" <?= seleccionado($b05['tipoLocalidad']['val'], $op['valor']) ?>><?= e($op['etiqueta']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
