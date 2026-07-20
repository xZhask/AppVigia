<?php
use App\Core\Csrf;
use App\Models\CatalogoItem;

$etiquetasTipo = [
    'TEXTO' => 'Texto libre', 'TEXTAREA' => 'Texto libre',
    'NUMERO' => 'Número', 'FECHA' => 'Fecha (dd/mm/aaaa)',
    'BOOLEANO' => 'SI / NO', 'SELECT' => 'Un valor del catálogo', 'MULTISELECT' => 'Valores del catálogo separados por coma',
];
?>
<div class="page-head">
  <div>
    <div class="page-title">Importar desde Excel</div>
    <div class="page-desc">Carga varios casos a la vez desde una planilla, en vez de digitarlos uno por uno</div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/casos/importar/lotes">Lotes importados</a>
</div>

<div class="card section">
  <div class="section-head"><span class="section-num">1</span><h3>Elige la enfermedad y descarga la plantilla</h3></div>
  <div class="section-body">
    <form method="get" action="/casos/importar" class="fields thirds">
      <div class="field wide">
        <label class="fl">Enfermedad / evento bajo vigilancia</label>
        <div class="control">
          <select name="enfermedad_id" onchange="this.form.submit()">
            <?php foreach ($enfermedades as $enf): ?>
              <option value="<?= (int) $enf['id'] ?>" <?= seleccionado($enfermedad['id'], $enf['id']) ?>><?= e($enf['nombre']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </form>
    <div style="margin-top:16px">
      <a class="btn btn-primary" href="/casos/importar/plantilla?enfermedad_id=<?= (int) $enfermedad['id'] ?>">
        <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5v7M4 6l3 3 3-3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
        Descargar plantilla (.xlsx)
      </a>
    </div>

    <div class="eyebrow" style="margin:22px 0 10px">Columnas de la plantilla</div>
    <div style="overflow-x:auto">
      <table>
        <thead><tr><th>Columna</th><th>Formato / valores válidos</th><th>Obligatorio</th></tr></thead>
        <tbody>
          <tr><td class="mono">fecha_notif</td><td>Fecha dd/mm/aaaa, no futura</td><td>Sí</td></tr>
          <tr><td class="mono">tipo_doc</td><td>DNI, CE, PTP, PAS u OTRO</td><td>No (por defecto DNI)</td></tr>
          <tr><td class="mono">num_doc</td><td>Si es DNI, exactamente 8 dígitos</td><td>Sí</td></tr>
          <tr><td class="mono">apellido_paterno</td><td>Texto libre</td><td>Sí</td></tr>
          <tr><td class="mono">apellido_materno</td><td>Texto libre</td><td>No</td></tr>
          <tr><td class="mono">nombres</td><td>Texto libre</td><td>Sí</td></tr>
          <tr><td class="mono">sexo</td><td>M o F</td><td>No</td></tr>
          <tr><td class="mono">fecha_nac</td><td>Fecha dd/mm/aaaa</td><td>No</td></tr>
          <tr><td class="mono">ubigeo</td><td>Código INEI de distrito, 6 dígitos</td><td>Sí</td></tr>
          <tr><td class="mono">fecha_inicio_sintomas</td><td>Fecha dd/mm/aaaa</td><td>Sí</td></tr>
          <tr><td class="mono">es_pnp</td><td>SI o NO</td><td>No (por defecto NO)</td></tr>
          <tr><td class="mono">grado</td><td>Abreviatura exacta (Cap., SO1, Cmdte...)</td><td>No</td></tr>
          <tr><td class="mono">situacion_pnp</td><td>ACTIVIDAD, RETIRO o DISPONIBILIDAD</td><td>No</td></tr>
          <tr><td class="mono">cip</td><td>Texto libre</td><td>No</td></tr>
          <tr><td class="mono">unidad</td><td>Nombre exacto de la unidad/dependencia</td><td>No</td></tr>
          <tr><td class="mono">tipo_beneficiario</td><td>TITULAR o DERECHOHABIENTE</td><td>No</td></tr>
          <?php foreach ($camposDinamicos as $campo): ?>
            <tr>
              <td class="mono"><?= e($campo['clave']) ?></td>
              <td>
                <?= $etiquetasTipo[$campo['tipo']] ?>
                <?php if (in_array($campo['tipo'], ['SELECT', 'MULTISELECT'], true) && $campo['catalogo_id']): ?>
                  <div class="cell-sub"><?= e(implode(' · ', array_column(CatalogoItem::porCatalogo((int) $campo['catalogo_id']), 'valor'))) ?></div>
                <?php endif; ?>
              </td>
              <td><?= $campo['obligatorio'] ? 'Sí' : 'No' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="hint" style="margin-top:10px">La fila 2 de la plantilla es un ejemplo: se ignora siempre al importar, sin importar lo que contenga. Tus datos empiezan en la fila 3.</p>
  </div>
</div>

<div class="card section">
  <div class="section-head"><span class="section-num">2</span><h3>Sube el archivo completo</h3></div>
  <div class="section-body">
    <form method="post" action="/casos/importar" enctype="multipart/form-data">
      <?= Csrf::campoOculto() ?>
      <input type="hidden" name="enfermedad_id" value="<?= (int) $enfermedad['id'] ?>">
      <div class="fields thirds">
        <?php if ($puedeElegirEstablecimiento): ?>
          <div class="field">
            <label class="fl">Establecimiento (EESS) <span class="req">*</span></label>
            <div class="control">
              <select name="establecimiento_id" required>
                <option value="">Seleccionar…</option>
                <?php foreach ($establecimientos as $est): ?>
                  <option value="<?= (int) $est['id'] ?>"><?= e($est['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <span class="hint">Todas las filas del archivo se registran bajo este establecimiento</span>
          </div>
        <?php else: ?>
          <div class="field">
            <label class="fl">Establecimiento (EESS)</label>
            <div class="control mono" style="color:var(--muted)"><?= e($establecimientoUsuarioNombre) ?></div>
          </div>
        <?php endif; ?>
        <div class="field wide">
          <label class="fl">Archivo (.xlsx o .csv) <span class="req">*</span></label>
          <div class="control"><input type="file" name="archivo" accept=".xlsx,.csv" required></div>
        </div>
      </div>
      <div class="rail-actions" style="margin-top:16px;max-width:220px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 9.5v-7M4 5.5l3-3 3 3M2 11.5h10" stroke="currentColor" stroke-width="1.4" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Importar
        </button>
      </div>
    </form>
  </div>
</div>
