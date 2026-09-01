<?php
/**
 * Fila dinámica de "Contactos directos" del caso (caso_contacto_directo).
 * B04X, cotejo 2026-09-01, Sección IV del PDF, ítem 35 -- censo de
 * personas con las que el caso tuvo contacto desde 4 días antes del inicio
 * del sarpullido hasta la caída total de costras. Distinto de
 * tablas-hijas/contactos.php (caso_contacto, ítem 33 "Contacto con caso
 * probable o confirmado de VM"): son dos censos independientes con
 * preguntas gatillo distintas, por eso viven en tablas y parciales
 * separados en vez de compartir uno (ver la nota de la migración
 * add_tabla_caso_contacto_directo.php). Variable esperada:
 * $filasContactosDirectos (array de ['nombres','parentesco','celular','doc',
 * 'grupo_poblacion']).
 *
 * Única ficha que usa este parcial por ahora -- a diferencia de
 * tablas-hijas/contactos.php, no hay $columnasContacto/$muestra() porque no
 * hace falta variar columnas por ficha todavía.
 */
$opcionesGrupoPoblacionContactoDirecto = [
    '1' => 'Gestante',
    '2' => 'Puérpera',
    '3' => 'Recién nacido',
    '4' => 'Niños menores de 8 años',
    '5' => 'Adultos mayores',
    '6' => 'Persona con inmunodepresión (por enfermedad o medicación) o con enfermedad que afecte la integridad de la piel',
];

$filaContactoDirecto = function (
    array $fila = ['nombres' => '', 'parentesco' => '', 'celular' => '', 'doc' => '', 'grupo_poblacion' => []],
    ?int $indice = null
) use ($opcionesGrupoPoblacionContactoDirecto): void {
    // Mismo motivo que contacto_fila_id[] en tablas-hijas/contactos.php:
    // "grupo_poblacion" es multivalor por fila (checklist de 6), así que
    // necesita una clave de array estable (contacto_directo_grupo_poblacion[ID][])
    // en vez de alinearse por posición de envío como las demás columnas.
    $idFila = $indice !== null ? (string) $indice : '__NUEVA_FILA__';
    $grupoFila = is_array($fila['grupo_poblacion'] ?? null)
        ? $fila['grupo_poblacion']
        : array_filter(explode(',', (string) ($fila['grupo_poblacion'] ?? '')));
    ?>
  <div class="subrow">
    <input type="hidden" name="contacto_directo_fila_id[]" value="<?= e($idFila) ?>">
    <div class="fields thirds" style="flex:1">
      <div class="field wide">
        <label class="fl">Apellidos y nombre</label>
        <div class="control"><input type="text" name="contacto_directo_nombres[]" value="<?= e($fila['nombres']) ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Parentesco / vínculo</label>
        <div class="control"><input type="text" name="contacto_directo_parentesco[]" value="<?= e($fila['parentesco'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Celular</label>
        <div class="control mono"><input type="text" name="contacto_directo_celular[]" value="<?= e($fila['celular'] ?? '') ?>"></div>
      </div>
      <div class="field">
        <label class="fl">Documento de identidad</label>
        <div class="control mono"><input type="text" name="contacto_directo_doc[]" value="<?= e($fila['doc'] ?? '') ?>"></div>
      </div>
      <div class="field wide">
        <label class="fl">Grupo de población <span class="hint">(mayor probabilidad de complicación -- selección múltiple)</span></label>
        <div class="chip-select">
          <?php foreach ($opcionesGrupoPoblacionContactoDirecto as $codigoGrupo => $etiquetaGrupo): ?>
          <?php $codigoGrupoStr = (string) $codigoGrupo; // PHP castea claves "1".."6" a int al armar el array ?>
          <label class="chip-option">
            <input type="checkbox" name="contacto_directo_grupo_poblacion[<?= e($idFila) ?>][]" value="<?= e($codigoGrupoStr) ?>" <?= marcado(in_array($codigoGrupoStr, $grupoFila, true)) ?>>
            <span class="chip"><?= e($etiquetaGrupo) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar contacto" style="margin-top:22px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php };
?>
<div class="subrows" data-lista="contactos-directos">
  <?php foreach ($filasContactosDirectos as $i => $fila): $filaContactoDirecto($fila, $i); endforeach; ?>
</div>
<template id="plantilla-contactos-directos"><?php $filaContactoDirecto(); ?></template>
<button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-contactos-directos" data-lista="contactos-directos" style="margin-top:12px">
  <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
  Agregar contacto
</button>
