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
$c = $clasificaciones[$caso['clasificacion']] ?? ['dot' => 'dot-sos', 'etiqueta' => $caso['clasificacion']];
$es = $estados[$caso['estado']];
// nucleo_omitidos: 'clasificacion' (cotejo Z21, 2026-09-11) -- las fichas
// cuyo PDF no trae "Clasificación del caso" tampoco muestran su chip acá; el
// valor de caso.clasificacion queda en el que la ficha use por defecto.
$mostrarClasificacionVer = !nucleoOmitido($enfermedadVer ?? [], 'clasificacion');
// campos_persona (Z21, 2026-09-12): campo_def que el formulario pinta dentro
// de la tarjeta de identidad (el "Código" de la gestante o del niño). Acá van
// en "Datos del paciente" y no se repiten en la tarjeta de su sección.
$clavesCamposPersonaVer = jsonDeEnfermedad($enfermedadVer ?? [], 'campos_persona');
$camposPersonaVer = array_filter(array_map(
    fn(string $clave) => CampoDef::porClave((int) ($enfermedadVer['id'] ?? 0), $clave),
    $clavesCamposPersonaVer
));
$edad = edadDesdeFecha($caso['fecha_nac']);

// Entrada F: si la ficha declaró unidades_edad, la edad capturada con su
// unidad manda sobre la derivada de fecha_nac -- son campos independientes
// que el PDF pide por separado, no uno derivado del otro (ver
// PETICION_MAPEO_Y_EDAD.md, Parte 2).
$etiquetasUnidadEdad = ['ANIOS' => 'años', 'MESES' => 'meses', 'DIAS' => 'días', 'HORAS' => 'horas', 'MINUTOS' => 'minutos'];
$unidadesEdadDeclaradas = [];
if (!empty($caso['enfermedad_unidades_edad'])) {
    $decodificadoUnidadesEdad = json_decode($caso['enfermedad_unidades_edad'], true);
    $unidadesEdadDeclaradas = is_array($decodificadoUnidadesEdad) ? $decodificadoUnidadesEdad : [];
}
if (!empty($unidadesEdadDeclaradas)) {
    $edadTexto = ($caso['edad_valor'] !== null && !empty($caso['edad_unidad']))
        ? $caso['edad_valor'] . ' ' . ($etiquetasUnidadEdad[$caso['edad_unidad']] ?? mb_strtolower($caso['edad_unidad']))
        : '—';
} else {
    $edadTexto = $edad !== null ? $edad . ' años' : '—';
}

$situacionEtiquetas = ['ACTIVIDAD' => 'Actividad', 'RETIRO' => 'Retiro', 'DISPONIBILIDAD' => 'Disponibilidad'];
$etniaEtiquetas = [
    'MESTIZO' => 'Mestizo', 'ANDINO' => 'Andino', 'ASIATICO_DESCENDIENTE' => 'Asiático descendiente',
    'AFRODESCENDIENTE' => 'Afrodescendiente', 'INDIGENA_AMAZONICO' => 'Indígena amazónico', 'OTRO' => 'Otro',
];
$tipoZonaEtiquetas = ['URBANO' => 'Urbano', 'PERIURBANO' => 'Periurbano', 'RURAL' => 'Rural'];
$estadoCivilEtiquetas = ['SOLTERO' => 'Soltero(a)', 'CASADO' => 'Casado(a)', 'CONVIVIENTE' => 'Conviviente', 'SEPARADO' => 'Separado(a)', 'VIUDO' => 'Viudo(a)'];
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
  <?php if ($mostrarClasificacionVer): ?><span class="chip"><span class="dot <?= $c['dot'] ?>"></span> <?= $c['etiqueta'] ?></span><?php endif; ?>
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
        <?php if (!in_array($enfermedad['cie10'] ?? null, ['A80', 'B05'], true) && ($caso['tipo_captacion'] || $caso['lugar_captacion'] || $caso['clasificacion_captacion'])): ?>
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
          <?php if (!empty($caso['n_historia_clinica'])): ?>
            <div class="field"><label class="fl">N.° de historia clínica</label><div class="control mono" style="background:var(--paper)"><?= e($caso['n_historia_clinica']) ?></div></div>
          <?php endif; foreach ($camposPersonaVer as $campoPersonaVer): ?><div class="field"><label class="fl"><?= e($campoPersonaVer['etiqueta']) ?></label><div class="control mono" style="background:var(--paper)"><?= e(campoValorTexto($campoPersonaVer, $valoresCampos[$campoPersonaVer['id']] ?? null)) ?></div></div><?php endforeach; ?>
          <div class="field"><label class="fl">Sexo</label><div class="control" style="background:var(--paper)"><?= $caso['sexo'] === 'F' ? 'Femenino' : ($caso['sexo'] === 'M' ? 'Masculino' : '—') ?></div></div>
          <div class="field"><label class="fl">Edad</label><div class="control mono" style="background:var(--paper)"><?= e($edadTexto) ?></div></div>
          <?php if (!empty($caso['nacimiento_distrito_nombre'])): ?>
            <div class="field"><label class="fl">Distrito de nacimiento</label><div class="control" style="background:var(--paper)"><?= e($caso['nacimiento_distrito_nombre']) ?></div></div>
          <?php endif; ?>
          <div class="field"><label class="fl">Distrito de domicilio</label><div class="control" style="background:var(--paper)"><?= e($caso['distrito_nombre'] ?? '—') ?></div></div>
          <div class="field"><label class="fl">N.° de celular</label><div class="control mono" style="background:var(--paper)"><?= e($caso['celular'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Nacionalidad</label><div class="control" style="background:var(--paper)"><?= e($caso['nacionalidad'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Localidad</label><div class="control" style="background:var(--paper)"><?= e($caso['localidad'] ?: '—') ?></div></div>
          <div class="field wide"><label class="fl">Domicilio actual</label><div class="control" style="background:var(--paper)"><?= e($caso['direccion'] ?: '—') ?></div></div>
          <?php if (!empty($caso['referencia_localizar'])): ?>
            <div class="field wide"><label class="fl">Referencia para localizar</label><div class="control" style="background:var(--paper)"><?= e($caso['referencia_localizar']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['tipo_zona'])): ?>
            <div class="field"><label class="fl">Tipo de zona</label><div class="control" style="background:var(--paper)"><?= e($tipoZonaEtiquetas[$caso['tipo_zona']] ?? $caso['tipo_zona']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['nombre_zona'])): ?>
            <div class="field"><label class="fl">Nombre de zona</label><div class="control" style="background:var(--paper)"><?= e($caso['nombre_zona']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['tipo_via'])): ?>
            <div class="field"><label class="fl">Tipo de vía</label><div class="control" style="background:var(--paper)"><?= e($caso['tipo_via']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['nombre_via'])): ?>
            <div class="field"><label class="fl">Nombre de vía</label><div class="control" style="background:var(--paper)"><?= e($caso['nombre_via']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['numero'])): ?>
            <div class="field"><label class="fl">Nro.</label><div class="control" style="background:var(--paper)"><?= e($caso['numero']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['mz_lote'])): ?>
            <div class="field"><label class="fl">Mz./Lote</label><div class="control" style="background:var(--paper)"><?= e($caso['mz_lote']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['tiempo_residencia'])): ?>
            <div class="field"><label class="fl">Tiempo de residencia</label><div class="control" style="background:var(--paper)"><?= e($caso['tiempo_residencia']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['ocupacion'])): ?>
            <div class="field"><label class="fl">Ocupación</label><div class="control" style="background:var(--paper)"><?= e($caso['ocupacion']) ?></div></div>
          <?php endif; ?>
          <?php if (!empty($caso['estado_civil'])): ?>
            <div class="field"><label class="fl">Estado civil</label><div class="control" style="background:var(--paper)"><?= e($estadoCivilEtiquetas[$caso['estado_civil']] ?? $caso['estado_civil']) ?></div></div>
          <?php endif; ?>
          <?php if (\App\Core\Auth::tieneRol('ADMIN')): ?>
            <div class="field"><label class="fl">Etnia / raza</label><div class="control" style="background:var(--paper)"><?= e(($etniaEtiquetas[$caso['etnia'] ?? ''] ?? '—') . (($caso['etnia'] ?? '') === 'OTRO' && !empty($caso['etnia_otra']) ? ' (' . $caso['etnia_otra'] . ')' : '')) ?></div></div>
            <?php if (!empty($caso['pueblo_etnico'])): ?>
              <div class="field"><label class="fl">Pueblo étnico o etnia</label><div class="control" style="background:var(--paper)"><?= e($caso['pueblo_etnico']) ?></div></div>
            <?php endif; ?>
          <?php endif; ?>
          <?php if (!empty($caso['nombre_tutor']) || !empty($caso['celular_tutor'])): ?>
            <div class="field wide"><label class="fl">Madre / Tutor / Responsable</label><div class="control" style="background:var(--paper)"><?= e($caso['nombre_tutor'] ?: '—') ?><?= !empty($caso['celular_tutor']) ? ' · N.° Celular: ' . e($caso['celular_tutor']) : '' ?></div></div>
          <?php endif; ?>
          <?php if ($caso['gestante']): ?>
            <div class="field"><label class="fl">Gestante</label><div class="control" style="background:var(--paper)">Sí<?= !empty($caso['trimestre_gestacion']) ? ' · Trimestre ' . e($caso['trimestre_gestacion']) : ($caso['semanas_gestacion'] ? ' · ' . (int) $caso['semanas_gestacion'] . ' semanas' : '') ?><?= !empty($caso['fur']) ? ' · FUR: ' . e(date('d/m/Y', strtotime($caso['fur']))) : '' ?></div></div>
          <?php endif; ?>
        </div>
        <?php if (!empty($caso['tiempo_reside_anios']) || !empty($caso['tiempo_reside_meses']) || !empty($caso['anterior_distrito_nombre']) || !empty($caso['anterior_nombre_zona']) || !empty($caso['anterior_nombre_via'])): ?>
          <div class="eyebrow" style="margin:18px 0 10px">Migración</div>
          <div class="fields thirds">
            <?php if (!empty($caso['tiempo_reside_anios']) || !empty($caso['tiempo_reside_meses'])): ?>
              <div class="field"><label class="fl">Tiempo que reside en domicilio actual</label><div class="control" style="background:var(--paper)"><?= (int) ($caso['tiempo_reside_anios'] ?? 0) ?> años, <?= (int) ($caso['tiempo_reside_meses'] ?? 0) ?> meses</div></div>
            <?php endif; ?>
          </div>
          <?php if (!empty($caso['anterior_distrito_nombre']) || !empty($caso['anterior_tipo_zona']) || !empty($caso['anterior_nombre_zona']) || !empty($caso['anterior_tipo_via']) || !empty($caso['anterior_nombre_via']) || !empty($caso['anterior_numero']) || !empty($caso['anterior_mz_lote'])): ?>
            <div class="eyebrow" style="margin:14px 0 10px">Domicilio anterior</div>
            <div class="fields thirds">
              <?php if (!empty($caso['anterior_distrito_nombre'])): ?>
                <div class="field"><label class="fl">Distrito</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_distrito_nombre']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_tipo_zona'])): ?>
                <div class="field"><label class="fl">Tipo de zona</label><div class="control" style="background:var(--paper)"><?= e($tipoZonaEtiquetas[$caso['anterior_tipo_zona']] ?? $caso['anterior_tipo_zona']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_nombre_zona'])): ?>
                <div class="field"><label class="fl">Nombre de zona</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_nombre_zona']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_tipo_via'])): ?>
                <div class="field"><label class="fl">Tipo de vía</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_tipo_via']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_nombre_via'])): ?>
                <div class="field"><label class="fl">Nombre de vía</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_nombre_via']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_numero'])): ?>
                <div class="field"><label class="fl">Nro.</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_numero']) ?></div></div>
              <?php endif; ?>
              <?php if (!empty($caso['anterior_mz_lote'])): ?>
                <div class="field"><label class="fl">Mz./Lote</label><div class="control" style="background:var(--paper)"><?= e($caso['anterior_mz_lote']) ?></div></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
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
    <?php
    // Una sección condicionada (seccion_def.depende_de) que no aplica a este
    // caso no se pinta acá: en Z21 (cotejo 2026-09-11) la mitad de las
    // secciones son de la otra rama de la ficha, y sin esto la ficha de un
    // niño nacido expuesto mostraría las secciones de la gestante llenas de
    // "—". Ninguna otra de las 24 fichas declara secciones condicionadas.
    $numeroSeccion = 3;
    foreach ($secciones as $seccion):
        if (!empty($seccion['depende_de']) && !campoVisiblePorDependencia(
            ['depende_de' => $seccion['depende_de'], 'valor_activador' => $seccion['valor_activador']],
            $valoresCampos
        )) {
            continue;
        }
        $camposSeccionVer = array_values(array_filter(
            CampoDef::porSeccion((int) $seccion['id']),
            fn(array $campoSeccion) => !in_array($campoSeccion['clave'], $clavesCamposPersonaVer, true)
        ));
        if (!$camposSeccionVer && $clavesCamposPersonaVer && CampoDef::porSeccion((int) $seccion['id'])) {
            continue; // todos sus campos ya están en "Datos del paciente"
        }
    ?>
      <div class="card section">
        <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3><?= e($seccion['nombre']) ?></h3></div>
        <div class="section-body">
          <?php if ($numeroSeccion === 3 && !nucleoOmitido($enfermedadVer ?? [], 'fecha_inicio_sintomas')): ?>
            <div class="fields" style="margin-bottom:16px">
              <div class="field"><label class="fl">Fecha de inicio de síntomas</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['fecha_inicio_sintomas'])) ?: '—' ?></div></div>
            </div>
          <?php endif; ?>
          <div class="fields thirds">
            <?php foreach ($camposSeccionVer as $campo): ?>
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
        <?php if (!empty($caso['lugar_contagio_distrito_nombre']) || !empty($caso['lugar_contagio_localidad'])): ?>
          <div class="eyebrow" style="margin-bottom:10px">Lugar probable de contagio</div>
          <div class="fields thirds" style="margin-bottom:18px">
            <?php if (!empty($caso['lugar_contagio_distrito_nombre'])): ?>
              <div class="field"><label class="fl">Distrito</label><div class="control" style="background:var(--paper)"><?= e($caso['lugar_contagio_distrito_nombre']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($caso['lugar_contagio_localidad'])): ?>
              <div class="field"><label class="fl">Localidad</label><div class="control" style="background:var(--paper)"><?= e($caso['lugar_contagio_localidad']) ?></div></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
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
            <?php if (!empty($vj['pais'])): ?>
            <div class="field"><label class="fl">Lugar visitado</label><div class="control" style="background:var(--paper)"><?= e($vj['pais']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['distrito_nombre'])): ?>
            <div class="field"><label class="fl">Distrito</label><div class="control" style="background:var(--paper)"><?= e($vj['distrito_nombre']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['localidad'])): ?>
            <div class="field"><label class="fl">Localidad/ciudad</label><div class="control" style="background:var(--paper)"><?= e($vj['localidad']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['direccion'])): ?>
            <div class="field"><label class="fl">Dirección</label><div class="control" style="background:var(--paper)"><?= e($vj['direccion']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['fecha_salida'])): ?>
            <div class="field"><label class="fl">Fecha de ingreso</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vj['fecha_salida'])) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['transporte_ida'])): ?>
            <div class="field"><label class="fl">Transporte ida</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower($vj['transporte_ida']))) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['fecha_retorno'])): ?>
            <div class="field"><label class="fl">Fecha de salida</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($vj['fecha_retorno'])) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['transporte_retorno'])): ?>
            <div class="field"><label class="fl">Transporte retorno</label><div class="control" style="background:var(--paper)"><?= e(ucfirst(strtolower($vj['transporte_retorno']))) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['tiempo_permanencia'])): ?>
            <div class="field"><label class="fl">Tiempo de permanencia</label><div class="control" style="background:var(--paper)"><?= e($vj['tiempo_permanencia']) ?></div></div>
            <?php endif; ?>
            <?php if (!empty($vj['semana_gestacion'])): ?>
            <div class="field"><label class="fl">Semana de gestación</label><div class="control mono" style="background:var(--paper)"><?= e($vj['semana_gestacion']) ?></div></div>
            <?php endif; ?>
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

        <?php foreach (rolesSujetoDeclarados($caso['enfermedad_columnas_sujeto'] ?? null) as $rolVer):
            $datosVer = $valoresSujetoPorRol[$rolVer] ?? [];
            if (empty($datosVer)) continue; // ningún caso_sujeto guardado para este rol todavía
            $columnasVerOrdenadas = array_intersect_key(metaColumnasSujeto(), array_flip(columnasSujeto($caso['enfermedad_columnas_sujeto'] ?? null, $rolVer)));
        ?>
          <div class="eyebrow" style="margin:18px 0 10px"><?= e(tituloSujeto($caso['enfermedad_titulo_sujeto'] ?? null, $rolVer)) ?></div>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <?php foreach ($columnasVerOrdenadas as $colVer => $infoVer):
                $esUbigeo = $infoVer['kind'] === 'ubigeo';
                $valorVer = $esUbigeo ? ($datosVer['distrito_nombre'] ?? '—') : ($datosVer[$colVer] ?? '—');
                $claseVer = $infoVer['kind'] === 'texto_wide' ? 'field wide' : 'field';
            ?>
              <div class="<?= $claseVer ?>"><label class="fl"><?= e($esUbigeo ? 'Distrito' : $infoVer['label']) ?></label><div class="control" style="background:var(--paper)"><?= e($valorVer !== null && $valorVer !== '' ? $valorVer : '—') ?></div></div>
            <?php endforeach; ?>
          </div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>

    <!-- Laboratorio -->
    <?php if (!empty($muestras)): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Laboratorio</h3></div>
      <div class="section-body">
        <?php
        // Se pintan las columnas que la ficha declara (columnas_tablas_hija),
        // no un juego fijo: antes del cotejo de A00 (2026-09-07) esta vista
        // mostraba siempre las mismas 6 y escondía todo lo demás que sí se
        // había capturado y guardado. $columnasMuestra/$datosMuestra los
        // resuelve CasosController::ver() con el mismo código que usan "Nueva
        // ficha" y "Editar"; el ?? de abajo mantiene el juego histórico por si
        // alguna vista incluye este archivo sin pasarlos.
        $colsMuestraVer = $columnasMuestra ?? ['tipo_muestra', 'tipo_prueba', 'resultado', 'fecha_toma', 'fecha_result'];
        $etiquetasMuestraVer = [
            'establecimiento'      => 'Establecimiento de salud',
            'tipo_muestra'         => 'Tipo de muestra',
            'tipo_prueba'          => 'Tipo de prueba',
            'recibio_antibiotico'  => '¿Recibió antibiótico?',
            'resultado'            => 'Resultado',
            'serogrupo'            => 'Serogrupo',
            'serotipo'             => 'Serotipo',
            'agente_aislado'       => 'Agente aislado',
            'genotipo'             => 'Genotipo',
            'titulacion'           => 'Titulación',
            'observaciones'        => 'Observaciones',
            'resultado_pcr'        => 'Resultado PCR',
            'resultado_igm'        => 'Resultado IgM',
            'resultado_igg'        => 'Resultado IgG',
            'fecha_toma'           => 'Fecha de toma',
            'fecha_envio_eess_red' => 'Fecha de envío EE.SS. → Red',
            'fecha_envio_red_lrr'  => 'Fecha de envío Red → LRR',
            'fecha_envio_lrr_ins'  => 'Fecha de envío LRR → INS',
            'fecha_envio_ins'      => 'Fecha de envío al laboratorio',
            'fecha_recepcion_ins'  => 'Fecha de recepción en laboratorio',
            'fecha_result'         => 'Fecha de resultado',
            'fecha_result_pcr'     => 'Fecha de resultado PCR',
            'fecha_result_igm'     => 'Fecha de resultado IgM',
            'fecha_result_igg'     => 'Fecha de resultado IgG',
        ];
        // Códigos -> etiqueta legible, para no mostrar "POS"/"HNF_FAR" crudos.
        $mapaEtiquetasMuestraVer = [];
        foreach ([
            'tipo_muestra' => 'opcionesTipoMuestra',
            'tipo_prueba'  => 'opcionesTipoPrueba',
            'resultado'    => 'opcionesResultado',
        ] as $col => $clave) {
            foreach (($datosMuestra[$clave] ?? []) as $op) {
                $mapaEtiquetasMuestraVer[$col][$op['valor']] = $op['etiqueta'];
            }
        }
        $valorMuestraVer = function (array $ms, string $col) use ($mapaEtiquetasMuestraVer): string {
            $bruto = $ms[$col] ?? null;
            if ($col === 'recibio_antibiotico') {
                return $bruto === null ? '—' : ($bruto ? 'Sí' : 'No');
            }
            if (str_starts_with($col, 'fecha_')) {
                return fechaIsoADmy($bruto) ?: '—';
            }
            if ($bruto === null || $bruto === '') {
                return $col === 'resultado' ? 'Pendiente' : '—';
            }
            return $mapaEtiquetasMuestraVer[$col][$bruto] ?? (string) $bruto;
        };
        ?>
        <?php foreach ($muestras as $ms): ?>
          <div class="subrow"><div class="fields thirds" style="flex:1">
            <?php foreach ($colsMuestraVer as $colMuestra): ?>
              <div class="field">
                <label class="fl"><?= e($etiquetasMuestraVer[$colMuestra] ?? $colMuestra) ?></label>
                <div class="control<?= str_starts_with($colMuestra, 'fecha_') ? ' mono' : '' ?>" style="background:var(--paper)"><?= e($valorMuestraVer($ms, $colMuestra)) ?></div>
              </div>
            <?php endforeach; ?>
          </div></div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>
    <?php endif; ?>

    <!-- A44: Evolución clínica -->
    <?php if (!empty($evoluciones)): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Evolución clínica</h3></div>
      <div class="section-body">
        <?php foreach ($evoluciones as $ev): ?>
          <div class="subrow" style="border:1px solid var(--line-2); border-radius:10px; padding:14px; margin-bottom:14px; width:100%">
            <div class="fields thirds" style="flex:1">
              <div class="field"><label class="fl">Fecha</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ev['fecha']) ?: '—') ?></div></div>
              <div class="field"><label class="fl">Temperatura</label><div class="control mono" style="background:var(--paper)"><?= e($ev['temperatura'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Hemoglobina</label><div class="control mono" style="background:var(--paper)"><?= e($ev['hemoglobina'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Hematocrito</label><div class="control mono" style="background:var(--paper)"><?= e($ev['hematocrito'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Transfusiones (U)</label><div class="control mono" style="background:var(--paper)"><?= e($ev['transfusiones'] ?? '—') ?></div></div>
              <div class="field"><label class="fl">Frotis</label><div class="control" style="background:var(--paper)"><?= e($ev['frotis'] ?? '—') ?></div></div>
            </div>
            <?php if (!empty($ev['hemocultivo_muestra_tomada'])): ?>
            <div class="eyebrow" style="margin:14px 0 8px">Hemocultivo</div>
            <div class="fields thirds" style="flex:1">
              <div class="field"><label class="fl">Fecha de toma</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ev['hemocultivo_fecha_toma'] ?? null) ?: '—') ?></div></div>
              <div class="field"><label class="fl">Resultado</label><div class="control" style="background:var(--paper)"><?= e($ev['hemocultivo_resultado'] ?? 'Pendiente') ?></div></div>
              <div class="field"><label class="fl">Fecha de resultado</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($ev['hemocultivo_fecha_resultado'] ?? null) ?: '—') ?></div></div>
            </div>
            <?php endif; ?>
            <?php
              $atbEtiquetasVer = ['penicilina' => 'Penicilina', 'cloranfenicol' => 'Cloranfenicol', 'rifampicina' => 'Rifampicina', 'ciprofloxacina' => 'Ciprofloxacina', 'eritromicina' => 'Eritromicina', 'cotrimoxazol' => 'Cotrimoxazol', 'ceftriaxona' => 'Ceftriaxona', 'otros' => 'Otros'];
              $atbUsadosVer = array_filter($atbEtiquetasVer, fn($etq, $slug) => !empty($ev["atb_{$slug}_usado"]), ARRAY_FILTER_USE_BOTH);
            ?>
            <?php if (!empty($atbUsadosVer)): ?>
            <div class="eyebrow" style="margin:14px 0 8px">Antibióticos usados</div>
            <div class="fields thirds" style="flex:1">
              <?php foreach ($atbUsadosVer as $slug => $etq): ?>
                <div class="field"><label class="fl"><?= $slug === 'otros' ? e($ev['atb_otros_especificar'] ?: 'Otros') : e($etq) ?></label><div class="control" style="background:var(--paper)"><?= e($ev["atb_{$slug}_dosis"] ?? '—') ?></div></div>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>
    <?php endif; ?>

    <!-- A44: Exámenes auxiliares -->
    <?php if (!empty($examenesAuxiliares)): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Exámenes auxiliares</h3></div>
      <div class="section-body">
        <?php foreach ($examenesAuxiliares as $exa): ?>
          <div class="subrow" style="border:1px solid var(--line-2); border-radius:10px; padding:14px; margin-bottom:14px; width:100%">
            <div class="fields thirds" style="flex:1">
              <div class="field"><label class="fl">Fecha</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($exa['fecha']) ?: '—') ?></div></div>
              <?php
                $etiquetasExamenVer = ['grupo_sanguineo' => 'Grupo sanguíneo', 'plaquetas' => 'Plaquetas', 'hematies' => 'Hematíes', 'tgo' => 'TGO', 'tgp' => 'TGP', 'fosfatasa_alcalina' => 'Fosfatasa alcalina', 'bilirrubina_directa' => 'Bilirrubina directa', 'bilirrubina_indirecta' => 'Bilirrubina indirecta', 'bilirrubina_total' => 'Bilirrubina total', 'urea' => 'Urea', 'glucosa' => 'Glucosa', 'creatinina' => 'Creatinina', 'leucocitos_totales' => 'Leucocitos totales', 'segmentados' => 'Segmentados', 'abastonados' => 'Abastonados', 'linfocitos' => 'Linfocitos', 'monocitos' => 'Monocitos', 'eosinofilos' => 'Eosinófilos', 'basofilos' => 'Basófilos', 'blastos' => 'Blastos', 'aglutinacion_tifico_o' => 'Aglutinación: Tífico "O"', 'aglutinacion_tifico_h' => 'Aglutinación: Tífico "H"', 'paratifico_a' => 'Paratífico A', 'paratifico_b' => 'Paratífico B', 'brucellas' => 'Brucellas'];
              ?>
              <?php foreach ($etiquetasExamenVer as $colExa => $etqExa): if (empty($exa[$colExa])) continue; ?>
              <div class="field"><label class="fl"><?= e($etqExa) ?></label><div class="control" style="background:var(--paper)"><?= e($exa[$colExa]) ?></div></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php $numeroSeccion++; ?>
    <?php endif; ?>

    <!-- Investigador -->
    <?php if ($caso['investigador_nombre'] || $caso['investigador_cargo'] || ($caso['investigador_profesion'] ?? '') || ($caso['investigador_telefono'] ?? '') || ($caso['investigador_email'] ?? '') || $caso['fecha_investigacion']): ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Investigador</h3></div>
      <div class="section-body">
        <div class="fields quarters">
          <div class="field"><label class="fl">Investigador / responsable</label><div class="control" style="background:var(--paper)"><?= e($caso['investigador_nombre'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Cargo</label><div class="control" style="background:var(--paper)"><?= e($caso['investigador_cargo'] ?: '—') ?></div></div>
          <div class="field"><label class="fl">Profesión</label><div class="control" style="background:var(--paper)"><?= e(($caso['investigador_profesion'] ?? '') ?: '—') ?></div></div>
          <div class="field"><label class="fl">Fecha de investigación</label><div class="control mono" style="background:var(--paper)"><?= e(fechaIsoADmy($caso['fecha_investigacion']) ?: '—') ?></div></div>
          <div class="field"><label class="fl">Teléfono</label><div class="control mono" style="background:var(--paper)"><?= e(($caso['investigador_telefono'] ?? '') ?: '—') ?></div></div>
          <div class="field"><label class="fl">Email</label><div class="control" style="background:var(--paper)"><?= e(($caso['investigador_email'] ?? '') ?: '—') ?></div></div>
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

    <?php
    // vinculo_caso (cotejo Z21, 2026-09-11): de un lado la ficha de la madre
    // a la que este caso está enlazado; del otro, las fichas de los niños
    // nacidos expuestos enlazadas a esta (una por cada producto, si el
    // embarazo fue múltiple) y el botón para registrar una más. Solo se
    // listan las fichas que este usuario puede ver (Z21 es ficha privada:
    // un REGISTRADOR solo ve las suyas).
    if (!empty($vinculoVer) && ($vinculoVer['madre'] || $vinculoVer['hijos'] || $vinculoVer['puedeRegistrar'])): ?>
      <div class="card rail-card">
        <div class="eyebrow" style="margin-bottom:12px">Fichas vinculadas</div>
        <?php if ($vinculoVer['madre']): ?>
          <div style="font-size:12.5px;margin-bottom:10px">
            <div style="color:var(--muted)"><?= e($vinculoVer['config']['titulo_madre'] ?? 'Ficha vinculada') ?></div>
            <a class="mono" href="/casos/<?= (int) $vinculoVer['madre']['id'] ?>"><?= e($vinculoVer['madre']['codigo']) ?></a>
            · <?= e(trim($vinculoVer['madre']['apellido_paterno'] . ' ' . $vinculoVer['madre']['nombres'])) ?>
          </div>
        <?php endif; ?>
        <?php if ($vinculoVer['hijos']): ?>
          <div style="font-size:12.5px;margin-bottom:10px">
            <div style="color:var(--muted);margin-bottom:4px"><?= e($vinculoVer['config']['titulo_vinculados'] ?? 'Fichas vinculadas') ?></div>
            <?php foreach ($vinculoVer['hijos'] as $hijoVinculado): ?>
              <div><a class="mono" href="/casos/<?= (int) $hijoVinculado['id'] ?>"><?= e($hijoVinculado['codigo']) ?></a>
                · <?= e(trim($hijoVinculado['apellido_paterno'] . ' ' . $hijoVinculado['nombres'])) ?></div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php
        // vinculo_caso.encadenar (pedido del usuario, 2026-09-11): cuántas
        // fichas vinculadas faltan según el campo numérico del propio caso
        // (Z21: N.º de nacidos vivos). Solo aparece si el campo trae número;
        // no se inventa un objetivo cuando está vacío.
        if (!empty($vinculoVer['pendientes'])): ?>
          <div style="font-size:12.5px;margin-bottom:10px">
            <div style="color:var(--muted)"><?= e($vinculoVer['config']['encadenar']['titulo_pendientes'] ?? 'Fichas vinculadas por registrar') ?></div>
            <strong><?= (int) $vinculoVer['pendientes'] ?></strong> de <?= (int) $vinculoVer['esperados'] ?>
          </div>
        <?php endif; ?>
        <?php if ($vinculoVer['puedeRegistrar']): ?>
          <a class="btn btn-ghost" style="width:100%;text-align:center" href="/casos/nuevo?enfermedad_id=<?= (int) $caso['enfermedad_id'] ?>&amp;vinculo=<?= (int) $caso['id'] ?>"><?= e($vinculoVer['config']['accion_registrar'] ?? 'Registrar ficha vinculada') ?></a>
        <?php endif; ?>
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
