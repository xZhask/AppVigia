<?php
/**
 * Plantilla Personalizada para Sección 1: Datos del fallecimiento (Muerte Materna O95)
 */
use App\Core\Database;

// Peticion 2, MAPA_IDS_CAMPOS.md "Hallazgo mayor": esta seccion tenia 14
// referencias que usaban una ID que SI existe en campo_def pero apunta a un
// campo distinto del que el HTML dice (p.ej. name="campo_16117" decia "Hora
// de fallecimiento" pero 16117 es hoy o95_hora_de_fallecimiento... que en
// realidad es el campo correcto para otra cosa -- ver el detalle campo por
// campo mas abajo). Se resuelve todo por clave, verificada una por una
// contra la etiqueta real de campo_def, no contra la ID que el codigo tenia.
$campoMomento = $campo('o95_momento_del_fallecimiento');
$campoFasePuerperio = $campo('o95_fase_del_puerperio_en_que_fallecio');
$campoEdadGestacional = $campo('o95_edad_gestacional_al_momento_del_fallecimiento');
$campoEdadGestDesconocida = $campo('o95_edad_gestacional_desconocida');
$campoFechaFallecimiento = $campo('o95_fecha_de_fallecimiento');
$campoHoraFallecimiento = $campo('o95_hora_de_fallecimiento');
$campoLugarFallecimiento = $campo('o95_lugar_del_fallecimiento');
$campoTipoEess = $campo('o95_tipo_eess_fallecimiento');
$campoEessPnpId = $campo('o95_eess_fallecimiento_id');
$campoNombreEess = $campo('o95_nombre_eess_fallecimiento');
$campoPermanenciaDias = $campo('o95_permanencia_dias');
$campoPermanenciaHoras = $campo('o95_permanencia_horas');
$campoPermanenciaMinutos = $campo('o95_permanencia_minutos');
$campoLugarOtroEspecificar = $campo('o95_lugar_fallecimiento_otro_especificar');
$campoCategoriaEess = $campo('o95_categoria_del_ee_ss');
$campoFechaHoraIngreso = $campo('o95_fecha_y_hora_de_ingreso_al_ee_ss');
$campoResponsableAtencion = $campo('o95_responsable_de_la_atencion');
$campoDepFallecimiento = $campo('o95_fallecimiento_dep_id');
$campoProvFallecimiento = $campo('o95_fallecimiento_prov_id');
$campoDistFallecimiento = $campo('o95_fallecimiento_dist_id');
$campoTipoFichaO95 = $campo('o95_tipo_de_ficha');

$valLugar = $campoLugarFallecimiento['val'] !== '' ? $campoLugarFallecimiento['val'] : 'Establecimiento de salud';
$valTipoEess = $campoTipoEess['val'] !== '' ? $campoTipoEess['val'] : 'EESS Sanidad FFAA/PNP';
$valMomento = $campoMomento['val'];

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

$depSelFall = $campoDepFallecimiento['val'];
$provSelFall = $campoProvFallecimiento['val'];
$distSelFall = $campoDistFallecimiento['val'];

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
        <select id="o95MomentoFallecimientoSel" name="<?= $campoMomento['name'] ?>" data-nosearch="true">
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
        <select name="<?= $campoFasePuerperio['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="Inmediato" <?= seleccionado($campoFasePuerperio['val'], 'Inmediato') ?>>Inmediato</option>
          <option value="Mediato" <?= seleccionado($campoFasePuerperio['val'], 'Mediato') ?>>Mediato</option>
          <option value="Tardío" <?= seleccionado($campoFasePuerperio['val'], 'Tardío') ?>>Tardío</option>
          <option value="Desconocido" <?= seleccionado($campoFasePuerperio['val'], 'Desconocido') ?>>Desconocido</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Fila 2: Edad gestacional (Semanas) con Checkbox Desconocido -->
  <div class="fields halves" style="margin-bottom:14px; align-items:flex-end;">
    <div class="field">
      <label class="fl">Edad gestacional (Semanas)</label>
      <div class="control mono">
        <input type="number" id="o95EdadGestacionalInput" name="<?= $campoEdadGestacional['name'] ?>" value="<?= e($campoEdadGestacional['val']) ?>" min="0" max="50" placeholder="Ej: 38" <?= !empty($campoEdadGestDesconocida['val']) ? 'disabled' : '' ?>>
      </div>
    </div>
    <div class="field" style="margin-bottom:6px;">
      <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600; color:var(--ink);">
        <input type="checkbox" id="o95EdadGestacionalDesconocidaChk" name="<?= $campoEdadGestDesconocida['name'] ?>" value="1" <?= !empty($campoEdadGestDesconocida['val']) ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
        <span>Desconocido</span>
      </label>
    </div>
  </div>

  <!-- Fila 3: Fecha de fallecimiento + Hora de fallecimiento -->
  <div class="fields halves" style="margin-bottom:14px;">
    <div class="field">
      <label class="fl">Fecha de fallecimiento <span class="req">*</span></label>
      <div class="control mono">
        <input type="date" name="<?= $campoFechaFallecimiento['name'] ?>" value="<?= e($campoFechaFallecimiento['val']) ?>" max="<?= date('Y-m-d') ?>">
      </div>
    </div>
    <div class="field">
      <label class="fl">Hora de fallecimiento (HH:MM) <span class="req">*</span></label>
      <div class="control mono">
        <input type="time" name="<?= $campoHoraFallecimiento['name'] ?>" value="<?= e($campoHoraFallecimiento['val']) ?>">
      </div>
    </div>
  </div>

  <!-- Fila 4: ¿Dónde ocurrió el fallecimiento? -->
  <div class="field" style="margin-bottom:14px;">
    <label class="fl" style="font-weight:700; color:var(--accent-deep); margin-bottom:8px;">¿Dónde ocurrió el fallecimiento? <span class="req">*</span></label>
    <div class="control-radio-group">
      <?php foreach (['Establecimiento de salud', 'Domicilio', 'Trayecto', 'Otro'] as $lugarOpt): ?>
        <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.875rem; font-weight:600;">
          <input type="radio" class="o95-lugar-radio" name="<?= $campoLugarFallecimiento['name'] ?>" value="<?= $lugarOpt ?>" <?= ($valLugar === $lugarOpt) ? 'checked' : '' ?> style="accent-color:var(--accent); width:16px; height:16px;">
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
        <select id="o95TipoEessSel" name="<?= $campoTipoEess['name'] ?>" data-nosearch="true">
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
          <select id="o95IpressPnpSel" name="<?= $campoEessPnpId['name'] ?>">
            <option value="">Seleccionar IPRESS PNP…</option>
            <?php foreach ($estPnpList as $estPnp): ?>
              <option value="<?= e($estPnp['nombre']) ?>" data-dep-id="<?= e($estPnp['departamento_id'] ?? '') ?>" data-prov-id="<?= e($estPnp['provincia_id'] ?? '') ?>" data-dist-id="<?= e($estPnp['distrito_id'] ?? '') ?>" <?= seleccionado($campoEessPnpId['val'], $estPnp['nombre']) ?>>
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
          <input type="text" name="<?= $campoNombreEess['name'] ?>" value="<?= e($campoNombreEess['val']) ?>" placeholder="Nombre del EE.SS.…">
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
            <input type="number" name="<?= $campoPermanenciaDias['name'] ?>" value="<?= e($campoPermanenciaDias['val']) ?>" min="0" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label class="fl">Horas</label>
          <div class="control mono">
            <input type="number" name="<?= $campoPermanenciaHoras['name'] ?>" value="<?= e($campoPermanenciaHoras['val']) ?>" min="0" max="23" placeholder="0">
          </div>
        </div>
        <div class="field">
          <label class="fl">Minutos</label>
          <div class="control mono">
            <input type="number" name="<?= $campoPermanenciaMinutos['name'] ?>" value="<?= e($campoPermanenciaMinutos['val']) ?>" min="0" max="59" placeholder="0">
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
        <input type="text" name="<?= $campoLugarOtroEspecificar['name'] ?>" value="<?= e($campoLugarOtroEspecificar['val']) ?>" placeholder="Especificar otro lugar…">
      </div>
    </div>
  </div>

  <!-- Bloque: Especificar si es Trayecto -->
  <div id="bloqueTrayectoFallecimiento" style="margin-top:14px;" <?= ($valLugar !== 'Trayecto') ? 'hidden style="display:none;"' : '' ?>>
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">Ubicación / Especificar trayecto (Opcional)</label>
      <div class="control">
        <?php /* name con sufijo _trayecto a propósito, igual que antes de la migración: no calza con ningún campo_def.id real, así que este valor no se guarda hoy si Lugar=Trayecto (bug preexistente, fuera de alcance de esta petición — no se resuelve por mecánica). */ ?>
        <input type="text" name="<?= $campoLugarOtroEspecificar['name'] ?>_trayecto" value="<?= e($campoLugarOtroEspecificar['val']) ?>" placeholder="Ej: Vía pública, ambulancia…">
      </div>
    </div>
  </div>

  <!-- Bloque Anexo 2: Categoría del EE.SS., Fecha/Hora de Ingreso, Responsable de la atención -->
  <?php
  // Ver comentario equivalente en secciones-clinicas.php: $campo(...)['val']
  // nunca es null, hay que comparar con '' antes de seguir la cadena ??.
  $valTipoFichaO95 = $valoresFijos['o95_tipo_ficha']
      ?? ($campoTipoFichaO95['val'] !== '' ? $campoTipoFichaO95['val'] : null)
      ?? $_POST['o95_tipo_ficha']
      ?? 'ANEXO_1';
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
          <select id="o95CategoriaEessSel" name="<?= $campoCategoriaEess['name'] ?>" data-nosearch="true">
            <option value="">Seleccionar…</option>
            <?php foreach (['I-1', 'I-2', 'I-3', 'I-4', 'II-1', 'II-2', 'II-E', 'III-1', 'III-E', 'III-2', 'Desconocido'] as $catItem): ?>
              <option value="<?= $catItem ?>" <?= seleccionado($campoCategoriaEess['val'], $catItem) ?>><?= $catItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Fecha y hora de ingreso al EE.SS. (Calculado automáticamente) -->
      <?php $mostrarIngreso = $esAnexo2Activo && ($valLugar === 'Establecimiento de salud'); ?>
      <div class="field o95-anexo-2-elem" id="campoFechaHoraIngresoO95" <?= !$mostrarIngreso ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Fecha y hora de ingreso al EE.SS. (Calculado)</label>
        <div class="control mono">
          <input type="datetime-local" id="o95FechaHoraIngresoInput" name="<?= $campoFechaHoraIngreso['name'] ?>" value="<?= e($campoFechaHoraIngreso['val']) ?>">
        </div>
        <span class="hint">Se calcula de la fecha de fallecimiento menos la permanencia</span>
      </div>
    </div>

    <!-- Responsable de la atención -->
    <div class="field o95-anexo-2-elem" id="campoResponsableAtencionO95" style="margin-bottom:14px;">
      <label class="fl">Responsable de la atención</label>
      <div class="control">
        <select name="<?= $campoResponsableAtencion['name'] ?>" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <?php foreach (['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Partera', 'Familiar', 'Otro', 'Desconocido'] as $respItem): ?>
            <option value="<?= $respItem ?>" <?= seleccionado($campoResponsableAtencion['val'], $respItem) ?>><?= $respItem ?></option>
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
    $nombreCampoDistrito = $campoDistFallecimiento['name'];
    $distritoRequerido = false;
    require __DIR__ . '/selector-ubigeo.php';
    ?>
    <!-- Inputs ocultos para enviar departamento y provincia en el POST -->
    <input type="hidden" id="o95-fallecimiento-ubigeo-dep-hidden" name="<?= $campoDepFallecimiento['name'] ?>" value="<?= e($depSelFall) ?>">
    <input type="hidden" id="o95-fallecimiento-ubigeo-prov-hidden" name="<?= $campoProvFallecimiento['name'] ?>" value="<?= e($provSelFall) ?>">
  </div>
</div>
