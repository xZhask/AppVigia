<?php
use App\Core\Csrf;

$enfermedadesPorGrupo = [];
foreach ($enfermedades as $enf) {
    $enfermedadesPorGrupo[$enf['familia'] ?? 'Otros eventos bajo vigilancia'][] = $enf;
}

$establecimientoNombreInicial = $establecimientoUsuarioNombre;
if ($puedeElegirEstablecimiento) {
    foreach ($establecimientos as $est) {
        if ((string) $est['id'] === (string) $valoresFijos['establecimiento_id']) {
            $establecimientoNombreInicial = $est['nombre'];
            break;
        }
    }
}
?>
<div class="page-head">
  <div>
    <div class="page-title">Nueva ficha de notificación</div>
    <div class="page-desc">El formulario se ajusta a la ficha modelo MINSA de la enfermedad seleccionada</div>
  </div>
</div>

<form method="post" action="/casos/nuevo">
  <?= Csrf::campoOculto() ?>

  <div class="disease-pick">
    <div class="ic"><svg width="19" height="19" viewBox="0 0 19 19"><circle cx="9.5" cy="9.5" r="7" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="M9.5 6v7M6 9.5h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
    <div>
      <div class="eyebrow" style="margin-bottom:2px">Enfermedad / evento bajo vigilancia</div>
      <select class="disease" id="diseaseSel" name="enfermedad_id" data-min-width="420">
        <?php foreach ($enfermedadesPorGrupo as $familia => $lista): ?>
          <optgroup label="<?= e($familia) ?>">
            <?php foreach ($lista as $enf): ?>
              <?php $disabled = (int) $enf['tiene_definicion'] === 0 ? ' disabled' : ''; ?>
              <option value="<?= (int) $enf['id'] ?>" data-cie10="<?= e($enf['cie10'] ?? '') ?>" data-claves="<?= e($enf['palabras_clave'] ?? '') ?>" data-tipo-notif="<?= e($enf['tipo_notif']) ?>" data-nombre-corto="<?= e($enf['nombre_corto'] ?: $enf['nombre']) ?>" <?= seleccionado($enfermedad['id'], $enf['id']) ?><?= $disabled ?>><?= e($enf['nombre_corto'] ?: $enf['nombre']) ?></option>
            <?php endforeach; ?>
          </optgroup>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="disease-meta">
      <span class="tag" id="cieTag">CIE-10 · <?= e($enfermedad['cie10'] ?? '—') ?></span>
      <span class="tag" id="tipoNotifTag"><?= $enfermedad['tipo_notif'] === 'INMEDIATA' ? 'Notificación inmediata' : 'Notificación semanal' ?></span>
    </div>
  </div>

  <div class="grid form-grid">
    <div>
      <div id="dupeSlot"></div>

      <!-- 1. Notificación -->
      <div class="card section">
        <div class="section-head">
          <span class="section-num">1</span><h3>Notificación</h3>
          <span style="margin-left:auto;display:flex;align-items:center;gap:6px">
            <span class="eyebrow">SE de la ficha</span>
            <span class="tag mono" id="seBadge" title="Se recalcula al guardar, según la fecha de notificación">SE <?= $semanaEpiPreview ?> · <?= $anioEpiPreview ?></span>
          </span>
        </div>
        <div class="section-body">
          <div class="fields thirds">
            <?php if ($puedeElegirEstablecimiento): ?>
              <div class="field" style="grid-column: span 2">
                <label class="fl">Establecimiento (EESS) <span class="req">*</span></label>
                <div class="control <?= isset($erroresFijos['establecimiento_id']) ? 'err' : '' ?>">
                  <svg class="lead" width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5 2 4v7h10V4L7 1.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                  <select name="establecimiento_id" data-min-width="420">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($establecimientos as $est): ?>
                      <option value="<?= (int) $est['id'] ?>" <?= seleccionado($valoresFijos['establecimiento_id'], $est['id']) ?>><?= e($est['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <?php if (isset($erroresFijos['establecimiento_id'])): ?><span class="hint err"><?= e($erroresFijos['establecimiento_id']) ?></span><?php else: ?><span class="hint">Del padrón RENIPRESS</span><?php endif; ?>
              </div>
            <?php else: ?>
              <input type="hidden" name="establecimiento_id" value="<?= (int) $valoresFijos['establecimiento_id'] ?>">
            <?php endif; ?>
            <div class="field">
              <label class="fl">Fecha de notificación <span class="req">*</span></label>
              <div class="control mono <?= isset($erroresFijos['fecha_notif']) ? 'err' : '' ?>">
                <input type="date" id="fechaNotif" name="fecha_notif" value="<?= e($valoresFijos['fecha_notif']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
              </div>
              <?php if (isset($erroresFijos['fecha_notif'])): ?>
                <span class="hint err"><?= e($erroresFijos['fecha_notif']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <?php require __DIR__ . '/../partials/notificacion-captacion.php'; ?>
        </div>
      </div>

      <!-- 2. persona -->
      <div class="card section">
        <div class="section-head"><span class="section-num">2</span><h3>Datos del persona</h3></div>
        <div class="section-body">
          <div class="lookup">
            <div class="field" style="flex:0 0 200px">
              <label class="fl">Documento de identidad <span class="req">*</span></label>
              <div class="control input-group doc-id-group">
                <div class="addon" style="width: 75px;">
                  <select id="tipoDoc" name="tipo_doc" data-nosearch="true" data-min-width="75">
                    <?php foreach (['DNI', 'CE', 'PTP', 'PAS', 'OTRO'] as $tipo): ?>
                      <option value="<?= $tipo ?>" <?= seleccionado($valoresFijos['tipo_doc'], $tipo) ?>><?= $tipo ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="input-main mono <?= isset($erroresFijos['num_doc']) ? 'err' : '' ?>">
                  <input type="text" id="numDoc" name="num_doc" value="<?= e($valoresFijos['num_doc']) ?>" placeholder="76540319">
                </div>
              </div>
              <?php if (isset($erroresFijos['num_doc'])): ?><span class="hint err"><?= e($erroresFijos['num_doc']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Sexo</label>
              <div class="control">
                <select id="sexo" name="sexo" data-nosearch="true">
                  <option value="">Seleccionar…</option>
                  <option value="F" <?= seleccionado($valoresFijos['sexo'], 'F') ?>>Femenino</option>
                  <option value="M" <?= seleccionado($valoresFijos['sexo'], 'M') ?>>Masculino</option>
                </select>
              </div>
            </div>
            <div class="field">
              <label class="fl">Fecha de nacimiento</label>
              <div style="display:flex;gap:8px;align-items:center">
                <div class="control mono <?= isset($erroresFijos['fecha_nac']) ? 'err' : '' ?>" style="flex:1">
                  <input type="date" id="fechaNac" name="fecha_nac" value="<?= e($valoresFijos['fecha_nac']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
                </div>
                <span class="tag" id="edadCalculada">—</span>
              </div>
              <?php if (isset($erroresFijos['fecha_nac'])): ?><span class="hint err"><?= e($erroresFijos['fecha_nac']) ?></span><?php endif; ?>
            </div>
          </div>
          <span class="hint" id="buscandopersonaHint" hidden>Consultando padrón y RENIEC…</span>
          <div class="found" id="found" style="display:none">
            <div class="pa" id="foundIniciales"></div>
            <div><div class="pn" id="foundNombre"></div><div class="pd" id="foundDetalle"></div></div>
            <div class="src"><svg width="13" height="13" viewBox="0 0 13 13"><path d="M2.5 6.5 5.5 9.5l5-6" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg> <span id="foundFuente">Autocompletado del padrón</span></div>
          </div>

          <div class="fields thirds" style="margin-top:16px">
            <div class="field">
              <label class="fl">Apellido paterno <span class="req">*</span></label>
              <div class="control <?= isset($erroresFijos['apellido_paterno']) ? 'err' : '' ?>">
                <input type="text" id="apellidoPaterno" name="apellido_paterno" value="<?= e($valoresFijos['apellido_paterno']) ?>">
              </div>
              <?php if (isset($erroresFijos['apellido_paterno'])): ?><span class="hint err"><?= e($erroresFijos['apellido_paterno']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Apellido materno</label>
              <div class="control">
                <input type="text" id="apellidoMaterno" name="apellido_materno" value="<?= e($valoresFijos['apellido_materno']) ?>">
              </div>
            </div>
            <div class="field">
              <label class="fl">Nombres <span class="req">*</span></label>
              <div class="control <?= isset($erroresFijos['nombres']) ? 'err' : '' ?>">
                <input type="text" id="nombres" name="nombres" value="<?= e($valoresFijos['nombres']) ?>">
              </div>
              <?php if (isset($erroresFijos['nombres'])): ?><span class="hint err"><?= e($erroresFijos['nombres']) ?></span><?php endif; ?>
            </div>
          </div>
          <div style="margin-top:14px">
            <?php $prefijo = 'pac-ubigeo'; $errorDistrito = $erroresFijos['distrito_id'] ?? null; require __DIR__ . '/../partials/selector-ubigeo.php'; ?>
          </div>

          <?php require __DIR__ . '/../partials/datos-paciente-nucleo.php'; ?>

          <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">
            <?php require __DIR__ . '/../partials/condicion-paciente.php'; ?>
          </div>
        </div>
      </div>

      <div id="secciones-clinicas">
        <?php
        $numeroSeccionInicial = 3;
        require __DIR__ . '/../partials/secciones-clinicas.php';
        ?>
      </div>

      <!-- 4. Antecedentes epidemiológicos -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Antecedentes epidemiológicos</h3></div>
        <div class="section-body">
          <?php if ((int)($enfermedad['usa_contactos'] ?? 0) === 1): ?>
            <div class="eyebrow" style="margin-bottom:10px">Contactos</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/contactos.php'; ?>
          <?php endif; ?>

          <?php if ((int)($enfermedad['usa_viajes'] ?? 0) === 1): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Viajes</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/viajes.php'; ?>
          <?php endif; ?>

          <?php if ((int)($enfermedad['usa_vacunas'] ?? 0) === 1): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Antecedentes vacunales</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/vacunas.php'; ?>
          <?php endif; ?>

          <?php if ((int)($enfermedad['usa_lugar_infeccion'] ?? 0) === 1): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Lugar probable de infección</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/lugar-infeccion.php'; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php $numeroSeccion++; ?>

      <!-- 5. Laboratorio -->
      <?php if ((int)($enfermedad['usa_muestras'] ?? 0) === 1): ?>
        <div class="card section">
          <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
          <div class="section-body">
            <?php require __DIR__ . '/../partials/tablas-hijas/muestras.php'; ?>
          </div>
        </div>
        <?php $numeroSeccion++; ?>
      <?php endif; ?>

      <!-- Investigador -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Investigador</h3></div>
        <div class="section-body">
          <?php require __DIR__ . '/../partials/investigador.php'; ?>
        </div>
      </div>
      <?php $numeroSeccion++; ?>

      <!-- Clasificación del caso -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Clasificación del caso</h3></div>
        <div class="section-body">
          <?php require __DIR__ . '/../partials/clasificacion-chips.php'; ?>
        </div>
      </div>
    </div>

    <!-- Right rail -->
    <aside class="rail">
      <div class="card rail-card">
        <div class="eyebrow" style="margin-bottom:12px">Avance de la ficha</div>
        <div class="progress" id="progresoFicha"></div>
      </div>
      <div class="card rail-card">
        <h4>Datos limpios desde el origen</h4>
        <p>Distritos, establecimientos y campos clínicos vienen de catálogos oficiales. La ficha se valida en el servidor antes de guardar: obligatorios, fechas y valores de catálogo.</p>
      </div>
      <div class="rail-actions">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Registrar ficha
        </button>
        <button class="btn btn-ghost" type="button" disabled title="Disponible en una próxima fase">Guardar borrador</button>
      </div>
      <div class="card rail-card">
        <div class="eyebrow" style="margin-bottom:10px">Resumen de la ficha</div>
        <div style="display:grid;gap:8px;font-size:12.5px">
          <div style="display:flex;justify-content:space-between;gap:8px">
            <span style="color:var(--muted)">Enfermedad</span>
            <span id="resumenEnfermedad" style="font-weight:600;text-align:right"><?= e($enfermedad['nombre_corto'] ?: $enfermedad['nombre']) ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;gap:8px">
            <span style="color:var(--muted)">Establecimiento</span>
            <span id="resumenEstablecimiento" style="font-weight:600;text-align:right"><?= e($establecimientoNombreInicial ? capitalizarNombre($establecimientoNombreInicial) : '—') ?></span>
          </div>
          <div style="display:flex;justify-content:space-between;gap:8px">
            <span style="color:var(--muted)">Notificación</span>
            <span id="resumenTipoNotif" style="font-weight:600;text-align:right"><?= $enfermedad['tipo_notif'] === 'INMEDIATA' ? 'Inmediata' : 'Semanal' ?></span>
          </div>
        </div>
      </div>
    </aside>
  </div>
</form>
