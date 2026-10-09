<?php
/**
 * Variables: $campo, $valor (string), $error (?string)
 * $campo['config'] (opcional, JSON, P96 2026-09-13): {"formato": "hora"} se
 * pinta como <input type="time"> y se guarda HH:MM; {"formato": "cie10"}
 * pide un código CIE-10 (P21.9). CasosController::validarCamposDinamicos()
 * valida y normaliza los dos. Sin config, texto libre como siempre (mismo
 * HTML de antes, por eso los atributos van en una sola expresión).
 */
$nombreCampo = 'campo_' . $campo['id'];
$formatoTexto = (json_decode((string) ($campo['config'] ?? ''), true) ?: [])['formato'] ?? null;
// {"calculado": "iniciales_fecha_nac"} (B24, 2026-09-14): nadie lo escribe. Se
// pinta de solo lectura y ficha.js lo adelanta con los apellidos, los nombres y
// la fecha de nacimiento; el servidor lo vuelve a calcular al guardar.
$calculoTexto = (json_decode((string) ($campo['config'] ?? ''), true) ?: [])['calculado'] ?? null;
if ($calculoTexto !== null): ?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?></label>
  <div class="control mono" style="background:var(--paper)">
    <input type="text" name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>" readonly tabindex="-1" data-calculado="<?= e($calculoTexto) ?>">
  </div>
  <span class="hint">Iniciales + fecha de nacimiento</span>
</div>
<?php return; endif;
// {"formato": "distrito"} (V99, 2026-10-08): el campo es un lugar del PDF
// (Departamento/Provincia/Distrito) y se pinta con el selector encadenado de
// siempre. Solo el <select> de Distrito lleva name="campo_<id>" y se guarda su
// id (ubigeo); Departamento y Provincia solo acotan la lista. Va en una
// closure para que las variables de selector-ubigeo.php ($departamentos,
// $prefijo...) no pisen las de la vista que incluye este partial.
if ($formatoTexto === 'distrito'):
    (function (array $campo, string $nombreCampo, string $valor, ?string $error): void { ?>
<div class="field wide">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <?php
  extract(contextoUbigeo($valor !== '' ? $valor : null));
  $prefijo = 'campo' . $campo['id'] . '-ubigeo';
  $nombreCampoDepartamento = $nombreCampo . '_departamento';
  $nombreCampoProvincia = $nombreCampo . '_provincia';
  $nombreCampoDistrito = $nombreCampo;
  $distritoRequerido = (bool) $campo['obligatorio'];
  $errorDistrito = $error;
  require __DIR__ . '/../selector-ubigeo.php';
  ?>
</div>
<?php })($campo, $nombreCampo, (string) $valor, $error);
    return;
endif;
$atributosTexto = match ($formatoTexto) {
    'hora'  => 'type="time"',
    'cie10' => 'type="text" maxlength="8" placeholder="P21.9" autocomplete="off" pattern="[A-Za-z][0-9]{2}(\.?[0-9A-Za-z]{1,2})?" title="Código CIE-10: una letra, dos dígitos y, si corresponde, el subcódigo (P21.9)" style="text-transform:uppercase"',
    default => 'type="text"',
};
?>
<div class="field">
  <label class="fl"><?= e($campo['etiqueta']) ?> <?= $campo['obligatorio'] ? '<span class="req">*</span>' : '' ?></label>
  <div class="control <?= $formatoTexto !== null ? 'mono ' : '' ?><?= $error ? 'err' : '' ?>">
    <input <?= $atributosTexto ?> name="<?= e($nombreCampo) ?>" value="<?= e($valor) ?>">
  </div>
  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
