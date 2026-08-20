<?php
use App\Core\Csrf;

require __DIR__ . '/../partials/campos-por-clave.php';

// nucleo_incluidos: ver nueva/index.php, mismo mecanismo.
$nucleoIncluidos = [];
if (!empty($enfermedad['nucleo_incluidos'])) {
    $decodificadoNucleoIncluidos = json_decode($enfermedad['nucleo_incluidos'], true);
    $nucleoIncluidos = is_array($decodificadoNucleoIncluidos) ? $decodificadoNucleoIncluidos : [];
}
$nucleoIncluye = fn(string $campo): bool => in_array($campo, $nucleoIncluidos, true);

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
  <script type="application/json" id="mapaCampos"><?= json_encode($mapaClaveNombreCampos, JSON_UNESCAPED_UNICODE) ?></script>

  <div class="disease-pick">
    <div class="ic"><svg width="19" height="19" viewBox="0 0 19 19"><circle cx="9.5" cy="9.5" r="7" stroke="currentColor" stroke-width="1.4" fill="none"/><path d="M9.5 6v7M6 9.5h7" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></div>
    <div>
      <div class="eyebrow" style="margin-bottom:2px">Enfermedad / evento bajo vigilancia</div>
      <div style="font-weight:600;font-size:14.5px"><?= e($caso['enfermedad_nombre']) ?></div>
    </div>
    <div class="disease-meta">
      <span class="tag" id="cieTag">CIE-10 · <?= e($caso['cie10'] ?? '—') ?></span>
      <span class="tag"><span class="state"><span class="dot <?= $es['dot'] ?>"></span> <?= $es['etiqueta'] ?></span></span>
    </div>
  </div>

  <div class="grid form-grid">
    <div>
      <!-- 1. Notificación -->
      <div class="card section">
        <div class="section-head">
          <span class="section-num">1</span><h3>Notificación</h3>
          <span style="margin-left:auto;display:flex;align-items:center;gap:6px">
            <span class="eyebrow">SE de la ficha</span>
            <span class="tag mono" title="Se recalcula al guardar, según la fecha de notificación">SE <?= (int) $caso['semana_epi'] ?> · <?= (int) $caso['anio_epi'] ?></span>
          </span>
        </div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field">
              <label class="fl">Establecimiento (EESS)</label>
              <div class="control" style="color:var(--muted)" title="<?= e($caso['establecimiento_nombre']) ?>">
                <svg class="lead" width="14" height="14" viewBox="0 0 14 14"><path d="M7 1.5 2 4v7h10V4L7 1.5Z" stroke="currentColor" stroke-width="1.2" fill="none"/></svg>
                <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e(capitalizarNombre($caso['establecimiento_nombre'])) ?></span>
              </div>
              <span class="hint">No editable: crea una nueva ficha si cambió de establecimiento</span>
            </div>
            <div class="field">
              <!-- A44 (pág. 42, cotejo 2026-08-18): el PDF pide "FECHA ENCUESTA", no
                   "Fecha de notificación" -- mismo campo núcleo (fecha_notif), solo
                   cambia la etiqueta para esta ficha. -->
              <label class="fl"><?= (($enfermedad['cie10'] ?? null) === 'A44') ? 'Fecha de encuesta' : 'Fecha de notificación' ?> <span class="req">*</span></label>
              <div class="control mono <?= isset($erroresFijos['fecha_notif']) ? 'err' : '' ?>">
                <input type="date" name="fecha_notif" value="<?= e($valoresFijos['fecha_notif']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
              </div>
              <?php if (isset($erroresFijos['fecha_notif'])): ?>
                <span class="hint err"><?= e($erroresFijos['fecha_notif']) ?></span>
              <?php endif; ?>
            </div>
          </div>
          <div id="notificacionCaptacionWrap" <?= in_array($enfermedad['cie10'] ?? null, ['A80', 'B05', 'O95', 'P35.0', 'A35', 'A33', 'A37.0', 'A97', 'A44'], true) ? 'hidden' : '' ?>>
            <?php require __DIR__ . '/../partials/notificacion-captacion.php'; ?>
          </div>
          <?php require __DIR__ . '/../partials/notificacion-fechas-pfa.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-b05.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-o95.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-b26.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-p350.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-a35.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-a33.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-a370.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-b01.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-a97.php'; ?>
          <?php require __DIR__ . '/../partials/notificacion-fechas-b57.php'; ?>
        </div>
      </div>

      <!-- 2. persona -->
      <div class="card section">
        <div class="section-head"><span class="section-num">2</span><h3>Datos del persona</h3></div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field">
              <label class="fl">Documento</label>
              <div class="control mono" style="color:var(--muted)"><?= e($valoresFijos['tipo_doc']) ?> <?= e($valoresFijos['num_doc']) ?></div>
              <span class="hint">No editable: es la identidad del persona</span>
            </div>
            <?php $esO95Edit = (($enfermedad['cie10'] ?? null) === 'O95'); ?>
            <div class="field" data-nucleo-incluido="n_historia_clinica" <?= $nucleoIncluye('n_historia_clinica') ? '' : 'hidden style="display:none;"' ?>>
              <label class="fl">N.° de historia clínica</label>
              <div class="control mono">
                <input type="text" name="n_historia_clinica" value="<?= e($valoresFijos['n_historia_clinica'] ?? '') ?>" placeholder="N.° H.C.…">
              </div>
            </div>
          </div>
          <div class="fields thirds" style="margin-top:14px">
            <div class="field">
              <label class="fl">Apellido paterno <span class="req">*</span></label>
              <div class="control <?= isset($erroresFijos['apellido_paterno']) ? 'err' : '' ?>">
                <input type="text" name="apellido_paterno" value="<?= e($valoresFijos['apellido_paterno']) ?>">
              </div>
              <?php if (isset($erroresFijos['apellido_paterno'])): ?><span class="hint err"><?= e($erroresFijos['apellido_paterno']) ?></span><?php endif; ?>
            </div>
            <div class="field">
              <label class="fl">Apellido materno</label>
              <div class="control">
                <input type="text" name="apellido_materno" value="<?= e($valoresFijos['apellido_materno']) ?>">
              </div>
            </div>
            <div class="field">
              <label class="fl">Nombres <span class="req">*</span></label>
              <div class="control <?= isset($erroresFijos['nombres']) ? 'err' : '' ?>">
                <input type="text" name="nombres" value="<?= e($valoresFijos['nombres']) ?>">
              </div>
              <?php if (isset($erroresFijos['nombres'])): ?><span class="hint err"><?= e($erroresFijos['nombres']) ?></span><?php endif; ?>
            </div>
          </div>
          <div class="fields thirds" style="margin-top:14px">
            <?php $esO95Edit = (($enfermedad['cie10'] ?? null) === 'O95'); ?>
            <div class="field o95-hide" <?= $esO95Edit ? 'hidden style="display:none;"' : '' ?>>
              <label class="fl">Sexo</label>
              <div class="control">
                <select name="sexo" data-nosearch="true">
                  <option value="">Seleccionar…</option>
                  <option value="F" <?= seleccionado($valoresFijos['sexo'] ?? '', 'F') ?>>Femenino</option>
                  <option value="M" <?= seleccionado($valoresFijos['sexo'] ?? '', 'M') ?>>Masculino</option>
                </select>
              </div>
            </div>
            <?php if ($esO95Edit): ?>
              <input type="hidden" name="sexo" value="F">
            <?php endif; ?>
            <div class="field">
              <label class="fl">Fecha de nacimiento</label>
              <div class="control mono <?= isset($erroresFijos['fecha_nac']) ? 'err' : '' ?>">
                <input type="date" id="fechaNac" name="fecha_nac" value="<?= e($valoresFijos['fecha_nac']) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
              </div>
              <?php if (isset($erroresFijos['fecha_nac'])): ?><span class="hint err"><?= e($erroresFijos['fecha_nac']) ?></span><?php endif; ?>
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

      <!-- 3. III. Lugar probable de infección (B26 Parotiditis) -->
      <?php require __DIR__ . '/../partials/lugar-probable-infeccion-b26.php'; ?>

      <!-- 3. III. Lugar probable de infección (B01 Varicela) -->
      <?php require __DIR__ . '/../partials/lugar-probable-infeccion-b01.php'; ?>

      <!-- 4. IV. Cuadro clínico (B26 Parotiditis) -->
      <?php require __DIR__ . '/../partials/cuadro-clinico-b26.php'; ?>

      <!-- 5. V. Antecedentes de vacunación y VII. Observaciones (B26 Parotiditis) -->
      <?php require __DIR__ . '/../partials/antecedentes-vacunacion-b26.php'; ?>

      <?php
      $numeroSeccionInicial = 3;
      require __DIR__ . '/../partials/secciones-clinicas.php';
      ?>

      <?php
      $isPfa = ($enfermedad['cie10'] ?? '') === 'A80';
      $isB05 = ($enfermedad['cie10'] ?? '') === 'B05';
      $isB26 = ($enfermedad['cie10'] ?? '') === 'B26';
      // Ítem Z.2: P35.0 muestra "Viajes" (los de la madre) condicionado al
      // booleano "¿Durante el embarazo viajó fuera del país?", dentro de
      // "Antecedentes de la madre" (secciones-clinicas.php) -- no siempre
      // visible acá, mismo trato que B05 con su propio booleano de viaje.
      $isP350 = ($enfermedad['cie10'] ?? '') === 'P35.0';
      // A37.0 (2026-08-06): "¿Viajó...?" y "¿Algún miembro de la
      // familia...?" gatean sus propias tablas caso_viaje/caso_contacto
      // dentro de "Lugar probable de infección" (secciones-clinicas.php) --
      // mismo trato que B05/P35.0 arriba, no siempre visibles acá.
      $isA370 = ($enfermedad['cie10'] ?? '') === 'A37.0';
      // A97 (2026-08-14): ítem 21 del PDF ("¿Dónde estuvo en las últimas dos
      // semanas...?") es la tabla de viajes SIN gate -- va justo al inicio
      // de "Antecedentes epidemiológicos" (antes de "Caso autóctono"), no al
      // final en esta tarjeta genérica. Mismo trato que B05/P35.0/A37.0
      // arriba, pero sin booleano disparador: se renderiza siempre, dentro
      // de secciones-clinicas.php.
      $isA97 = ($enfermedad['cie10'] ?? '') === 'A97';
      // A44 (cotejo 2026-08-18): "Viaje a localidades o comunidades vecinas"
      // (pág. 42 del PDF) reposicionada dentro de "Inicio de la enfermedad"
      // (secciones-clinicas.php), mismo trato que A97 arriba.
      $isA44 = ($enfermedad['cie10'] ?? '') === 'A44';
      $mostrarContactos = ((int) ($enfermedad['usa_contactos'] ?? 0) === 1) && !$isPfa && !$isB05 && !$isB26 && !$isP350 && !$isA370;
      $mostrarViajes = ((int) ($enfermedad['usa_viajes'] ?? 0) === 1) && !$isPfa && !$isB05 && !$isB26 && !$isP350 && !$isA370 && !$isA97 && !$isA44;
      $mostrarVacunas = ((int) ($enfermedad['usa_vacunas'] ?? 0) === 1) && !$isPfa && !$isB05 && !$isB26 && !$isP350 && !$isA370;
      $mostrarLugarInf = ((int) ($enfermedad['usa_lugar_infeccion'] ?? 0) === 1) && !$isPfa && !$isB05 && !$isB26 && !$isP350 && !$isA370;
      // Roles con columnas_sujeto que NO tienen sección propia en el
      // manifiesto (PETICION_P35_RUBEOLA_CONGENITA.md Fase 2): los que sí
      // tienen sección ya se anclan solos dentro de secciones-clinicas.php
      // (2c) -- repetirlos acá sería doble render. P96 no declara ninguna
      // sección con rol_sujeto propio, así que cae acá igual que siempre.
      $rolesSujetoAqui = rolesSujetoSinAnclaje($enfermedad['columnas_sujeto'] ?? null, \App\Models\CampoDef::rolesConSeccionPropia((int) $enfermedad['id']));
      $tieneAntecedentesEpidemiologicos = ($mostrarContactos || $mostrarViajes || $mostrarVacunas || $mostrarLugarInf || !empty($rolesSujetoAqui)) && !$isB26;
      ?>

      <!-- Antecedentes epidemiológicos -->
      <div class="card section" id="cardAntecedentesEpidemiologicos" <?= $tieneAntecedentesEpidemiologicos ? '' : 'hidden' ?>>
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Antecedentes epidemiológicos</h3></div>
        <div class="section-body">
          <?php if ($mostrarContactos && ($enfermedad['cie10'] ?? '') !== 'B05'): ?>
            <div class="eyebrow" style="margin-bottom:10px">Contactos</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/contactos.php'; ?>
          <?php endif; ?>

          <?php if ($mostrarViajes && ($enfermedad['cie10'] ?? '') !== 'B05'): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Viajes</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/viajes.php'; ?>
          <?php endif; ?>

          <?php if ($mostrarVacunas && ($enfermedad['cie10'] ?? '') !== 'B05'): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Antecedentes vacunales</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/vacunas.php'; ?>
          <?php endif; ?>

          <?php if ($mostrarLugarInf && ($enfermedad['cie10'] ?? '') !== 'B05'): ?>
            <div class="eyebrow" style="margin:22px 0 10px">Lugar probable de infección</div>
            <?php require __DIR__ . '/../partials/tablas-hijas/lugar-infeccion.php'; ?>
          <?php endif; ?>

          <?php foreach ($rolesSujetoAqui as $rolActual): ?>
            <?php
            $columnasDeclaradas = columnasSujeto($enfermedad['columnas_sujeto'] ?? null, $rolActual);
            $tituloBloque = tituloSujeto($enfermedad['titulo_sujeto'] ?? null, $rolActual);
            $valoresSujetoActual = $valoresSujetoPorRol[$rolActual] ?? [];
            require __DIR__ . '/../partials/tablas-hijas/residencia-madre.php';
            ?>
          <?php endforeach; ?>
        </div>
      </div>
      <?php if ($tieneAntecedentesEpidemiologicos) $numeroSeccion++; ?>

      <!-- Laboratorio -->
      <div class="card section" id="seccionLaboratorioCard" <?= (int) ($enfermedad['usa_muestras'] ?? 0) === 1 ? '' : 'hidden' ?>>
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
        <div class="section-body" id="seccionLaboratorioBody">
          <?php require __DIR__ . '/../partials/tablas-hijas/muestras.php'; ?>
        </div>
      </div>
      <?php if ((int) ($enfermedad['usa_muestras'] ?? 0) === 1) $numeroSeccion++; ?>
<?php
// clasificacion-caso-p350.php (ítem Z.8): ver nueva/index.php para la
// explicación completa. Adentro del bloque PHP, no como tag aparte, por el
// mismo motivo que el comentario de abajo.
if ($isP350) require __DIR__ . '/../partials/clasificacion-caso-p350.php';

// clasificacion-caso-a370.php (2026-08-07): ver nueva/index.php.
if ($isA370) require __DIR__ . '/../partials/clasificacion-caso-a370.php';

// observaciones-b01.php (2026-08-09): ver nueva/index.php.
$isB01 = ($enfermedad['cie10'] ?? '') === 'B01';
if ($isB01) require __DIR__ . '/../partials/observaciones-b01.php';

// Bloques condicionales de tabla hija (capacidad 6): ver nueva/index.php
// para la explicación completa. Un solo bloque PHP -- ver el mismo
// comentario ahí sobre por qué no imprimir nada de más.
foreach (($bloquesCondicionalesMuestra ?? []) as $bloqueMuestra):
    $filasBloque = $filasBloquesMuestra[$bloqueMuestra['contexto']] ?? [];
    $erroresBloque = [];
    $clasificacionActualBloque = $caso['clasificacion'];
    require __DIR__ . '/../partials/tablas-hijas/muestras-condicional.php';
    $numeroSeccion++;
endforeach;
?>

      <!-- Investigador -->
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Investigador</h3></div>
        <div class="section-body">
          <?php require __DIR__ . '/../partials/investigador.php'; ?>
        </div>
      </div>
      <?php $numeroSeccion++; ?>

      <!-- Clasificación del caso -->
      <?php $esB26Clasif = (($enfermedad['cie10'] ?? '') === 'B26'); ?>
      <div class="card section" id="cardClasificacionCaso" <?= $esB26Clasif ? 'hidden style="display:none;"' : '' ?>>
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Clasificación del caso</h3></div>
        <div class="section-body">
          <?php $clasificacionActual = $caso['clasificacion']; require __DIR__ . '/../partials/clasificacion-chips.php'; ?>
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
  <?= $avisoClavesFaltantesCampos() ?>
</form>
