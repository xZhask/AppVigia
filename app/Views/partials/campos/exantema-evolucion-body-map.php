<?php
/**
 * Mapa anatómico interactivo para la evolución del exantema por días (Día 1, 3, 5, 7).
 * Utilizado primordialmente en la ficha B05 (Sarampión / Rubéola).
 */
$valores = is_array($valor ?? null) ? $valor : [];
$zonasEvolucion = $valores['exantema_zonas'] ?? [];

$dias = [
    'dia1' => 'DÍA 1',
    'dia3' => 'DÍA 3',
    'dia5' => 'DÍA 5',
    'dia7' => 'DÍA 7',
];

$regiones = [
    'cara'     => 'Cara',
    'cuello'   => 'Cuello',
    'torax'    => 'Tórax',
    'abdomen'  => 'Abdomen',
    'espalda'  => 'Espalda',
    'brazos'   => 'Brazos',
    'palmas'   => 'Palmas',
    'piernas'  => 'Piernas',
    'plantas'  => 'Plantas',
];
?>

<div class="field wide exantema-body-map-section" style="margin-top: 20px; border-top: 1px solid var(--line-2); padding-top: 16px;">
  <div style="margin-bottom: 12px;">
    <label class="fl" style="font-size: 13.5px; font-weight: 600; color: var(--ink);">
      Sombrear las zonas del cuerpo de acuerdo a la cronología de la presentación del exantema:
    </label>
    <div style="font-size: 12px; color: var(--muted); margin-top: 2px;">
      Haga clic en las partes del cuerpo o marque las casillas para colorear las zonas afectadas por día.
    </div>
  </div>

  <div class="exantema-grid-dias" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 14px;">
    <?php foreach ($dias as $keyDia => $labelDia): 
      $zonasSeleccionadas = $zonasEvolucion[$keyDia] ?? [];
    ?>
      <div class="card-dia-exantema" data-dia="<?= $keyDia ?>" style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 10px; padding: 14px; display: flex; flex-direction: column; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
        <div style="font-size: 13px; font-weight: 700; color: var(--ink); margin-bottom: 10px; text-transform: uppercase; letter-spacing: 0.5px;">
          <?= $labelDia ?>
        </div>

        <!-- SVG Silhouette -->
        <div class="svg-container" style="position: relative; margin-bottom: 14px; background: rgba(0,0,0,0.02); border-radius: 8px; padding: 8px;">
          <svg viewBox="0 0 120 220" class="svg-body-map" style="width: 120px; height: 210px; cursor: pointer; overflow: visible;">
            <!-- CARA (Head/Face) -->
            <path data-region="cara" d="M 60 10 C 48 10, 44 22, 44 32 C 44 44, 50 52, 60 52 C 70 52, 76 44, 76 32 C 76 22, 72 10, 60 10 Z" class="body-part <?= in_array('cara', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- CUELLO (Neck) -->
            <path data-region="cuello" d="M 52 52 L 68 52 L 72 62 L 48 62 Z" class="body-part <?= in_array('cuello', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- TORAX (Chest) -->
            <path data-region="torax" d="M 40 64 L 80 64 L 76 96 L 44 96 Z" class="body-part <?= in_array('torax', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- ABDOMEN (Abdomen) -->
            <path data-region="abdomen" d="M 44 98 L 76 98 L 74 126 L 46 126 Z" class="body-part <?= in_array('abdomen', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- ESPALDA (Back Indicator circle in center) -->
            <circle data-region="espalda" cx="60" cy="80" r="7" class="body-part body-part-espalda <?= in_array('espalda', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            <text x="60" y="83" text-anchor="middle" font-size="8" font-weight="bold" fill="var(--ink-2)" pointer-events="none">E</text>

            <!-- BRAZOS (Arms: Left & Right) -->
            <path data-region="brazos" d="M 38 64 L 46 64 L 42 120 L 30 120 Z M 82 64 L 74 64 L 78 120 L 90 120 Z" class="body-part <?= in_array('brazos', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- PALMAS (Palms: Left & Right Hands) -->
            <path data-region="palmas" d="M 28 122 L 42 122 L 38 138 L 24 138 Z M 92 122 L 78 122 L 82 138 L 96 138 Z" class="body-part <?= in_array('palmas', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- PIERNAS (Legs: Left & Right) -->
            <path data-region="piernas" d="M 46 128 L 58 128 L 56 195 L 42 195 Z M 74 128 L 62 128 L 64 195 L 78 195 Z" class="body-part <?= in_array('piernas', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
            
            <!-- PLANTAS (Feet / Soles: Left & Right) -->
            <path data-region="plantas" d="M 40 197 L 56 197 L 54 208 L 34 208 Z M 80 197 L 64 197 L 66 208 L 86 208 Z" class="body-part <?= in_array('plantas', $zonasSeleccionadas, true) ? 'sombreado' : '' ?>" />
          </svg>
        </div>

        <!-- Checkboxes Grid below Silhouette -->
        <div class="checkboxes-zonas-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 6px; width: 100%; font-size: 12px;">
          <?php foreach ($regiones as $keyReg => $labelReg): 
            $isChecked = in_array($keyReg, $zonasSeleccionadas, true);
          ?>
            <label class="chk-zona-item <?= $isChecked ? 'active' : '' ?>" style="display: flex; align-items: center; gap: 6px; padding: 4px 6px; border-radius: 4px; cursor: pointer; user-select: none; transition: background 0.15s, color 0.15s;">
              <input type="checkbox"
                     name="exantema_zonas[<?= $keyDia ?>][]"
                     value="<?= $keyReg ?>"
                     class="chk-zona-input"
                     data-dia="<?= $keyDia ?>"
                     data-region="<?= $keyReg ?>"
                     <?= $isChecked ? 'checked' : '' ?>
                     style="cursor: pointer; accent-color: var(--accent);">
              <span><?= $labelReg ?></span>
            </label>
          <?php endforeach; ?>
        </div>

      </div>
    <?php endforeach; ?>
  </div>
</div>

<style>
.exantema-body-map-section .body-part {
  fill: var(--paper-2, #e5e7eb);
  stroke: var(--line-2, #9ca3af);
  stroke-width: 1.5;
  transition: fill 0.2s ease, stroke 0.2s ease, filter 0.2s ease;
}
.exantema-body-map-section .body-part:hover {
  fill: #fca5a5;
  stroke: #ef4444;
}
.exantema-body-map-section .body-part.sombreado {
  fill: #ef4444 !important;
  stroke: #b91c1c !important;
  stroke-width: 2;
  filter: drop-shadow(0 0 4px rgba(239, 68, 68, 0.6));
}
.exantema-body-map-section .chk-zona-item.active {
  background: var(--accent-subtle, rgba(239, 68, 68, 0.12));
  color: var(--accent, #dc2626);
  font-weight: 600;
}
</style>
