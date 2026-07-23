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
$etniaEtiquetas = [
    'MESTIZO' => 'Mestizo', 'ANDINO' => 'Andino', 'ASIATICO_DESCENDIENTE' => 'Asiático descendiente',
    'AFRODESCENDIENTE' => 'Afrodescendiente', 'INDIGENA_AMAZONICO' => 'Indígena amazónico', 'OTRO' => 'Otro',
];
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
        <?php if ($caso['tipo_captacion'] || $caso['lugar_captacion'] || $caso['clasificacion_captacion']): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Captación</div>
          <div class="fields thirds">
            <div class="field"><label class="fl">Tipo de captación</label><div class="control" style="background:var(--paper)"><?= $caso['tipo_captacion'] === 'ACTIVA' ? 'Activa' : ($caso['tipo_captacion'] === 'PASIVA' ? 'Pasiva' : '—') ?></div></div>
            <div class="field"><label class="fl">Lugar de captación</label><div class="control" style="background:var(--paper)"><?= $caso['lugar_captacion'] === 'INSTITUCIONAL' ? 'Institucional' : ($caso['lugar_captacion'] === 'COMUNIDAD' ? 'Comunidad' : '—') ?></div></div>
            <div class="field"><label class="fl">Clasificación en la captación</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower((string) $caso['clasificacion_captacion'])) ?: '—') ?></div></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Paciente -->
    <div class="card section">
      <div class="section-head"><span class="section-num">2</span><h3>Datos del paciente</h3></div>
      <div class="section-body">
        <div class="fields thirds">
          <div class="field"><label class="fl">Apellido paterno</label><div class="control" style="background:var(--paper)"><?= e($caso['apellido_paterno'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Apellido materno</label><div class="control" style="background:var(--paper)"><?= e($caso['apellido_materno'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Nombres</label><div class="control" style="background:var(--paper)"><?= e($caso['nombres'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Documento</label><div class="control mono" style="background:var(--paper)"><?= e($caso['tipo_doc']) ?> <?= e($caso['num_doc']) ?></div></div>
          <div class="field"><label class="fl">Sexo</label><div class="control" style="background:var(--paper)"><?= $caso['sexo'] === 'F' ? 'Femenino' : ($caso['sexo'] === 'M' ? 'Masculino' : '—') ?></div></div>
          <div class="field"><label class="fl">Edad</label><div class="control mono" style="background:var(--paper)"><?= $edad !== null ? $edad . ' años' : '—' ?></div></div>
          <div class="field"><label class="fl">Distrito de domicilio</label><div class="control" style="background:var(--paper)"><?= e($caso['distrito_nombre'] ?? '—') ?></div></div>
          <div class="field"><label class="fl">N.° de celular</label><div class="control mono" style="background:var(--paper)"><?= e($caso['celular'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Nacionalidad</label><div class="control" style="background:var(--paper)"><?= e($caso['nacionalidad'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Localidad</label><div class="control" style="background:var(--paper)"><?= e($caso['localidad'] ?: '—') ?></div></div>
          <div class="field wide"><label class="fl">Domicilio actual</label><div class="control" style="background:var(--paper)"><?= e($caso['direccion'] ?: '—') ?></div></div>
          <?php if (\App\Core\Auth::tieneRol('ADMIN')): ?>
            <div class="field"><label class="fl">Etnia / raza</label><div class="control" style="background:var(--paper)"><?= e($etniaEtiquetas[$caso['etnia'] ?? ''] ?? '—') ?></div></div>
          <?php endif; ?>
          <?php if ($caso['gestante']): ?>
            <div class="field"><label class="fl">Gestante</label><div class="control" style="background:var(--paper)">Sí<?= $caso['semanas_gestacion'] ? ' · ' . (int) $caso['semanas_gestacion'] . ' semanas' : '' ?></div></div>
          <?php endif; ?>
        </div>
        <?php if (($caso['condicion'] ?? 'PARTICULAR') !== 'PARTICULAR'): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Condición del paciente</div>
          <div class="fields thirds">
            <div class="field"><label class="fl">Condición</label><div class="control" style="background:var(--paper)"><?= $caso['condicion'] === 'EFECTIVO' ? 'Efectivo PNP' : 'Derechohabiente' ?></div></div>
            <div class="field wide"><label class="fl"><?= $caso['condicion'] === 'EFECTIVO' ? 'Detalle' : 'Vínculo' ?></label><div class="control" style="background:var(--paper)"><?= e(\App\Models\Persona::descripcionPnp($caso) ?: '—') ?></div></div>
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
            <div class="field"><label class="fl">Edad</label><div class="control mono" style="background:var(--paper)"><?= e($ct['edad'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Sexo</label><div class="control" style="background:var(--paper)"><?= $ct['sexo'] === 'F' ? 'Femenino' : ($ct['sexo'] === 'M' ? 'Masculino' : '—') ?></div></div>
            <div class="field"><label class="fl">Vacunado</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower((string) ($ct['vacunado'] ?? ''))) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de vacunación</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ct['fecha_vacunacion']) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Profilaxis</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower((string) ($ct['profilaxis'] ?? ''))) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Documento</label><div class="control mono" style="background:var(--paper)"><?= e($ct['doc'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Celular</label><div class="control mono" style="background:var(--paper)"><?= e($ct['celular'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de contacto</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ct['fecha_contacto'] ?? null) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Lugar de contacto</label><div class="control" style="background:var(--paper)"><?= e($ct['lugar_contacto'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de inicio de erupción</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ct['fecha_inicio_erupcion'] ?? null) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Vacunado &lt;72h del contacto</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower((string) ($ct['vacunado_72h'] ?? ''))) ?: '—') ?></div></div>
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
            <div class="field"><label class="fl">Fabricante</label><div class="control" style="background:var(--paper)"><?= e($vc['fabricante'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Lote</label><div class="control mono" style="background:var(--paper)"><?= e($vc['lote'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Vía</label><div class="control" style="background:var(--paper)"><?= e($vc['via'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Sitio</label><div class="control" style="background:var(--paper)"><?= e($vc['sitio'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de vencimiento</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vc['fecha_vencimiento'] ?? null) ?: '—') ?></div></div>
            <div class="field"><label class="fl">EE.SS. que vacunó</label><div class="control" style="background:var(--paper)"><?= e($vc['establecimiento'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Adyuvante</label><div class="control" style="background:var(--paper)"><?= e($vc['adyuvante'] ?? '—') ?></div></div>
          </div></div>
        <?php endforeach; endif; ?>

        <?php if (!empty($lugaresInfeccion)): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Lugar probable de infección</div>
          <?php foreach ($lugaresInfeccion as $li): ?>
            <div class="subrow"><div class="fields thirds" style="flex:1">
              <div class="field"><label class="fl">Lugar o institución</label><div class="control" style="background:var(--paper)"><?= e($li['lugar_institucion'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Localidad</label><div class="control" style="background:var(--paper)"><?= e($li['localidad_texto'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Permanencia (días)</label><div class="control mono" style="background:var(--paper)"><?= e($li['permanencia_dias'] ?? '—') ?></div></div>
            </div></div>
          <?php endforeach; ?>
        <?php endif; ?>

        <?php if (($caso['cie10'] ?? null) === 'P96' && !empty($sujetoMadre)): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Residencia habitual de la madre</div>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field wide"><label class="fl">Dirección</label><div class="control" style="background:var(--paper)"><?= e($sujetoMadre['direccion'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Distrito</label><div class="control" style="background:var(--paper)"><?= e($sujetoMadre['distrito_nombre'] ?? '—') ?></div></div>
          </div></div>
        <?php endif; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>

    <!-- Laboratorio -->
    <?php if (!empty($muestras)): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
      <div class="section-body">
        <?php foreach ($muestras as $ms): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <div class="field"><label class="fl">Tipo de muestra</label><div class="control" style="background:var(--paper)"><?= e($ms['tipo_muestra'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">Tipo de prueba</label><div class="control" style="background:var(--paper)"><?= e($ms['tipo_prueba'] ?? '—') ?></div></div>
            <div class="field"><label class="fl">¿Recibió antibiótico?</label><div class="control" style="background:var(--paper)"><?= $ms['recibio_antibiotico'] === null ? '—' : ($ms['recibio_antibiotico'] ? 'Sí' : 'No') ?></div></div>
            <div class="field"><label class="fl">Resultado</label><div class="control" style="background:var(--paper)"><?= e($ms['resultado'] ?? 'Pendiente') ?></div></div>
            <div class="field"><label class="fl">Fecha de toma</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ms['fecha_toma']) ?: '—') ?></div></div>
            <div class="field"><label class="fl">Fecha de resultado</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ms['fecha_result']) ?: '—') ?></div></div>
          </div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>
    <?php endif; ?>

    <!-- Investigador -->
    <?php if ($caso['investigador_nombre'] || $caso['investigador_cargo'] || $caso['fecha_investigacion']): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Investigador</h3></div>
      <div class="section-body">
        <div class="fields thirds">
          <div class="field"><label class="fl">Investigador / responsable</label><div class="control" style="background:var(--paper)"><?= e($caso['investigador_nombre'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Cargo</label><div class="control" style="background:var(--paper)"><?= e($caso['investigador_cargo'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Fecha de investigación</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['fecha_investigacion']) ?: '—') ?></div></div>
        </div>
      </div>
    </div>
    <?php $numeroSeccion++; ?>
    <?php endif; ?>
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
