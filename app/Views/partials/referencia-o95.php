<?php
/**
 * Plantilla especializada para la Sección: Referencia de Muerte Materna (O95).
 * Consolida los datos de Referencia de Anexo 1 y Anexo 2.
 */
use App\Core\Database;

$pdo = Database::conexion();

$valReferida = $valoresCampos[14309] ?? '';
$valEessOrigen = $valoresCampos[14310] ?? '';
$valDep = $valoresCampos[16131] ?? '';
$valProv = $valoresCampos[16132] ?? '';
$valDist = $valoresCampos[16133] ?? '';

// Cargar Ubigeo para Referencia (Departamento, Provincia, Distrito)
$depsReferencia = $pdo->query("SELECT id, nombre FROM departamento ORDER BY nombre")->fetchAll();

$provsReferencia = [];
if ($valDep) {
    $stmtP = $pdo->prepare("SELECT id, nombre FROM provincia WHERE departamento_id = ? ORDER BY nombre");
    $stmtP->execute([$valDep]);
    $provsReferencia = $stmtP->fetchAll();
}

$distsReferencia = [];
if ($valProv) {
    $stmtD = $pdo->prepare("SELECT id, nombre FROM distrito WHERE provincia_id = ? ORDER BY nombre");
    $stmtD->execute([$valProv]);
    $distsReferencia = $stmtD->fetchAll();
}

// Anexo 2
$valNumRefInst = isset($valoresCampos[14346]) && $valoresCampos[14346] !== '' ? (int)$valoresCampos[14346] : 0;

$valFechaIngOrigen = $valoresCampos[16148] ?? '';
$valHoraIngOrigen  = $valoresCampos[16149] ?? '';

$valFechaEgrOrigen = $valoresCampos[16150] ?? '';
$valHoraEgrOrigen  = $valoresCampos[16151] ?? '';

$valDemoraDias  = isset($valoresCampos[16152]) && $valoresCampos[16152] !== '' ? (int)$valoresCampos[16152] : 0;
$valDemoraHoras = isset($valoresCampos[16153]) && $valoresCampos[16153] !== '' ? (int)$valoresCampos[16153] : 0;

$valRespOrigen     = $valoresCampos[16154] ?? '';
$valRespOrigenOtro = $valoresCampos[16155] ?? '';

$valInstDestino = $valoresCampos[16156] ?? '';
$valEessDestino = $valoresCampos[16157] ?? '';

$valFechaIngDestino = $valoresCampos[16158] ?? '';
$valHoraIngDestino  = $valoresCampos[16159] ?? '';

$valTipoFichaO95 = $valoresFijos['o95_tipo_ficha'] ?? $valoresCampos[14300] ?? $_POST['o95_tipo_ficha'] ?? 'ANEXO_1';

$esReferidaSi = (strtoupper((string)$valReferida) === 'SI' || $valReferida === '1');
$esAnexo2 = ($valTipoFichaO95 === 'ANEXO_2');
$esRespOrigenOtro = (strtoupper((string)$valRespOrigen) === 'OTRO');
?>

<div id="bloqueReferenciaO95">
  <!-- 1. Precondición principal: ¿Referida? (SI / NO) -->
  <div class="field" style="margin-bottom:14px; max-width:280px;">
    <label class="fl">¿Referida? <span class="req">*</span></label>
    <div class="control">
      <select id="o95ReferidaSel" name="campo_14309" data-nosearch="true">
        <option value="NO" <?= seleccionado($valReferida, 'NO') ?>>NO</option>
        <option value="SI" <?= seleccionado($valReferida, 'SI') ?>>SI</option>
      </select>
    </div>
  </div>

  <!-- BLOQUE ANEXO 1: Datos básicos de la referencia (Visible solo si ¿Referida? = SI) -->
  <div id="bloqueReferenciaAnexo1O95" <?= !$esReferidaSi ? 'hidden style="display:none;"' : '' ?>>
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">EE.SS. de origen de la referencia</label>
      <div class="control">
        <input type="text" name="campo_14310" value="<?= e($valEessOrigen) ?>" placeholder="Nombre del EE.SS. que refiere…">
      </div>
    </div>

    <!-- Ubicación de la IPRESS que refiere (Departamento, Provincia, Distrito) -->
    <div style="background:var(--surface-2); padding:16px; border-radius:var(--radius-sm, 8px); border:1px solid var(--line); margin-bottom:18px;">
      <div class="eyebrow" style="margin-bottom:10px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
        Ubicación de la IPRESS que refiere
      </div>
      <?php
      $prefijo = 'o95-referencia-ubigeo';
      $departamentos = $depsReferencia;
      $provinciasIniciales = $provsReferencia;
      $distritosIniciales = $distsReferencia;
      $departamentoSeleccionado = $valDep;
      $provinciaSeleccionada = $valProv;
      $distritoSeleccionado = $valDist;
      $nombreCampoDepartamento = 'campo_16131';
      $nombreCampoProvincia = 'campo_16132';
      $nombreCampoDistrito = 'campo_16133';
      $distritoRequerido = false;
      require __DIR__ . '/selector-ubigeo.php';
      ?>
    </div>
  </div>

  <!-- BLOQUE ANEXO 2: Detalle de la referencia (Visible solo si Etapa = Anexo 2 Y ¿Referida? = SI) -->
  <div id="bloqueReferenciaAnexo2O95" style="margin-top:16px; padding-top:16px; border-top:1px dashed var(--line);" <?= (!$esReferidaSi || !$esAnexo2) ? 'hidden style="display:none;"' : '' ?>>
    <div class="eyebrow" style="margin-bottom:14px; font-weight:700; color:var(--accent-deep); text-transform:uppercase; letter-spacing:0.5px;">
      Detalle de la referencia (Anexo 2)
    </div>

    <!-- N.° de referencias institucionales -->
    <div class="field" style="margin-bottom:14px; max-width:280px;">
      <label class="fl">N.° de referencias institucionales</label>
      <div class="control">
        <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95NumRefInstInput" name="campo_14346" value="<?= $valNumRefInst ?>" placeholder="0" class="solo-enteros">
      </div>
    </div>

    <!-- Fecha y Hora de Ingreso al EE.SS. origen -->
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Fecha de ingreso al EE.SS. origen</label>
        <div class="control mono">
          <input type="date" id="o95FechaIngOrigenInput" name="campo_16148" value="<?= e($valFechaIngOrigen) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">Hora de ingreso (HH:MM)</label>
        <div class="control mono">
          <input type="time" id="o95HoraIngOrigenInput" name="campo_16149" value="<?= e($valHoraIngOrigen) ?>">
        </div>
      </div>
    </div>

    <!-- Fecha y Hora de Egreso del EE.SS. origen -->
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Fecha de egreso del EE.SS. origen</label>
        <div class="control mono">
          <input type="date" id="o95FechaEgrOrigenInput" name="campo_16150" value="<?= e($valFechaEgrOrigen) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">Hora de egreso (HH:MM)</label>
        <div class="control mono">
          <input type="time" id="o95HoraEgrOrigenInput" name="campo_16151" value="<?= e($valHoraEgrOrigen) ?>">
        </div>
      </div>
    </div>

    <!-- Tiempo de demora en llegar al EE.SS. destino -->
    <div class="field" style="margin-bottom:14px;">
      <label class="fl">Tiempo de demora en llegar al EE.SS. destino</label>
      <div class="fields pairs" style="margin-top:6px; max-width:400px;">
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95DemoraDiasInput" name="campo_16152" value="<?= $valDemoraDias ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Días</span>
        </div>
        <div class="field-inline" style="display:flex; align-items:center; gap:8px;">
          <div class="control" style="width:90px;">
            <input type="number" min="0" step="1" inputmode="numeric" pattern="[0-9]*" id="o95DemoraHorasInput" name="campo_16153" value="<?= $valDemoraHoras ?>" placeholder="0" class="solo-enteros" style="text-align:center;">
          </div>
          <span style="font-size:0.875rem;">Horas</span>
        </div>
      </div>
    </div>

    <!-- Responsable de la atención en EE.SS. origen -->
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Responsable de la atención en EE.SS. origen</label>
        <div class="control">
          <select id="o95RespOrigenSel" name="campo_16154">
            <option value="">Seleccionar responsable…</option>
            <?php foreach (['Médico G-O', 'Médico intensivista', 'Médico residente', 'Médico general', 'Obstetra', 'Enfermera(o)', 'Interno', 'Técnico', 'Otro', 'Desconocido'] as $respItem): ?>
              <option value="<?= $respItem ?>" <?= seleccionado($valRespOrigen, $respItem) ?>><?= $respItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field" id="bloqueRespOrigenOtroO95" <?= !$esRespOrigenOtro ? 'hidden style="display:none;"' : '' ?>>
        <label class="fl">Especificar otro responsable</label>
        <div class="control">
          <input type="text" name="campo_16155" value="<?= e($valRespOrigenOtro) ?>" placeholder="Especificar profesión/cargo…">
        </div>
      </div>
    </div>

    <!-- Institución y EE.SS. destino de la referencia -->
    <div class="fields pairs" style="margin-bottom:14px;">
      <div class="field">
        <label class="fl">Institución destino de la referencia</label>
        <div class="control">
          <select name="campo_16156">
            <option value="">Seleccionar institución…</option>
            <?php foreach (['EESS IGSS / Gobierno Regional', 'EESS EsSalud', 'EESS SSFFAA / PNP', 'EESS Privado'] as $instItem): ?>
              <option value="<?= $instItem ?>" <?= seleccionado($valInstDestino, $instItem) ?>><?= $instItem ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label class="fl">EE.SS. destino de la referencia</label>
        <div class="control">
          <input type="text" name="campo_16157" value="<?= e($valEessDestino) ?>" placeholder="Nombre del EE.SS. destino…">
        </div>
      </div>
    </div>

    <!-- Fecha y Hora de Ingreso al EE.SS. destino -->
    <div class="fields pairs">
      <div class="field">
        <label class="fl">Fecha de ingreso al EE.SS. destino</label>
        <div class="control mono">
          <input type="date" name="campo_16158" value="<?= e($valFechaIngDestino) ?>" max="<?= date('Y-m-d') ?>">
        </div>
      </div>
      <div class="field">
        <label class="fl">Hora de ingreso EE.SS. destino (HH:MM)</label>
        <div class="control mono">
          <input type="time" name="campo_16159" value="<?= e($valHoraIngDestino) ?>">
        </div>
      </div>
    </div>
  </div>
</div>
