<?php
use App\Core\Csrf;
use App\Models\IaasCaso;
use App\Models\IaasNorma;
use App\Models\Persona;

// Ficha de investigación epidemiológica de IAAS (Anexo 5). Numeración del
// papel; la sección III muestra solo el factor de riesgo del tipo elegido.
$anexo = 'Anexo 5';
$v = fn(string $c) => $caso[$c] ?? null;
$esNueva = !$caso['id'];
$anulada = !empty($caso['anulado_en']);
$tipo = $v('tipo_iaas') ?: '';
$factor = IaasNorma::FACTOR_POR_TIPO[$tipo] ?? '';
$salaActual = null;
foreach ($salasCaso as $s) {
    if ((int) $s['id'] === (int) $v('sala_id')) {
        $salaActual = $s;
    }
}
$pacienteFijo = !$esNueva || $internacion;
$paciente = !$esNueva ? $caso : ($internacion ?: null);
$estadoChip = $anulada ? ['sin', 'Anulada'] : ($caso['estado'] === 'COMPLETO' ? ['con', 'Completa'] : ['warn', 'Por completar']);
$microsPorFamilia = [];
foreach ($microorganismos as $m) {
    $microsPorFamilia[$m['familia']][] = $m;
}
$cultivoVacio = ['tipo_cultivo' => null, 'tipo_cultivo_otro' => null, 'fecha_extraccion' => null, 'microorganismo_id' => null, 'microorganismo_otro' => null, 'blee' => null, 'otro_mecanismo' => null, 'antibiograma' => []];
$radio = fn(string $nombre, string $valor, mixed $actual) => 'name="' . e($nombre) . '" value="' . e($valor) . '"' . ((string) $actual === $valor ? ' checked' : '');
$acciones = '<span class="ns-chip ' . $estadoChip[0] . ' iaas-estado-chip"><i></i>' . $estadoChip[1] . '</span>';
require __DIR__ . '/../_cabecera.php';
?>
<?php if ($errores): ?>
  <div class="iaas-aviso crit" role="alert"><div><b>Revisa la ficha:</b><ul><?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
<?php if ($anulada): ?>
  <div class="iaas-aviso info"><div><b>Ficha anulada</b> el <?= e(date('d/m/Y', strtotime($caso['anulado_en']))) ?> por <?= e($caso['anulado_nombre']) ?>: «<?= e($caso['motivo_anulacion']) ?>». No cuenta en los indicadores.</div></div>
<?php endif; ?>

<form method="post" action="<?= $esNueva ? '/iaas/casos' : '/iaas/casos/' . (int) $caso['id'] ?>" class="iaas-ficha" id="fichaIaas"
      data-horas-t="<?= e(json_encode($horasT)) ?>"<?= $anulada ? ' data-solo-lectura' : '' ?>>
  <?= Csrf::campoOculto() ?>
  <?php if ($esNueva && $internacion): ?><input type="hidden" name="internacion_id" value="<?= (int) $internacion['id'] ?>"><?php endif; ?>

  <div class="iaas-ficha-cols">
    <div class="iaas-ficha-main">

      <section class="card section" id="sec-1">
        <div class="section-head"><span class="section-num">I</span><h3>Datos del paciente</h3></div>
        <div class="section-body">
          <?php if ($pacienteFijo && $paciente): ?>
            <div class="found iaas-found">
              <div class="pa"><?= e(iniciales(Persona::nombreCompleto($paciente))) ?></div>
              <div><div class="pn"><?= e(Persona::nombreCompleto($paciente)) ?></div>
                <div class="pd"><?= e(documentoParaMostrar($paciente['tipo_doc'], $paciente['num_doc'])) ?> · <?= e($paciente['sexo'] ?? 'sexo sin registrar') ?><?= $paciente['fecha_nac'] ? ' · nació el ' . e(fechaIsoADmy($paciente['fecha_nac'])) . ' · ' . e(edadLegible($paciente['fecha_nac'], $v('fecha_iaas')) ?? '') . ' a la fecha de la IAAS' : '' ?></div></div>
              <?php if ($internacion || $v('internacion_id')): ?><span class="src">Vigilado en la ronda</span><?php endif; ?>
            </div>
          <?php else: ?>
            <div class="iaas-fila-doc">
              <div class="field"><label class="fl" for="cTipoDoc">Documento</label>
                <div class="control"><select id="cTipoDoc" name="tipo_doc" data-nosearch="true">
                  <?php foreach (['DNI' => 'DNI', 'CE' => 'Carné de extranjería', 'PTP' => 'PTP', 'PAS' => 'Pasaporte', 'OTRO' => 'Otro', 'SIN_DOCUMENTO' => 'Sin documento'] as $cod => $et): ?>
                    <option value="<?= $cod ?>"<?= ($v('tipo_doc') ?? 'DNI') === $cod ? ' selected' : '' ?>><?= $et ?></option>
                  <?php endforeach; ?>
                </select></div></div>
              <div class="field" data-num-doc><label class="fl" for="cNumDoc">Número</label><div class="control mono"><input id="cNumDoc" name="num_doc" maxlength="20" value="<?= e($v('num_doc')) ?>" autocomplete="off"></div></div>
              <button type="button" class="btn btn-ghost" data-buscar-persona>Buscar</button>
            </div>
            <div class="iaas-encontrado" data-encontrado hidden></div>
            <div class="fields thirds" data-datos-persona>
              <div class="field"><label class="fl" for="cApPat">1.2 Apellido paterno <span class="req">*</span></label><div class="control"><input id="cApPat" name="apellido_paterno" maxlength="60" value="<?= e($v('apellido_paterno')) ?>"></div></div>
              <div class="field"><label class="fl" for="cApMat">Apellido materno</label><div class="control"><input id="cApMat" name="apellido_materno" maxlength="60" value="<?= e($v('apellido_materno')) ?>"></div></div>
              <div class="field"><label class="fl" for="cNombres">Nombres <span class="req">*</span></label><div class="control"><input id="cNombres" name="nombres" maxlength="80" value="<?= e($v('nombres')) ?>"></div></div>
              <div class="field"><span class="fl">1.3 Sexo</span><div class="iaas-seg-radio"><label><input type="radio" <?= $radio('sexo', 'M', $v('sexo')) ?>><span>M</span></label><label><input type="radio" <?= $radio('sexo', 'F', $v('sexo')) ?>><span>F</span></label></div></div>
              <div class="field"><label class="fl" for="cFechaNac">1.4 Fecha de nacimiento</label><div class="control"><input type="date" id="cFechaNac" name="fecha_nac" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_nac')) ?>"></div></div>
            </div>
          <?php endif; ?>

          <div class="fields thirds iaas-sep">
            <div class="field"><label class="fl" for="cHc">1.1 Historia clínica</label><div class="control mono"><input id="cHc" name="historia_clinica" maxlength="30" value="<?= e($v('historia_clinica')) ?>"></div></div>
            <div class="field"><label class="fl" for="cSala">1.7 Servicio · 1.8 Sala <span class="req">*</span></label>
              <div class="control"><select id="cSala" name="sala_id" data-nosearch="true" data-sala>
                <?php foreach ($salasCaso as $s): ?>
                  <option value="<?= (int) $s['id'] ?>" data-neonatal="<?= IaasNorma::esNeonatal($s['upss']) ? '1' : '0' ?>"<?= (int) $s['id'] === (int) $v('sala_id') ? ' selected' : '' ?>><?= e(IaasNorma::nombreSala($s)) ?><?= $s['activo'] ? '' : ' (desactivada)' ?></option>
                <?php endforeach; ?>
              </select></div></div>
            <div class="field" data-solo-neonatal<?= $salaActual && IaasNorma::esNeonatal($salaActual['upss']) ? '' : ' hidden' ?>><label class="fl" for="cPeso">1.6 Peso al nacer (g)</label><div class="control mono"><input type="number" id="cPeso" name="peso_nacer_g" min="300" max="7000" value="<?= e($v('peso_nacer_g')) ?>"></div></div>
            <div class="field"><label class="fl" for="cIngIpress">1.9 Ingreso a la IPRESS</label><div class="control"><input type="date" id="cIngIpress" name="fecha_ingreso_ipress" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_ingreso_ipress')) ?>"></div></div>
            <div class="field"><label class="fl" for="cIngServ">1.10 Ingreso al servicio</label><div class="control"><input type="date" id="cIngServ" name="fecha_ingreso_servicio" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_ingreso_servicio')) ?>"></div></div>
            <div class="field"><label class="fl" for="cEgServ">1.11 Egreso del servicio</label><div class="control"><input type="date" id="cEgServ" name="fecha_egreso_servicio" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_egreso_servicio')) ?>"></div></div>
            <div class="field wide"><span class="fl">1.12 Condición de egreso</span>
              <div class="iaas-seg-radio">
                <label><input type="radio" <?= $radio('condicion_egreso', '', $v('condicion_egreso') ?? '') ?>><span>Sin egreso</span></label>
                <?php foreach (IaasNorma::CONDICION_EGRESO as $cod => $et): ?><label><input type="radio" <?= $radio('condicion_egreso', $cod, $v('condicion_egreso')) ?>><span><?= e($et) ?></span></label><?php endforeach; ?>
              </div></div>
          </div>

          <div class="iaas-dx iaas-sep">
            <span class="fl">1.13 Diagnóstico médico de ingreso · CIE-10</span>
            <?php foreach (['dx1_cie10', 'dx2_cie10', 'dx3_cie10'] as $n => $campo): $cod = $v($campo); ?>
              <div class="iaas-cie" data-cie10>
                <div class="control mono"><input name="<?= $campo ?>" value="<?= e($cod) ?>" maxlength="10" placeholder="<?= $n === 0 ? 'Código o texto: J18.9, neumonía…' : 'Comorbilidad (opcional)' ?>" autocomplete="off" aria-label="Diagnóstico <?= $n + 1 ?>"></div>
                <span class="iaas-cie-desc" data-cie10-desc><?= $cod ? e(descripcionCie10($cod) ?? '') : '' ?></span>
                <ul class="iaas-cie-lista" data-cie10-lista hidden></ul>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

      <section class="card section" id="sec-2">
        <div class="section-head"><span class="section-num">II</span><h3>Datos de la infección asociada a la atención en salud</h3></div>
        <div class="section-body">
          <span class="fl">2.1 Tipo de IAAS <span class="req">*</span></span>
          <div class="iaas-opciones tipos" role="radiogroup">
            <?php foreach (IaasNorma::TIPOS_IAAS as $cod => $et): ?>
              <label class="iaas-opcion"><input type="radio" <?= $radio('tipo_iaas', $cod, $tipo) ?> data-tipo-iaas required><span><b><?= e(IaasNorma::TIPOS_IAAS_SIGLA[$cod]) ?></b><?= e($et) ?></span></label>
            <?php endforeach; ?>
          </div>
          <div class="fields thirds iaas-sep">
            <div class="field"><label class="fl" for="cFechaIaas">2.2 Fecha de IAAS <span class="req">*</span></label><div class="control"><input type="date" id="cFechaIaas" name="fecha_iaas" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_iaas')) ?>" required></div><span class="hint">Fecha en que se clasifica como IAAS.</span></div>
            <div class="field" data-solo-tipo="ITS"<?= $tipo === 'ITS' ? '' : ' hidden' ?>><span class="fl">2.4 ITS secundaria a IAAS</span>
              <div class="iaas-seg-radio"><label><input type="radio" <?= $radio('its_secundaria', '1', $v('its_secundaria')) ?>><span>Sí</span></label><label><input type="radio" <?= $radio('its_secundaria', '0', $v('its_secundaria')) ?>><span>No</span></label></div>
              <span class="hint">Se desarrolla como consecuencia de otra IAAS.</span></div>
          </div>
          <div class="iaas-criterios iaas-sep">
            <span class="fl">2.3 Criterio de definición de caso <span class="hint">· Anexo 3</span></span>
            <?php foreach (IaasNorma::CRITERIOS as $t => $criterios): ?>
              <div class="iaas-opciones criterios" data-criterios="<?= $t ?>"<?= $t === $tipo ? '' : ' hidden' ?>>
                <?php foreach ($criterios as $cod => [$titulo, $resumen]): ?>
                  <label class="iaas-opcion criterio"><input type="radio" <?= $radio('criterio', $cod, $t === $tipo ? $v('criterio') : null) ?><?= $t === $tipo ? '' : ' disabled' ?>><span><b><?= e($titulo) ?></b><small><?= e($resumen) ?></small></span></label>
                <?php endforeach; ?>
              </div>
            <?php endforeach; ?>
            <p class="hint" data-sin-tipo<?= $tipo ? ' hidden' : '' ?>>Elige primero el tipo de IAAS.</p>
          </div>
        </div>
      </section>

      <section class="card section" id="sec-3">
        <div class="section-head"><span class="section-num">III</span><h3>Factor de riesgo asociado</h3><span class="adapt" data-factor-etq><?= ['dispositivo' => 'Dispositivo médico', 'cirugia' => 'Cirugía', 'parto' => 'Parto'][$factor] ?? 'Según el tipo de IAAS' ?></span></div>
        <div class="section-body">
          <p class="hint" data-factor-vacio<?= $factor ? ' hidden' : '' ?>>Elige el tipo de IAAS: aquí aparece el dispositivo, la cirugía o el parto que corresponde.</p>

          <div data-factor="dispositivo"<?= $factor === 'dispositivo' ? '' : ' hidden' ?>>
            <?php if ($dispositivosPaciente): ?>
              <div class="field"><label class="fl" for="cDispRonda">Dispositivo de la ronda</label>
                <div class="control"><select id="cDispRonda" name="dispositivo_id" data-nosearch="true" data-disp-ronda>
                  <option value="">— Ninguno (no estaba en la ronda) —</option>
                  <?php foreach ($dispositivosPaciente as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" data-tipo="<?= $d['tipo'] ?>" data-instalacion="<?= e($d['fecha_instalacion']) ?>" data-retiro="<?= e($d['fecha_retiro']) ?>"<?= (int) $v('dispositivo_id') === (int) $d['id'] ? ' selected' : '' ?>><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']] . ' · desde ' . fechaIsoADmy($d['fecha_instalacion']) . ($d['fecha_retiro'] ? ' hasta ' . fechaIsoADmy($d['fecha_retiro']) : ', instalado')) ?></option>
                  <?php endforeach; ?>
                </select></div><span class="hint">Une la ficha con la ronda: sus días en tratamiento se marcan ×.</span></div>
            <?php endif; ?>
            <span class="fl">3.1 Dispositivo médico</span>
            <div class="iaas-opciones">
              <?php foreach (IaasNorma::DISPOSITIVOS as $cod => $et): $permitido = in_array($cod, IaasNorma::DISPOSITIVOS_POR_TIPO[$tipo] ?? [], true); ?>
                <label class="iaas-opcion" data-disp-tipo="<?= $cod ?>"<?= $permitido || !$tipo ? '' : ' hidden' ?>><input type="radio" <?= $radio('dispositivo_tipo', $cod, $v('dispositivo_tipo')) ?>><span><b><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$cod]) ?></b><?= e($et) ?></span></label>
              <?php endforeach; ?>
            </div>
            <div class="fields iaas-sep">
              <div class="field"><label class="fl" for="cInst">3.2 Fecha de instalación</label><div class="control"><input type="date" id="cInst" name="fecha_instalacion" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_instalacion')) ?>"></div></div>
              <div class="field"><label class="fl" for="cRet">3.3 Fecha de retiro</label><div class="control"><input type="date" id="cRet" name="fecha_retiro" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_retiro')) ?>"></div><span class="hint">Vacía si sigue instalado.</span></div>
            </div>
          </div>

          <div data-factor="cirugia"<?= $factor === 'cirugia' ? '' : ' hidden' ?>>
            <div class="fields">
              <div class="field"><label class="fl" for="cTipoCir">3.7 Tipo de cirugía</label>
                <div class="control"><select id="cTipoCir" name="procedimiento_tipo_cir" data-nosearch="true" data-tipo-cirugia>
                  <option value="">Elige…</option>
                  <?php foreach (IaasNorma::CIRUGIAS_ISQ as $cod): ?>
                    <option value="<?= $cod ?>"<?= $factor === 'cirugia' && $v('procedimiento_tipo') === $cod ? ' selected' : '' ?>><?= e(IaasNorma::PROCEDIMIENTO_SINGULAR[$cod]) ?></option>
                  <?php endforeach; ?>
                </select></div></div>
              <div class="field"><label class="fl" for="cNomCir">3.6 Nombre de la cirugía</label><div class="control"><input id="cNomCir" name="procedimiento_nombre" maxlength="120" value="<?= e($v('procedimiento_nombre')) ?>" placeholder="Como figura en el reporte operatorio"></div></div>
              <div class="field"><label class="fl" for="cFechaCir">3.8 Fecha de la cirugía</label><div class="control"><input type="date" id="cFechaCir" name="fecha_procedimiento_cir" max="<?= date('Y-m-d') ?>" value="<?= e($factor === 'cirugia' ? $v('fecha_procedimiento') : '') ?>"></div><span class="hint">La ISQ se cuenta en el mes de la cirugía.</span></div>
              <div class="field"><label class="fl" for="cDur">3.9 Duración de la cirugía (min)</label><div class="control mono"><input type="number" id="cDur" name="duracion_min" min="1" max="1440" value="<?= e($v('duracion_min')) ?>" data-indice></div></div>
            </div>
            <div class="iaas-sep"><span class="fl">3.10 Clase de herida</span>
              <div class="iaas-opciones cuatro">
                <?php foreach (IaasNorma::CLASE_HERIDA as $cod => [$et, $desc]): ?>
                  <label class="iaas-opcion" title="<?= e($desc) ?>"><input type="radio" <?= $radio('clase_herida', $cod, $v('clase_herida')) ?> data-indice><span><b><?= $cod ?></b><?= e($et) ?></span></label>
                <?php endforeach; ?>
              </div></div>
            <div class="iaas-sep"><span class="fl">3.11 Clase de ASA</span>
              <div class="iaas-opciones cinco">
                <?php foreach (IaasNorma::ASA as $n => $desc): ?>
                  <label class="iaas-opcion" title="<?= e($desc) ?>"><input type="radio" <?= $radio('asa', (string) $n, $v('asa')) ?> data-indice><span><b>ASA <?= $n ?></b><?= e($desc) ?></span></label>
                <?php endforeach; ?>
              </div></div>
            <div class="fields iaas-sep">
              <div class="field" data-implante><span class="fl">3.12 Implante</span>
                <div class="iaas-seg-radio"><label><input type="radio" <?= $radio('implante', '1', $v('implante')) ?>><span>Sí</span></label><label><input type="radio" <?= $radio('implante', '0', $v('implante')) ?>><span>No</span></label></div>
                <span class="hint">Solo cadera u otra cirugía local. Con implante, la vigilancia de ISQ profunda dura 1 año.</span></div>
              <div class="field"><span class="fl">3.13 Índice de riesgo · ISQ</span>
                <div class="iaas-indice" data-indice-salida aria-live="polite"><b>—</b><small>Completa tipo de cirugía, duración, herida y ASA.</small></div></div>
            </div>
          </div>

          <div data-factor="parto"<?= $factor === 'parto' ? '' : ' hidden' ?>>
            <span class="fl">3.4 Procedimiento médico</span>
            <div class="iaas-opciones">
              <?php foreach (IaasNorma::PARTOS_END as $cod): ?>
                <label class="iaas-opcion"><input type="radio" <?= $radio('procedimiento_tipo_parto', $cod, $factor === 'parto' ? $v('procedimiento_tipo') : null) ?>><span><b><?= $cod === 'CESAREA' ? 'PC' : 'PV' ?></b><?= e(IaasNorma::PROCEDIMIENTO_SINGULAR[$cod]) ?></span></label>
              <?php endforeach; ?>
            </div>
            <div class="fields iaas-sep">
              <div class="field"><label class="fl" for="cFechaParto">3.5 Fecha del procedimiento</label><div class="control"><input type="date" id="cFechaParto" name="fecha_procedimiento_parto" max="<?= date('Y-m-d') ?>" value="<?= e($factor === 'parto' ? $v('fecha_procedimiento') : '') ?>"></div><span class="hint">La endometritis se cuenta en el mes del parto.</span></div>
            </div>
          </div>
        </div>
      </section>

      <section class="card section" id="sec-4">
        <div class="section-head"><span class="section-num">IV</span><h3>Agente aislado para la IAAS</h3><span class="adapt">Hasta 2 cultivos</span></div>
        <div class="section-body">
          <div class="iaas-tabs" role="tablist">
            <?php foreach ([1, 2] as $orden): $c = $cultivos[$orden] ?? null; ?>
              <button type="button" role="tab" class="iaas-tab<?= $orden === 1 ? ' on' : '' ?>" data-tab="<?= $orden ?>" aria-selected="<?= $orden === 1 ? 'true' : 'false' ?>"<?= $orden === 2 && !$c ? ' data-tab-opcional' : '' ?>>
                <?= $orden === 2 && !$c ? '+ Segundo cultivo' : 'Cultivo ' . $orden ?><?= !empty($c['microorganismo_abreviatura']) ? ' · ' . e($c['microorganismo_abreviatura']) : '' ?>
              </button>
            <?php endforeach; ?>
          </div>
          <?php foreach ([1, 2] as $orden): $c = ($cultivos[$orden] ?? []) + $cultivoVacio; $pre = "cultivo[$orden]"; ?>
            <div class="iaas-tab-panel" data-panel="<?= $orden ?>"<?= $orden === 1 ? '' : ' hidden' ?> role="tabpanel">
              <span class="fl">4.1 Tipo de cultivo</span>
              <div class="iaas-opciones cinco">
                <?php foreach (IaasNorma::TIPOS_CULTIVO as $cod => $et): ?>
                  <label class="iaas-opcion"><input type="radio" <?= $radio($pre . '[tipo_cultivo]', $cod, $c['tipo_cultivo']) ?>><span><?= e($et) ?></span></label>
                <?php endforeach; ?>
              </div>
              <div class="fields iaas-sep">
                <div class="field"><label class="fl" for="tcOtro<?= $orden ?>">Otro tipo de cultivo</label><div class="control"><input id="tcOtro<?= $orden ?>" name="<?= $pre ?>[tipo_cultivo_otro]" maxlength="80" value="<?= e($c['tipo_cultivo_otro']) ?>" placeholder="Solo si elegiste «Otro»"></div></div>
                <div class="field"><label class="fl" for="fExt<?= $orden ?>">4.2 Fecha de extracción de la muestra</label><div class="control"><input type="date" id="fExt<?= $orden ?>" name="<?= $pre ?>[fecha_extraccion]" max="<?= date('Y-m-d') ?>" value="<?= e($c['fecha_extraccion']) ?>"></div></div>
                <div class="field"><label class="fl" for="mo<?= $orden ?>">4.3 Microorganismo <?= $orden ?></label>
                  <div class="control"><select id="mo<?= $orden ?>" name="<?= $pre ?>[microorganismo_id]">
                    <option value="">Buscar por nombre o código (KPN, SAU…)</option>
                    <?php foreach ($microsPorFamilia as $familia => $lista): ?>
                      <optgroup label="<?= e(IaasNorma::FAMILIAS_MICROORGANISMO[$familia] ?? 'Otros') ?>">
                        <?php foreach ($lista as $m): ?><option value="<?= (int) $m['id'] ?>"<?= (int) $c['microorganismo_id'] === (int) $m['id'] ? ' selected' : '' ?>><?= e($m['nombre']) ?><?= $m['abreviatura'] !== $m['nombre'] && mb_strlen($m['abreviatura']) <= 5 ? ' · ' . e($m['abreviatura']) : '' ?></option><?php endforeach; ?>
                      </optgroup>
                    <?php endforeach; ?>
                  </select></div></div>
                <div class="field"><label class="fl" for="moOtro<?= $orden ?>">Especificar microorganismo</label><div class="control"><input id="moOtro<?= $orden ?>" name="<?= $pre ?>[microorganismo_otro]" maxlength="120" value="<?= e($c['microorganismo_otro']) ?>" placeholder="Si es «otro» o no está en la lista"></div></div>
              </div>
              <div class="iaas-atb iaas-sep">
                <div class="iaas-atb-cab"><span class="fl">4.5 Perfil de sensibilidad</span><span class="hint">S sensible · I intermedio · R resistente · sin marcar = no se probó</span></div>
                <div class="iaas-atb-lista">
                  <?php foreach ($antimicrobianos as $a): $r = $c['antibiograma'][(int) $a['id']] ?? ''; ?>
                    <div class="iaas-atb-fila<?= $r ? ' con' : '' ?>">
                      <span class="iaas-atb-nom"><?= e($a['nombre']) ?></span>
                      <div class="iaas-sir" role="radiogroup" aria-label="<?= e($a['nombre']) ?>">
                        <?php foreach (['S', 'I', 'R'] as $res): ?>
                          <label class="<?= $res ?>"><input type="radio" <?= $radio($pre . '[atb][' . (int) $a['id'] . ']', $res, $r) ?>><span><?= $res ?></span></label>
                        <?php endforeach; ?>
                        <button type="button" class="iaas-sir-x" data-limpiar-atb aria-label="Quitar resultado de <?= e($a['nombre']) ?>" title="Quitar"<?= $r ? '' : ' hidden' ?>>×</button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="fields iaas-sep">
                  <div class="field"><span class="fl">Producción de BLEE</span>
                    <div class="iaas-seg-radio"><label><input type="radio" <?= $radio($pre . '[blee]', '', $c['blee'] ?? '') ?>><span>Sin dato</span></label><label><input type="radio" <?= $radio($pre . '[blee]', 'SI', $c['blee']) ?>><span>Sí</span></label><label><input type="radio" <?= $radio($pre . '[blee]', 'NO', $c['blee']) ?>><span>No</span></label></div></div>
                  <div class="field"><label class="fl" for="mec<?= $orden ?>">Otro mecanismo de resistencia</label><div class="control"><input id="mec<?= $orden ?>" name="<?= $pre ?>[otro_mecanismo]" maxlength="160" value="<?= e($c['otro_mecanismo']) ?>" placeholder="Especificar"></div></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>

      <section class="card section" id="sec-5">
        <div class="section-head"><span class="section-num">V</span><h3>Seguimiento</h3><span class="adapt">No está en el papel · lo usa la ronda</span></div>
        <div class="section-body">
          <div class="fields">
            <div class="field"><label class="fl" for="cFin">Último día de tratamiento</label><div class="control"><input type="date" id="cFin" name="fecha_fin_tratamiento" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha_fin_tratamiento')) ?>"></div>
              <span class="hint">Mientras esté vacío, los días del dispositivo se marcan × y no suman al total.</span></div>
            <div class="field wide"><label class="fl" for="cObs">Observaciones</label><div class="control"><input id="cObs" name="observaciones" maxlength="500" value="<?= e($v('observaciones')) ?>"></div></div>
          </div>
        </div>
      </section>
    </div>

    <aside class="rail iaas-rail">
      <div class="card rail-card">
        <h4><?= $esNueva ? 'Nueva ficha' : e(IaasCaso::codigo($caso)) ?></h4>
        <p><?php if ($esNueva): ?>Puedes guardarla incompleta: queda «por completar» hasta tener lo que pide la norma.
          <?php else: ?>Registrada por <?= e($caso['creado_nombre']) ?> el <?= e(date('d/m/Y', strtotime($caso['creado_en']))) ?>.<?php endif; ?></p>
        <?php if (!$esNueva && !$anulada && $faltan): ?>
          <div class="iaas-faltan"><b>Para completarla falta:</b><ul><?php foreach ($faltan as $f): ?><li><?= e($f) ?></li><?php endforeach; ?></ul></div>
        <?php elseif (!$esNueva && !$anulada): ?>
          <div class="iaas-faltan ok"><b>Ficha completa.</b> Cuenta en los indicadores del mes.</div>
        <?php endif; ?>
        <nav class="progress iaas-indice-secciones" aria-label="Secciones">
          <a class="pstep" href="#sec-1"><span class="pd"></span>I · Paciente</a>
          <a class="pstep" href="#sec-2"><span class="pd"></span>II · Infección</a>
          <a class="pstep" href="#sec-3"><span class="pd"></span>III · Factor de riesgo</a>
          <a class="pstep" href="#sec-4"><span class="pd"></span>IV · Agente aislado</a>
          <a class="pstep" href="#sec-5"><span class="pd"></span>V · Seguimiento</a>
        </nav>
      </div>
      <div class="rail-actions">
        <?php if (!$anulada): ?><button type="submit" class="btn btn-primary">Guardar ficha</button><?php endif; ?>
        <?php if (!$esNueva): ?><button type="button" class="btn btn-ghost" data-imprimir>Imprimir</button><?php endif; ?>
        <a class="btn btn-quiet" href="/iaas/casos">Volver al listado</a>
        <?php if (!$esNueva && !$anulada): ?><button type="button" class="btn btn-quiet iaas-btn-peligro" data-modal="modalAnular">Anular ficha</button><?php endif; ?>
      </div>
    </aside>
  </div>
</form>

<?php if (!$esNueva && !$anulada): ?>
<div class="modal-fondo" id="modalAnular" hidden>
  <form class="modal-caja" method="post" action="/iaas/casos/<?= (int) $caso['id'] ?>/anular" role="dialog" aria-modal="true" aria-labelledby="modalAnularTit">
    <?= Csrf::campoOculto() ?>
    <div class="modal-cab"><h3 id="modalAnularTit">Anular <?= e(IaasCaso::codigo($caso)) ?></h3><p>La ficha deja de contar en los indicadores y, si estaba unida a la ronda, sus días vuelven a sumar. Queda registrado quién la anuló.</p></div>
    <div class="ns-campo ns-pad-modal"><label for="motivoAnular">Motivo <em>*</em></label><textarea id="motivoAnular" name="motivo" maxlength="255" required placeholder="Ej.: no cumple criterio, se registró dos veces"></textarea></div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-peligro">Anular ficha</button></div>
  </form>
</div>
<?php endif; ?>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
