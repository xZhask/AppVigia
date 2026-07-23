<?php
/**
 * Residencia habitual de la madre, solo para Muerte fetal y neonatal (P96)
 * -- PENDIENTES_POST_FASE5.md punto 4. Se captura en caso_sujeto (rol
 * MADRE: distrito_id + direccion), no como campo_def de texto libre.
 *
 * Variable esperada: $sujetoMadre (array con 'distrito_id'/'direccion', o
 * [] si la ficha es nueva / la madre no tiene residencia registrada aún).
 *
 * El selector de UBIGEO se renderiza en una función aparte (no comparte
 * scope con la vista que incluye este parcial) porque selector-ubigeo.php
 * usa variables locales ($departamentos, $provinciasIniciales, etc.) que
 * ya están definidas -con los datos del domicilio del paciente- en el
 * scope de fichas/editar.php y nueva/index.php; reutilizar esos mismos
 * nombres aquí los pisaría.
 */
$sujetoMadre = $sujetoMadre ?? [];
?>
<div class="eyebrow" style="margin:22px 0 10px">Residencia habitual de la madre</div>
<div class="fields thirds" style="margin-bottom:14px">
  <div class="field wide">
    <label class="fl">Dirección</label>
    <div class="control"><input type="text" name="madre_direccion" value="<?= e($sujetoMadre['direccion'] ?? '') ?>"></div>
  </div>
</div>
<?php
(function (array $sujetoMadre): void {
    $prefijo = 'madre-ubigeo';
    $nombreCampoDistrito = 'madre_distrito_id';
    $distritoRequerido = false;
    $errorDistrito = null;
    extract(contextoUbigeo($sujetoMadre['distrito_id'] ?? null));
    require __DIR__ . '/../selector-ubigeo.php';
})($sujetoMadre);
?>
