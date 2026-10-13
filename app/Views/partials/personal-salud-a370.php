<?php
/**
 * "X. PERSONAL DE SALUD QUE LLENA LA FICHA (preferentemente personal que
 * atiende el paciente)" de A37.0 -- ítems 66-67 del PDF (Nombre, Cargo;
 * "Firma y sello" es del papel). Es otra persona que el investigador de la
 * sección XI, que cubre la tarjeta núcleo "Investigador".
 *
 * Reposicionada fuera del loop genérico de secciones-clinicas.php para que
 * aparezca DESPUÉS de "Clasificación final" (clasificacion-caso-a370.php) y
 * ANTES de la tarjeta "Investigador": las secciones campo_def se pintan
 * todas antes de Laboratorio, así que sin este partial la X saldría antes
 * que la VIII. Mismo patrón que personal-epidemiologia-b04x.php, calcado.
 *
 * Requiere en scope: $campo (resolvedor de campos-por-clave.php),
 * $numeroSeccion (se incrementa acá, no en el llamador).
 *
 * El render va dentro de una función y el resolvedor entra con otro nombre
 * ($resolver), para no pisar la variable $campo del llamador
 * (ver [[partial_a_medida_no_debe_pisar_campo_ambiente]]).
 */
(function (callable $resolver) use (&$numeroSeccion): void {
    $resueltos = [];
    foreach (['a37_0_personal_salud_nombre', 'a37_0_personal_salud_cargo'] as $clave) {
        $r = $resolver($clave);
        if (!empty($r['campo'])) {
            $resueltos[] = $r;
        }
    }
    if (!$resueltos) {
        return; // la ficha no declara estos campos: no imprimir una tarjeta vacía
    }
    ?>
    <div class="card section">
      <div class="section-head">
        <span class="section-num"><?= $numeroSeccion ?></span>
        <h3>Personal de salud que llena la ficha</h3>
      </div>
      <div class="section-body">
        <p class="hint" style="margin-top:0">
          Preferentemente el personal que atiende al paciente.
        </p>
        <div class="fields">
          <?php foreach ($resueltos as $r):
              $campo = $r['campo'];
              $campo['obligatorio'] = (int) $campo['obligatorio'];
              $valor = $r['val'];
              $error = $r['err'];
              $opciones = $r['opciones'];
              require __DIR__ . '/campo-dinamico.php';
          endforeach; ?>
        </div>
      </div>
    </div>
    <?php
})($campo);
$numeroSeccion++;
