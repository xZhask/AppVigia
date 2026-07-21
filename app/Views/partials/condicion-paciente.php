<?php
/**
 * Condición del paciente (CAMBIOS_CONDICION_PACIENTE.md): tarjetas de radio
 * EFECTIVO / DERECHOHABIENTE / PARTICULAR + campos propios de cada una.
 * Compartido por "Nueva ficha" y "Editar ficha" para que no diverjan.
 *
 * Variables esperadas:
 *   $condicionPaciente  'EFECTIVO'|'DERECHOHABIENTE'|'PARTICULAR'
 *   $valoresPnp          ['cip','situacion_pnp','grado_id','categoria_pnp',
 *                         'vinculo_titular','doc_titular','titular_id','titular_nombre']
 *   $grados              filas de grado_pnp (GradoPnp::todos('jerarquia'))
 */
$nombresNiveles = [
    'OFICIAL_GENERAL' => 'Oficiales generales',
    'OFICIAL_SUPERIOR' => 'Oficiales superiores',
    'OFICIAL_SUBALTERNO' => 'Oficiales subalternos',
    'SUBOFICIAL' => 'Suboficiales',
    'CADETE' => 'Personal en formación',
    'ALUMNO' => 'Personal en formación',
    'EMPLEADO_CIVIL' => 'Empleados civiles',
];
?>
<div class="eyebrow" style="margin-bottom:11px">Condición del paciente</div>

<div class="cond-pick">
  <div class="cond-opt">
    <input type="radio" name="condicion" id="c-efectivo" value="EFECTIVO" <?= seleccionado($condicionPaciente, 'EFECTIVO') ?>>
    <label for="c-efectivo">
      <span class="cond-radio"></span>
      <span class="cond-txt">
        <span class="cond-name">Efectivo PNP</span>
        <span class="cond-desc">Titular de la Sanidad PNP</span>
      </span>
    </label>
  </div>
  <div class="cond-opt">
    <input type="radio" name="condicion" id="c-derecho" value="DERECHOHABIENTE" <?= seleccionado($condicionPaciente, 'DERECHOHABIENTE') ?>>
    <label for="c-derecho">
      <span class="cond-radio"></span>
      <span class="cond-txt">
        <span class="cond-name">Derechohabiente</span>
        <span class="cond-desc">Familiar de un efectivo</span>
      </span>
    </label>
  </div>
  <div class="cond-opt">
    <input type="radio" name="condicion" id="c-particular" value="PARTICULAR" <?= seleccionado($condicionPaciente, 'PARTICULAR') ?>>
    <label for="c-particular">
      <span class="cond-radio"></span>
      <span class="cond-txt">
        <span class="cond-name">Particular</span>
        <span class="cond-desc">Sin vínculo con la PNP</span>
      </span>
    </label>
  </div>
</div>

<!-- Panel: efectivo -->
<div class="cond-panel" id="p-efectivo" <?= $condicionPaciente === 'EFECTIVO' ? '' : 'hidden' ?>>
  <div class="cond-title"><span class="eyebrow">Datos del efectivo</span><span class="rule"></span></div>
  <div class="fields thirds">
    <div class="field half">
      <label class="fl">Grado</label>
      <div class="control">
        <select id="gradoId" name="grado_id">
          <option value="">Seleccionar…</option>
          <?php
          $grupoActual = '';
          foreach ($grados as $grado):
              $nombreGrupo = $nombresNiveles[$grado['nivel']] ?? $grado['nivel'];
              if ($nombreGrupo !== $grupoActual):
                  if ($grupoActual !== '') echo '</optgroup>';
                  $grupoActual = $nombreGrupo;
                  echo '<optgroup label="' . e($grupoActual) . '">';
              endif;
          ?>
            <option value="<?= (int) $grado['id'] ?>" data-nivel="<?= e($grado['nivel']) ?>" <?= seleccionado($valoresPnp['grado_id'], $grado['id']) ?>><?= e($grado['nombre']) ?></option>
          <?php endforeach;
          if ($grupoActual !== '') echo '</optgroup>';
          ?>
        </select>
      </div>
    </div>
    <div class="field" id="campoCategoria">
      <label class="fl">Categoría</label>
      <div class="control">
        <select id="categoriaPnp" name="categoria_pnp" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="ARMAS" <?= seleccionado($valoresPnp['categoria_pnp'], 'ARMAS') ?>>Armas</option>
          <option value="SERVICIOS" <?= seleccionado($valoresPnp['categoria_pnp'], 'SERVICIOS') ?>>Servicios</option>
          <option value="ASIMILADO" <?= seleccionado($valoresPnp['categoria_pnp'], 'ASIMILADO') ?>>Asimilado</option>
        </select>
      </div>
      <span class="hint">Solo para oficiales y suboficiales</span>
    </div>
    <div class="field">
      <label class="fl">Situación</label>
      <div class="control">
        <select id="situacionPnp" name="situacion_pnp" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="ACTIVIDAD" <?= seleccionado($valoresPnp['situacion_pnp'], 'ACTIVIDAD') ?>>Actividad</option>
          <option value="RETIRO" <?= seleccionado($valoresPnp['situacion_pnp'], 'RETIRO') ?>>Retiro</option>
          <option value="DISPONIBILIDAD" <?= seleccionado($valoresPnp['situacion_pnp'], 'DISPONIBILIDAD') ?>>Disponibilidad</option>
        </select>
      </div>
    </div>
    <div class="field half" id="campoCip">
      <label class="fl">CIP</label>
      <div class="control mono"><input type="text" id="cip" name="cip" value="<?= e($valoresPnp['cip']) ?>" maxlength="12"></div>
      <span class="hint">Carné de Identidad Personal</span>
    </div>
  </div>
</div>

<!-- Panel: derechohabiente -->
<div class="cond-panel" id="p-derecho" <?= $condicionPaciente === 'DERECHOHABIENTE' ? '' : 'hidden' ?>>
  <div class="cond-title"><span class="eyebrow">Titular del que depende</span><span class="rule"></span></div>
  <div class="fields thirds">
    <div class="field">
      <label class="fl">Vínculo con el titular</label>
      <div class="control">
        <select id="vinculoTitular" name="vinculo_titular" data-nosearch="true">
          <option value="">Seleccionar…</option>
          <option value="CONYUGE" <?= seleccionado($valoresPnp['vinculo_titular'], 'CONYUGE') ?>>Cónyuge</option>
          <option value="CONVIVIENTE" <?= seleccionado($valoresPnp['vinculo_titular'], 'CONVIVIENTE') ?>>Conviviente</option>
          <option value="HIJO" <?= seleccionado($valoresPnp['vinculo_titular'], 'HIJO') ?>>Hijo(a)</option>
          <option value="PADRE" <?= seleccionado($valoresPnp['vinculo_titular'], 'PADRE') ?>>Padre</option>
          <option value="MADRE" <?= seleccionado($valoresPnp['vinculo_titular'], 'MADRE') ?>>Madre</option>
          <option value="OTRO" <?= seleccionado($valoresPnp['vinculo_titular'], 'OTRO') ?>>Otro</option>
        </select>
      </div>
    </div>
    <div class="field">
      <label class="fl">Documento del titular</label>
      <div class="control mono"><input type="text" id="docTitular" placeholder="DNI del efectivo" value="<?= e($valoresPnp['doc_titular']) ?>" maxlength="15"></div>
    </div>
    <div class="field" style="justify-content:flex-end">
      <button type="button" class="control" id="btnBuscarTitular" style="justify-content:center;gap:7px;color:var(--ink-2);font-weight:500">
        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="6" cy="6" r="4" stroke="currentColor" stroke-width="1.3"/><path d="m9 9 3 3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>
        Buscar titular
      </button>
    </div>
    <input type="hidden" id="titularId" name="titular_id" value="<?= e($valoresPnp['titular_id']) ?>">
    <div class="field wide">
      <span class="hint" id="titularEncontrado"><?= $valoresPnp['titular_nombre'] !== '' ? 'Vinculado a: ' . e($valoresPnp['titular_nombre']) : 'Vincular al titular permite detectar conglomerados familiares. Es opcional: si no se conoce, puede dejarse vacío.' ?></span>
    </div>
  </div>
</div>

<!-- Panel: particular -->
<div class="cond-panel" id="p-particular" <?= $condicionPaciente === 'PARTICULAR' ? '' : 'hidden' ?>>
  <span class="hint">Sin datos institucionales adicionales. La ficha se registra igual y entra en los reportes.</span>
</div>
