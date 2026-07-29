<?php
/**
 * Plantilla Personalizada para Sección 1: Datos del fallecimiento (Muerte Materna O95)
 */
use App\Core\Database;

$valLugar = $valoresCampos[14307] ?? 'Establecimiento de salud';
$valTipoEess = $valoresCampos[16117] ?? 'EESS Sanidad FFAA/PNP';
$valMomento = $valoresCampos[14304] ?? '';

// Cargar lista de IPRESS PNP de la BD
$pdo = Database::conexion();
$estPnpList = $pdo->query("SELECT es.id, es.nombre, d.id as distrito_id, p.id as provincia_id, dep.id as departamento_id 
    FROM establecimiento es 
    LEFT JOIN distrito d ON d.id = es.distrito_id 
    LEFT JOIN provincia p ON p.id = d.provincia_id 
    LEFT JOIN departamento dep ON dep.id = p.departamento_id 
    ORDER BY es.nombre")->fetchAll();

// Cargar departamentos para el ubigeo del fallecimiento
$depsFallecimiento = $pdo->query("SELECT id, nombre FROM departamento ORDER BY nombre")->fetchAll();

$depSelFall = $valoresCampos[16120] ?? '';
$provSelFall = $valoresCampos[16121] ?? '';
$distSelFall = $valoresCampos[16122] ?? '';

$provsFallecimiento = [];
if ($depSelFall) {
    $stmtP = $pdo->prepare("SELECT id, nombre FROM provincia WHERE departamento_id = ? ORDER BY nombre");
    $stmtP->execute([$depSelFall]);
    $provsFallecimiento = $stmtP->fetchAll();
}

$distsFallecimiento = [];
if ($provSelFall) {
    $stmtD = $pdo->prepare("SELECT id, nombre FROM distrito WHERE provincia_id = ? ORDER BY nombre");
    $stmtD->execute([$provSelFall]);
    $distsFallecimiento = $stmtD->fetchAll();
}
?>
<div id="datosFallecimientoO95Wrap">
  <!-- Fila 1: Momento del fallecimiento + Fase del puerperio (Condicional) -->
  <div class="fields halves" style="margin-bottom:14px;">
    <div class="field">
      <label class="fl">Momento del fallecimiento <span class="req">*</span></label>
      <div class="control">
        <select id="o95MomentoFallecimientoSel" name="campo_14304" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="Embarazo" <?= seleccionado($valMomento, 'Embarazo') ?>>Embarazo</option>
          <option value="Parto" <?= seleccionado($valMomento, 'Parto') ?>>Parto</option>
          <option value="Puerperio" <?= seleccionado($valMomento, 'Puerperio') ?>>Puerperio</option>
          <option value="Desconocido" <?= seleccionado($valMomento, 'Desconocido') ?>>Desconocido</option>
        </select>
      </div>
    </div>

    <div class="field" id="campoFasePuerperioO95" <?= $valMomento !== 'Puerperio' ? 'hidden style="display:none;"' : '' ?>>
      <label class="fl">Fase del puerperio <span class="req">*</span></label>
      <div class="control">
        <select name="campo_14320" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="Inmediato" <?= seleccionado($valoresCampos[14320] ?? '', 'Inmediato') ?>>Inmediato</option>
          <option value="Mediato" <?= seleccionado($valoresCampos[14320] ?? '', 'Mediato') ?>>Mediato</option>
          <option value="Tardío" <?= seleccionado($valoresCampos[14320] ?? '', 'Tardío') ?>>Tardío</option>
          <option value="Desconocido" <?= seleccionado($valoresCampos[14320] ?? '', 'Desconocido') ?>>Desconocido</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Fila 2: Edad gestacional (Semanas) con Checkbox Desconocido -->
  <div class="fields halves" style="margin-bottom:14px; align-items:flex-end;">
    <div class="field">
      <label class="fl">Edad gestacional (Semanas)</label>
      <div class="control mono">
        <input type="number" id="o95EdadGestacionalInput" name="campo_14305" value="<?= e($valoresCampos[14305] ?? '') ?>" min="0" max="50" placeholder="Ej: 38" <?= !empty($valoresCampos[16115]) ? 'disabled' : '' ?>>
      </div>
    </div>
    <div class="field" style="margin-bottom:6px;">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600; color:var(--ink);">
        <input type="checkbox" id="o95EdadGestacionalDesconocidaChk" name="campo_16115" value="1" <?= !empty($valoresCampos[16115]) ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
        <span>Desconocido</span>
      </label>
    </div>
  </div>

  <!-- Fila 3: Fecha de fallecimiento + Hora de fallecimiento -->
  <div class="fields halves" style="margin-bottom:14px;">
    <div class="field">
      <label class="fl">Fecha de fallecimiento <span class="req">*</span></label>
      <div class="control mono">
        <input type="date" name="campo_14306" value="<?= e($valoresCampos[14306] ?? '') ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Hora de fallecimiento (HH:MM) <span class="req">*</span></label>
      <div class="control mono">
        <input type="time" name="campo_16116" value="<?= e($valoresCampos[16116] ?? '') ?>">
      </div>
    </div>
  </div>

  <!-- Fila 4: ¿Dónde ocurrió el fallecimiento? -->
  <div class="field" style="margin-bottom:14px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Dónde ocurrió el fallecimiento? <span class="req">*</span></label>
    <div class="control-radio-group">
      <?php foreach (['Establecimiento de salud', 'Domicilio', 'Trayecto', 'Otro'] as $lugarOpt): ?>
        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
          <input type="radio" class="o95-lugar-radio" name="campo_14307" value="<?= $lugarOpt ?>" <?= ($valLugar === $lugarOpt) ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
          <span><?= $lugarOpt ?></span>
        </label>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Bloque: Si es Establecimiento de Salud -->
  <div id="bloqueEessFallecimientoO95" style="margin-top:14px;" <?= ($valLugar !== 'Establecimiento de salud') ? 'hidden style="display:none;"' : '' ?>>
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">Tipo de establecimiento de salud</label>
      <div class="control">
        <select id="o95TipoEessSel" name="campo_16117" data-nosearch="true">
          <option value="EESS Sanidad FFAA/PNP" <?= seleccionado($valTipoEess, 'EESS Sanidad FFAA/PNP') ?>>EESS Sanidad FFAA/PNP</option>
          <option value="EESS MINSA / IGSS" <?= seleccionado($valTipoEess, 'EESS MINSA / IGSS') ?>>EESS MINSA / IGSS / Gobierno Regional</option>
          <option value="EESS EsSalud" <?= seleccionado($valTipoEess, 'EESS EsSalud') ?>>EESS EsSalud</option>
          <option value="EESS Privado" <?= seleccionado($valTipoEess, 'EESS Privado') ?>>EESS Privado</option>
        </select>
      </div>
    </div>

    <!-- Si es Sanidad PNP: IPRESS PNP Dropdown con datos demograficos precargados -->
    <div id="subBloqueSanidadPnp" <?= ($valTipoEess !== 'EESS Sanidad FFAA/PNP') ? 'hidden style="display:none;"' : '' ?>>
      <div class="field" style="margin-bottom:14px;">
        <label class="fl">IPRESS PNP (Sanidad)</label>
        <div class="control">
          <select id="o95IpressPnpSel" name="campo_16119">
            <option value="">Seleccionar IPRESS PNP…</option>
            <?php foreach ($estPnpList as $estPnp): ?>
              <option value="<?= e($estPnp['nombre']) ?>" data-dep-id="<?= e($estPnp['departamento_id'] ?? '') ?>" data-prov-id="<?= e($estPnp['provincia_id'] ?? '') ?>" data-dist-id="<?= e($estPnp['distrito_id'] ?? '') ?>" <?= seleccionado($valoresCampos[16119] ?? '', $estPnp['nombre']) ?>>
                <?= e($estPnp['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Si es MINSA, EsSalud, Privado: Nombre del establecimiento -->
    <div id="subBloqueOtroEess" <?= ($valTipoEess === 'EESS Sanidad FFAA/PNP') ? 'hidden style="display:none;"' : '' ?>>
      <div class="field" style="margin-bottom:14px;">
        <label class="fl">Nombre del establecimiento de salud</label>
        <div class="control">
          <input type="text" name="campo_16118" value="<?= e($valoresCampos[16118] ?? '') ?>" placeholder="Nombre del EE.SS.…">
        </div>
      </div>
    </div>

    <!-- Permanencia (estadía) en el EE.SS.: Días, Horas, Minutos -->
    <div class="field" style="margin-top:14px; margin-bottom:14px;">
      <label class="fl" style="font-weight:600; color:var(--ink);">Permanencia (estadía) en el EE.SS.</label>
      <div class="fields thirds" style="margin-top:6px;">
        <div class="field">
          <label class="fl">Días</label>
          <div class="control mono">
            <input type="number" name="campo_16123" value="<?= e($valoresCampos[16123] ?? '') ?>" min="0" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label class="fl">Horas</label>
          <div class="control mono">
            <input type="number" name="campo_16124" value="<?= e($valoresCampos[16124] ?? '') ?>" min="0" max="23" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label class="fl">Minutos</label>
          <div class="control mono">
            <input type="number" name="campo_16125" value="<?= e($valoresCampos[16125] ?? '') ?>" min="0" max="59" placeholder="0">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bloque: Especificar si es Otro -->
  <div id="bloqueEspecificarOtroLugar" style="margin-top:14px;" <?= ($valLugar !== 'Otro') ? 'hidden style="display:none;"' : '' ?>>
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">Especificar lugar</label>
      <div class="control">
        <input type="text" name="campo_16126" value="<?= e($valoresCampos[16126] ?? '') ?>" placeholder="Especificar otro lugar…">
      </div>
    </div>
  </div>

  <!-- Bloque: Especificar si es Trayecto -->
  <div id="bloqueTrayectoFallecimiento" style="margin-top:14px;" <?= ($valLugar !== 'Trayecto') ? 'hidden style="display:none;"' : '' ?>>
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">Ubicación / Especificar trayecto (Opcional)</label>
      <div class="control">
        <input type="text" name="campo_16126_trayecto" value="<?= e($valoresCampos[16126] ?? '') ?>" placeholder="Ej: Vía pública, ambulancia…">
      </div>
    </div>
  </div>

  <!-- Bloque Anexo 2: Categoría del EE.SS., Fecha/Hora de Ingreso, Responsable de la atención -->
  <?php
  $valTipoFichaO95 = $valoresFijos['o95_tipo_ficha'] ?? $valoresCampos[14300] ?? $_POST['o95_tipo_ficha'] ?? 'ANEXO_1';
  $esAnexo2Activo = ($valTipoFichaO95 === 'ANEXO_2');
  ?>
  <div id="bloqueAnexo2FallecimientoO95" class="o95-anexo-2-elem" style="margin-top:16px; padding-top:14px; border-top:1px dashed var(--line-2);" <?= !$esAnexo2Activo ? 'hidden style="display:none;"' : '' ?>>
    <div class="eyebrow" style="margin-bottom:10px; color:var(--accent-deep); font-weight:700;">Datos ampliados del fallecimiento (Anexo 2)</div>
    
    <div class="fields halves" style="margin-bottom:14px;">
      <!-- Categoría del EE.SS. (Solo para IPRESS no PNP) -->
      <?php $mostrarCat = $esAnexo2Activo && ($valLugar === 'Establecimiento de salud') && ($valTipoEess !== 'EESS Sanidad FFAA/PNP'); ?>
      <div class="field o95-anexo-2-elem" id="campoCategoriaEessO95" <?= !$mostrarCat ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Categoría del EE.SS.</label>
        <div class="control">
          <select id="o95CategoriaEessSel" name="campo_14321" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <?php foreach (['I-1', 'I-2', 'I-3', 'I-4', 'II-1', 'II-2', 'II-E', 'III-1', 'III-E', 'III-2', 'Desconocido'] as $catItem): ?>
              <option value="<?= $catItem ?>" <?= seleccionado($valoresCampos[14321] ?? '', $catItem) ?>><?= $catItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Fecha y hora de ingreso al EE.SS. (Calculado automáticamente) -->
      <?php $mostrarIngreso = $esAnexo2Activo && ($valLugar === 'Establecimiento de salud'); ?>
      <div class="field o95-anexo-2-elem" id="campoFechaHoraIngresoO95" <?= !$mostrarIngreso ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Fecha y hora de ingreso al EE.SS. (Calculado)</label>
        <div class="control mono">
          <input type="datetime-local" id="o95FechaHoraIngresoInput" name="campo_14322" value="<?= e($valoresCampos[14322] ?? '') ?>">
        </div>
        <span class="hint">Se calcula de la fecha de fallecimiento menos la permanencia</span>
      </div>
    </div>

    <!-- Responsable de la atención -->
    <div class="field o95-anexo-2-elem" id="campoResponsableAtencionO95" style="margin-bottom:14px;">
      <label class="fl">Responsable de la atención</label>
      <div class="control">
        <select name="campo_14323" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach (['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Partera', 'Familiar', 'Otro', 'Desconocido'] as $respItem): ?>
            <option value="<?= $respItem ?>" <?= seleccionado($valoresCampos[14323] ?? '', $respItem) ?>><?= $respItem ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <!-- Ubigeo del Lugar del Fallecimiento (Departamento, Provincia, Distrito) -->
  <!-- Aparecerá ÚNICAMENTE cuando se seleccione MINSA, EsSalud, Privado u Otro -->
  <!-- Oculto para Sanidad PNP (se consideran internamente), Domicilio y Trayecto -->
  <?php
  $mostrarUbigeoSeccion = ($valLugar === 'Otro') || ($valLugar === 'Establecimiento de salud' && $valTipoEess !== 'EESS Sanidad FFAA/PNP');
  ?>
  <div id="bloqueUbigeoFallecimientoO95" style="margin-top:16px; padding-top:14px; border-top:1px dashed var(--line-2);" <?= !$mostrarUbigeoSeccion ? 'hidden style="display:none;"' : '' ?>>
    <div class="eyebrow" style="margin-bottom:10px; color:var(--accent-deep); font-weight:700;">Ubicación del lugar del fallecimiento</div>
    <?php
    $prefijo = 'o95-fallecimiento-ubigeo';
    $departamentos = $depsFallecimiento;
    $provinciasIniciales = $provsFallecimiento;
    $distritosIniciales = $distsFallecimiento;
    $departamentoSeleccionado = $depSelFall;
    $provinciaSeleccionada = $provSelFall;
    $distritoSeleccionado = $distSelFall;
    $nombreCampoDistrito = 'campo_16122';
    $distritoRequerido = false;
    require __DIR__ . '/selector-ubigeo.php';
    ?>
    <!-- Inputs ocultos para enviar departamento y provincia en el POST -->
    <input type="hidden" id="o95-fallecimiento-ubigeo-dep-hidden" name="campo_16120" value="<?= e($depSelFall) ?>">
    <input type="hidden" id="o95-fallecimiento-ubigeo-prov-hidden" name="campo_16121" value="<?= e($provSelFall) ?>">
  </div>
</div>
