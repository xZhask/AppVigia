<?php
/**
 * Mapa anatómico para la SECUENCIA DE APARICIÓN del exantema/lesión de B04X
 * (Viruela del mono / Mpox), ítem 50 del PDF: "Coloque en los espacios la
 * numeración según la secuencia de la aparición del exantema o lesión".
 *
 * Reemplaza el render genérico de campos/matriz.php SOLO para la clave
 * b04x_secuencia_de_aparicion_por_zona (ver el despacho por clave en
 * secciones-clinicas.php, mismo mecanismo que b55_lesiones). El FORMATO DE
 * DATOS NO CAMBIA: los inputs conservan el name="campo_<id>[<fila>][0]" que
 * ya emitía matriz.php en modo "libre", así que el servidor
 * (CasosController, rama MATRIZ genérica) y lo ya guardado en caso_valor
 * siguen igual -- esto es solo una capa visual encima de los mismos campos.
 *
 * Por qué un partial propio y no el de B05 (exantema-evolucion-body-map.php):
 * ese captura otra cosa -- sombrea zonas por día (Día 1/3/5/7) y guarda
 * arrays de regiones bajo su propio name "exantema_zonas[...]". Acá el dato
 * es UN número de orden por zona. Se reutiliza su arte SVG y sus clases
 * (.body-part), no su modelo de datos.
 *
 * Variables esperadas (las mismas que matriz.php): $campo, $valor, $error.
 */
$nombreCampo = 'campo_' . $campo['id'];
$configSec   = json_decode($campo['config'] ?? '{}', true) ?: [];
$filasSec    = $configSec['filas'] ?? [];
$valoresSec  = is_array($valor) ? $valor : [];

// Índice de fila (el del manifiesto, que es el que viaja en el name) ->
// región del dibujo. Si alguien reordena las filas en el manifiesto, esto
// se desalinea: por eso se mapea por ETIQUETA de fila, no por posición.
$regionPorEtiqueta = [
    'Genital/perianal'        => 'genital',
    'Oral (boca, labios)'     => 'oral',
    'Cara'                    => 'cara',
    'Tórax, espalda'          => 'torax',
    'Abdomen'                 => 'abdomen',
    'Extremidades superiores' => 'brazos',
    'Extremidades inferiores' => 'piernas',
    'Palma de mano'           => 'palmas',
];

$valorFila = function (int $fIdx) use ($valoresSec): string {
    $fila = $valoresSec[$fIdx] ?? ($valoresSec[(string) $fIdx] ?? []);
    if (is_array($fila)) {
        return (string) ($fila[0] ?? ($fila['0'] ?? ''));
    }
    return (string) $fila;
};

// Silueta reutilizada de exantema-evolucion-body-map.php (mismo viewBox
// 0 0 120 220). Cambios: se recortaron las piernas para dejar sitio a la
// zona genital/perianal (que ese mapa no tiene) y se agregó la boca.
$silueta = function (string $vista) use ($regionPorEtiqueta, $filasSec, $valorFila): void {
    $esFrente = ($vista === 'frente');
    // Badges: centro aproximado de cada región, para el número de orden.
    $badges = [
        'cara'     => [60, 28],
        'oral'     => [60, 44],
        'torax'    => [60, 82],
        'abdomen'  => [60, 112],
        'genital'  => [60, 135],
        'brazos'   => [34, 95],
        'palmas'   => [33, 132],
        'piernas'  => [50, 175],
    ];
    // La cara y la boca solo existen en la vista frontal.
    $regionesVista = $esFrente
        ? ['cara', 'oral', 'torax', 'abdomen', 'genital', 'brazos', 'palmas', 'piernas']
        : ['torax', 'abdomen', 'genital', 'brazos', 'palmas', 'piernas'];
    ?>
    <div class="secuencia-b04x-vista">
      <div class="secuencia-b04x-vista-titulo"><?= $esFrente ? 'Frente' : 'Espalda' ?></div>
      <svg viewBox="0 0 120 220" class="secuencia-b04x-svg" role="img"
           aria-label="Silueta corporal, vista <?= $esFrente ? 'frontal' : 'posterior' ?>">
        <!-- Cabeza: clicable solo de frente (zona "Cara"); de espaldas es decorativa -->
        <path <?= $esFrente ? 'data-region="cara"' : '' ?>
              d="M 60 10 C 48 10, 44 22, 44 32 C 44 44, 50 52, 60 52 C 70 52, 76 44, 76 32 C 76 22, 72 10, 60 10 Z"
              class="body-part <?= $esFrente ? '' : 'body-part-inerte' ?>" />
        <?php if ($esFrente): ?>
          <!-- Boca: zona "Oral (boca, labios)", encima de la cara para captar el clic -->
          <ellipse data-region="oral" cx="60" cy="42" rx="8" ry="4" class="body-part" />
        <?php endif; ?>
        <!-- Cuello (decorativo, no es una zona del PDF) -->
        <path d="M 52 52 L 68 52 L 72 62 L 48 62 Z" class="body-part body-part-inerte" />
        <!-- Tórax / espalda: una sola zona en el PDF, presente en ambas vistas -->
        <path data-region="torax" d="M 40 64 L 80 64 L 76 96 L 44 96 Z" class="body-part" />
        <path data-region="abdomen" d="M 44 98 L 76 98 L 74 124 L 46 124 Z" class="body-part" />
        <!-- Genital / perianal -->
        <path data-region="genital" d="M 50 126 L 70 126 L 68 140 L 52 140 Z" class="body-part" />
        <!-- Extremidades superiores -->
        <path data-region="brazos" d="M 38 64 L 46 64 L 42 120 L 30 120 Z M 82 64 L 74 64 L 78 120 L 90 120 Z" class="body-part" />
        <!-- Palma de mano -->
        <path data-region="palmas" d="M 28 122 L 42 122 L 38 138 L 24 138 Z M 92 122 L 78 122 L 82 138 L 96 138 Z" class="body-part" />
        <!-- Extremidades inferiores -->
        <path data-region="piernas" d="M 46 142 L 58 142 L 56 205 L 42 205 Z M 74 142 L 62 142 L 64 205 L 78 205 Z" class="body-part" />
        <!-- Pies (decorativos) -->
        <path d="M 40 207 L 56 207 L 54 214 L 34 214 Z M 80 207 L 64 207 L 66 214 L 86 214 Z" class="body-part body-part-inerte" />

        <?php foreach ($regionesVista as $reg):
            [$bx, $by] = $badges[$reg]; ?>
          <g class="secuencia-b04x-badge" data-badge-region="<?= e($reg) ?>" hidden>
            <circle cx="<?= $bx ?>" cy="<?= $by ?>" r="8" />
            <text x="<?= $bx ?>" y="<?= $by + 3 ?>" text-anchor="middle"></text>
          </g>
        <?php endforeach; ?>
      </svg>
    </div>
    <?php
};
?>
<div class="field wide secuencia-exantema-b04x" data-nombre-campo="<?= e($nombreCampo) ?>">
  <label class="fl"><?= e($campo['etiqueta']) ?><?= $campo['obligatorio'] ? ' <span class="req">*</span>' : '' ?></label>
  <div class="secuencia-b04x-ayuda">
    Haga clic sobre una zona del dibujo para asignarle el siguiente número de orden,
    o escriba el número directamente en la tabla. Volver a hacer clic en una zona
    numerada la borra.
  </div>

  <div class="secuencia-b04x-layout">
    <div class="secuencia-b04x-tabla">
      <?php foreach ($filasSec as $fIdx => $etiquetaFila):
        $region = $regionPorEtiqueta[$etiquetaFila] ?? null;
        $valFila = $valorFila((int) $fIdx);
      ?>
        <div class="secuencia-b04x-fila<?= $valFila !== '' ? ' tiene-valor' : '' ?>"
             <?= $region ? 'data-fila-region="' . e($region) . '"' : '' ?>>
          <span class="secuencia-b04x-etiqueta"><?= e($etiquetaFila) ?></span>
          <input type="number" inputmode="numeric" min="1" max="<?= count($filasSec) ?>" step="1"
                 name="<?= e($nombreCampo) ?>[<?= (int) $fIdx ?>][0]"
                 value="<?= e($valFila) ?>"
                 class="secuencia-b04x-input"
                 <?= $region ? 'data-region="' . e($region) . '"' : '' ?>
                 aria-label="N.° de orden de aparición: <?= e($etiquetaFila) ?>">
        </div>
      <?php endforeach; ?>
    </div>

    <div class="secuencia-b04x-figuras">
      <?php $silueta('frente'); ?>
      <?php $silueta('espalda'); ?>
    </div>
  </div>

  <?php if ($error): ?><span class="hint err"><?= e($error) ?></span><?php endif; ?>
</div>
<?php // Estilos: public/css/campos-dinamicos.css (.secuencia-exantema-b04x), siempre cargado desde shell.php ?>
