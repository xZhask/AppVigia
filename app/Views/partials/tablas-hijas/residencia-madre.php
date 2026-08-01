<?php
/**
 * Bloque de identidad/residencia de un sujeto secundario (hoy MADRE en
 * P35.0 y P96) -- PETICION_P35_RUBEOLA_CONGENITA.md Fase 2. Se captura en
 * caso_sujeto, columnas declaradas por columnasSujeto()/tieneSujeto()
 * (app/Core/ayudantes.php), no como campo_def de texto libre. Genérico por
 * columna vía metaColumnasSujeto(): P96 declara solo direccion+distrito_id
 * (residencia), P35.0 declara las 8 columnas de identidad -- mismo partial,
 * mismo orden canónico, ningún `if` por ficha.
 *
 * Variables esperadas:
 *   $rolActual            string, ej. 'MADRE'
 *   $columnasDeclaradas   array de nombres de columna (columnasSujeto())
 *   $tituloBloque         string, encabezado de la tarjeta
 *   $valoresSujetoActual  array [columna => valor], o [] si es ficha nueva
 *
 * El selector de UBIGEO se renderiza en una función aparte (no comparte
 * scope con la vista que incluye este parcial) porque selector-ubigeo.php
 * usa variables locales ($departamentos, $provinciasIniciales, etc.) que ya
 * están definidas -con los datos del domicilio del paciente- en el scope de
 * fichas/editar.php y nueva/index.php; reutilizar esos mismos nombres aquí
 * los pisaría.
 */
$valoresSujetoActual = $valoresSujetoActual ?? [];
$prefijoNombre = mb_strtolower($rolActual);

$meta = metaColumnasSujeto();
$columnasOrdenadas = array_intersect_key($meta, array_flip($columnasDeclaradas));
$columnasSimples = array_filter($columnasOrdenadas, fn($info) => $info['kind'] !== 'ubigeo');
?>
<div class="eyebrow" style="margin:22px 0 10px"><?= e($tituloBloque) ?></div>

<?php if (!empty($columnasSimples)): ?>
<div class="fields thirds" style="margin-bottom:14px">
  <?php foreach ($columnasSimples as $col => $info):
      $nombreCampo = $prefijoNombre . '_' . $col;
      $valor = $valoresSujetoActual[$col] ?? '';
      $claseField = $info['kind'] === 'texto_wide' ? 'field wide' : 'field';
  ?>
    <div class="<?= $claseField ?>">
      <label class="fl"><?= e($info['label']) ?></label>
      <?php if ($info['kind'] === 'tipo_doc'): ?>
        <div class="control">
          <select name="<?= e($nombreCampo) ?>" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <?php foreach (['DNI', 'CE', 'PTP', 'PAS', 'OTRO'] as $tipo): ?>
              <option value="<?= $tipo ?>" <?= seleccionado($valor, $tipo) ?>><?= $tipo ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php elseif ($info['kind'] === 'sexo'): ?>
        <div class="control">
          <select name="<?= e($nombreCampo) ?>" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <option value="M" <?= seleccionado($valor, 'M') ?>>Masculino</option>
            <option value="F" <?= seleccionado($valor, 'F') ?>>Femenino</option>
          </select>
        </div>
      <?php elseif ($info['kind'] === 'numero'): ?>
        <div class="control mono"><input type="number" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>"></div>
      <?php elseif ($info['kind'] === 'fecha'): ?>
        <div class="control mono"><input type="date" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>"></div>
      <?php else: ?>
        <div class="control"><input type="text" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>"></div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (isset($columnasOrdenadas['distrito_id'])): ?>
  <?php
  (function (string $prefijoNombre, array $valoresSujetoActual): void {
      $prefijo = $prefijoNombre . '-ubigeo';
      $nombreCampoDistrito = $prefijoNombre . '_distrito_id';
      $distritoRequerido = false;
      $errorDistrito = null;
      extract(contextoUbigeo($valoresSujetoActual['distrito_id'] ?? null));
      require __DIR__ . '/../selector-ubigeo.php';
  })($prefijoNombre, $valoresSujetoActual);
  ?>
<?php endif; ?>
