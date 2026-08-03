<?php
/**
 * "Clasificación del caso" de P35.0 (campo_def único, p35_0_clasificacion_del_caso),
 * reposicionada fuera del loop genérico de secciones-clinicas.php para que
 * aparezca DESPUÉS de "Laboratorio" y ANTES de "Seguimiento de excreción
 * viral" (ítem 43) -- orden pedido por el usuario (PENDIENTES.md ítem Z.8):
 * muestra -> resultado -> clasificación -> seguimiento si corresponde.
 *
 * Requiere en scope: $campo (resolvedor de campos-por-clave.php),
 * $numeroSeccion (se incrementa acá, no en el llamador, para no repetirlo
 * en nueva/index.php y fichas/editar.php).
 *
 * Envuelto en una función para no pisar la variable $campo del llamador con
 * la fila de campo_def -- mismo motivo que $renderizarCampos en
 * secciones-clinicas.php (ver el comentario ahí sobre por qué $campoFechaUltSeg
 * se resuelve ANTES de entrar a esa closure).
 */
(function (callable $campo) use (&$numeroSeccion): void {
    $resuelto = $campo('p35_0_clasificacion_del_caso');
    $campo = $resuelto['campo'];
    $campo['obligatorio'] = (int) $campo['obligatorio'];
    $valor = $resuelto['val'];
    $error = $resuelto['err'];
    $opciones = $resuelto['opciones'];
    ?>
    <div class="card section">
      <div class="section-head"><span class="section-num"><?= $numeroSeccion ?></span><h3>Clasificación del caso</h3></div>
      <div class="section-body">
        <div class="fields" style="margin-bottom:0">
          <?php require __DIR__ . '/campo-dinamico.php'; ?>
        </div>
      </div>
    </div>
    <?php
})($campo);
$numeroSeccion++;
