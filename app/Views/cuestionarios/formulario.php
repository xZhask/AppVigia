<?php
use App\Core\Csrf;
use App\Models\CasoCuestionario;

/**
 * Cuestionario asociado a un caso (CuestionariosController). Se arma solo con
 * la definición de config/cuestionarios/<clave>.php: ningún ítem está escrito
 * a mano acá. Sin permiso de edición se pinta igual, pero de solo lectura.
 *
 * Variables: $caso, $definicion, $datos, $guardado, $errores, $editable, $sugerido.
 */
$deshabilitado = $editable ? '' : ' disabled';
$valorDe = fn(string $clave) => $datos[$clave] ?? null;

// Atributos de una fila condicionada ("depende"): el script del final la
// muestra u oculta en vivo; el servidor descarta la respuesta si no aplica.
$atributosDepende = function (?array $depende) use ($valorDe): string {
    if (!$depende) {
        return '';
    }
    $valorPadre = $valorDe($depende['id']);
    $valoresPadre = is_array($valorPadre) ? $valorPadre : ($valorPadre === null ? [] : [$valorPadre]);
    $visible = (bool) array_intersect($valoresPadre, $depende['valores']);
    return ' data-cuestionario-depende="' . e($depende['id']) . '" data-valores="' . e(implode(',', $depende['valores'])) . '"' . ($visible ? '' : ' hidden');
};

// Control de respuesta según el tipo de la pregunta.
$control = function (string $clave, array $pregunta) use ($valorDe, $deshabilitado, $errores): void {
    $tipo = $pregunta['tipo'];
    $nombre = 'r[' . $clave . ']';
    $valor = $valorDe($clave);
    if ($tipo === 'si_no' || $tipo === 'opciones') {
        $opciones = $tipo === 'si_no' ? ['SI' => 'Sí', 'NO' => 'No'] : $pregunta['opciones'];
        ?>
        <div class="seg cuestionario-seg" style="flex-shrink:0; min-width:<?= count($opciones) > 2 ? 90 * count($opciones) : 130 ?>px">
          <?php foreach ($opciones as $codigo => $etiqueta): $marcado = (string) $valor === (string) $codigo; ?>
            <label class="seg-label <?= $marcado ? 'on' : '' ?>" style="flex:1; text-align:center; cursor:pointer;">
              <input type="radio" class="sr-only" name="<?= e($nombre) ?>" value="<?= e($codigo) ?>" <?= $marcado ? 'checked' : '' ?><?= $deshabilitado ?>>
              <?= e($etiqueta) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <?php
    } elseif ($tipo === 'multiple') {
        $marcados = is_array($valor) ? $valor : [];
        ?>
        <div style="display:flex; flex-wrap:wrap; gap:8px 16px">
          <?php foreach ($pregunta['opciones'] as $codigo => $etiqueta): ?>
            <label style="display:flex; align-items:center; gap:6px; font-size:13.5px; cursor:pointer">
              <input type="checkbox" name="<?= e($nombre) ?>[]" value="<?= e($codigo) ?>" <?= in_array($codigo, $marcados, true) ? 'checked' : '' ?><?= $deshabilitado ?>>
              <?= e($etiqueta) ?>
            </label>
          <?php endforeach; ?>
        </div>
        <?php
    } elseif ($tipo === 'textarea') {
        ?><textarea class="textarea-control" name="<?= e($nombre) ?>" rows="3"<?= $deshabilitado ?>><?= e((string) $valor) ?></textarea><?php
    } elseif ($tipo === 'tabla') {
        ?>
        <div style="overflow-x:auto; width:100%">
          <table style="border-collapse:collapse; width:100%">
            <?php foreach ($pregunta['filas'] as $fila => $etiquetaFila): ?>
              <tr>
                <td style="font-size:12.5px; color:var(--ink-2); padding:4px 8px 4px 0; white-space:nowrap"><?= e($etiquetaFila) ?></td>
                <?php for ($columna = 1; $columna <= (int) $pregunta['columnas']; $columna++): $claveCelda = "{$clave}.{$fila}.{$columna}"; ?>
                  <td style="padding:3px"><div class="control"><input type="text" name="r[<?= e($claveCelda) ?>]" value="<?= e((string) $valorDe($claveCelda)) ?>"<?= $deshabilitado ?>></div></td>
                <?php endfor; ?>
              </tr>
            <?php endforeach; ?>
          </table>
        </div>
        <?php
    } else {
        $tipoInput = ['numero' => 'number', 'fecha' => 'date'][$tipo] ?? 'text';
        $extraInput = $tipo === 'numero' ? ' min="0" step="1"' : ($tipo === 'fecha' ? ' max="' . date('Y-m-d') . '"' : '');
        ?><div class="control <?= $tipo !== 'texto' ? 'mono' : '' ?> <?= isset($errores[$clave]) ? 'err' : '' ?>" style="width:100%; max-width:<?= $tipo === 'texto' ? '100%' : '220px' ?>"><input type="<?= $tipoInput ?>" name="<?= e($nombre) ?>" value="<?= e((string) $valor) ?>"<?= $extraInput ?><?= $deshabilitado ?>></div><?php
    }
    if (isset($errores[$clave])) {
        ?><span class="hint err"><?= e($errores[$clave]) ?></span><?php
    }
};

// Fila de una pregunta (o subítem): enunciado | respuesta | consideración,
// más su "extra" y su "especificar" si los declara.
$fila = function (array $pregunta, string $numero, bool $esSubitem = false) use ($control, $atributosDepende, $valorDe, $deshabilitado): void {
    $clave = $pregunta['clave'];
    $tipo = $pregunta['tipo'];
    $enLinea = in_array($tipo, ['si_no', 'opciones', 'titulo'], true);
    ?>
    <div class="cuestionario-fila"<?= $atributosDepende($pregunta['depende'] ?? null) ?> style="border-bottom:1px solid var(--line-2); padding:8px 0 8px <?= $esSubitem ? '28px' : '0' ?>">
      <div style="display:flex; gap:14px; align-items:<?= $enLinea ? 'center' : 'flex-start' ?>; flex-wrap:wrap">
        <div style="flex:1 1 320px; font-size:13.5px; color:var(--ink); <?= $tipo === 'titulo' ? 'font-weight:500' : '' ?>">
          <span class="mono" style="color:var(--muted); margin-right:6px"><?= e($numero) ?></span><?= e($pregunta['texto']) ?>
        </div>
        <?php if ($enLinea && $tipo !== 'titulo'): $control($clave, $pregunta); endif; ?>
        <div style="width:110px; font-size:12px; color:var(--muted); text-align:right"><?= e($pregunta['consideracion'] ?? '') ?></div>
      </div>
      <?php if (!$enLinea): ?><div style="margin-top:8px"><?php $control($clave, $pregunta); ?></div><?php endif; ?>
      <?php if (!empty($pregunta['extra'])): ?>
        <div class="field" style="margin-top:8px; max-width:420px">
          <label class="fl"><?= e($pregunta['extra']['etiqueta']) ?></label>
          <div class="control"><input type="text" name="r[<?= e($clave) ?>.extra]" value="<?= e((string) $valorDe($clave . '.extra')) ?>"<?= $deshabilitado ?>></div>
        </div>
      <?php endif; ?>
      <?php if (!empty($pregunta['especificar'])): ?>
        <div class="field"<?= $atributosDepende(['id' => $clave, 'valores' => [$pregunta['especificar']['valor']]]) ?> style="margin-top:8px; max-width:420px">
          <label class="fl"><?= e($pregunta['especificar']['etiqueta']) ?></label>
          <div class="control"><input type="text" name="r[<?= e($clave) ?>.especificar]" value="<?= e((string) $valorDe($clave . '.especificar')) ?>"<?= $deshabilitado ?>></div>
        </div>
      <?php endif; ?>
    </div>
    <?php
};
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= e($definicion['titulo']) ?></div>
    <div class="page-desc"><?= e($definicion['subtitulo'] ?? '') ?></div>
  </div>
  <div class="spacer"></div>
  <a class="btn btn-ghost" href="/casos/<?= (int) $caso['id'] ?>">Volver a la ficha <span class="mono"><?= e($caso['codigo']) ?></span></a>
</div>

<?php if (!empty($errores)): ?>
  <div class="card" style="border-left:3px solid var(--danger, #c0392b); padding:12px 16px; margin-bottom:16px; font-size:13.5px">Revisa los campos marcados: no se guardó el cuestionario.</div>
<?php endif; ?>

<form method="post" action="/casos/<?= (int) $caso['id'] ?>/cuestionario/<?= e($definicion['clave']) ?>" id="formCuestionario">
  <?= Csrf::campoOculto() ?>

  <div class="card section" style="margin-bottom:16px">
    <div class="section-head"><h3>Caso</h3></div>
    <div class="section-body">
      <div class="fields thirds">
        <div class="field"><label class="fl">N.° del caso</label><div class="control mono" style="background:var(--paper)"><?= e($caso['codigo']) ?></div></div>
        <div class="field"><label class="fl">Paciente</label><div class="control" style="background:var(--paper)"><?= e(trim($caso['apellido_paterno'] . ' ' . $caso['apellido_materno'] . ', ' . $caso['nombres'], ' ,')) ?></div></div>
        <div class="field"><label class="fl">Establecimiento</label><div class="control" style="background:var(--paper)"><?= e($caso['establecimiento_nombre']) ?></div></div>
      </div>
      <?php if (!empty($definicion['aviso'])): ?>
        <p style="font-size:12.5px; color:<?= $sugerido ? 'var(--accent)' : 'var(--muted)' ?>; margin:12px 0 0">
          <?= $sugerido ? 'Este caso cumple la condición: ' : '' ?><?= e($definicion['aviso']) ?>
        </p>
      <?php endif; ?>
      <?php if ($guardado): ?>
        <p style="font-size:12.5px; color:var(--muted); margin:6px 0 0">Última actualización: <?= e(date('d/m/Y H:i', strtotime($guardado['actualizado_en']))) ?><?= !empty($guardado['usuario_nombre']) ? ' · ' . e($guardado['usuario_nombre']) : '' ?></p>
      <?php endif; ?>
    </div>
  </div>

  <?php foreach ($definicion['cabecera'] ?? [] as $grupo): ?>
    <div class="card section" style="margin-bottom:16px">
      <div class="section-head"><h3><?= e($grupo['titulo']) ?></h3></div>
      <div class="section-body">
        <div class="fields thirds">
          <?php foreach ($grupo['campos'] as $campo): $campo['clave'] = 'cab.' . $campo['id']; ?>
            <div class="field <?= in_array($campo['tipo'], ['opciones'], true) ? 'wide' : '' ?>">
              <label class="fl"><?= e($campo['texto']) ?></label>
              <?php $control($campo['clave'], $campo); ?>
              <?php if (!empty($campo['especificar'])): ?>
                <div<?= $atributosDepende(['id' => $campo['clave'], 'valores' => [$campo['especificar']['valor']]]) ?> style="margin-top:8px">
                  <label class="fl"><?= e($campo['especificar']['etiqueta']) ?></label>
                  <div class="control"><input type="text" name="r[<?= e($campo['clave']) ?>.especificar]" value="<?= e((string) $valorDe($campo['clave'] . '.especificar')) ?>"<?= $deshabilitado ?>></div>
                </div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php endforeach; ?>

  <?php foreach ($definicion['secciones'] as $seccion): ?>
    <?php if (!empty($definicion['indicaciones'][$seccion['id']])): ?>
      <div class="card" style="padding:12px 16px; margin-bottom:16px; font-size:13px; color:var(--ink-2)">
        <div class="eyebrow" style="margin-bottom:6px">Indicaciones</div>
        <?= e($definicion['indicaciones'][$seccion['id']]) ?>
      </div>
    <?php endif; ?>
    <div class="card section" style="margin-bottom:16px">
      <div class="section-head"><span class="section-num"><?= e($seccion['id']) ?></span><h3><?= e($seccion['titulo']) ?></h3></div>
      <div class="section-body">
        <?php if (!empty($seccion['subtitulo'])): ?><div class="eyebrow" style="margin-bottom:8px"><?= e($seccion['subtitulo']) ?></div><?php endif; ?>
        <?php foreach (CasoCuestionario::preguntasConClave($seccion) as $pregunta):
            $fila($pregunta, $pregunta['id'] . '.');
            foreach ($pregunta['subitems'] as $subitem) {
                $fila($subitem, $subitem['letra'] . ')', true);
            }
        endforeach; ?>
        <?php if (!empty($seccion['comentario'])): ?>
          <div class="field" style="margin-top:14px">
            <label class="fl"><?= e($seccion['comentario']) ?></label>
            <textarea class="textarea-control" name="r[comentario.<?= e($seccion['id']) ?>]" rows="3"<?= $deshabilitado ?>><?= e((string) $valorDe('comentario.' . $seccion['id'])) ?></textarea>
          </div>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($editable): ?>
    <div style="display:flex; gap:10px; justify-content:flex-end; margin-bottom:32px">
      <a class="btn btn-ghost" href="/casos/<?= (int) $caso['id'] ?>">Cancelar</a>
      <button class="btn btn-primary" type="submit">Guardar cuestionario</button>
    </div>
  <?php endif; ?>
</form>

<script>
// Radios segmentados (.seg-label.on) y filas condicionadas
// (data-cuestionario-depende): misma lógica que el servidor en
// CasoCuestionario::validar(), acá solo se adelanta la vista.
(function () {
  var form = document.getElementById('formCuestionario');
  if (!form) return;
  function valoresDe(clave) {
    var nombre = 'r[' + clave + ']';
    var marcados = form.querySelectorAll('[name="' + nombre + '"]:checked, [name="' + nombre + '[]"]:checked');
    return Array.prototype.map.call(marcados, function (el) { return el.value; });
  }
  function refrescar() {
    form.querySelectorAll('.cuestionario-seg').forEach(function (seg) {
      seg.querySelectorAll('.seg-label').forEach(function (lbl) {
        var input = lbl.querySelector('input');
        lbl.classList.toggle('on', !!(input && input.checked));
      });
    });
    var cambio;
    var pasadas = 0;
    do {
      cambio = false;
      pasadas++;
      form.querySelectorAll('[data-cuestionario-depende]').forEach(function (el) {
        var permitidos = (el.getAttribute('data-valores') || '').split(',');
        var clavePadre = el.getAttribute('data-cuestionario-depende');
        var padre = form.querySelector('[name="r[' + clavePadre + ']"], [name="r[' + clavePadre + '][]"]');
        var padreOculto = padre && padre.closest('[hidden]');
        var visible = !padreOculto && valoresDe(el.getAttribute('data-cuestionario-depende')).some(function (v) { return permitidos.indexOf(v) !== -1; });
        if (el.hidden === !visible) return;
        el.hidden = !visible;
        cambio = true;
        if (!visible) {
          el.querySelectorAll('input, textarea').forEach(function (input) {
            if (input.type === 'radio' || input.type === 'checkbox') input.checked = false; else input.value = '';
          });
        }
      });
    } while (cambio && pasadas < 10);
  }
  form.addEventListener('change', refrescar);
  refrescar();
})();
</script>
