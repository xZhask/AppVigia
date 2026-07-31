<?php
/**
 * III. LUGAR PROBABLE DE INFECCIÓN - Exclusivo para Parotiditis con complicaciones (B26)
 * 
 * Registros dinámicos con botón "+ Agregar lugar"
 */

$esB26Card = (($enfermedad['cie10'] ?? null) === 'B26');

// Este partial se incluye sin condición aunque B26 no sea la ficha activa
// (mismo motivo que notificacion-fechas-b26.php): $campoB26 resuelve
// siempre contra la B26 real, no contra $enfermedad.
//
// Petición 2, cotejo B05/O95 (2026-07-30): esto asignaba $campo (el
// resolvedor AMBIENTE), no una variable propia -- pisaba $campo para el
// resto de la misma carga de página, incluido secciones-clinicas.php,
// que se incluye después. Ver el comentario equivalente y más largo en
// notificacion-fechas-o95.php.
$campoB26 = $resolvedorPara('B26');

$campoContactoCaso = $campoB26('b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con');
$campoContactosPorLugar = $campoB26('b26_contactos_por_lugar');
$campoContactoGestante = $campoB26('b26_tuvo_contacto_con_gestante');
$campoTrimestreGestanteContacto = $campoB26('b26_trimestre_de_gestacion_contacto');

// Extraer valores existentes
$valDirInf = $valoresCampos['b26_inf_direccion'] ?? '';
$valDepInf = $valoresCampos['b26_inf_departamento_id'] ?? '';
$valProvInf = $valoresCampos['b26_inf_provincia_id'] ?? '';
$valDistInf = $valoresCampos['b26_inf_distrito_id'] ?? '';
$valLocInf = $valoresCampos['b26_inf_localidad'] ?? '';

// ¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis?
$rawContactoCaso = $campoContactoCaso['val'];
if (is_array($rawContactoCaso)) {
    $valContactoCaso = $rawContactoCaso['valor'] ?? '';
} else {
    $valContactoCaso = (string) $rawContactoCaso;
}
if ($valContactoCaso === '1') $valContactoCaso = 'SI';
if ($valContactoCaso === '0') $valContactoCaso = 'NO';

// Contactos por lugar (Matriz dinámica)
$rawMatriz = $campoContactosPorLugar['val'];
$matrizContactos = is_array($rawMatriz) ? $rawMatriz : (json_decode((string) $rawMatriz, true) ?? []);

$filasLugaresDynamic = [];
if (!empty($matrizContactos)) {
    if (isset($matrizContactos[0]) && is_array($matrizContactos[0])) {
        $filasLugaresDynamic = $matrizContactos;
    } else {
        foreach ($matrizContactos as $k => $item) {
            if (!empty($item['activo'])) {
                $filasLugaresDynamic[] = [
                    'tipo'      => $k,
                    'nombre'    => $item['nombre'] ?? '',
                    'direccion' => $item['direccion'] ?? '',
                    'sanos'     => $item['sanos'] ?? '',
                    'enfermos'  => $item['enfermos'] ?? '',
                ];
            }
        }
    }
}

// ¿Tuvo contacto con gestante?
$rawContactoGestante = $campoContactoGestante['val'];
if (is_array($rawContactoGestante)) {
    $valContactoGestante = $rawContactoGestante['valor'] ?? '';
} else {
    $valContactoGestante = (string) $rawContactoGestante;
}
if ($valContactoGestante === '1') $valContactoGestante = 'SI';
if ($valContactoGestante === '0') $valContactoGestante = 'NO';

$valFechaContactoGestante = $valoresCampos['b26_fecha_contacto_gestante'] ?? '';
$valTrimestreGestanteContacto = $campoTrimestreGestanteContacto['val'];

// Closure para renderizar cada fila dinámica de lugar
$filaLugarContactoB26 = function (array $f = ['tipo' => 'COLEGIO', 'nombre' => '', 'direccion' => '', 'sanos' => '', 'enfermos' => '']): void {
    $tipo = $f['tipo'] ?? 'COLEGIO';
    $esCasa = ($tipo === 'CASA');
?>
  <div class="subrow row-lugar-b26" style="margin-bottom:12px;padding:14px;border:1px solid var(--line);border-radius:8px;background:var(--card-bg, rgba(255,255,255,0.02))">
    <div style="flex:1">
      <div class="fields halves" style="margin-bottom:10px">
        <div class="field">
          <label class="fl">Tipo de lugar</label>
          <div class="control">
            <select name="b26_lugar_tipo[]" class="sel-lugar-tipo-b26" data-nosearch="true">
              <option value="CASA" <?= seleccionado($tipo, 'CASA') ?>>Casa</option>
              <option value="NIDO_GUARDERIA" <?= seleccionado($tipo, 'NIDO_GUARDERIA') ?>>Nido / guardería</option>
              <option value="COLEGIO" <?= seleccionado($tipo, 'COLEGIO') ?>>Colegio</option>
              <option value="ESCUELA_MILITAR" <?= seleccionado($tipo, 'ESCUELA_MILITAR') ?>>Escuela Militar / policial</option>
              <option value="UNIVERSIDAD_INSTITUTO" <?= seleccionado($tipo, 'UNIVERSIDAD_INSTITUTO') ?>>Universidad / Instituto</option>
              <option value="CENTRO_TRABAJO" <?= seleccionado($tipo, 'CENTRO_TRABAJO') ?>>Centro de trabajo</option>
              <option value="ESTABLECIMIENTO_SALUD" <?= seleccionado($tipo, 'ESTABLECIMIENTO_SALUD') ?>>Establecimiento de Salud</option>
              <option value="OTROS" <?= seleccionado($tipo, 'OTROS') ?>>Otros (especificar)</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label class="fl">Nombre del lugar</label>
          <div class="control">
            <input type="text" name="b26_lugar_nombre[]" value="<?= e($f['nombre']) ?>" placeholder="<?= $esCasa ? '— No aplica —' : 'Nombre del lugar…' ?>" class="inp-lugar-nombre-b26" <?= $esCasa ? 'disabled' : '' ?>>
          </div>
        </div>
      </div>
      <div class="fields thirds" style="margin-bottom:0">
        <div class="field">
          <label class="fl">Dirección</label>
          <div class="control">
            <input type="text" name="b26_lugar_direccion[]" value="<?= e($f['direccion']) ?>" placeholder="Dirección del lugar…" class="inp-lugar-direccion-b26">
          </div>
        </div>
        <div class="field">
          <label class="fl">N.° Contactos sanos</label>
          <div class="control mono">
            <input type="number" min="0" step="1" name="b26_lugar_sanos[]" value="<?= e($f['sanos']) ?>" placeholder="0" class="inp-lugar-sanos-b26 mono" style="text-align:center">
          </div>
        </div>
        <div class="field">
          <label class="fl">N.° Contactos enfermos</label>
          <div class="control mono">
            <input type="number" min="0" step="1" name="b26_lugar_enfermos[]" value="<?= e($f['enfermos']) ?>" placeholder="0" class="inp-lugar-enfermos-b26 mono" style="text-align:center">
          </div>
        </div>
      </div>
    </div>
    <button type="button" class="ra quitar-fila" title="Quitar lugar" style="margin-top:22px;margin-left:10px">
      <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><path d="M3 4.5h9M6 4.5V3a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1v1.5M4.5 4.5v8a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-8" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/><path d="M6.3 7v4M8.7 7v4" stroke="currentColor" stroke-width="1.2" stroke-linecap="round"/></svg>
    </button>
  </div>
<?php }; ?>

<div class="card section b26-lugar-infeccion-card" id="cardLugarProbableInfeccionB26" <?= $esB26Card ? '' : 'hidden style="display:none;"' ?>>
  <div class="section-head">
    <span class="section-num">3</span>
    <h3>Lugar probable de infección</h3>
  </div>
  <div class="section-body">
    
    <!-- Dirección, Ubigeo y Localidad -->
    <div class="eyebrow" style="margin-bottom:12px">Ubicación del probable contagio</div>
    
    <div class="fields" style="margin-bottom:14px">
      <div class="field wide">
        <label class="fl">Dirección</label>
        <div class="control">
          <input type="text" name="b26_inf_direccion" value="<?= e($valDirInf) ?>" placeholder="Dirección del lugar probable de infección…">
        </div>
      </div>
    </div>

    <div style="margin-bottom:14px">
      <?php
        $prefijo = 'b26-inf-ubigeo';
        $nombreCampoDepartamento = 'b26_inf_departamento_id';
        $nombreCampoProvincia = 'b26_inf_provincia_id';
        $nombreCampoDistrito = 'b26_inf_distrito_id';
        $distritoRequerido = false;
        $departamentoSeleccionado = $valDepInf;
        $provinciaSeleccionada = $valProvInf;
        $distritoSeleccionado = $valDistInf;
        require __DIR__ . '/selector-ubigeo.php';
      ?>
    </div>

    <div class="fields" style="margin-bottom:20px">
      <div class="field wide">
        <label class="fl">Localidad</label>
        <div class="control">
          <input type="text" name="b26_inf_localidad" value="<?= e($valLocInf) ?>" placeholder="Localidad o caserío…">
        </div>
      </div>
    </div>

    <hr style="border:0;border-top:1px solid var(--line);margin:18px 0">

    <!-- 2. ¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis? -->
    <div class="field" style="margin-bottom:18px">
      <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
        ¿En las últimas 2 a 4 semanas estuvo en contacto con otro caso de parotiditis?
      </label>
      <div class="control" style="margin-top:6px">
        <div style="display:flex;gap:20px;align-items:center" id="wrapContactoCasoB26">
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoContactoCaso['name'] ?>" value="SI" class="radio-contacto-caso-b26" <?= ($valContactoCaso === 'SI') ? 'checked' : '' ?>>
            <span>Sí</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoContactoCaso['name'] ?>" value="NO" class="radio-contacto-caso-b26" <?= ($valContactoCaso === 'NO') ? 'checked' : '' ?>>
            <span>No</span>
          </label>
          <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
            <input type="radio" name="<?= $campoContactoCaso['name'] ?>" value="IGNORADO" class="radio-contacto-caso-b26" <?= ($valContactoCaso === 'IGNORADO') ? 'checked' : '' ?>>
            <span>Ignorado</span>
          </label>
        </div>
      </div>
    </div>

    <!-- Bloque Condicional: Se muestra solo si Contacto con otro caso == SI -->
    <div id="wrapDetalleContactosB26" <?= ($valContactoCaso === 'SI') ? '' : 'hidden style="display:none;"' ?>>
      
      <!-- 3. Lista Dinámica "Contactos por lugar" -->
      <div class="eyebrow" style="margin-top:20px;margin-bottom:10px">Contactos por lugar</div>
      
      <div class="subrows" data-lista="b26-lugar-contactos" id="listaLugarContactosB26">
        <?php foreach ($filasLugaresDynamic as $filaLugar): ?>
          <?php $filaLugarContactoB26($filaLugar); ?>
        <?php endforeach; ?>
      </div>

      <template id="plantilla-b26-lugar-contactos">
        <?php $filaLugarContactoB26(); ?>
      </template>

      <button type="button" class="btn btn-ghost agregar-fila" data-plantilla="plantilla-b26-lugar-contactos" data-lista="b26-lugar-contactos" style="margin-top:10px;margin-bottom:24px">
        <svg width="14" height="14" viewBox="0 0 14 14"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
        Agregar lugar
      </button>

      <!-- 4. ¿Tuvo contacto con gestante? -->
      <div class="field" style="margin-top:18px;margin-bottom:14px">
        <label class="fl" style="font-weight:600;font-size:14px;color:var(--text-main);margin-bottom:8px">
          ¿Este caso tuvo contacto con gestante?
        </label>
        <div class="control" style="margin-top:6px">
          <div style="display:flex;gap:20px;align-items:center" id="wrapContactoGestanteB26">
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoContactoGestante['name'] ?>" value="SI" class="radio-contacto-gestante-b26" <?= ($valContactoGestante === 'SI') ? 'checked' : '' ?>>
              <span>Sí</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoContactoGestante['name'] ?>" value="NO" class="radio-contacto-gestante-b26" <?= ($valContactoGestante === 'NO') ? 'checked' : '' ?>>
              <span>No</span>
            </label>
            <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-weight:500;font-size:14px">
              <input type="radio" name="<?= $campoContactoGestante['name'] ?>" value="IGNORADO" class="radio-contacto-gestante-b26" <?= ($valContactoGestante === 'IGNORADO') ? 'checked' : '' ?>>
              <span>Ignorado</span>
            </label>
          </div>
        </div>
      </div>

      <!-- Subcampos condicionales de Contacto con gestante == SI -->
      <div class="fields halves" id="wrapGestanteDetalleB26" style="margin-top:12px" <?= ($valContactoGestante === 'SI') ? '' : 'hidden style="display:none;"' ?>>
        <div class="field">
          <label class="fl">Fecha del contacto con gestante</label>
          <div class="control mono">
            <input type="date" name="b26_fecha_contacto_gestante" value="<?= e($valFechaContactoGestante) ?>" max="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <div class="field">
          <label class="fl">Trimestre de gestación en que sucedió el contacto</label>
          <div class="control">
            <select name="<?= $campoTrimestreGestanteContacto['name'] ?>" data-nosearch="true">
              <option value="">Seleccionar…</option>
              <option value="I" <?= seleccionado($valTrimestreGestanteContacto, 'I') ?>>I Trimestre</option>
              <option value="II" <?= seleccionado($valTrimestreGestanteContacto, 'II') ?>>II Trimestre</option>
              <option value="III" <?= seleccionado($valTrimestreGestanteContacto, 'III') ?>>III Trimestre</option>
            </select>
          </div>
        </div>
      </div>

    </div>

  </div>
</div>
