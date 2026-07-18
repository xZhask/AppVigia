<?php
use App\Core\Csrf;
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
      <select class="disease" id="diseaseSel" name="enfermedad_id">
        <?php foreach ($enfermedades as $enf): ?>
          <option value="<?= (int) $enf['id'] ?>" <?= seleccionado($enfermedad['id'], $enf['id']) ?>><?= e($enf['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="disease-meta">
      <span class="tag" id="cieTag">CIE-10 · <?= e($enfermedad['cie10'] ?? '—') ?></span>
      <span class="tag"><?= $enfermedad['tipo_notif'] === 'INMEDIATA' ? 'Notificación inmediata' : 'Notificación semanal' ?></span>
    </div>
  </div>

  <div class="grid form-grid">
    <div>
      <!-- 1. Notificación -->
      <div class="card section">
        <div class="section-head"><span class="section-num">1</span><h3>Notificación</h3></div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field">
              <label class="fl">DIRESA / DIRIS</label>
              <div class="control"><select disabled><option>DIRSAPOL — Sanidad PNP</option></select></div>
            </div>
            <div class="field">
              <label class="fl">Establecimiento (EESS) <span class="req">*</span></label>
              <?php if ($puedeElegirEstablecimiento): ?>
                <div class="control <?= isset($erroresFijos['establecimiento_id']) ? 'err' : '' ?>">
                  <svg class="lead" width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5 2 4v7h10V4L7 1.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                  <select name="establecimiento_id">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($establecimientos as $est): ?>
                      <option value="<?= (int) $est['id'] ?>" <?= seleccionado($valoresFijos['establecimiento_id'], $est['id']) ?>><?= e($est['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php else: ?>
                <div class="control mono" style="color:var(--muted)">
                  <svg class="lead" width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5 2 4v7h10V4L7 1.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                  <?= e($establecimientoUsuarioNombre) ?>
                </div>
                <input type="hidden" name="establecimiento_id" value="<?= (int) $valoresFijos['establecimiento_id'] ?>">
              <?php endif; ?>
              <?php if (isset($erroresFijos['establecimiento_id'])): ?><span class="hint err"><?= e($erroresFijos['establecimiento_id']) ?></span><?php else: ?><span class="hint">Del padrón RENIPRESS</span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Fecha de notificación <span class="req">*</span></label>
              <div class="control mono <?= isset($erroresFijos['fecha_notif']) ? 'err' : '' ?>">
                <input type="text" name="fecha_notif" value="<?= e($valoresFijos['fecha_notif']) ?>" placeholder="dd/mm/aaaa">
              </div>
              <?php if (isset($erroresFijos['fecha_notif'])): ?><span class="hint err"><?= e($erroresFijos['fecha_notif']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Semana epidemiológica</label>
              <div class="control mono"><input value="SE <?= $semanaEpiPreview ?> · <?= $anioEpiPreview ?>" readonly style="color:var(--muted)"></div>
              <span class="hint">Se recalcula al guardar, según la fecha de notificación</span>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Paciente -->
      <div class="card section">
        <div class="section-head"><span class="section-num">2</span><h3>Datos del paciente</h3></div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field">
              <label class="fl">Tipo de documento</label>
              <div class="control">
                <select name="tipo_doc">
                  <?php foreach (['DNI', 'CE', 'PTP', 'PAS', 'OTRO'] as $tipo): ?>
                    <option value="<?= $tipo ?>" <?= seleccionado($valoresFijos['tipo_doc'], $tipo) ?>><?= $tipo ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="field">
              <label class="fl">N.° de documento <span class="req">*</span></label>
              <div class="control mono <?= isset($erroresFijos['num_doc']) ? 'err' : '' ?>">
                <input type="text" name="num_doc" value="<?= e($valoresFijos['num_doc']) ?>" placeholder="76540319">
              </div>
              <?php if (isset($erroresFijos['num_doc'])): ?><span class="hint err"><?= e($erroresFijos['num_doc']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="fields thirds" style="margin-top:14px">
            <div class="field wide">
              <label class="fl">Apellidos y nombres <span class="req">*</span></label>
              <div class="control <?= isset($erroresFijos['apellidos_nombres']) ? 'err' : '' ?>">
                <input type="text" name="apellidos_nombres" value="<?= e($valoresFijos['apellidos_nombres']) ?>">
              </div>
              <?php if (isset($erroresFijos['apellidos_nombres'])): ?><span class="hint err"><?= e($erroresFijos['apellidos_nombres']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Sexo</label>
              <div class="control">
                <select name="sexo">
                  <option value="">Seleccionar…</option>
                  <option value="F" <?= seleccionado($valoresFijos['sexo'], 'F') ?>>Femenino</option>
                  <option value="M" <?= seleccionado($valoresFijos['sexo'], 'M') ?>>Masculino</option>
                </select>
              </div>
            </div>
            <div class="field">
              <label class="fl">Fecha de nacimiento</label>
              <div class="control mono <?= isset($erroresFijos['fecha_nac']) ? 'err' : '' ?>">
                <input type="text" id="fechaNac" name="fecha_nac" value="<?= e($valoresFijos['fecha_nac']) ?>" placeholder="dd/mm/aaaa">
              </div>
              <?php if (isset($erroresFijos['fecha_nac'])): ?><span class="hint err"><?= e($erroresFijos['fecha_nac']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Edad</label>
              <div class="control mono"><input id="edadCalculada" value="" readonly style="color:var(--muted)"></div>
            </div>
          </div>
          <div class="fields thirds" style="margin-top:14px">
            <?php $prefijo = 'pac-ubigeo'; $errorDistrito = $erroresFijos['distrito_id'] ?? null; require __DIR__ . '/../partials/selector-ubigeo.php'; ?>
          </div>
        </div>
      </div>

      <div id="secciones-clinicas">
        <?php
        $numeroSeccionInicial = 3;
        require __DIR__ . '/../partials/secciones-clinicas.php';
        require __DIR__ . '/../partials/secciones-placeholder.php';
        ?>
      </div>
    </div>

    <!-- Right rail -->
    <aside class="rail">
      <div class="card rail-card">
        <div class="eyebrow" style="margin-bottom:12px">Avance de la ficha</div>
        <div class="progress">
          <div class="pstep"><span class="pd"></span> Notificación</div>
          <div class="pstep"><span class="pd"></span> Paciente</div>
          <div class="pstep cur"><span class="pd"></span> Cuadro clínico</div>
          <div class="pstep"><span class="pd"></span> Epidemiológicos</div>
          <div class="pstep"><span class="pd"></span> Laboratorio</div>
          <div class="pstep"><span class="pd"></span> Clasificación</div>
        </div>
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
    </aside>
  </div>
</form>
