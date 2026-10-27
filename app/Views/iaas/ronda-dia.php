<?php
use App\Controllers\IaasRondaController;
use App\Core\Csrf;
use App\Models\IaasCaso;
use App\Models\IaasNorma;
use App\Models\Persona;

// Ronda del día (lo incluye ronda.php). Lo común se hace con un clic:
// "Confirmar sala" marca todo lo pendiente; cada fila se marca o desmarca sola.
$esHoy = $fecha === $hoy;
$anterior = date('Y-m-d', strtotime($fecha . ' -1 day'));
$siguiente = date('Y-m-d', strtotime($fecha . ' +1 day'));
$volver = $urlRonda(['fecha' => $esHoy ? null : $fecha]);
$conDispositivos = (bool) $dispositivosSala;
$diaCorto = fn(string $f) => ['dom', 'lun', 'mar', 'mié', 'jue', 'vie', 'sáb'][(int) date('w', strtotime($f))] . ' ' . (int) substr($f, 8, 2);
$enCurso = array_values(array_filter($pacientes, fn($p) => !$p['egreso_hoy']));
$egresadosHoy = array_values(array_filter($pacientes, fn($p) => $p['egreso_hoy']));
$porMarcar = 0;
$instalados = 0;
foreach ($enCurso as $p) {
    foreach ($p['dispositivos'] as $d) {
        if ($d['instalado']) {
            $instalados++;
            $porMarcar += $d['marca'] === null ? 1 : 0;
        }
    }
}
$soloLectura = $mesCerrado;
$campos = fn() => Csrf::campoOculto() . '<input type="hidden" name="volver" value="' . e($volver) . '"><input type="hidden" name="fecha_ronda" value="' . e($fecha) . '">';
$ico = [
    'check' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg>',
    'alerta' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>',
    'candado' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>',
    'mas' => '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
];
?>
<div class="iaas-dia-barra">
  <nav class="ns-semana iaas-dia-nav" aria-label="Día de la ronda">
    <a class="ns-flecha" href="<?= e($urlRonda(['fecha' => $anterior])) ?>" aria-label="Día anterior">‹</a>
    <div class="ns-semana-lbl"><b><?= $esHoy ? 'Hoy' : e(ucfirst($diaCorto($fecha))) ?></b><small><?= e(IaasRondaController::nombreDia($fecha)) ?></small></div>
    <?php if ($esHoy): ?><span class="ns-flecha off" aria-hidden="true">›</span>
    <?php else: ?><a class="ns-flecha" href="<?= e($urlRonda(['fecha' => $siguiente !== $hoy ? $siguiente : null])) ?>" aria-label="Día siguiente">›</a><?php endif; ?>
  </nav>
  <form method="get" class="iaas-ir-fecha">
    <input type="hidden" name="sala" value="<?= (int) $sala['id'] ?>">
    <label class="sr-only" for="irFecha">Ir a una fecha</label>
    <input type="date" id="irFecha" name="fecha" value="<?= e($fecha) ?>" max="<?= e($hoy) ?>" onchange="this.form.submit()">
  </form>
  <?php if (!$esHoy): ?><a class="btn btn-ghost ns-btn-sm" href="<?= e($urlRonda()) ?>">Volver a hoy</a><?php endif; ?>
  <div class="spacer"></div>
  <?php if ($conDispositivos && $instalados): ?>
    <div class="iaas-progreso" role="status" aria-live="polite" data-progreso>
      <div class="iaas-progreso-txt"><b data-marcados><?= $instalados - $porMarcar ?></b> de <b><?= $instalados ?></b> dispositivos marcados</div>
      <div class="iaas-progreso-barra" aria-hidden="true"><i style="width:<?= $instalados ? round(($instalados - $porMarcar) / $instalados * 100) : 0 ?>%"></i></div>
    </div>
  <?php endif; ?>
</div>

<?php if ($soloLectura): ?>
  <div class="iaas-aviso info"><?= $ico['candado'] ?><div><b>Mes cerrado.</b> La ronda de <?= e(IaasNorma::nombreMes((int) substr($fecha, 5, 2), (int) substr($fecha, 0, 4), false)) ?> de esta sala está cerrada y se muestra solo para consulta. Un administrador puede reabrirla desde Indicadores.</div></div>
<?php elseif ($faltantes): $nDias = count($faltantes); $lista = array_map($diaCorto, array_slice(array_keys($faltantes), -6)); ?>
  <div class="iaas-aviso warn">
    <?= $ico['alerta'] ?>
    <div><b><?= $nDias === 1 ? 'Falta marcar 1 día' : "Faltan marcar $nDias días" ?></b> en esta sala: <?= e(implode(', ', $lista)) ?><?= $nDias > 6 ? ' y otros' : '' ?>. Sin esas marcas, el total de días con dispositivo queda corto.
      <a href="<?= e($urlRonda(['vista' => 'mes', 'mes' => substr(array_key_first($faltantes), 0, 7)])) ?>">Revisar en el mes</a></div>
    <form method="post" action="/iaas/ronda/completar" data-confirmar="Se marcarán como «sigue igual» los <?= $nDias ?> día(s) sin marca de esta sala, con el estado que corresponda a cada dispositivo. ¿Continuar?">
      <?= $campos() ?><input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>"><input type="hidden" name="fecha" value="<?= e($fecha) ?>">
      <button type="submit" class="btn btn-ghost ns-btn-sm">Completar como «sigue igual»</button>
    </form>
  </div>
<?php endif; ?>

<div class="iaas-ronda<?= $procedimientosSala ? ' con-lado' : '' ?><?= !$conDispositivos ? ' solo-proc' : '' ?>">
  <?php if ($conDispositivos): ?>
  <section class="iaas-pacientes" aria-label="Pacientes con dispositivo">
    <?php if (!$enCurso && !$egresadosHoy): ?>
      <div class="card iaas-vacio">
        <h2>No hay pacientes con dispositivo en esta sala</h2>
        <p>Agrega a un paciente cuando le instalen <?= e(implode(', ', array_map(fn($t) => IaasNorma::DISPOSITIVOS_SIGLA[$t], $dispositivosSala))) ?>. Desde ahí, la ronda de cada día es un clic.</p>
        <?php if (!$soloLectura): ?><button type="button" class="btn btn-primary" data-modal="modalPaciente"><?= $ico['mas'] ?>Agregar paciente</button><?php endif; ?>
      </div>
    <?php endif; ?>

    <?php foreach ($enCurso as $p):
      $edad = edadLegible($p['fecha_nac'], $fecha, true);
      $cat = $neonatal ? IaasNorma::categoriaPeso($p['peso_nacer_g'] !== null ? (int) $p['peso_nacer_g'] : null) : null;
      $meta = array_filter([
          $p['sexo'], $edad, $p['historia_clinica'] ? 'HC ' . $p['historia_clinica'] : null,
          $neonatal && $p['peso_nacer_g'] ? number_format((int) $p['peso_nacer_g'], 0, ',', ' ') . ' g al nacer' : null,
          'día ' . $p['dia'] . ' en la sala',
      ]);
      $datosEditar = json_encode([
          'internacion_id' => $p['id'], 'nombre' => Persona::nombreCompleto($p), 'n_cama' => $p['n_cama'], 'historia_clinica' => $p['historia_clinica'],
          'peso_nacer_g' => $p['peso_nacer_g'], 'diagnostico_ingreso' => $p['diagnostico_ingreso'], 'diagnostico_vigilado' => $p['diagnostico_vigilado'],
          'observaciones' => $p['observaciones'],
      ], JSON_UNESCAPED_UNICODE);
    ?>
      <article class="card iaas-pac" id="pac-<?= (int) $p['id'] ?>">
        <header class="iaas-pac-cab">
          <span class="iaas-cama mono" title="Cama"><?= $p['n_cama'] !== null && $p['n_cama'] !== '' ? e($p['n_cama']) : '—' ?></span>
          <div class="iaas-pac-id">
            <b><?= e(Persona::nombreCompleto($p)) ?></b>
            <small><?= e(implode(' · ', $meta)) ?></small>
            <?php if ($p['diagnostico_vigilado'] || $p['diagnostico_ingreso']): ?><small class="iaas-dx"><?= e($p['diagnostico_vigilado'] ?: $p['diagnostico_ingreso']) ?></small><?php endif; ?>
          </div>
          <?php if (!$soloLectura): ?>
          <div class="iaas-pac-acc">
            <button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalDispositivo" data-internacion="<?= (int) $p['id'] ?>" data-nombre="<?= e(Persona::nombreCompleto($p)) ?>" data-ingreso="<?= e($p['fecha_ingreso']) ?>"><?= $ico['mas'] ?>Dispositivo</button>
            <button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalEgreso" data-internacion="<?= (int) $p['id'] ?>" data-nombre="<?= e(Persona::nombreCompleto($p)) ?>" data-ingreso="<?= e($p['fecha_ingreso']) ?>">Egreso</button>
            <button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalEditar" data-datos="<?= e($datosEditar) ?>">Editar</button>
          </div>
          <?php endif; ?>
        </header>

        <?php if (!$p['dispositivos']): ?>
          <p class="iaas-sin-disp"><span class="iaas-sim H" aria-hidden="true">→</span> Sin dispositivo instalado; sigue hospitalizado. Registra el egreso cuando salga de la sala.</p>
        <?php endif; ?>

        <ul class="iaas-disps">
          <?php foreach ($p['dispositivos'] as $d):
            $marca = $d['marca'];
            $caso = $d['caso'];
            $estadoClase = $d['retirado_hoy'] ? 'retirado' : ($marca === 'E' ? 'on' : ($marca === 'T' ? 'tr' : 'pend'));
          ?>
            <li class="iaas-disp <?= $estadoClase ?>" data-disp="<?= (int) $d['id'] ?>">
              <span class="iaas-sigla" title="<?= e(IaasNorma::DISPOSITIVOS[$d['tipo']]) ?>"><?= e(IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']]) ?></span>
              <span class="iaas-disp-txt">
                <b><?= e(IaasNorma::DISPOSITIVOS[$d['tipo']]) ?></b>
                <small>
                  <?php if ($d['retirado_hoy']): ?>Retirado este día · se instaló el <?= e(fechaIsoADmy($d['fecha_instalacion'])) ?>
                  <?php else: ?>Día <?= (int) $d['dia'] ?> · instalado el <?= e(fechaIsoADmy($d['fecha_instalacion'])) ?><?php endif; ?>
                </small>
                <?php if ($caso): ?>
                  <a class="iaas-caso-chip" href="/iaas/casos/<?= (int) $caso['id'] ?>" title="Los días con la IAAS en tratamiento no suman al total">× IAAS en tratamiento · <?= e(IaasCaso::codigo($caso)) ?><?= $caso['estado'] === 'POR_COMPLETAR' ? ' · por completar' : '' ?></a>
                <?php endif; ?>
              </span>

              <?php if ($d['retirado_hoy']): ?>
                <?php if (!$soloLectura): ?>
                <form method="post" action="/iaas/ronda/retirar" class="iaas-disp-acc">
                  <?= $campos() ?><input type="hidden" name="dispositivo_id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="deshacer" value="1">
                  <button type="submit" class="btn btn-quiet ns-btn-sm">Deshacer retiro</button>
                </form>
                <?php endif; ?>
              <?php else: ?>
                <form method="post" action="/iaas/ronda/marcar" class="iaas-marca-form" data-marca>
                  <?= $campos() ?><input type="hidden" name="dispositivo_id" value="<?= (int) $d['id'] ?>"><input type="hidden" name="fecha" value="<?= e($fecha) ?>">
                  <input type="hidden" name="marcado" value="<?= $marca === null ? '1' : '0' ?>">
                  <button type="submit" class="iaas-marca" aria-pressed="<?= $marca === null ? 'false' : 'true' ?>"<?= $soloLectura ? ' disabled' : '' ?>
                          title="<?= $marca === null ? 'Marcar: el dispositivo sigue instalado este día' : 'Quitar la marca de este día' ?>">
                    <span class="iaas-marca-ico" aria-hidden="true"><?= $marca === 'T' ? '×' : '✓' ?></span>
                    <span data-etq><?= $marca === 'E' ? 'Marcado' : ($marca === 'T' ? 'En tratamiento' : 'Sigue') ?></span>
                  </button>
                </form>
                <?php if (!$soloLectura): ?>
                <div class="iaas-disp-acc">
                  <button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalRetiro" data-dispositivo="<?= (int) $d['id'] ?>" data-nombre="<?= e(IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']] . ' · ' . Persona::nombreCompleto($p)) ?>" data-instalacion="<?= e($d['fecha_instalacion']) ?>">Retiro</button>
                  <?php if ($caso): ?>
                    <button type="button" class="btn btn-quiet ns-btn-sm" data-modal="modalFin" data-caso="<?= (int) $caso['id'] ?>" data-nombre="<?= e(IaasCaso::codigo($caso) . ' · ' . Persona::nombreCompleto($p)) ?>" data-desde="<?= e($caso['fecha_iaas']) ?>">Fin del tratamiento</button>
                  <?php else: ?>
                    <button type="button" class="btn btn-quiet ns-btn-sm iaas-btn-iaas" data-modal="modalIaas" data-dispositivo="<?= (int) $d['id'] ?>" data-nombre="<?= e(IaasNorma::DISPOSITIVOS_SIGLA[$d['tipo']] . ' · ' . Persona::nombreCompleto($p)) ?>" data-tipo="<?= e(IaasNorma::TIPOS_IAAS[IaasNorma::tipoPorDispositivo($d['tipo'])]) ?>" data-instalacion="<?= e($d['fecha_instalacion']) ?>">Inicia IAAS</button>
                  <?php endif; ?>
                </div>
                <?php endif; ?>
              <?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>
      </article>
    <?php endforeach; ?>

    <?php foreach ($egresadosHoy as $p): ?>
      <article class="card iaas-pac egresado">
        <header class="iaas-pac-cab">
          <span class="iaas-cama mono"><?= $p['n_cama'] !== null && $p['n_cama'] !== '' ? e($p['n_cama']) : '—' ?></span>
          <div class="iaas-pac-id"><b><?= e(Persona::nombreCompleto($p)) ?></b><small>Egresó este día · <?= e(mb_strtolower(IaasNorma::CONDICION_EGRESO[$p['condicion_egreso']] ?? '')) ?></small></div>
          <?php if (!$soloLectura): ?>
          <form method="post" action="/iaas/ronda/egresar" class="iaas-pac-acc">
            <?= $campos() ?><input type="hidden" name="internacion_id" value="<?= (int) $p['id'] ?>"><input type="hidden" name="deshacer" value="1">
            <button type="submit" class="btn btn-quiet ns-btn-sm">Deshacer egreso</button>
          </form>
          <?php endif; ?>
        </header>
      </article>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <?php if ($procedimientosSala): $registrado = (bool) $procedimientos; ?>
  <aside class="iaas-lado">
    <form method="post" action="/iaas/ronda/procedimientos" class="card tarjeta iaas-proc">
      <?= $campos() ?><input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>"><input type="hidden" name="fecha" value="<?= e($fecha) ?>">
      <div class="tarjeta-cab">
        <div><h3><?= $sala['upss'] === 'GINECO_OBSTETRICIA' ? 'Partos del día' : 'Cirugías del día' ?></h3>
          <div class="sub"><?= $sala['upss'] === 'GINECO_OBSTETRICIA' ? 'Anexo 6' : 'Anexo 7' ?> · denominador de las tasas</div></div>
        <div class="spacer"></div>
        <span class="ns-chip <?= $registrado ? 'con' : 'sin' ?>"><i></i><?= $registrado ? 'Registrado' : 'Sin registrar' ?></span>
      </div>
      <?php foreach ($procedimientosSala as $tipo): $n = $procedimientos[$tipo] ?? 0; ?>
        <div class="iaas-paso">
          <label for="proc-<?= $tipo ?>"><?= e(IaasNorma::PROCEDIMIENTOS[$tipo]) ?></label>
          <div class="iaas-stepper" data-stepper>
            <button type="button" data-paso="-1" aria-label="Uno menos"<?= $soloLectura ? ' disabled' : '' ?>>−</button>
            <input type="number" id="proc-<?= $tipo ?>" name="cantidad[<?= $tipo ?>]" value="<?= $n ?>" min="0" max="500" inputmode="numeric"<?= $soloLectura ? ' readonly' : '' ?>>
            <button type="button" data-paso="1" aria-label="Uno más"<?= $soloLectura ? ' disabled' : '' ?>>+</button>
          </div>
        </div>
      <?php endforeach; ?>
      <p class="hint">Si este día no hubo, guarda en 0: así queda registrado que no hubo. Las <?= $sala['upss'] === 'GINECO_OBSTETRICIA' ? 'endometritis e ISQ' : 'ISQ' ?> no se escriben aquí: se cuentan desde las fichas IAAS, en la fecha del procedimiento.</p>
      <?php if (!$soloLectura): ?><button type="submit" class="btn btn-primary iaas-btn-bloque"><?= $registrado ? 'Actualizar' : 'Guardar' ?> registro del día</button><?php endif; ?>
    </form>
  </aside>
  <?php endif; ?>
</div>

<?php if ($conDispositivos): ?>
<details class="iaas-leyenda-plegable">
  <summary>¿Qué significa cada marca?</summary>
  <?php require __DIR__ . '/_leyenda.php'; ?>
</details>
<?php endif; ?>

<?php if ($conDispositivos && !$soloLectura): ?>
<?php if ($enCurso || $egresadosHoy): ?>
<div class="iaas-barra-pie" role="region" aria-label="Acciones de la sala">
  <div class="iaas-barra-txt" data-pendientes-txt>
    <?php if ($porMarcar): ?><b data-pendientes><?= $porMarcar ?></b> por marcar en <?= e(IaasNorma::nombreSala($sala)) ?>
    <?php elseif ($instalados): ?><?= $ico['check'] ?> Sala al día<?php else: ?>Sin dispositivos instalados<?php endif; ?>
  </div>
  <button type="button" class="btn btn-ghost" data-modal="modalPaciente"><?= $ico['mas'] ?>Agregar paciente</button>
  <form method="post" action="/iaas/ronda/confirmar" data-confirmar-sala>
    <?= $campos() ?><input type="hidden" name="sala_id" value="<?= (int) $sala['id'] ?>"><input type="hidden" name="fecha" value="<?= e($fecha) ?>">
    <button type="submit" class="btn btn-primary" data-btn-confirmar<?= $porMarcar ? '' : ' disabled' ?>><?= $ico['check'] ?>Confirmar sala: todo sigue igual</button>
  </form>
</div>
<?php endif; ?>

<?php require __DIR__ . '/_modales-ronda.php'; ?>
<?php endif; ?>
