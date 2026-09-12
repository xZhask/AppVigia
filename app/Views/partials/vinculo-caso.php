<?php
/**
 * "vinculo_caso" (motor de la ficha Z21, cotejo 2026-09-11, "opción A" del
 * usuario): enlaza este caso con otro caso de la MISMA ficha -- en Z21, la
 * ficha del niño nacido expuesto con la ficha de su madre. Se pinta al
 * inicio de la sección que declara el manifiesto ("seccion"), así hereda su
 * visibilidad: esa sección solo aparece con el valor "activador".
 *
 * Guarda en caso.caso_vinculado_id. CasosController revalida que el caso
 * elegido sea un candidato real y visible para el usuario, y copia a los
 * campos "copiar" los datos de la ficha vinculada; el navegador solo los
 * adelanta y los deja de solo lectura (ver aplicarVinculoCaso() en ficha.js).
 * "Sin vincular" sigue siendo válido: la madre puede no tener ficha en el
 * sistema (notificada en otra IPRESS), y entonces esos campos se escriben a
 * mano, como en el papel.
 *
 * Dos formas de elegir la ficha vinculada:
 *
 *  1. "fijar_por_procedencia": true (Z21, pedido del usuario 2026-09-11) --
 *     NO hay lista de candidatos. O el vínculo viene fijado desde la ficha de
 *     la madre (botón "Registrar niño nacido expuesto"), o se identifica a la
 *     madre por código de ficha / documento EXACTO en el buscador. Nació de un
 *     riesgo real: con el desplegable de todas las gestantes se podía enlazar
 *     al niño con la madre equivocada de un solo clic.
 *  2. Sin esa declaración: el <select> de candidatos de siempre (mecanismo
 *     genérico; hoy ninguna otra ficha declara vinculo_caso).
 *
 * Variables: $vinculoCasoVista (CasosController::datosVinculoCasoVista()).
 */
$cfgVinculoCaso = $vinculoCasoVista['config'];
$seleccionadoVinculoCaso = (int) ($vinculoCasoVista['seleccionado'] ?? 0);
$errorVinculoCaso = $vinculoCasoVista['error'] ?? null;
$porProcedenciaVinculo = !empty($vinculoCasoVista['porProcedencia']);
$datosSeleccionVinculo = $vinculoCasoVista['seleccionadoDatos'] ?? null;
$textosBuscarVinculo = $cfgVinculoCaso['buscar'] ?? [];
$ayudaVinculo = $errorVinculoCaso
    ?: ($porProcedenciaVinculo ? ($textosBuscarVinculo['ayuda'] ?? '') : ($cfgVinculoCaso['ayuda'] ?? ''));
?>
<?php if (!$porProcedenciaVinculo): ?>
  <div class="fields" style="margin-bottom:16px">
    <div class="field wide">
      <label class="fl"><?= e($cfgVinculoCaso['etiqueta']) ?></label>
      <div class="control <?= $errorVinculoCaso ? 'err' : '' ?>">
        <select name="caso_vinculado_id" data-vinculo-caso data-destinos="<?= e(json_encode($vinculoCasoVista['destinos'], JSON_UNESCAPED_UNICODE)) ?>" data-min-width="420">
          <option value=""><?= e($cfgVinculoCaso['opcion_sin_vinculo'] ?? 'Sin vincular') ?></option>
          <?php foreach ($vinculoCasoVista['candidatos'] as $candidatoVinculo): ?>
            <option value="<?= (int) $candidatoVinculo['id'] ?>" data-copiar="<?= e(json_encode($candidatoVinculo['copiar_por_nombre'], JSON_UNESCAPED_UNICODE)) ?>" <?= seleccionado($seleccionadoVinculoCaso, (int) $candidatoVinculo['id']) ?>><?= e($candidatoVinculo['texto']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($errorVinculoCaso): ?>
        <span class="hint err"><?= e($errorVinculoCaso) ?></span>
      <?php elseif (!empty($cfgVinculoCaso['ayuda'])): ?>
        <span class="hint"><?= e($cfgVinculoCaso['ayuda']) ?></span>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="fields" style="margin-bottom:16px">
    <div class="field wide"
         data-vinculo-buscador
         data-enfermedad-id="<?= (int) ($enfermedad['id'] ?? 0) ?>"
         data-caso-id="<?= (int) ($vinculoCasoVista['casoIdActual'] ?? 0) ?>"
         data-destinos="<?= e(json_encode($vinculoCasoVista['destinos'], JSON_UNESCAPED_UNICODE)) ?>"
         data-no-encontrada="<?= e($textosBuscarVinculo['no_encontrada'] ?? 'No se encontró una ficha con ese código o documento.') ?>">
      <label class="fl"><?= e($textosBuscarVinculo['etiqueta'] ?? $cfgVinculoCaso['etiqueta']) ?></label>

      <?php // Ficha ya vinculada: se muestra, no se elige. ?>
      <div class="control" data-vinculo-fijado style="<?= $datosSeleccionVinculo ? '' : 'display:none' ?>">
        <span class="mono" data-vinculo-texto style="font-size:12.5px"><?= e($datosSeleccionVinculo['texto'] ?? '') ?></span>
      </div>

      <?php // Sin vínculo todavía: identificar a la madre por código o documento. ?>
      <div class="control <?= $errorVinculoCaso ? 'err' : '' ?>" data-vinculo-busqueda style="<?= $datosSeleccionVinculo ? 'display:none' : '' ?>">
        <input type="text" data-vinculo-consulta autocomplete="off"
               placeholder="<?= e($textosBuscarVinculo['placeholder'] ?? 'Código de ficha o documento') ?>">
      </div>

      <input type="hidden" name="caso_vinculado_id" data-vinculo-id
             value="<?= $datosSeleccionVinculo ? (int) $datosSeleccionVinculo['id'] : '' ?>"
             data-copiar="<?= e(json_encode($datosSeleccionVinculo['copiar_por_nombre'] ?? [], JSON_UNESCAPED_UNICODE)) ?>">

      <div style="display:flex;gap:8px;margin-top:8px">
        <button class="btn btn-ghost" type="button" data-vinculo-accion="buscar"
                style="<?= $datosSeleccionVinculo ? 'display:none' : '' ?>"><?= e($textosBuscarVinculo['accion'] ?? 'Buscar') ?></button>
        <button class="btn btn-ghost" type="button" data-vinculo-accion="quitar"
                style="<?= $datosSeleccionVinculo ? '' : 'display:none' ?>"><?= e($textosBuscarVinculo['quitar'] ?? 'Quitar vínculo') ?></button>
      </div>

      <span class="hint <?= $errorVinculoCaso ? 'err' : '' ?>" data-vinculo-hint><?= e($ayudaVinculo) ?></span>
    </div>
  </div>
<?php endif; ?>
