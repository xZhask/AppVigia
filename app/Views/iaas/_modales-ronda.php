<?php
use App\Models\IaasNorma;

// Ventanas de la ronda del día (las llena iaas.js con los data-* del botón
// que las abre). Lo incluye ronda-dia.php, que define $campos(), $sala,
// $fecha, $neonatal y $dispositivosSala.
$hoyMax = e($hoy);
?>
<div class="modal-fondo" id="modalPaciente" hidden>
  <form class="modal-caja iaas-modal-ancho" method="post" action="/iaas/ronda/ingresar" role="dialog" aria-modal="true" aria-labelledby="modalPacienteTit" data-form-paciente>
    <?= $campos() ?><input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>">
    <div class="modal-cab"><h3 id="modalPacienteTit">Agregar paciente</h3><p><?= e(IaasNorma::nombreSala($sala)) ?> · se vigila desde su ingreso al servicio.</p></div>
    <div class="iaas-modal-cuerpo">
      <div class="iaas-fila-doc">
        <div class="field"><label class="fl" for="pTipoDoc">Documento</label>
          <div class="control"><select id="pTipoDoc" name="tipo_doc" data-nosearch="true">
            <option value="DNI">DNI</option><option value="CE">Carné de extranjería</option><option value="PTP">PTP</option>
            <option value="PAS">Pasaporte</option><option value="OTRO">Otro</option><option value="SIN_DOCUMENTO">Sin documento</option>
          </select></div></div>
        <div class="field" data-num-doc><label class="fl" for="pNumDoc">Número</label>
          <div class="control mono"><input id="pNumDoc" name="num_doc" maxlength="20" autocomplete="off" inputmode="numeric"></div></div>
        <button type="button" class="btn btn-ghost" data-buscar-persona>Buscar</button>
      </div>
      <div class="iaas-encontrado" data-encontrado hidden></div>
      <div class="fields thirds" data-datos-persona>
        <div class="field"><label class="fl" for="pApPat">Apellido paterno <span class="req">*</span></label><div class="control"><input id="pApPat" name="apellido_paterno" maxlength="60"></div></div>
        <div class="field"><label class="fl" for="pApMat">Apellido materno</label><div class="control"><input id="pApMat" name="apellido_materno" maxlength="60"></div></div>
        <div class="field"><label class="fl" for="pNombres">Nombres <span class="req">*</span></label><div class="control"><input id="pNombres" name="nombres" maxlength="80"></div></div>
        <div class="field"><span class="fl">Sexo</span>
          <div class="iaas-seg-radio"><label><input type="radio" name="sexo" value="M"><span>M</span></label><label><input type="radio" name="sexo" value="F"><span>F</span></label></div></div>
        <div class="field"><label class="fl" for="pFechaNac">Fecha de nacimiento</label><div class="control"><input type="date" id="pFechaNac" name="fecha_nac" max="<?= $hoyMax ?>"></div></div>
        <div class="field"><label class="fl" for="pHc">Historia clínica</label><div class="control mono"><input id="pHc" name="historia_clinica" maxlength="30"></div></div>
      </div>
      <div class="fields thirds iaas-sep">
        <div class="field"><label class="fl" for="pCama">N.º de cama</label><div class="control mono"><input id="pCama" name="n_cama" maxlength="10"></div></div>
        <div class="field"><label class="fl" for="pIngreso">Ingreso al servicio <span class="req">*</span></label><div class="control"><input type="date" id="pIngreso" name="fecha_ingreso" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-fecha-ingreso></div></div>
        <?php if ($neonatal): ?>
          <div class="field"><label class="fl" for="pPeso">Peso al nacer (g) <span class="req">*</span></label><div class="control mono"><input type="number" id="pPeso" name="peso_nacer_g" min="300" max="7000" inputmode="numeric" required data-peso></div><span class="hint" data-cat-peso>Se ubica en su categoría de peso para los indicadores.</span></div>
        <?php endif; ?>
        <div class="field wide"><label class="fl" for="pDx">Diagnóstico médico de ingreso</label><div class="control"><input id="pDx" name="diagnostico_ingreso" maxlength="200"></div></div>
        <div class="field wide"><label class="fl" for="pDxV">Diagnóstico médico vigilado</label><div class="control"><input id="pDxV" name="diagnostico_vigilado" maxlength="200" placeholder="El que presenta durante el mes vigilado"></div></div>
      </div>
      <fieldset class="iaas-disp-elegir iaas-sep">
        <legend class="fl">Dispositivos instalados <span class="req">*</span> <span class="hint">· marca los que tiene y desde cuándo</span></legend>
        <?php foreach ($dispositivosSala as $tipo): ?>
          <div class="iaas-disp-op" data-disp-op>
            <label><input type="checkbox" name="disp[<?= $tipo ?>]" value="1" data-disp-check><span class="iaas-sigla"><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$tipo]) ?></span><?= e(IaasNorma::DISPOSITIVOS[$tipo]) ?></label>
            <div class="control" data-disp-fecha hidden><input type="date" name="disp_fecha[<?= $tipo ?>]" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" aria-label="Instalación de <?= e(IaasNorma::DISPOSITIVOS_SIGLA[$tipo]) ?>"></div>
          </div>
        <?php endforeach; ?>
      </fieldset>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Agregar a la ronda</button></div>
  </form>
</div>

<div class="modal-fondo" id="modalDispositivo" hidden>
  <form class="modal-caja" method="post" action="/iaas/ronda/dispositivo" role="dialog" aria-modal="true" aria-labelledby="modalDispTit">
    <?= $campos() ?><input type="hidden" name="internacion_id" data-campo="internacion">
    <div class="modal-cab"><h3 id="modalDispTit">Agregar dispositivo</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <div class="iaas-opciones">
        <?php foreach ($dispositivosSala as $i => $tipo): ?>
          <label class="iaas-opcion"><input type="radio" name="tipo" value="<?= $tipo ?>"<?= $i === 0 ? ' checked' : '' ?>><span><b><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$tipo]) ?></b><?= e(IaasNorma::DISPOSITIVOS[$tipo]) ?></span></label>
        <?php endforeach; ?>
      </div>
      <div class="field"><label class="fl" for="dFecha">Fecha de instalación</label><div class="control"><input type="date" id="dFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-min="ingreso"></div><span class="hint">El día de la instalación cuenta como día de exposición.</span></div>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Agregar</button></div>
  </form>
</div>

<div class="modal-fondo" id="modalRetiro" hidden>
  <form class="modal-caja" method="post" action="/iaas/ronda/retirar" role="dialog" aria-modal="true" aria-labelledby="modalRetiroTit">
    <?= $campos() ?><input type="hidden" name="dispositivo_id" data-campo="dispositivo">
    <div class="modal-cab"><h3 id="modalRetiroTit">Registrar retiro</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <div class="field"><label class="fl" for="rFecha">Fecha de retiro</label><div class="control"><input type="date" id="rFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-min-despues="instalacion"></div>
        <span class="hint">El día del retiro ya no cuenta como exposición. Si fue un error, podrás deshacerlo.</span></div>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Registrar retiro</button></div>
  </form>
</div>

<div class="modal-fondo" id="modalIaas" hidden>
  <form class="modal-caja" method="post" action="/iaas/ronda/iniciar-iaas" role="dialog" aria-modal="true" aria-labelledby="modalIaasTit">
    <?= $campos() ?><input type="hidden" name="dispositivo_id" data-campo="dispositivo">
    <div class="modal-cab"><h3 id="modalIaasTit">Inicia una IAAS</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <p class="iaas-modal-nota">Se crea la ficha de investigación (Anexo 5) como <b data-campo-txt="tipo"></b>, con el paciente y el dispositivo ya cargados. Desde esa fecha, sus días se marcan con <span class="iaas-sim T">×</span> (en tratamiento) y no suman al total.</p>
      <div class="field"><label class="fl" for="iFecha">Fecha de la IAAS</label><div class="control"><input type="date" id="iFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-min="instalacion"></div><span class="hint">Fecha en que se clasifica como IAAS.</span></div>
    </div>
    <div class="ns-modal-acciones">
      <button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button>
      <button type="submit" class="btn btn-ghost" name="completar" value="0">Registrar; completo la ficha después</button>
      <button type="submit" class="btn btn-primary" name="completar" value="1">Registrar y completar la ficha</button>
    </div>
  </form>
</div>

<div class="modal-fondo" id="modalFin" hidden>
  <form class="modal-caja" method="post" action="/iaas/ronda/fin-tratamiento" role="dialog" aria-modal="true" aria-labelledby="modalFinTit">
    <?= $campos() ?><input type="hidden" name="caso_id" data-campo="caso">
    <div class="modal-cab"><h3 id="modalFinTit">Fin del tratamiento</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <div class="field"><label class="fl" for="fFecha">Último día de tratamiento</label><div class="control"><input type="date" id="fFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-min="desde"></div>
        <span class="hint">Desde el día siguiente, si el dispositivo sigue instalado, sus días vuelven a sumar.</span></div>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
  </form>
</div>

<div class="modal-fondo" id="modalEgreso" hidden>
  <form class="modal-caja" method="post" action="/iaas/ronda/egresar" role="dialog" aria-modal="true" aria-labelledby="modalEgresoTit">
    <?= $campos() ?><input type="hidden" name="internacion_id" data-campo="internacion">
    <div class="modal-cab"><h3 id="modalEgresoTit">Registrar egreso</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <div class="field"><span class="fl">Condición de egreso</span>
        <div class="iaas-opciones tres">
          <?php foreach (IaasNorma::CONDICION_EGRESO as $codigo => $etiqueta): ?>
            <label class="iaas-opcion"><input type="radio" name="condicion" value="<?= $codigo ?>" required><span><b><?= $codigo ?></b><?= e($etiqueta) ?></span></label>
          <?php endforeach; ?>
        </div></div>
      <div class="field"><label class="fl" for="eFecha">Fecha de egreso del servicio</label><div class="control"><input type="date" id="eFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= $hoyMax ?>" required data-min="ingreso"></div>
        <span class="hint">Los dispositivos que seguían instalados se retiran con el egreso.</span></div>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Registrar egreso</button></div>
  </form>
</div>

<div class="modal-fondo" id="modalEditar" hidden>
  <form class="modal-caja iaas-modal-ancho" method="post" action="/iaas/ronda/internacion" role="dialog" aria-modal="true" aria-labelledby="modalEditarTit">
    <?= $campos() ?><input type="hidden" name="internacion_id" data-campo="internacion_id">
    <div class="modal-cab"><h3 id="modalEditarTit">Datos del paciente en la sala</h3><p data-campo-txt="nombre"></p></div>
    <div class="iaas-modal-cuerpo">
      <div class="fields thirds">
        <div class="field"><label class="fl" for="eCama">N.º de cama</label><div class="control mono"><input id="eCama" name="n_cama" maxlength="10" data-campo="n_cama"></div></div>
        <div class="field"><label class="fl" for="eHc">Historia clínica</label><div class="control mono"><input id="eHc" name="historia_clinica" maxlength="30" data-campo="historia_clinica"></div></div>
        <?php if ($neonatal): ?>
          <div class="field"><label class="fl" for="ePeso">Peso al nacer (g)</label><div class="control mono"><input type="number" id="ePeso" name="peso_nacer_g" min="300" max="7000" required data-campo="peso_nacer_g"></div></div>
        <?php endif; ?>
        <div class="field wide"><label class="fl" for="eDx">Diagnóstico médico de ingreso</label><div class="control"><input id="eDx" name="diagnostico_ingreso" maxlength="200" data-campo="diagnostico_ingreso"></div></div>
        <div class="field wide"><label class="fl" for="eDxV">Diagnóstico médico vigilado</label><div class="control"><input id="eDxV" name="diagnostico_vigilado" maxlength="200" data-campo="diagnostico_vigilado"></div></div>
        <div class="field wide"><label class="fl" for="eObs">Observaciones</label><div class="control"><input id="eObs" name="observaciones" maxlength="255" data-campo="observaciones" placeholder="Por ejemplo, cultivos solicitados y sus resultados"></div></div>
      </div>
    </div>
    <div class="ns-modal-acciones"><button type="button" class="btn btn-ghost" data-cerrar-modal>Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
  </form>
</div>
