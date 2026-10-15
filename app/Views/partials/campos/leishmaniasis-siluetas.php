<?php
/**
 * Dibujos del mapa de lesiones de B55 (Leishmaniasis, PDF pág. 46): cuerpo
 * frente/espalda (hombre o mujer) y cabeza en perfil derecho, frente y perfil
 * izquierdo. Lo comparten el formulario (leishmaniasis-body-map.php, donde el
 * JS agrega los marcadores) y la vista de solo lectura (fichas/ver.php, que
 * los pinta acá desde lo guardado).
 *
 * Variables opcionales:
 *   $generoSilueta       'hombre' | 'mujer' (por defecto 'hombre')
 *   $lesionesSilueta     lista guardada de b55_lesiones (vista, x, y, tipo...)
 *   $soloLecturaSilueta  true en ver.php: sin cursor de marcar
 */
$esMujerSilueta = ($generoSilueta ?? 'hombre') === 'mujer';
$cursorSilueta = !empty($soloLecturaSilueta) ? 'cursor: default;' : 'cursor: crosshair;';
$pintarMarcadoresSilueta = function (string $vista) use ($lesionesSilueta): void {
    foreach (($lesionesSilueta ?? []) as $indice => $lesion) {
        if (($lesion['vista'] ?? '') !== $vista || !is_numeric($lesion['x'] ?? null) || !is_numeric($lesion['y'] ?? null)) {
            continue;
        }
        $numero = (int) ($lesion['num'] ?? ($indice + 1));
        $clase = ($lesion['tipo'] ?? '') === 'Cicatriz' ? 'marker-pin cicatriz' : 'marker-pin';
        printf('<div class="%s" style="left: %s%%; top: %s%%;" title="%s">%d</div>',
            $clase, (float) $lesion['x'], (float) $lesion['y'], e(textoLesionB55($lesion, $numero)), $numero);
    }
};
?>
<div class="leish-siluetas">
    <!-- Contenedor Visual de Siluetas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 20px;">
      
      <!-- Bloque 1: Cuerpo Completo (Frente y Espalda) -->
      <div style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; text-align: center;">
          Cuerpo Completo (Frente / Espalda)
        </div>
        <div style="display: flex; justify-content: center; gap: 12px;">
          
          <!-- Vista Frente -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Anterior</span>
            <div class="svg-map-wrapper" data-vista="cuerpo_anterior" style="position: relative; width: 130px; height: 260px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); <?= $cursorSilueta ?>">
              <svg viewBox="0 0 100 200" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Líneas anatómicas guía -->
                <line x1="5" y1="35" x2="95" y2="35" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="70" x2="95" y2="70" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="100" x2="95" y2="100" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="140" x2="95" y2="140" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                
                <!-- Silueta Frente Hombre -->
                <g class="silueta-hombre"<?= $esMujerSilueta ? ' style="display: none;"' : '' ?>>
                  <!-- Cabeza -->
                  <circle cx="50" cy="18" r="11" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Cuello -->
                  <rect x="46" y="29" width="8" height="6" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Torso -->
                  <path d="M 33 35 L 67 35 L 63 92 L 37 92 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Brazos -->
                  <path d="M 33 35 L 22 75 L 18 105 L 25 105 L 30 78 L 36 50 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 67 35 L 78 75 L 82 105 L 75 105 L 70 78 L 64 50 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Pelvis / Piernas -->
                  <path d="M 37 92 L 63 92 L 60 145 L 58 190 L 49 190 L 51 115 L 49 115 L 51 190 L 42 190 L 40 145 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                </g>

                <!-- Silueta Frente Mujer (Oculta por defecto) -->
                <g class="silueta-mujer"<?= $esMujerSilueta ? '' : ' style="display: none;"' ?>>
                  <circle cx="50" cy="18" r="10" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <rect x="46" y="28" width="8" height="6" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Torso femenino con cintura -->
                  <path d="M 35 34 Q 50 36 65 34 L 61 60 Q 50 63 39 60 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 39 60 Q 50 63 61 60 L 66 94 L 34 94 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Brazos -->
                  <path d="M 35 34 L 23 72 L 20 102 L 26 102 L 31 75 L 37 48 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 65 34 L 77 72 L 80 102 L 74 102 L 69 75 L 63 48 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Caderas y piernas -->
                  <path d="M 34 94 Q 30 110 38 145 L 42 190 L 49 190 L 49 115 L 51 115 L 51 190 L 58 190 L 62 145 Q 70 110 66 94 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"><?php $pintarMarcadoresSilueta('cuerpo_anterior'); ?></div>
            </div>
          </div>

          <!-- Vista Espalda -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Posterior</span>
            <div class="svg-map-wrapper" data-vista="cuerpo_posterior" style="position: relative; width: 130px; height: 260px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); <?= $cursorSilueta ?>">
              <svg viewBox="0 0 100 200" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Líneas anatómicas guía -->
                <line x1="5" y1="35" x2="95" y2="35" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="70" x2="95" y2="70" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="100" x2="95" y2="100" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="140" x2="95" y2="140" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                
                <!-- Silueta Espalda Hombre -->
                <g class="silueta-hombre"<?= $esMujerSilueta ? ' style="display: none;"' : '' ?>>
                  <circle cx="50" cy="18" r="11" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <rect x="46" y="29" width="8" height="6" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Columna/Espalda -->
                  <path d="M 33 35 L 67 35 L 63 92 L 37 92 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <line x1="50" y1="38" x2="50" y2="88" stroke="var(--muted)" stroke-width="0.8" />
                  <!-- Brazos posteriores -->
                  <path d="M 33 35 L 22 75 L 18 105 L 25 105 L 30 78 L 36 50 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 67 35 L 78 75 L 82 105 L 75 105 L 70 78 L 64 50 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <!-- Piernas posteriores -->
                  <path d="M 37 92 L 63 92 L 60 145 L 58 190 L 49 190 L 51 115 L 49 115 L 51 190 L 42 190 L 40 145 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                </g>

                <!-- Silueta Espalda Mujer -->
                <g class="silueta-mujer"<?= $esMujerSilueta ? '' : ' style="display: none;"' ?>>
                  <circle cx="50" cy="18" r="10" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <rect x="46" y="28" width="8" height="6" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 35 34 Q 50 36 65 34 L 61 60 Q 50 63 39 60 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 39 60 Q 50 63 61 60 L 66 94 L 34 94 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <line x1="50" y1="36" x2="50" y2="88" stroke="var(--muted)" stroke-width="0.8" />
                  <path d="M 35 34 L 23 72 L 20 102 L 26 102 L 31 75 L 37 48 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 65 34 L 77 72 L 80 102 L 74 102 L 69 75 L 63 48 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                  <path d="M 34 94 Q 30 110 38 145 L 42 190 L 49 190 L 49 115 L 51 115 L 51 190 L 58 190 L 62 145 Q 70 110 66 94 Z" fill="var(--line)" stroke="var(--muted)" stroke-width="1.2" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"><?php $pintarMarcadoresSilueta('cuerpo_posterior'); ?></div>
            </div>
          </div>

        </div>
      </div>

      <!-- Bloque 2: Vistas de Cabeza y Rostro (Perfil Izq, Frente, Perfil Der) -->
      <div style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 10px; padding: 14px;">
        <div style="font-size: 12px; font-weight: 700; color: var(--ink); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; text-align: center;">
          Detalle Cefálico / Rostro
        </div>
        <div style="display: flex; justify-content: center; gap: 8px; flex-wrap: nowrap;">
          
          <!-- Perfil Derecho -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 10.5px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Perfil Der.</span>
            <div class="svg-map-wrapper" data-vista="cabeza_perfil_der" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); <?= $cursorSilueta ?>">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Perfil derecho: mira a la derecha, como en el PDF -->
                <g>
                  <path class="cara-base" d="M38 132 L40 104 C28 98 20 84 20 64 C20 34 36 16 58 16 C74 16 84 28 84 44 L83 54 L91 66 Q93 69 88 70 L84 71 L86 76 L83 78 L85 82 C85 88 82 94 74 95 L66 97 L67 132" />
                  <path class="cara-sutil" d="M23 62 C24 38 38 22 58 21 C70 21 78 27 81 36" />
                  <path class="cara-base" d="M50 55 C43 53 42 71 50 73 Q54 64 50 55 Z" />
                  <path class="cara-trazo" d="M49 59 Q46 64 49 69" />
                  <path class="cara-trazo" d="M70 47 Q75 45 80 47" />
                  <path class="cara-trazo" d="M71 53 Q75 50 79 53 Q75 55 71 53 Z" />
                  <path class="cara-trazo" d="M84 68 Q86 69.5 88 68.5" />
                  <path class="cara-trazo" d="M83 78 L79 78.5" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"><?php $pintarMarcadoresSilueta('cabeza_perfil_der'); ?></div>
            </div>
          </div>

          <!-- Frente -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 10.5px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Frontal</span>
            <div class="svg-map-wrapper" data-vista="cabeza_frontal" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); <?= $cursorSilueta ?>">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Rostro frontal -->
                <path class="cara-base" d="M40 99 L39 124 L61 124 L60 99 Z" />
                <path class="cara-linea" d="M39 124 Q24 128 12 136 M61 124 Q76 128 88 136" />
                <path class="cara-base" d="M20 50 C14 48 13 63 19 67 Z M80 50 C86 48 87 63 81 67 Z" />
                <path class="cara-base" d="M50 14 C70 14 80 30 80 52 C80 74 72 92 60 100 Q50 106 40 100 C28 92 20 74 20 52 C20 30 30 14 50 14 Z" />
                <path class="cara-sutil" d="M23 44 C26 26 38 19 50 19 C62 19 74 26 77 44" />
                <path class="cara-trazo" d="M31 45 Q37 42 43 45 M57 45 Q63 42 69 45" />
                <path class="cara-trazo" d="M32 52 Q37 48.5 42 52 Q37 55 32 52 Z M58 52 Q63 48.5 68 52 Q63 55 58 52 Z" />
                <circle class="cara-pupila" cx="37" cy="52" r="1.4" />
                <circle class="cara-pupila" cx="63" cy="52" r="1.4" />
                <path class="cara-trazo" d="M48.5 55 Q47 62 45.5 67 M45 68.5 Q50 72 55 68.5" />
                <path class="cara-trazo" d="M41 81 Q45.5 78.5 50 79.5 Q54.5 78.5 59 81 Q50 86.5 41 81 Z" />
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"><?php $pintarMarcadoresSilueta('cabeza_frontal'); ?></div>
            </div>
          </div>

          <!-- Perfil Izquierdo -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 10.5px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Perfil Izq.</span>
            <div class="svg-map-wrapper" data-vista="cabeza_perfil_izq" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); <?= $cursorSilueta ?>">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Perfil izquierdo: el derecho reflejado (mira a la izquierda) -->
                <g transform="translate(100 0) scale(-1 1)">
                  <path class="cara-base" d="M38 132 L40 104 C28 98 20 84 20 64 C20 34 36 16 58 16 C74 16 84 28 84 44 L83 54 L91 66 Q93 69 88 70 L84 71 L86 76 L83 78 L85 82 C85 88 82 94 74 95 L66 97 L67 132" />
                  <path class="cara-sutil" d="M23 62 C24 38 38 22 58 21 C70 21 78 27 81 36" />
                  <path class="cara-base" d="M50 55 C43 53 42 71 50 73 Q54 64 50 55 Z" />
                  <path class="cara-trazo" d="M49 59 Q46 64 49 69" />
                  <path class="cara-trazo" d="M70 47 Q75 45 80 47" />
                  <path class="cara-trazo" d="M71 53 Q75 50 79 53 Q75 55 71 53 Z" />
                  <path class="cara-trazo" d="M84 68 Q86 69.5 88 68.5" />
                  <path class="cara-trazo" d="M83 78 L79 78.5" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"><?php $pintarMarcadoresSilueta('cabeza_perfil_izq'); ?></div>
            </div>
          </div>

        </div>
        <div style="font-size: 11px; color: var(--muted); text-align: center; margin-top: 12px; font-style: italic;">
          Recomendado para registrar úlceras o nódulos nasales, labiales, pabellón auricular y mejillas.
        </div>
      </div>

    </div>
</div>

<style>
.svg-map-wrapper .cara-base { fill: var(--line); stroke: var(--muted); stroke-width: 1.3; stroke-linejoin: round; stroke-linecap: round; }
.svg-map-wrapper .cara-trazo { fill: none; stroke: var(--ink-2); stroke-width: 1.1; stroke-linejoin: round; stroke-linecap: round; }
.svg-map-wrapper .cara-sutil { fill: none; stroke: var(--muted); stroke-width: .8; stroke-dasharray: 2 2; opacity: .7; }
.svg-map-wrapper .cara-pupila { fill: var(--ink-2); }
.svg-map-wrapper .cara-linea { fill: none; stroke: var(--muted); stroke-width: 1.3; stroke-linecap: round; }
.leish-siluetas .marker-pin {
  position: absolute;
  transform: translate(-50%, -50%);
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #ef4444;
  color: #ffffff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 2px #ffffff, 0 2px 5px rgba(0,0,0,0.3);
  cursor: pointer;
  user-select: none;
  transition: transform 0.15s ease, background 0.15s ease;
  z-index: 10;
}
.leish-siluetas .marker-pin.cicatriz {
  background: #4b5563 !important;
}
.leish-siluetas .marker-pin:hover,
.leish-siluetas .marker-pin.activo {
  transform: translate(-50%, -50%) scale(1.25);
  background: #b91c1c;
  z-index: 20;
}
</style>
