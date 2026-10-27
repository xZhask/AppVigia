<?php
use App\Core\Csrf;
use App\Models\IaasNorma;

// Catálogos > Parámetros IAAS (solo ADMIN). Ver IaasParametrosController.
?>
<div class="page-head">
  <div>
    <div class="eyebrow">Catálogos · IAAS</div>
    <div class="page-title">Parámetros IAAS</div>
    <div class="page-desc">Valores de la NTS N° 163-MINSA/2020/CDC que usa el módulo. Se cargaron literales; ajústalos solo si el área lo indica.</div>
  </div>
</div>

<div class="iaas-param-cols">
  <section class="card tarjeta" id="horas">
    <div class="tarjeta-cab"><div><h3>Horas t · índice de riesgo de ISQ</h3><div class="sub">Percentil 75 de la duración de cada cirugía (Anexo 5, ítem 28). Valores de referencia NNIS/NHSN.</div></div></div>
    <form method="post" action="/catalogos/iaas/horas">
      <?= Csrf::campoOculto() ?>
      <table class="iaas-tabla-param">
        <thead><tr><th>Cirugía</th><th class="n">Horas t</th><th>Última modificación</th></tr></thead>
        <tbody>
          <?php foreach ($horasT as $h): ?>
            <tr>
              <td><?= e(IaasNorma::PROCEDIMIENTO_SINGULAR[$h['procedimiento']]) ?></td>
              <td class="n"><div class="control mono iaas-control-corto"><input name="horas[<?= e($h['procedimiento']) ?>]" value="<?= e(rtrim(rtrim(number_format((float) $h['horas'], 2, ',', ''), '0'), ',')) ?>" inputmode="decimal" aria-label="Horas t de <?= e(IaasNorma::PROCEDIMIENTO_SINGULAR[$h['procedimiento']]) ?>"><span class="lead">h</span></div></td>
              <td class="cell-sub"><?= $h['usuario_nombre'] ? e($h['usuario_nombre']) . ' · ' . e(date('d/m/Y', strtotime($h['actualizado_en']))) : 'Valor inicial' ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <p class="hint">Si la duración de la cirugía supera t, suma 1 punto al índice. Cambiar t recalcula el índice de todas las fichas al mostrarlas.</p>
      <button type="submit" class="btn btn-primary ns-btn-sm">Guardar horas t</button>
    </form>
  </section>

  <section class="card tarjeta" id="antimicrobianos">
    <div class="tarjeta-cab"><div><h3>Antimicrobianos del antibiograma</h3><div class="sub">Anexo 5, punto 4.5 · en el orden del papel</div></div></div>
    <div class="iaas-aviso info iaas-aviso-compacto"><div><b>Consulta pendiente con el área:</b> «Sulfaperazona/Sulfametoxazol» (¿Trimetoprim/Sulfametoxazol?) y Cefepime repetido. Se cargaron tal cual la norma.</div></div>
    <ol class="iaas-param-lista">
      <?php foreach ($antimicrobianos as $a): ?>
        <li class="<?= $a['activo'] ? '' : 'inactivo' ?>">
          <form method="post" action="/catalogos/iaas/antimicrobiano" class="iaas-param-fila">
            <?= Csrf::campoOculto() ?><input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
            <div class="control"><input name="nombre" value="<?= e($a['nombre']) ?>" maxlength="80" aria-label="Nombre"></div>
            <label class="iaas-param-activo"><input type="checkbox" name="activo" value="1"<?= $a['activo'] ? ' checked' : '' ?>> Activo</label>
            <button type="submit" class="btn btn-quiet ns-btn-sm">Guardar</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ol>
    <form method="post" action="/catalogos/iaas/antimicrobiano" class="iaas-param-fila iaas-param-nuevo">
      <?= Csrf::campoOculto() ?>
      <div class="control"><input name="nombre" maxlength="80" placeholder="Agregar antimicrobiano" aria-label="Nuevo antimicrobiano"></div>
      <button type="submit" class="btn btn-ghost ns-btn-sm">Agregar</button>
    </form>
  </section>
</div>

<section class="card tarjeta" id="microorganismos">
  <div class="tarjeta-cab"><div><h3>Microorganismos</h3><div class="sub">Anexo 11 · lista de códigos por familias</div></div></div>
  <div class="tabla-desplazable">
    <table class="iaas-tabla-param">
      <thead><tr><th>Familia</th><th>Microorganismo</th><th>Abreviatura</th><th>Activo</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($microorganismos as $m): $form = 'micro' . (int) $m['id']; ?>
          <tr class="<?= $m['activo'] ? '' : 'inactivo' ?>">
            <td><div class="filtro-caja"><select name="familia" form="<?= $form ?>" data-nosearch="true" aria-label="Familia"><?php foreach (IaasNorma::FAMILIAS_MICROORGANISMO as $n => $fam): ?><option value="<?= $n ?>"<?= (int) $m['familia'] === $n ? ' selected' : '' ?>><?= e($fam) ?></option><?php endforeach; ?></select></div></td>
            <td><div class="control"><input name="nombre" form="<?= $form ?>" value="<?= e($m['nombre']) ?>" maxlength="120" aria-label="Nombre"></div></td>
            <td><div class="control mono iaas-control-corto"><input name="abreviatura" form="<?= $form ?>" value="<?= e($m['abreviatura']) ?>" maxlength="40" aria-label="Abreviatura"></div></td>
            <td><input type="checkbox" name="activo" value="1" form="<?= $form ?>"<?= $m['activo'] ? ' checked' : '' ?> aria-label="Activo"></td>
            <td><form id="<?= $form ?>" method="post" action="/catalogos/iaas/microorganismo"><?= Csrf::campoOculto() ?><input type="hidden" name="id" value="<?= (int) $m['id'] ?>"><button type="submit" class="btn btn-quiet ns-btn-sm">Guardar</button></form></td>
          </tr>
        <?php endforeach; ?>
        <tr class="iaas-param-nuevo">
          <td><div class="filtro-caja"><select name="familia" form="microNuevo" data-nosearch="true" aria-label="Familia"><?php foreach (IaasNorma::FAMILIAS_MICROORGANISMO as $n => $fam): ?><option value="<?= $n ?>"><?= e($fam) ?></option><?php endforeach; ?></select></div></td>
          <td><div class="control"><input name="nombre" form="microNuevo" maxlength="120" placeholder="Agregar microorganismo" aria-label="Nuevo microorganismo"></div></td>
          <td><div class="control mono iaas-control-corto"><input name="abreviatura" form="microNuevo" maxlength="40" placeholder="ABC" aria-label="Abreviatura"></div></td>
          <td></td>
          <td><form id="microNuevo" method="post" action="/catalogos/iaas/microorganismo"><?= Csrf::campoOculto() ?><button type="submit" class="btn btn-ghost ns-btn-sm">Agregar</button></form></td>
        </tr>
      </tbody>
    </table>
  </div>
</section>
