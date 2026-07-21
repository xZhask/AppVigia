<?php
/**
 * Renderiza las secciones dinámicas (seccion_def + campo_def) de una enfermedad.
 * Es la ÚNICA plantilla que dibuja estas secciones: la usan tanto la vista
 * completa de "Nueva ficha" como el endpoint AJAX que las recarga al cambiar
 * de enfermedad, para que ambas queden siempre idénticas.
 *
 * Variables esperadas:
 *   $enfermedad          fila de enfermedad
 *   $numeroSeccionInicial int, número de sección con el que empezar a contar
 *   $valoresCampos        [campo_def_id => valor] (valor es array para MULTISELECT)
 *   $erroresCampos        [campo_def_id => mensaje]
 *   $fechaInicioSintomas  string dd/mm/aaaa (campo fijo de caso.fecha_inicio_sintomas,
 *                          siempre existe sin importar la enfermedad)
 *   $errorFechaInicioSintomas ?string
 */

use App\Models\CampoDef;
use App\Models\CatalogoItem;
use App\Models\SeccionDef;

$secciones = SeccionDef::porEnfermedad((int) $enfermedad['id']);
$opcionesPorCatalogo = [];
$numeroSeccion = $numeroSeccionInicial;

$renderizarCampos = function (int $seccionId) use (&$opcionesPorCatalogo, $valoresCampos, $erroresCampos): void {
    $campos = CampoDef::porSeccion($seccionId);
    $puedeVerSensibles = \App\Core\Auth::tieneRol('ADMIN');
    $campos = array_filter($campos, fn($c) => empty($c['sensible']) || $puedeVerSensibles);
    
    $camposBooleanos = array_filter($campos, fn($c) => $c['tipo'] === 'BOOLEANO');
    $camposOtros = array_filter($campos, fn($c) => $c['tipo'] !== 'BOOLEANO');

    if (!empty($camposOtros)): ?>
        <div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
          <?php
          $tipoAnterior = null;
          foreach ($camposOtros as $campo):
            $campo['obligatorio'] = (int) $campo['obligatorio'];
            $valor = $valoresCampos[$campo['id']] ?? ($campo['tipo'] === 'MULTISELECT' ? [] : '');
            $error = $erroresCampos[$campo['id']] ?? null;
            $opciones = [];
            if ($campo['catalogo_id']) {
                $opcionesPorCatalogo[$campo['catalogo_id']] ??= CatalogoItem::porCatalogo((int) $campo['catalogo_id']);
                $opciones = $opcionesPorCatalogo[$campo['catalogo_id']];
            }
            $esSubgrupo = ($campo['tipo'] === 'GRUPO_SI_NO' && $tipoAnterior === 'GRUPO_SI_NO');
            $tieneDependencia = !empty($campo['depende_de']);
            if ($tieneDependencia):
                $oculto = !campoVisiblePorDependencia($campo, $valoresCampos);
                ?><div class="dep-wrap" data-depende-de="campo_<?= (int) $campo['depende_de'] ?>" data-valor-activador="<?= e($campo['valor_activador']) ?>" <?= $oculto ? 'hidden' : '' ?>><?php
            endif;
            require __DIR__ . '/campo-dinamico.php';
            if ($tieneDependencia): ?></div><?php endif;
            $tipoAnterior = $campo['tipo'];
          endforeach; ?>
        </div>
    <?php endif;

    if (!empty($camposBooleanos)): ?>
        <div class="eyebrow" style="margin-bottom:12px">Signos y síntomas</div>
        <div class="sym-grid">
          <?php foreach ($camposBooleanos as $campo):
            $campo['obligatorio'] = (int) $campo['obligatorio'];
            $valor = $valoresCampos[$campo['id']] ?? '';
            $error = $erroresCampos[$campo['id']] ?? null;
            $opciones = [];
            $tieneDependencia = !empty($campo['depende_de']);
            if ($tieneDependencia):
                $oculto = !campoVisiblePorDependencia($campo, $valoresCampos);
                ?><div class="dep-wrap" data-depende-de="campo_<?= (int) $campo['depende_de'] ?>" data-valor-activador="<?= e($campo['valor_activador']) ?>" <?= $oculto ? 'hidden' : '' ?>><?php
            endif;
            require __DIR__ . '/campo-dinamico.php';
            if ($tieneDependencia): ?></div><?php endif;
          endforeach; ?>
        </div>
    <?php endif;

    if (empty($campos)): ?>
        <p style="color:var(--muted);font-size:13px;margin:0">
          Todavía no hay campos clínicos definidos aquí. Se puede notificar igual completando notificación y persona.
        </p>
    <?php endif;
};

$rolPrevio = null;

$mostrarSeparadorSujeto = function(int $seccionId) use (&$rolPrevio) {
    $campos = \App\Models\CampoDef::porSeccion($seccionId);
    $rolActual = !empty($campos) ? $campos[0]['rol_sujeto'] : 'CASO_INDICE';
    
    if ($rolActual !== $rolPrevio) {
        $nombreRol = ucwords(strtolower(str_replace('_', ' ', $rolActual)));
        echo '<div style="margin: 24px 0 16px; padding-bottom: 8px; border-bottom: 2px solid var(--accent); color: var(--accent); font-weight: 600; font-size: 16px;">';
        echo '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: text-bottom; margin-right: 6px;"><circle cx="12" cy="7" r="4"></circle><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path></svg>';
        echo 'Sujeto: ' . htmlspecialchars($nombreRol) . '</div>';
        $rolPrevio = $rolActual;
    }
};
?>

<?php if (!empty($secciones)): ?>
  <?php $mostrarSeparadorSujeto((int) $secciones[0]['id']); ?>
<?php endif; ?>

<div class="card section">
  <div class="section-head">
    <span class="section-num"><?= $numeroSeccion ?></span>
    <h3><?= e($secciones[0]['nombre'] ?? 'Cuadro clínico') ?></h3>
    <span class="adapt">
      <svg width="12" height="12" viewBox="0 0 12 12"><path d="M6 1v10M1 6h10" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
      según <span id="adaptName"><?= e(mb_strtolower($enfermedad['nombre'])) ?></span>
    </span>
  </div>
  <div class="section-body">
    <div class="fields" style="margin-bottom:16px">
      <div class="field">
        <label class="fl">Fecha de inicio de síntomas <span class="req">*</span></label>
        <div class="control mono <?= $errorFechaInicioSintomas ? 'err' : '' ?>">
          <input type="date" name="fecha_inicio_sintomas" value="<?= e($fechaInicioSintomas) ?>" min="1900-01-01" max="<?= date('Y-m-d') ?>">
        </div>
        <?php if ($errorFechaInicioSintomas): ?><span class="hint err"><?= e($errorFechaInicioSintomas) ?></span><?php endif; ?>
      </div>
    </div>
    <?php if (!empty($secciones)): ?>
      <?php $renderizarCampos((int) $secciones[0]['id']); ?>
    <?php else: ?>
      <p style="color:var(--muted);font-size:13px;margin:0">
        Todavía no hay campos clínicos definidos para <?= e(mb_strtolower($enfermedad['nombre'])) ?>.
        Se puede notificar igual completando esta fecha y las secciones de notificación y persona.
      </p>
    <?php endif; ?>
  </div>
</div>
<?php $numeroSeccion++; ?>

<?php foreach (array_slice($secciones, 1) as $seccion): ?>
  <?php $mostrarSeparadorSujeto((int) $seccion['id']); ?>
  <div class="card section">
    <div class="section-head">
      <span class="section-num"><?= $numeroSeccion ?></span>
      <h3><?= e($seccion['nombre']) ?></h3>
    </div>
    <div class="section-body">
      <?php $renderizarCampos((int) $seccion['id']); ?>
    </div>
  </div>
  <?php $numeroSeccion++; ?>
<?php endforeach; ?>
<input type="hidden" id="numeroSiguienteSeccion" value="<?= $numeroSeccion ?>">
