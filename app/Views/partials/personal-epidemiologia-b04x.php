<?php
/**
 * "XI. PERSONAL DE EPIDEMIOLOGÍA QUIEN REALIZA EL CONTROL DE CALIDAD
 * (GERESA/DIRESA/DIRIS/RED DE SALUD)" de B04X -- ítems 61-62 del PDF
 * (Apellidos y nombres, Teléfono; "Firma y sello" es del papel).
 *
 * Reposicionada fuera del loop genérico de secciones-clinicas.php para que
 * aparezca DESPUÉS de la tarjeta núcleo "Investigador", que es la sección X
 * del PDF ("Personal de salud quien llena la ficha", ítems 58-60). Las
 * secciones campo_def se pintan todas antes de esa tarjeta, así que sin este
 * partial la XI saldría antes que la X. Mismo patrón que
 * observaciones-b01.php y clasificacion-caso-a370.php/p350.php, calcado.
 *
 * Requiere en scope: $campo (resolvedor de campos-por-clave.php),
 * $numeroSeccion (se incrementa acá, no en el llamador, para no repetirlo
 * en nueva/index.php y fichas/editar.php).
 *
 * Todo el render va dentro de una función y el resolvedor entra como
 * parámetro con OTRO nombre ($resolver), para no pisar la variable $campo
 * del llamador con una fila de campo_def -- campo-dinamico.php lee $campo,
 * así que acá adentro esa asignación es local y no se escapa
 * (ver [[partial_a_medida_no_debe_pisar_campo_ambiente]]).
 */
(function (callable $resolver) use (&$numeroSeccion): void {
    $resueltos = [];
    foreach (['b04x_control_calidad_nombres', 'b04x_control_calidad_telefono'] as $clave) {
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
        <h3>Personal de epidemiología (control de calidad)</h3>
      </div>
      <div class="section-body">
        <p class="hint" style="margin-top:0">
          Personal de epidemiología de la GERESA/DIRESA/DIRIS o Red de Salud que realiza el control de calidad de la ficha.
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
