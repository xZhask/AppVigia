<?php
/**
 * Mapa Anatómico Corporal y Facial con Tabla Dinámica de Lesiones Cutáneas
 * Específico para la Ficha B55 (Leishmaniasis).
 *
 * Permite marcar y numerar lesiones en siluetas SVG (Cuerpo Frente/Espalda y Cabeza Frente/Perfiles)
 * sincronizado con la tabla de características y cálculo de superficie en mm².
 */

$nombreCampo = 'campo_' . ($campo['id'] ?? 'b55_lesiones');
$valorCrudo = $valor ?? [];
$lesiones = [];

if (is_array($valorCrudo)) {
    $lesiones = $valorCrudo;
} elseif (is_string($valorCrudo) && trim($valorCrudo) !== '') {
    $decoded = json_decode($valorCrudo, true);
    if (is_array($decoded)) {
        $lesiones = $decoded;
    }
}

// Revisión 2026-08-24: nombres reales de "N.° de lesiones activas"/"N.° de
// cicatrices" (campo_<id>, el id se regenera en cada cargar_fichas.php
// --apply) para el auto-conteo más abajo. NO se puede usar el closure
// $campo(...) de campos-por-clave.php acá: dentro del loop de
// secciones-clinicas.php que hace `require` de este partial, "$campo" ya
// fue reasignado al ARRAY del campo actual (b55_lesiones), no al
// resolvedor -- $mapaClaveNombreCampos (la misma info, sin ese choque de
// nombre) sigue disponible sin shadow.
$nombreCampoActivasB55 = $mapaClaveNombreCampos['b55_n_de_lesiones_activas'] ?? '';
$nombreCampoCicatricesB55 = $mapaClaveNombreCampos['b55_n_de_cicatrices'] ?? '';
?>

<div class="field wide leishmaniasis-lesiones-widget" style="margin-top: 10px; margin-bottom: 24px;">
  <div style="background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin-bottom: 16px; border-bottom: 1px solid var(--line-2); padding-bottom: 12px;">
      <div>
        <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          MARCAR Y NUMERAR LAS LESIONES CUTÁNEAS
        </h4>
        <div style="font-size: 12px; color: var(--muted); margin-top: 3px;">
          Haga clic sobre cualquier zona del cuerpo o rostro para situar un marcador numerado y registrar la lesión.
        </div>
      </div>

      <div style="display: flex; gap: 8px; align-items: center;">
        <span style="font-size: 11.5px; font-weight: 600; color: var(--ink-2); text-transform: uppercase; letter-spacing: 0.5px;">Género silueta:</span>
        <div class="seg" style="display: inline-flex;">
          <label class="seg-label on" id="btnSiluetaHombre" style="cursor: pointer; padding: 4px 10px; font-size: 12px; font-weight: 600;">
            <input type="radio" name="_b55_genero_silueta" value="hombre" checked class="sr-only"> Hombre
          </label>
          <label class="seg-label" id="btnSiluetaMujer" style="cursor: pointer; padding: 4px 10px; font-size: 12px; font-weight: 600;">
            <input type="radio" name="_b55_genero_silueta" value="mujer" class="sr-only"> Mujer
          </label>
        </div>
      </div>
    </div>

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
            <div class="svg-map-wrapper" data-vista="cuerpo_anterior" style="position: relative; width: 130px; height: 260px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); cursor: crosshair;">
              <svg viewBox="0 0 100 200" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Líneas anatómicas guía -->
                <line x1="5" y1="35" x2="95" y2="35" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="70" x2="95" y2="70" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="100" x2="95" y2="100" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="140" x2="95" y2="140" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                
                <!-- Silueta Frente Hombre -->
                <g class="silueta-hombre">
                  <!-- Cabeza -->
                  <circle cx="50" cy="18" r="11" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Cuello -->
                  <rect x="46" y="29" width="8" height="6" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Torso -->
                  <path d="M 33 35 L 67 35 L 63 92 L 37 92 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Brazos -->
                  <path d="M 33 35 L 22 75 L 18 105 L 25 105 L 30 78 L 36 50 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 67 35 L 78 75 L 82 105 L 75 105 L 70 78 L 64 50 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Pelvis / Piernas -->
                  <path d="M 37 92 L 63 92 L 60 145 L 58 190 L 49 190 L 51 115 L 49 115 L 51 190 L 42 190 L 40 145 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                </g>

                <!-- Silueta Frente Mujer (Oculta por defecto) -->
                <g class="silueta-mujer" style="display: none;">
                  <circle cx="50" cy="18" r="10" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <rect x="46" y="28" width="8" height="6" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Torso femenino con cintura -->
                  <path d="M 35 34 Q 50 36 65 34 L 61 60 Q 50 63 39 60 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 39 60 Q 50 63 61 60 L 66 94 L 34 94 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Brazos -->
                  <path d="M 35 34 L 23 72 L 20 102 L 26 102 L 31 75 L 37 48 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 65 34 L 77 72 L 80 102 L 74 102 L 69 75 L 63 48 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Caderas y piernas -->
                  <path d="M 34 94 Q 30 110 38 145 L 42 190 L 49 190 L 49 115 L 51 115 L 51 190 L 58 190 L 62 145 Q 70 110 66 94 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"></div>
            </div>
          </div>

          <!-- Vista Espalda -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 11px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Posterior</span>
            <div class="svg-map-wrapper" data-vista="cuerpo_posterior" style="position: relative; width: 130px; height: 260px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); cursor: crosshair;">
              <svg viewBox="0 0 100 200" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Líneas anatómicas guía -->
                <line x1="5" y1="35" x2="95" y2="35" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="70" x2="95" y2="70" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="100" x2="95" y2="100" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                <line x1="5" y1="140" x2="95" y2="140" stroke="var(--line-2)" stroke-dasharray="2 2" stroke-width="0.8" />
                
                <!-- Silueta Espalda Hombre -->
                <g class="silueta-hombre">
                  <circle cx="50" cy="18" r="11" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <rect x="46" y="29" width="8" height="6" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Columna/Espalda -->
                  <path d="M 33 35 L 67 35 L 63 92 L 37 92 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <line x1="50" y1="38" x2="50" y2="88" stroke="var(--line-3, #6b7280)" stroke-width="0.8" />
                  <!-- Brazos posteriores -->
                  <path d="M 33 35 L 22 75 L 18 105 L 25 105 L 30 78 L 36 50 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 67 35 L 78 75 L 82 105 L 75 105 L 70 78 L 64 50 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <!-- Piernas posteriores -->
                  <path d="M 37 92 L 63 92 L 60 145 L 58 190 L 49 190 L 51 115 L 49 115 L 51 190 L 42 190 L 40 145 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                </g>

                <!-- Silueta Espalda Mujer -->
                <g class="silueta-mujer" style="display: none;">
                  <circle cx="50" cy="18" r="10" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <rect x="46" y="28" width="8" height="6" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 35 34 Q 50 36 65 34 L 61 60 Q 50 63 39 60 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 39 60 Q 50 63 61 60 L 66 94 L 34 94 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <line x1="50" y1="36" x2="50" y2="88" stroke="var(--line-3, #6b7280)" stroke-width="0.8" />
                  <path d="M 35 34 L 23 72 L 20 102 L 26 102 L 31 75 L 37 48 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 65 34 L 77 72 L 80 102 L 74 102 L 69 75 L 63 48 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                  <path d="M 34 94 Q 30 110 38 145 L 42 190 L 49 190 L 49 115 L 51 115 L 51 190 L 58 190 L 62 145 Q 70 110 66 94 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                </g>
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"></div>
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
            <div class="svg-map-wrapper" data-vista="cabeza_perfil_der" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); cursor: crosshair;">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Perfil mirando a la izquierda -->
                <path d="M 65 130 L 65 95 C 75 80 75 40 60 20 C 45 5 25 15 20 30 C 18 35 22 45 20 52 C 16 55 10 65 14 70 C 18 73 22 73 20 78 C 17 83 22 88 28 88 C 35 90 40 100 45 130 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.4" />
                <!-- Ojo / Oreja -->
                <ellipse cx="28" cy="48" rx="4" ry="2" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <path d="M 50 45 C 56 45 56 65 50 68" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"></div>
            </div>
          </div>

          <!-- Frente -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 10.5px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Frontal</span>
            <div class="svg-map-wrapper" data-vista="cabeza_frontal" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); cursor: crosshair;">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Rostro Frontal -->
                <path d="M 30 130 L 32 95 C 20 85 15 65 15 45 C 15 15 85 15 85 45 C 85 65 80 85 68 95 L 70 130 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.4" />
                <!-- Ojos / Nariz / Boca / Orejas -->
                <ellipse cx="35" cy="50" rx="5" ry="3" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <ellipse cx="65" cy="50" rx="5" ry="3" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <path d="M 50 48 L 47 68 L 53 68 Z" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <path d="M 40 80 Q 50 86 60 80" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <path d="M 15 45 C 10 45 10 60 15 65 M 85 45 C 90 45 90 60 85 65" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"></div>
            </div>
          </div>

          <!-- Perfil Izquierdo -->
          <div style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-size: 10.5px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">Perfil Izq.</span>
            <div class="svg-map-wrapper" data-vista="cabeza_perfil_izq" style="position: relative; width: 90px; height: 130px; background: rgba(0,0,0,0.015); border-radius: 8px; border: 1px dashed var(--line); cursor: crosshair;">
              <svg viewBox="0 0 100 140" style="width: 100%; height: 100%; pointer-events: none;">
                <!-- Perfil mirando a la derecha -->
                <path d="M 35 130 L 35 95 C 25 80 25 40 40 20 C 55 5 75 15 80 30 C 82 35 78 45 80 52 C 84 55 90 65 86 70 C 82 73 78 73 80 78 C 83 83 78 88 72 88 C 65 90 60 100 55 130 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.4" />
                <!-- Ojo / Oreja -->
                <ellipse cx="72" cy="48" rx="4" ry="2" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
                <path d="M 50 45 C 44 45 44 65 50 68" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
              </svg>
              <div class="markers-layer" style="position: absolute; inset: 0; pointer-events: auto;"></div>
            </div>
          </div>

        </div>
        <div style="font-size: 11px; color: var(--muted); text-align: center; margin-top: 12px; font-style: italic;">
          Recomendado para registrar úlceras o nódulos nasales, labiales, pabellón auricular y mejillas.
        </div>
      </div>

    </div>

    <!-- Revisión 2026-08-24: CasosController::validarCamposDinamicos() para
         tipo=MATRIZ exige $_POST[nombreCampo] como ARRAY
         (campo_<id>[fila][col], el contrato real de campos/matriz.php) --
         un solo <input hidden> con un JSON como STRING llegaba como texto
         plano y se descartaba en silencio (if (!is_array($valorCrudo))
         { $valorCrudo = []; }), así que NINGUNA lesión se guardaba nunca.
         Los inputs ocultos ahora se generan en notación de array PHP
         (campo_<id>[<índice>][<propiedad>], uno por cada propiedad de cada
         lesión) dentro de este contenedor, reconstruido por JS cada vez
         que la lista cambia -- ver sincronizarCamposOcultos() más abajo. -->
    <div id="camposOcultosLesionesB55"></div>

    <!-- Tabla Dinámica de Lesiones -->
    <div style="margin-top: 14px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
        <label class="fl" style="margin: 0; font-size: 13px; font-weight: 700; color: var(--ink);">
          Tabla de Registro de Lesiones y Cicatrices
        </label>
        <button type="button" class="btn btn-ghost" id="btnAgregarLesionB55" style="font-size: 12px; padding: 4px 10px;">
          <svg width="14" height="14" viewBox="0 0 14 14" style="margin-right: 4px;"><path d="M7 3v8M3 7h8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
          Agregar Fila
        </button>
      </div>

      <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 8px;">
        <table id="tablaLesionesB55" style="width: 100%; border-collapse: collapse; min-width: 780px; font-size: 12px;">
          <thead>
            <tr style="background: var(--paper-2); border-bottom: 1px solid var(--line);">
              <th style="padding: 8px 10px; width: 40px; text-align: center; color: var(--faint);">#</th>
              <th style="padding: 8px 10px; width: 125px; text-align: left; color: var(--faint);">Fecha Inicio</th>
              <th style="padding: 8px 10px; width: 120px; text-align: left; color: var(--faint);">Tipo</th>
              <th style="padding: 8px 10px; width: 140px; text-align: left; color: var(--faint);">Localización</th>
              <th style="padding: 8px 8px; width: 85px; text-align: center; color: var(--faint);">Ganglios</th>
              <th style="padding: 8px 8px; width: 85px; text-align: center; color: var(--faint);">Infección</th>
              <th style="padding: 8px 10px; width: 130px; text-align: center; color: var(--faint);">Diámetros (mm)</th>
              <th style="padding: 8px 10px; width: 110px; text-align: center; color: var(--faint);">Superficie</th>
              <th style="padding: 8px 6px; width: 45px; text-align: center;"></th>
            </tr>
          </thead>
          <tbody id="tbodyLesionesB55">
            <!-- Filas dinámicas generadas por JavaScript o PHP inicial -->
          </tbody>
        </table>
      </div>
      <div style="font-size: 11.5px; color: var(--muted); margin-top: 6px;">
        * Fórmula MINSA para cálculo de área: <code>Área = (d1 × d2) / 4 mm²</code> (o elíptica aproximada).
      </div>
    </div>

  </div>
</div>

<style>
.leishmaniasis-lesiones-widget .marker-pin {
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
.leishmaniasis-lesiones-widget .marker-pin.cicatriz {
  background: #4b5563 !important;
}
.leishmaniasis-lesiones-widget .marker-pin:hover,
.leishmaniasis-lesiones-widget .marker-pin.activo {
  transform: translate(-50%, -50%) scale(1.25);
  background: #b91c1c;
  z-index: 20;
}
.leishmaniasis-lesiones-widget tr.fila-activa {
  background: rgba(239, 68, 68, 0.08) !important;
}
</style>

<script>
(function() {
  const datosIniciales = <?= json_encode($lesiones, JSON_UNESCAPED_UNICODE) ?>;
  let listaLesiones = Array.isArray(datosIniciales) ? datosIniciales : [];

  const nombreCampoLesiones = <?= json_encode($nombreCampo) ?>;
  const contenedorOcultos = document.getElementById('camposOcultosLesionesB55');
  const tbody = document.getElementById('tbodyLesionesB55');
  const wrappers = document.querySelectorAll('.svg-map-wrapper');

  // Alternar género de silueta
  const btnHombre = document.getElementById('btnSiluetaHombre');
  const btnMujer = document.getElementById('btnSiluetaMujer');
  if (btnHombre && btnMujer) {
    btnHombre.addEventListener('click', () => {
      btnHombre.classList.add('on');
      btnMujer.classList.remove('on');
      document.querySelectorAll('.silueta-hombre').forEach(el => el.style.display = '');
      document.querySelectorAll('.silueta-mujer').forEach(el => el.style.display = 'none');
    });
    btnMujer.addEventListener('click', () => {
      btnMujer.classList.add('on');
      btnHombre.classList.remove('on');
      document.querySelectorAll('.silueta-hombre').forEach(el => el.style.display = 'none');
      document.querySelectorAll('.silueta-mujer').forEach(el => el.style.display = '');
    });
  }

  function actualizarJson() {
    // Reconstruye los <input hidden> en notación de array PHP
    // (campo_<id>[índice][propiedad]) para que
    // CasosController::validarCamposDinamicos() (tipo=MATRIZ) reciba un
    // array de verdad en $_POST, no un string JSON plano que se descarta.
    if (contenedorOcultos) {
      contenedorOcultos.innerHTML = '';
      listaLesiones.forEach((lesion, idx) => {
        Object.keys(lesion).forEach((propiedad) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = nombreCampoLesiones + '[' + idx + '][' + propiedad + ']';
          const val = lesion[propiedad];
          input.value = (val === undefined || val === null) ? '' : val;
          contenedorOcultos.appendChild(input);
        });
      });
    }

    // Nombres reales resueltos en PHP (campos-por-clave.php) -- ver
    // comentario en la parte superior del archivo sobre por qué no se
    // puede usar el selector CSS [name*="clave"] (los name reales son
    // "campo_<id>", nunca contienen la clave) ni un id hardcodeado (se
    // regenera en cada carga del manifiesto).
    const inputActivas = <?= $nombreCampoActivasB55 ? 'document.querySelector(\'[name="' . e($nombreCampoActivasB55) . '"]\')' : 'null' ?>;
    const inputCicatrices = <?= $nombreCampoCicatricesB55 ? 'document.querySelector(\'[name="' . e($nombreCampoCicatricesB55) . '"]\')' : 'null' ?>;

    let activas = 0;
    let cicatrices = 0;
    listaLesiones.forEach(l => {
      if (l.tipo === 'Cicatriz') cicatrices++;
      else activas++;
    });

    if (inputActivas && (!inputActivas.value || inputActivas.value === '0')) inputActivas.value = activas;
    if (inputCicatrices && (!inputCicatrices.value || inputCicatrices.value === '0')) inputCicatrices.value = cicatrices;
  }

  function estimarLocalizacion(vista, yPorc) {
    if (vista.startsWith('cabeza')) return 'Cabeza';
    if (yPorc < 18) return 'Cabeza';
    if (yPorc < 48) return 'Torso';
    if (yPorc < 60) return 'Pelvis';
    return 'Miembro inferior';
  }

  function renderizarMarcadores() {
    document.querySelectorAll('.markers-layer').forEach(layer => layer.innerHTML = '');

    listaLesiones.forEach((lesion, index) => {
      const num = index + 1;
      lesion.num = num;

      if (lesion.vista && lesion.x !== undefined && lesion.y !== undefined) {
        const wrapper = document.querySelector(`.svg-map-wrapper[data-vista="${lesion.vista}"]`);
        if (wrapper) {
          const layer = wrapper.querySelector('.markers-layer');
          if (layer) {
            const pin = document.createElement('div');
            pin.className = 'marker-pin' + (lesion.tipo === 'Cicatriz' ? ' cicatriz' : '');
            pin.textContent = num;
            pin.style.left = lesion.x + '%';
            pin.style.top = lesion.y + '%';
            pin.title = `Lesión #${num}: ${lesion.tipo || 'Úlcera'} (${lesion.localizacion || ''})`;

            pin.addEventListener('click', (e) => {
              e.stopPropagation();
              resaltarLesion(index);
            });

            layer.appendChild(pin);
          }
        }
      }
    });
  }

  function resaltarLesion(index) {
    document.querySelectorAll('#tbodyLesionesB55 tr').forEach((tr, i) => {
      if (i === index) {
        tr.classList.add('fila-activa');
        tr.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      } else {
        tr.classList.remove('fila-activa');
      }
    });
    document.querySelectorAll('.marker-pin').forEach((pin, i) => {
      if (i === index) pin.classList.add('activo');
      else pin.classList.remove('activo');
    });
  }

  function calcularSuperficie(d1, d2) {
    const num1 = parseFloat(d1);
    const num2 = parseFloat(d2);
    if (!isNaN(num1) && !isNaN(num2) && num1 > 0 && num2 > 0) {
      return ((num1 * num2) / 4).toFixed(2);
    }
    return '0.00';
  }

  function renderizarTabla() {
    if (!tbody) return;
    tbody.innerHTML = '';

    if (listaLesiones.length === 0) {
      tbody.innerHTML = `<tr><td colspan="9" style="text-align:center; padding: 18px; color: var(--muted); font-style: italic;">No hay lesiones registradas. Haga clic en la silueta anatómica o en "Agregar Fila".</td></tr>`;
      return;
    }

    listaLesiones.forEach((lesion, index) => {
      const num = index + 1;
      const tr = document.createElement('tr');
      tr.style.borderBottom = '1px solid var(--line-2)';

      tr.innerHTML = `
        <td style="padding: 6px 10px; text-align: center; font-weight: 700; color: var(--ink);">${num}</td>
        <td style="padding: 6px 8px;">
          <input type="date" class="ctrl-fecha" value="${lesion.fecha_inicio || ''}" max="<?= date('Y-m-d') ?>" style="width: 100%; padding: 4px 6px; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
        </td>
        <td style="padding: 6px 8px;">
          <select class="ctrl-tipo" style="width: 100%; padding: 4px 6px; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
            <option value="Ulcera" ${lesion.tipo === 'Ulcera' || !lesion.tipo ? 'selected' : ''}>Úlcera</option>
            <option value="Nodulo" ${lesion.tipo === 'Nodulo' ? 'selected' : ''}>Nódulo</option>
            <option value="Verrugosa" ${lesion.tipo === 'Verrugosa' ? 'selected' : ''}>Verrugosa</option>
            <option value="Cicatriz" ${lesion.tipo === 'Cicatriz' ? 'selected' : ''}>Cicatriz</option>
          </select>
        </td>
        <td style="padding: 6px 8px;">
          <select class="ctrl-loc" style="width: 100%; padding: 4px 6px; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
            <option value="Cabeza" ${lesion.localizacion === 'Cabeza' ? 'selected' : ''}>1-Cabeza</option>
            <option value="Miembro superior" ${lesion.localizacion === 'Miembro superior' ? 'selected' : ''}>2-Miembro superior</option>
            <option value="Miembro inferior" ${lesion.localizacion === 'Miembro inferior' ? 'selected' : ''}>3-Miembro inferior</option>
            <option value="Torso" ${lesion.localizacion === 'Torso' ? 'selected' : ''}>4-Torso</option>
            <option value="Pelvis" ${lesion.localizacion === 'Pelvis' ? 'selected' : ''}>5-Pelvis</option>
          </select>
        </td>
        <td style="padding: 6px 4px; text-align: center;">
          <select class="ctrl-ganglios" style="padding: 4px 4px; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
            <option value="NO" ${lesion.ganglios === 'NO' || !lesion.ganglios ? 'selected' : ''}>No</option>
            <option value="SI" ${lesion.ganglios === 'SI' ? 'selected' : ''}>Sí</option>
          </select>
        </td>
        <td style="padding: 6px 4px; text-align: center;">
          <select class="ctrl-infeccion" style="padding: 4px 4px; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
            <option value="NO" ${lesion.infeccion === 'NO' || !lesion.infeccion ? 'selected' : ''}>No</option>
            <option value="SI" ${lesion.infeccion === 'SI' ? 'selected' : ''}>Sí</option>
          </select>
        </td>
        <td style="padding: 6px 8px; text-align: center;">
          <div style="display: flex; gap: 4px; align-items: center; justify-content: center;">
            <input type="number" step="0.5" min="0" class="ctrl-d1" placeholder="d1" value="${lesion.d1 || ''}" style="width: 50px; padding: 4px 4px; text-align: center; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
            <span>×</span>
            <input type="number" step="0.5" min="0" class="ctrl-d2" placeholder="d2" value="${lesion.d2 || ''}" style="width: 50px; padding: 4px 4px; text-align: center; font-size: 11.5px; border: 1px solid var(--line); border-radius: 4px; background: var(--paper); color: var(--ink);">
          </div>
        </td>
        <td style="padding: 6px 8px; text-align: center; font-weight: 600; color: var(--ink-2);">
          <span class="lbl-superficie">${calcularSuperficie(lesion.d1, lesion.d2)}</span> mm²
        </td>
        <td style="padding: 6px 4px; text-align: center;">
          <button type="button" class="btn-eliminar-fila" title="Eliminar lesión" style="background: none; border: none; cursor: pointer; color: var(--danger, #ef4444); padding: 4px;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
          </button>
        </td>
      `;

      // Eventos de inputs de fila
      tr.querySelector('.ctrl-fecha').addEventListener('change', (e) => {
        listaLesiones[index].fecha_inicio = e.target.value;
        actualizarJson();
      });
      tr.querySelector('.ctrl-tipo').addEventListener('change', (e) => {
        listaLesiones[index].tipo = e.target.value;
        actualizarJson();
        renderizarMarcadores();
      });
      tr.querySelector('.ctrl-loc').addEventListener('change', (e) => {
        listaLesiones[index].localizacion = e.target.value;
        actualizarJson();
      });
      tr.querySelector('.ctrl-ganglios').addEventListener('change', (e) => {
        listaLesiones[index].ganglios = e.target.value;
        actualizarJson();
      });
      tr.querySelector('.ctrl-infeccion').addEventListener('change', (e) => {
        listaLesiones[index].infeccion = e.target.value;
        actualizarJson();
      });

      const d1Input = tr.querySelector('.ctrl-d1');
      const d2Input = tr.querySelector('.ctrl-d2');
      const lblSup = tr.querySelector('.lbl-superficie');

      function actualizarDiametros() {
        listaLesiones[index].d1 = d1Input.value;
        listaLesiones[index].d2 = d2Input.value;
        const sup = calcularSuperficie(d1Input.value, d2Input.value);
        listaLesiones[index].superficie = sup;
        lblSup.textContent = sup;
        actualizarJson();
      }

      d1Input.addEventListener('input', actualizarDiametros);
      d2Input.addEventListener('input', actualizarDiametros);

      tr.querySelector('.btn-eliminar-fila').addEventListener('click', () => {
        listaLesiones.splice(index, 1);
        actualizarJson();
        renderizarMarcadores();
        renderizarTabla();
      });

      tr.addEventListener('click', () => resaltarLesion(index));

      tbody.appendChild(tr);
    });
  }

  // Evento de clic sobre siluetas para agregar marcador
  wrappers.forEach(wrap => {
    wrap.addEventListener('click', (e) => {
      const rect = wrap.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      const vista = wrap.getAttribute('data-vista');
      const locSugerida = estimarLocalizacion(vista, y);

      const nueva = {
        num: listaLesiones.length + 1,
        vista: vista,
        x: parseFloat(x.toFixed(1)),
        y: parseFloat(y.toFixed(1)),
        fecha_inicio: '<?= date('Y-m-d') ?>',
        tipo: 'Ulcera',
        localizacion: locSugerida,
        ganglios: 'NO',
        infeccion: 'NO',
        d1: '',
        d2: '',
        superficie: '0.00'
      };

      listaLesiones.push(nueva);
      actualizarJson();
      renderizarMarcadores();
      renderizarTabla();
      resaltarLesion(listaLesiones.length - 1);
    });
  });

  // Botón Agregar Fila manual
  const btnAgregar = document.getElementById('btnAgregarLesionB55');
  if (btnAgregar) {
    btnAgregar.addEventListener('click', () => {
      listaLesiones.push({
        num: listaLesiones.length + 1,
        vista: 'cuerpo_anterior',
        x: 50,
        y: 50,
        fecha_inicio: '<?= date('Y-m-d') ?>',
        tipo: 'Ulcera',
        localizacion: 'Miembro superior',
        ganglios: 'NO',
        infeccion: 'NO',
        d1: '',
        d2: '',
        superficie: '0.00'
      });
      actualizarJson();
      renderizarMarcadores();
      renderizarTabla();
      resaltarLesion(listaLesiones.length - 1);
    });
  }

  // Inicialización -- actualizarJson() debe correr también acá (no solo
  // tras cada edición): al editar un caso ya guardado, si el usuario no
  // toca el widget de lesiones, los <input hidden> deben existir desde el
  // primer render con los datos ya cargados -- si no, un guardado sin
  // tocar esta sección enviaría la lista vacía y borraría las lesiones ya
  // registradas (mismo motivo por el que evaluarDependencias() limpia
  // valores al ocultar un campo: acá es el caso inverso, hay que
  // SEMBRARLOS para no perderlos).
  actualizarJson();
  renderizarMarcadores();
  renderizarTabla();
})();
</script>
