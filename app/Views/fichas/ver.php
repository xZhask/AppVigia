<?php
use App\Core\Csrf;
use App\Models\CampoDef;

$clasificaciones = [
    'SOSPECHOSO' => ['dot' => 'dot-sos', 'etiqueta' => 'Sospechoso'],
    'PROBABLE'   => ['dot' => 'dot-pro', 'etiqueta' => 'Probable'],
    'CONFIRMADO' => ['dot' => 'dot-con', 'etiqueta' => 'Confirmado'],
    'DESCARTADO' => ['dot' => 'dot-des', 'etiqueta' => 'Descartado'],
];
$estados = [
    'ABIERTA'    => ['dot' => 'st-open',   'etiqueta' => 'Abierta'],
    'VALIDACION' => ['dot' => 'st-val',    'etiqueta' => 'Validación'],
    'CERRADA'    => ['dot' => 'st-closed', 'etiqueta' => 'Cerrada'],
];
$c = $clasificaciones[$caso['clasificacion']];
$es = $estados[$caso['estado']];
$edad = edadDesdeFecha($caso['fecha_nac']);

$situacionEtiquetas = ['ACTIVIDAD' => 'Actividad', 'RETIRO' => 'Retiro', 'DISPONIBILIDAD' => 'Disponibilidad'];
$beneficiarioEtiquetas = ['TITULAR' => 'Titular', 'DERECHOHABIENTE' => 'Derechohabiente'];
$accionEtiquetas = [
    'CREACION'      => 'Creación',
    'EDICION'       => 'Edición',
    'CLASIFICACION' => 'Cambio de clasificación',
    'CIERRE'        => 'Cierre',
    'ANULACION'     => 'Anulación',
];

?>
<div class="page-head">
  <div>
    <div class="page-title">Ficha <span class="mono"><?= e($caso['codigo']) ?></span></div>
    <div class="page-desc"><?= e($caso['enfermedad_nombre']) ?> · <?= e($caso['establecimiento_nombre']) ?></div>
  </div>
  <div class="spacer"></div>
  <span class="chip"><span class="dot <?= $c['dot'] ?>"></span> <?= $c['etiqueta'] ?></span>
  <?php if ($caso['anulado']): ?>
    <span class="state"><span class="dot st-closed"></span> Anulada</span>
  <?php else: ?>
    <span class="state"><span class="dot <?= $es['dot'] ?>"></span> <?= $es['etiqueta'] ?></span>
  <?php endif; ?>
</div>

<?php if ($caso['anulado']): ?>
  <div class="dupe" style="margin-bottom:18px">
    <span class="di"><svg width="17" height="17" viewBox="0 0 17 17"><path d="M8.5 1.5 16 15H1L8.5 1.5Z" stroke="currentColor" stroke-width="1.3" fill="none" stroke-linejoin="round"/><path d="M8.5 6.5v3.5M8.5 12.3v.1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg></span>
    <div class="body"><b>Ficha anulada.</b> Motivo: <?= e($caso['motivo_anulacion']) ?></div>
  </div>
<?php endif; ?>

<div class="grid form-grid">
  <div>
    <!-- Notificación -->
    <div class="card section">
      <div class="section-head"><span class="section-num">1</span><h3>Notificación</h3></div>
      <div class="section-body">
        <div class="fields thirds">
          <div class="field"><label class="fl">Establecimiento</label><div class="control" style="background:var(--paper)"><?= e($caso['establecimiento_nombre']) ?></div></div>
          <div class="field"><label class="fl">Red de salud</label><div class="control" style="background:var(--paper)"><?= e($caso['red_nombre'] ?? '—') ?></div></div>
          <div class="field"><label class="fl">Fecha de notificación</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['fecha_notif'])) ?></div></div>
          <div class="field"><label class="fl">Semana epidemiológica</label><div class="control mono" style="background:var(--paper)">SE <?= (int) $caso['semana_epi'] ?> · <?= (int) $caso['anio_epi'] ?></div></div>
          <div class="field"><label class="fl">Registrado por</label><div class="control" style="background:var(--paper)"><?= e($caso['usuario_nombre']) ?></div></div>
          <div class="field"><label class="fl">Fecha de registro</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['creado_en'])) ?></div></div>
        </div>
      </div>
    </div>

    <!-- Paciente -->
    <div class="card section">
      <div class="section-head"><span class="section-num">2</span><h3>Datos del paciente</h3></div>
      <div class="section-body">
        <div class="fields thirds">
          <div class="field wide"><label class="fl">Apellidos y nombres</label><div class="control" style="background:var(--paper)"><?= e($caso['apellidos_nombres']) ?></div></div>
          <div class="field"><label class="fl">Documento</label><div class="control mono" style="background:var(--paper)"><?= e($caso['tipo_doc']) ?> <?= e($caso['num_doc']) ?></div></div>
          <div class="field"><label class="fl">Sexo</label><div class="control" style="background:var(--paper)"><?= $caso['sexo'] === 'F' ? 'Femenino' : ($caso['sexo'] === 'M' ? 'Masculino' : '—') ?></div></div>
          <div class="field"><label class="fl">Edad</label><div class="control mono" style="background:var(--paper)"><?= $edad !== null ? $edad . ' años' : '—' ?></div></div>
          <div class="field"><label class="fl">Distrito de domicilio</label><div class="control" style="background:var(--paper)"><?= e($caso['distrito_nombre'] ?? '—') ?></div></div>
        </div>
        <?php if ($caso['es_pnp']): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Datos PNP</div>
          <div class="fields thirds">
            <div class="field"><label class="fl">Grado</label><div class="control" style="background:var(--paper)"><?= e($caso['grado_nombre'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Situación</label><div class="control" style="background:var(--paper)"><?= e($situacionEtiquetas[$caso['situacion_pnp']] ?? '—') ?></div></div>
            <div class="field"><label class="fl">CIP</label><div class="control mono" style="background:var(--paper)"><?= e($caso['cip'] ?? '—') ?></div></div>
            <div class="field wide"><label class="fl">Unidad / dependencia</label><div class="control" style="background:var(--paper)"><?= e($caso['unidad_nombre'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Tipo de beneficiario</label><div class="control" style="background:var(--paper)"><?= e($beneficiarioEtiquetas[$caso['tipo_beneficiario']] ?? '—') ?></div></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Cuadro clínico (dinámico) -->
    <?php $numeroSeccion = 3; foreach ($secciones as $seccion): ?>
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3><?= e($seccion['nombre']) ?></h3></div>
        <div class="section-body">
          <?php if ($numeroSeccion === 3): ?>
            <div class="fields" style="margin-bottom:16px">
              <div class="field"><label class="fl">Fecha de inicio de síntomas</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['fecha_inicio_sintomas'])) ?: '—' ?></div></div>
            </div>
          <?php endif; ?>
          <div class="fields thirds">
            <?php foreach (CampoDef::porSeccion((int) $seccion['id']) as $campo): ?>
              <div class="field">
                <label class="fl"><?= e($campo['etiqueta']) ?></label>
                <div class="control" style="background:var(--paper)"><?= e(campoValorTexto($campo, $valoresCampos[$campo['id']] ?? null)) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php $numeroSeccion++; endforeach; ?>

    <!-- Antecedentes epidemiológicos -->
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Antecedentes epidemiológicos</h3></div>
      <div class="section-body">
        <div class="eyebrow" style="margin-bottom:10px">Contactos</div>
        <?php if (empty($contactos)): ?>
          <p style="color:var(--muted);font-size:13px;margin:0 0 18px">No se registraron contactos.</p>
        <?php else: foreach ($contactos as $ct): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field"><label class="fl">Nombres</label><div class="control" style="background:var(--paper)"><?= e($ct['nombres']) ?></div></div>
            <div class="field"><label class="fl">Parentesco</label><div class="control" style="background:var(--paper)"><?= e($ct['parentesco'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Documento</label><div class="control mono" style="background:var(--paper)"><?= e($ct['doc'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Celular</label><div class="control mono" style="background:var(--paper)"><?= e($ct['celular'] ?? '—') ?></div></div>
          </div></div>
        <?php endforeach; endif; ?>

        <div class="eyebrow" style="margin:18px 0 10px">Viajes</div>
        <?php if (empty($viajes)): ?>
          <p style="color:var(--muted);font-size:13px;margin:0 0 18px">No se registraron viajes.</p>
        <?php else: foreach ($viajes as $vj): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field"><label class="fl">Lugar visitado</label><div class="control" style="background:var(--paper)"><?= e($vj['pais'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de salida</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vj['fecha_salida']) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de retorno</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vj['fecha_retorno']) ?: '—') ?></div></div>
          </div></div>
        <?php endforeach; endif; ?>

        <div class="eyebrow" style="margin:18px 0 10px">Antecedentes vacunales</div>
        <?php if (empty($vacunas)): ?>
          <p style="color:var(--muted);font-size:13px;margin:0">No se registraron antecedentes vacunales.</p>
        <?php else: foreach ($vacunas as $vc): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field"><label class="fl">Vacuna</label><div class="control" style="background:var(--paper)"><?= e($vc['vacuna']) ?></div></div>
            <div class="field"><label class="fl">Dosis</label><div class="control" style="background:var(--paper)"><?= e($vc['dosis'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Fecha</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vc['fecha']) ?: '—') ?></div></div>
          </div></div>
        <?php endforeach; endif; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>

    <!-- Laboratorio -->
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
      <div class="section-body">
        <?php if (empty($muestras)): ?>
          <p style="color:var(--muted);font-size:13px;margin:0">No se registraron muestras.</p>
        <?php else: foreach ($muestras as $ms): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field"><label class="fl">Tipo de muestra</label><div class="control" style="background:var(--paper)"><?= e($ms['tipo_muestra'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Tipo de prueba</label><div class="control" style="background:var(--paper)"><?= e($ms['tipo_prueba'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Resultado</label><div class="control" style="background:var(--paper)"><?= e($ms['resultado'] ?? 'Pendiente') ?></div></div>
            <div class="field"><label class="fl">Fecha de toma</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ms['fecha_toma']) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de resultado</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ms['fecha_result']) ?: '—') ?></div></div>
          </div></div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <!-- Right rail -->
  <aside class="rail">
    <?php if (!$caso['anulado']): ?>
      <div class="card rail-card">
        <div class="eyebrow" style="margin-bottom:12px">Acciones</div>
        <div class="rail-actions">
          <?php if ($puedeEditar): ?>
            <a class="btn btn-primary" href="/casos/<?= (int) $caso['id'] ?>/editar">
              <svg width="14" height="14" viewBox="0 0 14 14"><path d="M10 2.5 12.5 5 5 12.5 2 13l.5-3L10 2.5Z" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linejoin="round"/></svg>
              Editar ficha
            </a>
          <?php endif; ?>

          <?php if ($caso['estado'] === 'ABIERTA' && $puedeEditar): ?>
            <form method="post" action="/casos/<?= (int) $caso['id'] ?>/estado">
              <?= Csrf::campoOculto() ?>
              <input type="hidden" name="estado" value="VALIDACION">
              <button class="btn btn-ghost" type="submit" style="width:100%">Enviar a validación</button>
            </form>
          <?php endif; ?>

          <?php if ($caso['estado'] === 'VALIDACION' && $puedeCerrar): ?>
            <form method="post" action="/casos/<?= (int) $caso['id'] ?>/estado">
              <?= Csrf::campoOculto() ?>
              <input type="hidden" name="estado" value="CERRADA">
              <button class="btn btn-primary" type="submit" style="width:100%">Cerrar ficha</button>
            </form>
            <form method="post" action="/casos/<?= (int) $caso['id'] ?>/estado">
              <?= Csrf::campoOculto() ?>
              <input type="hidden" name="estado" value="ABIERTA">
              <button class="btn btn-ghost" type="submit" style="width:100%">Devolver a abierta</button>
            </form>
          <?php endif; ?>

          <?php if ($puedeAnular): ?>
            <form method="post" action="/casos/<?= (int) $caso['id'] ?>/anular" onsubmit="return pedirMotivoAnulacion(this)">
              <?= Csrf::campoOculto() ?>
              <input type="hidden" name="motivo" value="">
              <button class="btn btn-ghost" type="submit" style="width:100%;color:var(--s-confirmado)">Anular ficha</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="card rail-card">
      <div class="eyebrow" style="margin-bottom:12px">Bitácora</div>
      <?php if (empty($bitacora)): ?>
        <p style="color:var(--muted);font-size:12.5px;margin:0">Sin movimientos registrados.</p>
      <?php else: ?>
        <div style="display:grid;gap:12px">
          <?php foreach ($bitacora as $mov): ?>
            <div style="font-size:12.5px">
              <div style="font-weight:600;color:var(--ink)"><?= e($accionEtiquetas[$mov['accion']] ?? $mov['accion']) ?></div>
              <div class="mono" style="color:var(--faint);margin:2px 0"><?= e(date('d/m/Y H:i', strtotime($mov['fecha']))) ?> · <?= e($mov['usuario_nombre'] ?? 'Sistema') ?></div>
              <?php if (!empty($mov['detalle'])): ?><div style="color:var(--muted)"><?= e($mov['detalle']) ?></div><?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </aside>
</div>

<script>
function pedirMotivoAnulacion(form) {
  var motivo = prompt('Motivo de anulación de la ficha:');
  if (!motivo || !motivo.trim()) return false;
  form.motivo.value = motivo.trim();
  return true;
}
</script>
