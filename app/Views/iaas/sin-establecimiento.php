<?php require __DIR__ . '/_cabecera.php'; ?>
<div class="card iaas-vacio">
  <h2>Elige un establecimiento</h2>
  <p><?= $esAdmin
      ? 'La vigilancia de IAAS se lleva por establecimiento. Elige uno en el selector de arriba para ver su ronda, sus casos y sus indicadores.'
      : 'Tu usuario no tiene un establecimiento asignado. Pide a un administrador que lo asigne en Usuarios.' ?></p>
</div>
