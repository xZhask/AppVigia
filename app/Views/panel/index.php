<?php
use App\Models\Tablero;

// Panel de vigilancia (rediseño 2026-10-17). Ver PanelController.
?>
<?php if ($totalFichas === 0): ?>
  <div class="page-head">
    <div>
      <div class="page-title">Panel de vigilancia</div>
      <div class="page-desc">Consolidado de notificaciones de las IPRESS PNP</div>
    </div>
  </div>
  <?php
  $icono = '<svg width="19" height="19" viewBox="0 0 19 19" fill="none"><path d="M2 9.5 5 9.5 7 5 9.5 13 12 3 14 8.5 15.5 8.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
  $mensaje = 'Aún no hay fichas registradas. En cuanto se notifique el primer caso, aquí verás la curva epidemiológica y los indicadores del área.';
  $accionTexto = 'Registrar ficha';
  $accionHref = '/casos/nuevo';
  require __DIR__ . '/../partials/estado-vacio.php';
  ?>
<?php else:
  $m = $metricas;
  $actual = $periodo['actual'];
  $consultaPanel = ['periodo' => $periodo['clave'] === '4' ? '' : $periodo['clave'], 'enfermedad' => $idElegida, 'red' => $redId ?? ''];
  $url = fn(array $cambios = []) => '/' . (($q = queryConPagina($consultaPanel, $cambios)) !== '' ? '?' . $q : '');
  $textoAnterior = $periodo['clave'] === 'anio' ? 'mismo periodo de ' . ($actual['anio'] - 1) : ($periodo['n'] === 1 ? 'SE anterior' : 'periodo anterior');
  $delta = function (int $hoy, int $antes): array {
      $diferencia = $hoy - $antes;
      return [$diferencia > 0 ? 'up' : ($diferencia < 0 ? 'down' : 'flat'), ($diferencia > 0 ? '+' : ($diferencia < 0 ? '−' : '±')) . abs($diferencia)];
  };
  [$claseFichas, $textoFichas] = $delta($m['fichas'], $m['fichas_ant']);
  [$claseConf, $textoConf] = $delta($m['confirmados'], $m['confirmados_ant']);
  // El listado de fichas entiende SE actual, últimas 4 y el año.
  $periodoListado = ['actual' => 'actual', '4' => 'ultimas4', 'anio' => ''][$periodo['clave']] ?? null;
  $urlFichasPeriodo = $periodoListado === null ? null : '/casos?pestana=TODAS' . ($periodoListado !== '' ? '&periodo=' . $periodoListado : '');
  $etiquetaSituacion = [
      'sobre'        => ['crit', 'Sobre umbral'],
      'esperado'     => ['ok', 'Esperado'],
      'sin_historia' => ['na', 'Sin historia'],
  ];
  $icoAlerta = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>';
  $icoOk = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>';
  ?>
  <div class="page-head">
    <div>
      <div class="page-title">Panel de vigilancia</div>
      <div class="page-desc">Consolidado de notificaciones de las IPRESS PNP<?= $esRegistrador ? ' · tu establecimiento' : '' ?></div>
    </div>
    <div class="spacer"></div>
    <div class="periodo-panel">
      <span class="plabel">Periodo</span>
      <nav class="seg-periodo" aria-label="Periodo del panel">
        <?php foreach (Tablero::PERIODOS as $clave => $etiqueta): ?>
          <a href="<?= e($url(['periodo' => $clave === '4' ? '' : $clave])) ?>" class="<?= $periodo['clave'] === (string) $clave ? 'on' : '' ?>"<?= $periodo['clave'] === (string) $clave ? ' aria-current="true"' : '' ?>><?= $clave === 'anio' ? 'Año ' . $actual['anio'] : $etiqueta ?></a>
        <?php endforeach; ?>
      </nav>
      <span class="periodo-txt mono"><?= e($periodo['etiqueta']) ?></span>
    </div>
  </div>

  <div class="grid metrics" style="margin-bottom:16px">
    <a class="card metric metric-enlace"<?= $urlFichasPeriodo ? ' href="' . e($urlFichasPeriodo) . '"' : '' ?>>
      <div class="eyebrow">Fichas · <?= e($periodo['corta']) ?></div>
      <div class="metric-val"><?= number_format($m['fichas'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="delta <?= $claseFichas ?>"><?= $textoFichas ?></span> vs. <?= e($textoAnterior) ?> (<?= $m['fichas_ant'] ?>)</div>
    </a>
    <a class="card metric metric-enlace" href="/casos">
      <div class="eyebrow">En investigación</div>
      <div class="metric-val"><?= number_format($m['abiertas'], 0, ',', ' ') ?></div>
      <div class="metric-meta">
        <?php if ($m['sin_final'] > 0): ?>
          <span class="st crit"><?= $icoAlerta ?><?= $m['sin_final'] ?> sin clasificación final</span>
        <?php else: ?>
          <span class="state"><span class="dot st-open"></span> fichas abiertas</span>
        <?php endif; ?>
      </div>
    </a>
    <div class="card metric">
      <div class="eyebrow">Confirmados · <?= e($periodo['corta']) ?></div>
      <div class="metric-val"><?= number_format($m['confirmados'], 0, ',', ' ') ?></div>
      <div class="metric-meta"><span class="delta <?= $claseConf ?>"><?= $textoConf ?></span> vs. <?= e($textoAnterior) ?> (<?= $m['confirmados_ant'] ?>)</div>
    </div>
    <a class="card metric metric-enlace" href="/casos?pestana=VALIDACION">
      <div class="eyebrow">Pend. de validación</div>
      <div class="metric-val"><?= number_format($m['en_validacion'], 0, ',', ' ') ?></div>
      <div class="metric-meta">
        <?php if ($m['en_validacion'] > 0): ?>
          de <?= $m['en_validacion_est'] ?> establecimiento<?= $m['en_validacion_est'] === 1 ? '' : 's' ?> · <span class="enlace-txt">revisar →</span>
        <?php else: ?>
          <span class="st ok"><?= $icoOk ?>Nada pendiente</span>
        <?php endif; ?>
      </div>
    </a>
  </div>

  <div class="tablero">
    <!-- Enfermedades -->
    <div class="card tb-c5 tarjeta">
      <div class="tarjeta-cab"><div><h3>Enfermedades en vigilancia</h3><div class="sub">Clic en una fila para ver su curva · <?= e($periodo['etiqueta']) ?></div></div></div>
      <div class="tabla-desplazable">
      <table class="tabla-enf">
        <thead><tr><th>Enfermedad</th><th>Últimas 12 SE</th><th class="n"><?= e($periodo['corta']) ?></th><?php if ($periodo['clave'] !== 'anio'): ?><th class="n">Año</th><?php endif; ?><th>Situación</th></tr></thead>
        <?php
        $conAnio = $periodo['clave'] !== 'anio';
        $filasEnf = function (array $lista) use ($idElegida, $url, $etiquetaSituacion, $icoAlerta, $icoOk, $conAnio) {
            foreach ($lista as $enf):
                $max = max(2, ...array_column($enf['mini'], 'n'));
                [$claseSit, $textoSit] = $etiquetaSituacion[$enf['situacion']];
                ?>
                <tr class="<?= $enf['id'] === $idElegida ? 'sel' : '' ?>" data-href="<?= e($url(['enfermedad' => $enf['id']])) ?>#curva">
                  <td><a href="<?= e($url(['enfermedad' => $enf['id']])) ?>#curva" class="enf-nombre"><?= e($enf['nombre']) ?></a><?php if ($enf['inmediata']): ?><span class="tag-inm">Inmediata</span><?php endif; ?><div class="cell-sub mono"><?= e($enf['cie10']) ?></div></td>
                  <td>
                    <svg class="mini" width="84" height="24" viewBox="0 0 84 24" role="img" aria-label="Casos de las últimas 12 SE: <?= e(implode(', ', array_column($enf['mini'], 'n'))) ?>">
                      <?php foreach ($enf['mini'] as $i => $punto): $alto = $punto['n'] ? max(2, $punto['n'] / $max * 22) : 1; ?>
                        <rect x="<?= $i * 7 + 0.5 ?>" y="<?= 24 - $alto ?>" width="5.5" height="<?= $alto ?>" rx="1.2" fill="<?= $punto['n'] ? ($punto['sobre'] ? 'var(--s-confirmado)' : 'var(--accent)') : 'var(--line)' ?>"><title>SE <?= e(explode('-', $punto['clave'])[1]) ?>: <?= $punto['n'] ?></title></rect>
                      <?php endforeach; ?>
                    </svg>
                  </td>
                  <td class="n mono fuerte"><?= $enf['periodo'] ?></td>
                  <?php if ($conAnio): ?><td class="n mono"><?= $enf['anio'] ?></td><?php endif; ?>
                  <td><span class="st <?= $claseSit ?>"><?= $claseSit === 'crit' ? $icoAlerta : ($claseSit === 'ok' ? $icoOk : '') ?><?= $textoSit ?></span></td>
                </tr>
                <?php
            endforeach;
        };
        $principales = array_values(array_filter($enfermedades, fn($e) => $e['anio'] > 0 || $e['periodo'] > 0 || $e['id'] === $idElegida));
        $resto = array_values(array_filter($enfermedades, fn($e) => !($e['anio'] > 0 || $e['periodo'] > 0 || $e['id'] === $idElegida)));
        ?>
        <tbody><?php $filasEnf($principales); ?></tbody>
        <?php if ($resto): ?>
          <tbody class="enf-resto" id="enfResto" hidden><?php $filasEnf($resto); ?></tbody>
        <?php endif; ?>
      </table>
      </div>
      <?php if ($resto): ?>
        <button type="button" class="btn-enlace ver-mas" data-mostrar="enfResto" data-texto-ocultar="Ocultar las enfermedades sin casos en el año">Ver las <?= count($resto) ?> enfermedades sin casos en el año</button>
      <?php endif; ?>
    </div>

    <!-- Curva + inmediatas -->
    <div class="tb-c7 pila">
      <div class="card tarjeta" id="curva">
        <?php
        $c = $curva;
        $puntos = $c['puntos'];
        $n = count($puntos);
        $W = 760; $H = 270; $ml = 30; $mr = 8; $mt = 26; $mb = 26;
        $iw = $W - $ml - $mr; $ih = $H - $mt - $mb;
        $tope = 0;
        foreach ($puntos as $p) {
            $tope = max($tope, $p['n'], $p['zona'] ? $p['zona']['q3'] * 1.3 : 0);
        }
        $tope = max(3, (int) ceil($tope));
        $paso = $tope <= 6 ? 1 : ($tope <= 12 ? 2 : (int) ceil($tope / 5));
        $tope = (int) (ceil($tope / $paso) * $paso);
        $bw = $iw / max(1, $n);
        $x = fn(int $i) => $ml + $i * $bw;
        $y = fn(float $v) => $mt + $ih - min($v, $tope) / $tope * $ih;
        $anioCurva = $actual['anio'];
        ?>
        <div class="tarjeta-cab">
          <div><h3>Curva epidemiológica · <?= e($c['enfermedad']['nombre']) ?></h3>
            <div class="sub"><?= e($c['enfermedad']['cie10']) ?> · casos notificados por semana · SE <?= $puntos[0]['semana']['semana'] ?><?= $puntos[0]['semana']['anio'] !== $anioCurva ? ' · ' . $puntos[0]['semana']['anio'] : '' ?>–<?= $actual['semana'] ?> · <?= $anioCurva ?></div></div>
          <div class="spacer"></div>
          <a class="enlace-cab" href="/reportes?<?= e(http_build_query(['enfermedad_id' => $c['enfermedad']['id'], 'filas' => 'se', 'columnas' => 'clasificacion', 'periodo' => 'anio', 'modo' => 'grafico'])) ?>">Abrir en Reportes →</a>
        </div>
        <svg class="curva" viewBox="0 0 <?= $W ?> <?= $H ?>" role="img" aria-label="Curva epidemiológica de <?= e($c['enfermedad']['nombre']) ?>">
          <?php if ($c['conCorredor']): foreach ($puntos as $i => $p): $z = $p['zona']; ?>
            <rect x="<?= $x($i) ?>" y="<?= $y($tope) ?>" width="<?= $bw + 0.3 ?>" height="<?= $y($z['q3']) - $y($tope) ?>" fill="var(--z4)"/>
            <rect x="<?= $x($i) ?>" y="<?= $y($z['q3']) ?>" width="<?= $bw + 0.3 ?>" height="<?= $y($z['q2']) - $y($z['q3']) ?>" fill="var(--z3)"/>
            <rect x="<?= $x($i) ?>" y="<?= $y($z['q2']) ?>" width="<?= $bw + 0.3 ?>" height="<?= $y($z['q1']) - $y($z['q2']) ?>" fill="var(--z2)"/>
            <rect x="<?= $x($i) ?>" y="<?= $y($z['q1']) ?>" width="<?= $bw + 0.3 ?>" height="<?= $y(0) - $y($z['q1']) ?>" fill="var(--z1)"/>
          <?php endforeach; endif; ?>
          <?php for ($v = 0; $v <= $tope; $v += $paso): ?>
            <line x1="<?= $ml ?>" x2="<?= $W - $mr ?>" y1="<?= $y($v) ?>" y2="<?= $y($v) ?>" stroke="var(--line-2)"/>
            <text x="<?= $ml - 6 ?>" y="<?= $y($v) + 3 ?>" text-anchor="end"><?= $v ?></text>
          <?php endfor; ?>
          <?php foreach ($puntos as $i => $p):
            $s = $p['semana'];
            $zonaTxt = '';
            if ($p['zona']) {
                $zonaTxt = ' · ' . ($p['n'] > $p['zona']['q3'] ? 'zona de epidemia' : ($p['n'] > $p['zona']['q2'] ? 'zona de alarma' : ($p['n'] > $p['zona']['q1'] ? 'zona de seguridad' : 'zona de éxito')));
            }
            ?>
            <?php if ($p['n'] > 0): ?>
              <rect x="<?= $x($i) + $bw * 0.14 ?>" y="<?= $y($p['n']) ?>" width="<?= $bw * 0.72 ?>" height="<?= $y(0) - $y($p['n']) ?>" rx="<?= min(3, $bw * 0.2) ?>" fill="<?= $p['sobre'] ? 'var(--s-confirmado)' : 'var(--accent)' ?>"/>
            <?php endif; ?>
            <rect class="hit" x="<?= $x($i) ?>" y="<?= $mt ?>" width="<?= $bw ?>" height="<?= $ih ?>" fill="transparent"><title>SE <?= $s['semana'] ?> · <?= $s['anio'] ?>: <?= $p['n'] ?> <?= $p['n'] === 1 ? 'caso' : 'casos' ?><?= $zonaTxt ?></title></rect>
            <?php if ($s['semana'] % 4 === 1 || $i === $n - 1): ?>
              <text x="<?= $x($i) + $bw / 2 ?>" y="<?= $H - 8 ?>" text-anchor="middle"<?= $i === $n - 1 ? ' class="actual"' : '' ?>>SE <?= $s['semana'] ?></text>
            <?php endif; ?>
          <?php endforeach; ?>
          <?php
          $enPeriodo = array_keys(array_filter($puntos, fn($p) => $p['periodo']));
          if ($enPeriodo && count($enPeriodo) < $n):
              $px = $x($enPeriodo[0]); $pw = count($enPeriodo) * $bw; ?>
            <rect x="<?= $px ?>" y="<?= $mt ?>" width="<?= $pw ?>" height="<?= $ih ?>" fill="none" stroke="var(--accent)" stroke-width="1.4" stroke-dasharray="3 3" rx="2"/>
            <rect x="<?= $px ?>" y="<?= $mt - 8 ?>" width="<?= $pw ?>" height="3.5" rx="1.5" fill="var(--accent)"/>
            <text x="<?= $px + $pw ?>" y="<?= $mt - 12 ?>" text-anchor="end" class="actual">Periodo</text>
          <?php endif; ?>
          <line x1="<?= $ml ?>" x2="<?= $W - $mr ?>" y1="<?= $y(0) ?>" y2="<?= $y(0) ?>" stroke="var(--line)"/>
        </svg>
        <div class="leyenda">
          <span><i style="background:var(--accent)"></i>Casos</span>
          <?php if ($c['conCorredor']): ?>
            <span><i style="background:var(--s-confirmado)"></i>Sobre el umbral (zona de epidemia)</span>
            <span><i class="zona" style="background:var(--z1)"></i>Éxito</span>
            <span><i class="zona" style="background:var(--z2)"></i>Seguridad</span>
            <span><i class="zona" style="background:var(--z3)"></i>Alarma</span>
            <span><i class="zona" style="background:var(--z4)"></i>Epidemia</span>
          <?php endif; ?>
          <span><i class="zona" style="background:none;border:1.5px dashed var(--accent)"></i>Periodo seleccionado</span>
        </div>
        <p class="nota-curva">
          <?php if ($c['conCorredor']): ?>
            Corredor endémico por cuartiles de <?= $c['aniosHistoria'] ?> años anteriores (éxito &lt; Q1, seguridad &lt; mediana, alarma &lt; Q3, epidemia &gt; Q3).
          <?php else: ?>
            <b>Sin corredor endémico:</b> se necesitan <?= $c['minimoAnios'] ?> años anteriores con datos y esta enfermedad tiene <?= $c['aniosHistoria'] === 0 ? 'ninguno' : $c['aniosHistoria'] ?>.<?= $c['enfermedad']['tipo_notif'] === 'INMEDIATA' ? ' Todo caso de una enfermedad de notificación inmediata requiere investigación.' : '' ?>
          <?php endif; ?>
        </p>
      </div>

      <div class="card tarjeta">
        <div class="tarjeta-cab"><div><h3>Notificaciones inmediatas recientes</h3></div><div class="spacer"></div><a class="enlace-cab" href="/casos?pestana=TODAS&amp;periodo=todo">Ver fichas →</a></div>
        <?php if (!$inmediatas): ?>
          <p class="vacio-tarjeta">Sin fichas de enfermedades de notificación inmediata.</p>
        <?php else: ?>
          <ul class="lista-inm">
            <?php foreach ($inmediatas as $f): $dc = datosClasificacion((string) $f['clasificacion']); ?>
              <li>
                <a href="/casos/<?= (int) $f['id'] ?>"><?= e($f['enfermedad_nombre']) ?> <span class="cell-sub mono"><?= e($f['cie10']) ?> · <?= e($f['codigo']) ?></span></a>
                <span class="badge-clas" style="--c:<?= $dc['color'] ?>"><span class="dot"></span><?= e($dc['etiqueta']) ?></span>
                <span class="cell-sub"><?= e(nombreEnOracion($f['establecimiento_nombre'])) ?></span>
                <span class="cell-sub mono"><?= e(fechaIsoADmy($f['fecha_notif'])) ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
    </div>

    <!-- Cumplimiento -->
    <div class="card tb-c8 tarjeta" id="cumplimiento">
      <?php
      $semanasMapa = $cumplimiento['semanas'];
      $filas = $cumplimiento['filas'];
      $totalFilas = count($filas);
      $etiquetas = ['T' => 'Cerrada a tiempo', 'L' => 'Cerrada fuera de plazo', 'X' => 'No notificó (sin cerrar, plazo vencido)', 'P' => 'Sin cerrar, plazo vigente'];
      $primeraSe = $semanasMapa[0];
      ?>
      <div class="tarjeta-cab">
        <div>
          <h3>Cumplimiento de notificación</h3>
          <div class="sub">Por establecimiento y semana · SE <?= $primeraSe['semana'] ?><?= $primeraSe['anio'] !== $actual['anio'] ? ' · ' . $primeraSe['anio'] : '' ?>–<?= $actual['semana'] ?><?= $periodo['n'] < 8 ? ' (se muestran al menos 8 SE)' : ($periodo['n'] > count($semanasMapa) ? ' (últimas ' . count($semanasMapa) . ' SE)' : '') ?> · de menor a mayor cumplimiento</div>
        </div>
        <div class="spacer"></div>
        <?php if ($redes): ?>
          <form method="get" action="/#cumplimiento" class="filtro-red">
            <?php foreach (['periodo' => $consultaPanel['periodo'], 'enfermedad' => $idElegida] as $k => $v): if ($v !== '' && $v !== null): ?><input type="hidden" name="<?= $k ?>" value="<?= e((string) $v) ?>"><?php endif; endforeach; ?>
            <div class="filtro-caja"><select name="red" aria-label="Red" onchange="this.form.submit()">
              <option value="">Todas las redes</option>
              <?php foreach ($redes as $red): ?><option value="<?= (int) $red['id'] ?>" <?= seleccionado((string) $redId, $red['id']) ?>><?= e($red['nombre']) ?></option><?php endforeach; ?>
            </select></div>
          </form>
        <?php endif; ?>
      </div>
      <p class="resumen-cumpl">
        <b class="mono"><?= $notificaronActual ?></b> de <?= $totalFilas ?> establecimiento<?= $totalFilas === 1 ? '' : 's' ?> ya cerraron la SE <?= $semanaObjetivo['semana'] ?>.
        Cada establecimiento cierra cada semana, tenga casos o no. Clic en una celda para ver esa semana en <a href="/notificacion-semanal">Notificación semanal</a>.
      </p>
      <?php if (!$filas): ?>
        <p class="vacio-tarjeta">No hay establecimientos activos en esta red.</p>
      <?php else: ?>
      <div class="mapa-desplazable">
        <table class="mapa">
          <colgroup><col class="c-nm"><?php foreach ($semanasMapa as $s): ?><col><?php endforeach; ?><col class="c-pc"></colgroup>
          <thead><tr><th></th>
            <?php foreach ($semanasMapa as $i => $s): $muchas = count($semanasMapa) > 16; ?>
              <th class="<?= $i === count($semanasMapa) - 1 ? 'actual' : '' ?>"><?= !$muchas || $s['semana'] % 2 === 1 || $i === count($semanasMapa) - 1 ? $s['semana'] : '' ?></th>
            <?php endforeach; ?>
            <th class="pc">%</th></tr></thead>
          <?php foreach ([array_slice($filas, 0, $filasMapa), array_slice($filas, $filasMapa)] as $bloque => $filasBloque): if (!$filasBloque) continue; ?>
            <tbody<?= $bloque ? ' id="mapaResto" hidden' : '' ?>>
              <?php foreach ($filasBloque as $fila): ?>
                <tr>
                  <td class="nm" title="<?= e($fila['nombre']) ?>"><?= e(nombreEnOracion($fila['nombre'])) ?><small><?= e($fila['red_nombre'] ?? '—') ?></small></td>
                  <?php foreach ($fila['celdas'] as $celda):
                    $s = $celda['semana'];
                    $texto = nombreEnOracion($fila['nombre']) . ' · SE ' . $s['semana'] . ' · ' . $s['anio'] . ': ' . $etiquetas[$celda['estado']]
                        . ' · ' . ($celda['fichas'] ? $celda['fichas'] . ' ficha' . ($celda['fichas'] === 1 ? '' : 's') : 'sin fichas');
                    $enlace = '/notificacion-semanal?' . http_build_query(['anio' => $s['anio'], 'semana' => $s['semana']] + ($esRegistrador ? [] : ['q' => $fila['nombre']]));
                    ?>
                    <td><a class="celda <?= $celda['estado'] ?>" href="<?= e($enlace) ?>" title="<?= e($texto) ?>" aria-label="<?= e($texto) ?>"></a></td>
                  <?php endforeach; ?>
                  <td class="pc mono<?= $fila['porcentaje'] !== null && $fila['porcentaje'] < 80 ? ' bajo' : '' ?>"><?= $fila['porcentaje'] === null ? '—' : $fila['porcentaje'] . ' %' ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          <?php endforeach; ?>
        </table>
      </div>
      <?php if ($totalFilas > $filasMapa): ?>
        <button type="button" class="btn-enlace ver-mas" data-mostrar="mapaResto" data-texto-ocultar="Mostrar solo los <?= $filasMapa ?> primeros">Ver los <?= $totalFilas - $filasMapa ?> establecimientos restantes</button>
      <?php endif; ?>
      <?php endif; ?>
      <div class="leyenda">
        <span><i class="celda T"></i>Cerrada a tiempo</span>
        <span><i class="celda L"></i>Cerrada fuera de plazo</span>
        <span><i class="celda X"></i>No notificó</span>
        <span><i class="celda P"></i>Pendiente (plazo vigente)</span>
      </div>
    </div>

    <div class="tb-c4 pila">
      <div class="card tarjeta">
        <?php $totalClas = array_sum($clasificacion); ?>
        <div class="tarjeta-cab"><div><h3>Clasificación de casos</h3><div class="sub"><?= $totalClas ?> ficha<?= $totalClas === 1 ? '' : 's' ?> · <?= e($periodo['etiqueta']) ?></div></div></div>
        <div class="barra-apilada" role="img" aria-label="Distribución por clasificación">
          <?php if ($totalClas === 0): ?><span style="flex:1;background:var(--line-2)"></span><?php endif; ?>
          <?php foreach ($clasificacion as $codigo => $total): if ($total > 0): $dc = datosClasificacion($codigo); ?>
            <span style="flex:<?= $total ?>;background:<?= $dc['color'] ?>" title="<?= e($dc['etiqueta']) ?>: <?= $total ?> (<?= round($total / $totalClas * 100) ?> %)"></span>
          <?php endif; endforeach; ?>
        </div>
        <div class="lista-clas">
          <?php foreach ($clasificacion as $codigo => $total): $dc = datosClasificacion($codigo); ?>
            <i style="background:<?= $dc['color'] ?>"></i><span><?= e($dc['etiqueta']) ?></span><span class="mono fuerte"><?= $total ?></span><span class="mono pct"><?= $totalClas ? round($total / $totalClas * 100) : 0 ?> %</span>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card tarjeta">
        <?php
        $tramos = [['0–1', 0, 1], ['2–3', 2, 3], ['4–7', 4, 7], ['8–14', 8, 14], ['> 14', 15, PHP_INT_MAX]];
        $conteos = array_map(fn($t) => count(array_filter($dias, fn($d) => $d >= $t[1] && $d <= $t[2])), $tramos);
        $maxTramo = max(1, ...$conteos);
        $mediana = $dias ? $dias[intdiv(count($dias), 2)] : null;
        ?>
        <div class="tarjeta-cab"><div><h3>Oportunidad de notificación</h3><div class="sub">Días entre inicio de síntomas y notificación · <?= e($periodo['etiqueta']) ?></div></div></div>
        <?php if (!$dias): ?>
          <p class="vacio-tarjeta">Ninguna ficha del periodo tiene fecha de inicio de síntomas.</p>
        <?php else: ?>
          <div class="mediana"><span class="mono"><?= $mediana ?></span> <?= $mediana === 1 ? 'día' : 'días' ?> (mediana de <?= count($dias) ?> ficha<?= count($dias) === 1 ? '' : 's' ?>)</div>
          <svg class="histo" viewBox="0 0 300 104" role="img" aria-label="Fichas por días hasta la notificación">
            <?php foreach ($tramos as $i => $t): $alto = $conteos[$i] ? max(3, $conteos[$i] / $maxTramo * 66) : 0; $x0 = $i * 60; ?>
              <rect x="<?= $x0 + 8 ?>" y="<?= 84 - $alto ?>" width="44" height="<?= $alto ?>" rx="3" fill="<?= $i === 0 ? 'var(--accent)' : ($i < 3 ? 'color-mix(in srgb,var(--accent) 40%,var(--surface))' : 'var(--s-probable)') ?>"><title><?= $t[0] ?> días: <?= $conteos[$i] ?> fichas</title></rect>
              <text x="<?= $x0 + 30 ?>" y="<?= 84 - $alto - 5 ?>" text-anchor="middle" class="val"><?= $conteos[$i] ?></text>
              <text x="<?= $x0 + 30 ?>" y="100" text-anchor="middle"><?= $t[0] ?> d</text>
            <?php endforeach; ?>
            <line x1="0" x2="300" y1="84" y2="84" stroke="var(--line)"/>
          </svg>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <script src="<?= e(asset('/js/panel.js')) ?>"></script>
<?php endif; ?>
