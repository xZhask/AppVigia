<?php
use App\Core\Csrf;
use App\Models\IaasNorma;

// Registro de exposición laboral (Anexo 8). Objeto y profundidad solo para
// punzocortantes; el papel no pide el nombre del trabajador.
$anexo = 'Anexo 8';
$r = $registro;
$v = fn(string $c) => $r[$c] ?? null;
$esNuevo = !$r['id'];
$anulado = !empty($r['anulado_en']);
$epp = $v('epp') ? explode(',', $v('epp')) : [];
$radio = fn(string $nombre, string $valor, mixed $actual) => 'name="' . e($nombre) . '" value="' . e($valor) . '"' . ((string) $actual === $valor ? ' checked' : '');
$chips = function (string $nombre, array $opciones, mixed $actual, bool $req = false) use ($radio): string {
    $html = '<div class="iaas-opciones chips">';
    foreach ($opciones as $cod => $et) {
        $html .= '<label class="iaas-opcion"><input type="radio" ' . $radio($nombre, (string) $cod, $actual) . ($req ? ' required' : '') . '><span>' . e(is_array($et) ? $et[0] : $et) . '</span></label>';
    }
    return $html . '</div>';
};
require __DIR__ . '/../_cabecera.php';
?>
<?php if ($errores): ?>
  <div class="iaas-aviso crit" role="alert"><div><b>Revisa el registro:</b><ul><?php foreach ($errores as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div></div>
<?php endif; ?>
<?php if ($anulado): ?>
  <div class="iaas-aviso info"><div><b>Registro anulado</b> el <?= e(date('d/m/Y', strtotime($r['anulado_en']))) ?>. No cuenta en el indicador.</div></div>
<?php endif; ?>

<form method="post" action="<?= $esNuevo ? '/iaas/exposicion' : '/iaas/exposicion/' . (int) $r['id'] ?>" class="iaas-ficha iaas-expo" id="formExposicion">
  <?= Csrf::campoOculto() ?>
  <div class="iaas-ficha-cols">
    <div class="iaas-ficha-main">
      <section class="card section">
        <div class="section-head"><span class="section-num">1</span><h3>La exposición</h3></div>
        <div class="section-body">
          <span class="fl">Factor de riesgo <span class="req">*</span></span>
          <div class="iaas-opciones">
            <?php foreach (IaasNorma::FACTORES_EXPOSICION as $cod => $et): ?>
              <label class="iaas-opcion"><input type="radio" <?= $radio('factor', $cod, $v('factor')) ?> required data-factor-expo><span><b><?= $cod === 'PUNZOCORTANTE' ? 'Punzocortante' : 'Salpicadura' ?></b><?= $cod === 'PUNZOCORTANTE' ? 'Aguja, bisturí u otro instrumento' : 'Sangre o fluidos en mucosa o piel no intacta' ?></span></label>
            <?php endforeach; ?>
          </div>
          <div class="fields thirds iaas-sep">
            <div class="field"><label class="fl" for="xFecha">Fecha <span class="req">*</span></label><div class="control"><input type="date" id="xFecha" name="fecha" max="<?= date('Y-m-d') ?>" value="<?= e($v('fecha')) ?>" required></div></div>
            <div class="field"><label class="fl" for="xHora">Hora</label><div class="control mono"><input type="time" id="xHora" name="hora" value="<?= e($v('hora') ? substr($v('hora'), 0, 5) : '') ?>"></div></div>
            <div class="field"><span class="fl">Localización</span><?= $chips('localizacion', IaasNorma::LOCALIZACIONES, $v('localizacion')) ?></div>
          </div>
          <div class="iaas-sep"><span class="fl">Servicio o lugar <span class="req">*</span></span><?= $chips('servicio', IaasNorma::SERVICIOS_EXPOSICION, $v('servicio'), true) ?>
            <div class="control iaas-otro" data-otro="servicio"<?= $v('servicio') === 'OTRO' ? '' : ' hidden' ?>><input name="servicio_otro" maxlength="80" value="<?= e($v('servicio_otro')) ?>" placeholder="¿Qué servicio?" aria-label="Otro servicio"></div></div>
          <div class="iaas-sep"><span class="fl">Procedimiento en que ocurrió</span><?= $chips('procedimiento', IaasNorma::PROCEDIMIENTOS_EXPOSICION, $v('procedimiento')) ?>
            <div class="control iaas-otro" data-otro="procedimiento"<?= $v('procedimiento') === 'OTRO' ? '' : ' hidden' ?>><input name="procedimiento_otro" maxlength="80" value="<?= e($v('procedimiento_otro')) ?>" placeholder="¿Qué procedimiento?" aria-label="Otro procedimiento"></div></div>

          <div class="iaas-bloque-cond" data-solo-punzo<?= $v('factor') === 'SALPICADURA' ? ' hidden' : '' ?>>
            <div class="fields iaas-sep">
              <div class="field"><label class="fl" for="xObjeto">Objeto o instrumento punzocortante</label><div class="control"><input id="xObjeto" name="objeto" maxlength="120" value="<?= e($v('objeto')) ?>" placeholder="Ej.: aguja hipodérmica, bisturí, lanceta"></div></div>
              <div class="field"><span class="fl">Profundidad de la punción</span>
                <div class="iaas-opciones">
                  <?php foreach (IaasNorma::PROFUNDIDADES as $cod => [$et, $desc]): ?>
                    <label class="iaas-opcion"><input type="radio" <?= $radio('profundidad', $cod, $v('profundidad')) ?>><span><b><?= e($et) ?></b><?= e($desc) ?></span></label>
                  <?php endforeach; ?>
                </div></div>
            </div>
          </div>
          <div class="iaas-sep"><span class="fl">Fluido o secreción de la exposición</span><?= $chips('fluido', IaasNorma::FLUIDOS, $v('fluido')) ?>
            <div class="control iaas-otro" data-otro="fluido"<?= $v('fluido') === 'OTRO' ? '' : ' hidden' ?>><input name="fluido_otro" maxlength="80" value="<?= e($v('fluido_otro')) ?>" placeholder="Ej.: LCR, líquido pleural, amniótico" aria-label="Otro fluido"></div></div>
        </div>
      </section>

      <section class="card section">
        <div class="section-head"><span class="section-num">2</span><h3>Persona expuesta</h3><span class="adapt">El formato no pide el nombre</span></div>
        <div class="section-body">
          <div class="fields thirds">
            <div class="field"><label class="fl" for="xId">DNI, HC o autogenerado</label><div class="control mono"><input id="xId" name="identificador" maxlength="30" value="<?= e($v('identificador')) ?>"></div></div>
            <div class="field"><label class="fl" for="xEdad">Edad (años)</label><div class="control mono"><input type="number" id="xEdad" name="edad_anios" min="14" max="99" value="<?= e($v('edad_anios')) ?>"></div></div>
            <div class="field"><span class="fl">Sexo</span><div class="iaas-seg-radio"><label><input type="radio" <?= $radio('sexo', 'M', $v('sexo')) ?>><span>M</span></label><label><input type="radio" <?= $radio('sexo', 'F', $v('sexo')) ?>><span>F</span></label></div></div>
          </div>
          <div class="iaas-sep"><span class="fl">Profesión u ocupación <span class="req">*</span></span><?= $chips('profesion', IaasNorma::PROFESIONES, $v('profesion'), true) ?>
            <div class="control iaas-otro" data-otro="profesion"<?= $v('profesion') === 'OTRO' ? '' : ' hidden' ?>><input name="profesion_otra" maxlength="80" value="<?= e($v('profesion_otra')) ?>" placeholder="¿Cuál?" aria-label="Otra profesión"></div></div>
          <div class="iaas-sep"><span class="fl">Condición <span class="req">*</span></span><?= $chips('condicion', IaasNorma::CONDICIONES_PERSONAL, $v('condicion'), true) ?></div>
        </div>
      </section>

      <section class="card section">
        <div class="section-head"><span class="section-num">3</span><h3>Protección y seguimiento</h3></div>
        <div class="section-body">
          <span class="fl">Equipo de protección personal al momento de la exposición</span>
          <div class="iaas-opciones chips">
            <?php foreach (IaasNorma::EPP as $cod => $et): ?>
              <label class="iaas-opcion"><input type="checkbox" name="epp[]" value="<?= $cod ?>"<?= in_array($cod, $epp, true) ? ' checked' : '' ?>><span><?= e($et) ?></span></label>
            <?php endforeach; ?>
          </div>
          <span class="hint">Sin marcar = no usaba ninguno.</span>
          <div class="fields iaas-sep">
            <div class="field"><span class="fl">Identificación del paciente fuente</span><div class="iaas-seg-radio"><label><input type="radio" <?= $radio('paciente_fuente', '1', $v('paciente_fuente')) ?>><span>Sí</span></label><label><input type="radio" <?= $radio('paciente_fuente', '0', $v('paciente_fuente')) ?>><span>No</span></label></div></div>
            <div class="field"><span class="fl">Profilaxis post exposición</span><div class="iaas-seg-radio"><label><input type="radio" <?= $radio('profilaxis', '1', $v('profilaxis')) ?> data-profilaxis><span>Sí</span></label><label><input type="radio" <?= $radio('profilaxis', '0', $v('profilaxis')) ?> data-profilaxis><span>No</span></label></div></div>
            <div class="field wide" data-medicamento<?= (string) $v('profilaxis') === '1' ? '' : ' hidden' ?>><label class="fl" for="xMed">Medicamento prescrito</label><div class="control"><input id="xMed" name="profilaxis_medicamento" maxlength="160" value="<?= e($v('profilaxis_medicamento')) ?>"></div></div>
            <div class="field wide"><label class="fl" for="xObs">Observaciones</label><div class="control"><input id="xObs" name="observaciones" maxlength="255" value="<?= e($v('observaciones')) ?>"></div></div>
          </div>
        </div>
      </section>
    </div>

    <aside class="rail iaas-rail">
      <div class="card rail-card">
        <h4><?= $esNuevo ? 'Nuevo registro' : 'Registro N.º ' . (int) $r['id'] ?></h4>
        <p>Cuenta en el indicador 9 del Anexo 10, sobre el personal de salud del año (Anexo 9). Solo lo ve tu establecimiento.</p>
      </div>
      <div class="rail-actions">
        <?php if (!$anulado): ?><button type="submit" class="btn btn-primary">Guardar</button><?php endif; ?>
        <a class="btn btn-quiet" href="/iaas/exposicion">Volver al listado</a>
        <?php if (!$esNuevo && !$anulado): ?>
          <button type="submit" form="formAnularExpo" class="btn btn-quiet iaas-btn-peligro">Anular registro</button>
        <?php endif; ?>
      </div>
    </aside>
  </div>
</form>
<?php if (!$esNuevo && !$anulado): ?>
  <form id="formAnularExpo" method="post" action="/iaas/exposicion/<?= (int) $r['id'] ?>/anular" data-confirmar="¿Anular este registro? Dejará de contar en el indicador.">
    <?= Csrf::campoOculto() ?>
  </form>
<?php endif; ?>
<script src="<?= e(asset('/js/iaas.js')) ?>"></script>
