<?php
/**
 * "VII. Observaciones" de B01 (campo_def único, b01_observaciones),
 * reposicionada fuera del loop genérico de secciones-clinicas.php para que
 * aparezca DESPUÉS de "Laboratorio" -- el PDF (pág. 3) trae VI. Laboratorio
 * antes que VII. Observaciones, pero en el manifiesto es una sección propia
 * (orden 5) que caería antes de la tarjeta fija de Laboratorio. Mismo
 * patrón que clasificacion-caso-a370.php/p350.php, calcado.
 *
 * Requiere en scope: $campo (resolvedor de campos-por-clave.php),
 * $numeroSeccion (se incrementa acá, no en el llamador, para no repetirlo
 * en nueva/index.php y fichas/editar.php).
 *
 * Envuelto en una función para no pisar la variable $campo del llamador con
 * la fila de campo_def -- mismo motivo que $renderizarCampos en
 * secciones-clinicas.php.
 */
(function (callable $campo) use (&$numeroSeccion): void {
    $resuelto = $campo('b01_observaciones');
    $campo = $resuelto['campo'];
    $campo['obligatorio'] = (int) $campo['obligatorio'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    $opciones = $resuelto['opciones'];
    ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Observaciones</h3></div>
      <div class="section-body">
        <div class="fields" style="margin-bottom:0">
          <?php require __DIR__ . '/campo-dinamico.php'; ?>
        </div>
      </div>
    </div>
    <?php
})($campo);
$numeroSeccion++;
