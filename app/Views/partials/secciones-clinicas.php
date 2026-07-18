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
    $camposBooleanos = array_filter($campos, fn($c) => $c['tipo'] === 'BOOLEANO');
    $camposOtros = array_filter($campos, fn($c) => $c['tipo'] !== 'BOOLEANO');

    if (!empty($camposOtros)): ?>
        <div class="fields" style="margin-bottom:<?= empty($camposBooleanos) ? '0' : '16px' ?>">
          <?php foreach ($camposOtros as $campo):
            $campo['obligatorio'] = (int) $campo['obligatorio'];
            $valor = $valoresCampos[$campo['id']] ?? ($campo['tipo'] === 'MULTISELECT' ? [] : '');
            $error = $erroresCampos[$campo['id']] ?? null;
            $opciones = [];
            if ($campo['catalogo_id']) {
                $opcionesPorCatalogo[$campo['catalogo_id']] ??= CatalogoItem::porCatalogo((int) $campo['catalogo_id']);
                $opciones = $opcionesPorCatalogo[$campo['catalogo_id']];
            }
            require __DIR__ . '/campo-dinamico.php';
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
            require __DIR__ . '/campo-dinamico.php';
          endforeach; ?>
        </div>
    <?php endif;

    if (empty($campos)): ?>
        <p style="color:var(--muted);font-size:13px;margin:0">
          Todavía no hay campos clínicos definidos aquí. Se puede notificar igual completando notificación y paciente.
        </p>
    <?php endif;
};
?>
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
          <input type="text" name="fecha_inicio_sintomas" value="<?= e($fechaInicioSintomas) ?>" placeholder="dd/mm/aaaa">
        </div>
        <?php if ($errorFechaInicioSintomas): ?><span class="hint err"><?= e($errorFechaInicioSintomas) ?></span><?php endif; ?>
      </div>
    </div>
    <?php if (!empty($secciones)): ?>
      <?php $renderizarCampos((int) $secciones[0]['id']); ?>
    <?php else: ?>
      <p style="color:var(--muted);font-size:13px;margin:0">
        Todavía no hay campos clínicos definidos para <?= e(mb_strtolower($enfermedad['nombre'])) ?>.
        Se puede notificar igual completando esta fecha y las secciones de notificación y paciente.
      </p>
    <?php endif; ?>
  </div>
</div>
<?php $numeroSeccion++; ?>

<?php foreach (array_slice($secciones, 1) as $seccion): ?>
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
