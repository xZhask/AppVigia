<?php
/**
 * "Clasificación final" de A37.0 (campo_def único, a37_0_clasificacion_final),
 * reposicionada fuera del loop genérico de secciones-clinicas.php para que
 * aparezca DESPUÉS de "Laboratorio" -- orden pedido por el usuario
 * (PENDIENTES.md ítem 13): en el manifiesto es la última sección (orden: 8,
 * antes que Laboratorio, que es una tarjeta fija), pero el PDF trae VIII.
 * Laboratorio antes que IX. Clasificación final del caso. Mismo patrón que
 * clasificacion-caso-p350.php (ítem Z.8), calcado.
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
    $resuelto = $campo('a37_0_clasificacion_final');
    $campo = $resuelto['campo'];
    $campo['obligatorio'] = (int) $campo['obligatorio'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    $opciones = $resuelto['opciones'];
    ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Clasificación final</h3></div>
      <div class="section-body">
        <div class="fields" style="margin-bottom:0">
          <?php require __DIR__ . '/campo-dinamico.php'; ?>
        </div>
      </div>
    </div>
    <?php
})($campo);
$numeroSeccion++;
