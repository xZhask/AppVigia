<?php
use App\Core\Csrf;

$estados = [
    'ABIERTA'    => ['dot' => 'st-open',   'etiqueta' => 'Abierta'],
    'VALIDACION' => ['dot' => 'st-val',    'etiqueta' => 'Validación'],
    'CERRADA'    => ['dot' => 'st-closed', 'etiqueta' => 'Cerrada'],
];
$es = $estados[$caso['estado']];
?>
<div class="page-head">
  <div>
    <div class="page-title">Editar ficha <span class="mono"><?= e($caso['codigo']) ?></span></div>
    <div class="page-desc"><?= e($caso['enfermedad_nombre']) ?> · <?= e($caso['establecimiento_nombre']) ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/casos/<?= (int) $caso['id'] ?>">Cancelar</a>
</div>

<form method="post" action="/casos/<?= (int) $caso['id'] ?>">
  <?= Csrf::campoOculto() ?>

  <div class="disease-pick">
    <div class="ic"><svg width="19" height="19" viewBox="0 0 19 19"><circle cx="9.5" cy="9.5" r="7" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="M9.5 6v7M6 9.5h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
    <div>
      <div class="eyebrow" style="margin-bottom:2px">Enfermedad / evento bajo vigilancia</div>
      <div style="font-weight:600;font-size:14.5px"><?= e($caso['enfermedad_nombre']) ?></div>
    </div>
    <div class="disease-meta">
      <span class="tag">CIE-10 · <?= e($caso['cie10'] ?? '—') ?></span>
      <span class="tag"><span class="state"><span class="dot <?= $es['dot'] ?>"></span> <?= $es['etiqueta'] ?></span></span>
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
              <label class="fl">Establecimiento (EESS)</label>
              <div class="control mono" style="color:var(--muted)">
                <svg class="lead" width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5 2 4v7h10V4L7 1.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                <?= e($caso['establecimiento_nombre']) ?>
              </div>
              <span class="hint">No editable: crea una nueva ficha si cambió de establecimiento</span>
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
              <div class="control mono"><input value="SE <?= (int) $caso['semana_epi'] ?> · <?= (int) $caso['anio_epi'] ?>" readonly style="color:var(--muted)"></div>
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
              <label class="fl">Documento</label>
              <div class="control mono" style="color:var(--muted)"><?= e($valoresFijos['tipo_doc']) ?> <?= e($valoresFijos['num_doc']) ?></div>
              <span class="hint">No editable: es la identidad del paciente</span>
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
          </div>
          <div class="fields thirds" style="margin-top:14px">
            <?php $prefijo = 'pac-ubigeo'; $errorDistrito = $erroresFijos['distrito_id'] ?? null; require __DIR__ . '/../partials/selector-ubigeo.php'; ?>
          </div>

          <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--line)">
            <label class="sym" style="padding:0 0 10px">
              <input type="checkbox" id="esPnp" name="es_pnp" <?= marcado($esPnp) ?>>
              Es efectivo PNP
            </label>
            <div class="fields thirds" id="pnpFields" <?= $esPnp ? '' : 'hidden' ?>>
              <div class="field">
                <label class="fl">Grado</label>
                <div class="control">
                  <select name="grado_id">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($grados as $grado): ?>
                      <option value="<?= (int) $grado['id'] ?>" <?= seleccionado($valoresPnp['grado_id'], $grado['id']) ?>><?= e($grado['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="field">
                <label class="fl">Situación</label>
                <div class="control">
                  <select name="situacion_pnp">
                    <option value="">Seleccionar…</option>
                    <option value="ACTIVIDAD" <?= seleccionado($valoresPnp['situacion_pnp'], 'ACTIVIDAD') ?>>Actividad</option>
                    <option value="RETIRO" <?= seleccionado($valoresPnp['situacion_pnp'], 'RETIRO') ?>>Retiro</option>
                    <option value="DISPONIBILIDAD" <?= seleccionado($valoresPnp['situacion_pnp'], 'DISPONIBILIDAD') ?>>Disponibilidad</option>
                  </select>
                </div>
              </div>
              <div class="field">
                <label class="fl">CIP</label>
                <div class="control mono"><input type="text" name="cip" value="<?= e($valoresPnp['cip']) ?>"></div>
              </div>
              <div class="field wide">
                <label class="fl">Unidad / dependencia</label>
                <div class="control">
                  <select name="unidad_id">
                    <option value="">Seleccionar…</option>
                    <?php foreach ($unidades as $unidad): ?>
                      <option value="<?= (int) $unidad['id'] ?>" <?= seleccionado($valoresPnp['unidad_id'], $unidad['id']) ?>><?= e($unidad['nombre']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="field">
                <label class="fl">Tipo de beneficiario</label>
                <div class="control">
                  <select name="tipo_beneficiario">
                    <option value="">Seleccionar…</option>
                    <option value="TITULAR" <?= seleccionado($valoresPnp['tipo_beneficiario'], 'TITULAR') ?>>Titular</option>
                    <option value="DERECHOHABIENTE" <?= seleccionado($valoresPnp['tipo_beneficiario'], 'DERECHOHABIENTE') ?>>Derechohabiente</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <?php
      $numeroSeccionInicial = 3;
      require __DIR__ . '/../partials/secciones-clinicas.php';
      ?>

      <!-- Antecedentes epidemiológicos -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Antecedentes epidemiológicos</h3></div>
        <div class="section-body">
          <div class="eyebrow" style="margin-bottom:10px">Contactos</div>
          <?php require __DIR__ . '/../partials/tablas-hijas/contactos.php'; ?>
          <div class="eyebrow" style="margin:22px 0 10px">Viajes</div>
          <?php require __DIR__ . '/../partials/tablas-hijas/viajes.php'; ?>
          <div class="eyebrow" style="margin:22px 0 10px">Antecedentes vacunales</div>
          <?php require __DIR__ . '/../partials/tablas-hijas/vacunas.php'; ?>
        </div>
      </div>
      <?php $numeroSeccion++; ?>

      <!-- Laboratorio -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
        <div class="section-body">
          <?php require __DIR__ . '/../partials/tablas-hijas/muestras.php'; ?>
        </div>
      </div>
      <?php $numeroSeccion++; ?>

      <!-- Clasificación del caso -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Clasificación del caso</h3></div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field">
              <label class="fl">Clasificación</label>
              <div class="control">
                <select name="clasificacion">
                  <option value="SOSPECHOSO" <?= seleccionado($caso['clasificacion'], 'SOSPECHOSO') ?>>Sospechoso</option>
                  <option value="PROBABLE" <?= seleccionado($caso['clasificacion'], 'PROBABLE') ?>>Probable</option>
                  <option value="CONFIRMADO" <?= seleccionado($caso['clasificacion'], 'CONFIRMADO') ?>>Confirmado</option>
                  <option value="DESCARTADO" <?= seleccionado($caso['clasificacion'], 'DESCARTADO') ?>>Descartado</option>
                </select>
              </div>
            </div>
          </div>
          <div class="sym-grid" style="margin-top:14px;grid-template-columns:1fr">
            <label class="sym"><input type="checkbox" name="hospitalizado" <?= marcado($caso['hospitalizado']) ?>> Hospitalizado</label>
            <label class="sym"><input type="checkbox" name="fallecido" <?= marcado($caso['fallecido']) ?>> Fallecido</label>
          </div>
        </div>
      </div>
    </div>

    <!-- Right rail -->
    <aside class="rail">
      <div class="card rail-card">
        <h4>Guardando cambios</h4>
        <p>Los cambios en clasificación quedan registrados en la bitácora de la ficha. El estado y la anulación se gestionan desde la vista de la ficha.</p>
      </div>
      <div class="rail-actions">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          Guardar cambios
        </button>
        <a class="btn btn-ghost" href="/casos/<?= (int) $caso['id'] ?>" style="text-align:center">Cancelar</a>
      </div>
    </aside>
  </div>
</form>
