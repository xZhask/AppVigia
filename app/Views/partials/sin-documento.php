<?php
/**
 * nucleo_ajustes.sin_documento (P96, muerte fetal y neonatal, 2026-09-13):
 * casilla "Sin documento de identidad" junto al documento, para una persona
 * que no lo tiene (un óbito fetal nunca tiene DNI). Marcada, ficha.js
 * deshabilita el tipo y el número (no viajan en el POST) y
 * CasosController::crear() guarda tipo_doc = SIN_DOCUMENTO con num_doc NULL.
 * Solo se pinta en las fichas que declaran el ajuste.
 *
 * Por rama (A50, 2026-09-14): con nucleo_condicional.ajustes.sin_documento la
 * casilla solo se ve en esa rama (el producto de la gestación) y ficha.js la
 * desmarca al cambiar a otra.
 *
 * Variables: $valoresFijos, $enfermedad, $valoresCampos.
 */
$sinDocumentoMarcado = ($valoresFijos['tipo_doc'] ?? '') === 'SIN_DOCUMENTO';
$reglasSinDocumento = reglasAjusteNucleo($enfermedad, 'sin_documento');
$atributosSinDocumento = '';
if ($reglasSinDocumento) {
    $valoresSinDocumento = array_merge(...array_column($reglasSinDocumento, 'valores'));
    $atributosSinDocumento = atributosRama(
        $reglasSinDocumento[0]['campo_id'],
        $valoresSinDocumento,
        false,
        in_array((string) (($valoresCampos ?? [])[$reglasSinDocumento[0]['campo_id']] ?? ''), $valoresSinDocumento, true)
    );
}
?>
<div class="field"<?= $atributosSinDocumento ?> style="flex:0 0 auto;align-self:center">
  <label class="sym" style="margin-top:18px"><input type="checkbox" id="sinDocumento" name="sin_documento" value="1" <?= $sinDocumentoMarcado ? 'checked' : '' ?>> Sin documento de identidad</label>
</div>
