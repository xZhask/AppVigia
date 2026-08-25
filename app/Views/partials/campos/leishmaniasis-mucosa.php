<?php
/**
 * Diagramas Anatómicos ORL (4 vistas) y Matriz de Compromiso de Estructuras Mucosas
 * Específico para la Ficha B55 (Leishmaniasis).
 */

$nombreCampo = 'campo_' . ($campo['id'] ?? 'b55_compromiso_de_estructuras');
$valorCrudo = $valor ?? [];
$valoresEstructuras = [];

if (is_array($valorCrudo)) {
    $valoresEstructuras = $valorCrudo;
} elseif (is_string($valorCrudo) && trim($valorCrudo) !== '') {
    $decoded = json_decode($valorCrudo, true);
    if (is_array($decoded)) {
        $valoresEstructuras = $decoded;
    }
}

$estructuras = [
    'nariz_narinas'        => ['grupo' => 'Nariz', 'label' => 'Narinas', 'region_svg' => 'narinas'],
    'nariz_1_3_anterior'   => ['grupo' => 'Nariz', 'label' => '1/3 anterior', 'region_svg' => 'septo_anterior'],
    'nariz_septo'          => ['grupo' => 'Nariz', 'label' => 'Septo nasal', 'region_svg' => 'septo'],
    'nariz_cornetes'       => ['grupo' => 'Nariz', 'label' => 'Cornetes', 'region_svg' => 'cornetes'],
    'boca_labios'          => ['grupo' => 'Boca',  'label' => 'Labios', 'region_svg' => 'labios'],
    'boca_arcada'          => ['grupo' => 'Boca',  'label' => 'Arcada dental', 'region_svg' => 'arcada'],
    'boca_paladar'         => ['grupo' => 'Boca',  'label' => 'Paladar (duro / blando)', 'region_svg' => 'paladar'],
    'boca_uvula'           => ['grupo' => 'Boca',  'label' => 'Úvula', 'region_svg' => 'uvula'],
    'faringe'              => ['grupo' => 'Otros', 'label' => 'Faringe / Rinofaringe', 'region_svg' => 'faringe'],
    'epiglotis'            => ['grupo' => 'Otros', 'label' => 'Epiglotis', 'region_svg' => 'epiglotis'],
    'cuerdas_vocales'      => ['grupo' => 'Otros', 'label' => 'Cuerdas vocales', 'region_svg' => 'cuerdas'],
    'otras_estructuras'    => ['grupo' => 'Otros', 'label' => 'Otras estructuras', 'region_svg' => ''],
];
?>

<div class="field wide leishmaniasis-mucosa-widget" style="margin-top: 10px; margin-bottom: 24px;">
  <div style="background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
    
    <div style="margin-bottom: 16px; border-bottom: 1px solid var(--line-2); padding-bottom: 12px;">
      <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); display: flex; align-items: center; gap: 8px;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2zm0 18a8 8 0 1 1 8-8 8 8 0 0 1-8 8z"></path>
          <path d="M12 6v6l4 2"></path>
        </svg>
        DIAGRAMAS ANATÓMICOS ORL Y COMPROMISO DE ESTRUCTURAS MUCOSAS
      </h4>
      <div style="font-size: 12px; color: var(--muted); margin-top: 3px;">
        Examen clínico otorrinolaringológico: evalúe eritema, edema, infiltración o ulceración por estructura anatómica.
      </div>
    </div>

    <!-- 4 Diagramas Anatómicos Vectoriales -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 18px;">
      
      <!-- Vista 1: Corte Sagital de Fosas y Faringe -->
      <div class="card-diagrama-orl" style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 8px; padding: 10px; text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: var(--ink); text-transform: uppercase; margin-bottom: 6px;">1. Corte Sagital</div>
        <div style="height: 110px; display: flex; align-items: center; justify-content: center;">
          <svg viewBox="0 0 120 100" style="width: 100%; height: 100%;">
            <!-- Silueta craneal sagital -->
            <path d="M 20 80 C 15 65 15 45 25 30 C 35 15 55 10 75 12 C 90 15 100 30 95 50 C 90 70 85 85 85 95" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Nariz y Fosas nasales -->
            <path data-region="septo" d="M 25 35 L 12 50 L 22 55 L 45 52 L 45 38 Z" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
            <!-- Paladar duro y blando -->
            <path data-region="paladar" d="M 24 60 L 55 60 L 60 68 L 52 68 L 24 64 Z" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
            <!-- Rinofaringe / Faringe -->
            <path data-region="faringe" d="M 55 40 L 70 42 L 72 85 L 62 85 L 60 55 Z" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
            <!-- Laringe y Epiglotis -->
            <path data-region="epiglotis" d="M 52 75 L 56 68 L 58 75 Z" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
            <path data-region="cuerdas" d="M 50 82 L 60 82 L 58 92 L 48 92 Z" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
          </svg>
        </div>
        <div style="font-size: 10px; color: var(--muted); margin-top: 4px;">Fosas / Paladar / Laringe</div>
      </div>

      <!-- Vista 2: Laringoscopia (Cuerdas Vocales y Epiglotis) -->
      <div class="card-diagrama-orl" style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 8px; padding: 10px; text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: var(--ink); text-transform: uppercase; margin-bottom: 6px;">2. Laringe / Cuerdas</div>
        <div style="height: 110px; display: flex; align-items: center; justify-content: center;">
          <svg viewBox="0 0 120 100" style="width: 100%; height: 100%;">
            <!-- Anillo laríngeo exterior -->
            <path d="M 25 35 C 35 15 85 15 95 35 C 105 55 95 85 60 88 C 25 85 15 55 25 35 Z" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Epiglotis superior -->
            <path data-region="epiglotis" d="M 35 30 Q 60 18 85 30 Q 60 40 35 30 Z" class="orl-region" fill="var(--paper-3, #d1d5db)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
            <!-- Cuerdas vocales verdaderas (en 'V') -->
            <path data-region="cuerdas" d="M 60 38 L 42 70 L 48 74 L 60 46 L 72 74 L 78 70 Z" class="orl-region" fill="#ffffff" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Glotis / Espacio central -->
            <polygon points="60,42 48,70 72,70" fill="#1f2937" />
          </svg>
        </div>
        <div style="font-size: 10px; color: var(--muted); margin-top: 4px;">Cuerdas / Epiglotis</div>
      </div>

      <!-- Vista 3: Rinoscopia Anterior (Narinas / Septo Nasal) -->
      <div class="card-diagrama-orl" style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 8px; padding: 10px; text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: var(--ink); text-transform: uppercase; margin-bottom: 6px;">3. Rinoscopia Anterior</div>
        <div style="height: 110px; display: flex; align-items: center; justify-content: center;">
          <svg viewBox="0 0 120 100" style="width: 100%; height: 100%;">
            <!-- Base nasal y narinas -->
            <path d="M 15 80 C 20 40 40 20 60 20 C 80 20 100 40 105 80 Z" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Narina Izquierda -->
            <ellipse data-region="narinas" cx="42" cy="55" rx="14" ry="24" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Narina Derecha -->
            <ellipse data-region="narinas" cx="78" cy="55" rx="14" ry="24" class="orl-region" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Septo Nasal (Columela central) -->
            <rect data-region="septo" x="56" y="24" width="8" height="56" class="orl-region" fill="var(--paper-3, #d1d5db)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Cornetes en la pared lateral -->
            <path data-region="cornetes" d="M 32 50 Q 38 60 32 70 M 88 50 Q 82 60 88 70" class="orl-region" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.5" />
          </svg>
        </div>
        <div style="font-size: 10px; color: var(--muted); margin-top: 4px;">Narinas / Tabique / Cornetes</div>
      </div>

      <!-- Vista 4: Cavidad Oral Abierta -->
      <div class="card-diagrama-orl" style="background: var(--paper); border: 1px solid var(--line-2); border-radius: 8px; padding: 10px; text-align: center;">
        <div style="font-size: 11px; font-weight: 700; color: var(--ink); text-transform: uppercase; margin-bottom: 6px;">4. Cavidad Oral</div>
        <div style="height: 110px; display: flex; align-items: center; justify-content: center;">
          <svg viewBox="0 0 120 100" style="width: 100%; height: 100%;">
            <!-- Labios abiertos -->
            <ellipse cx="60" cy="50" rx="46" ry="36" fill="var(--paper-2, #e5e7eb)" stroke="var(--line-3, #6b7280)" stroke-width="1.4" />
            <!-- Arcada dental superior e inferior -->
            <path data-region="arcada" d="M 28 35 Q 60 22 92 35" class="orl-region" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="2.5" />
            <path data-region="arcada" d="M 30 68 Q 60 80 90 68" class="orl-region" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="2.5" />
            <!-- Paladar y Úvula -->
            <path data-region="paladar" d="M 32 38 Q 60 48 88 38" class="orl-region" fill="none" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <path data-region="uvula" d="M 57 42 Q 60 56 63 42 Z" class="orl-region" fill="var(--paper-3, #d1d5db)" stroke="var(--line-3, #6b7280)" stroke-width="1.2" />
            <!-- Lengua inferior -->
            <ellipse cx="60" cy="66" rx="24" ry="10" fill="var(--paper-3, #d1d5db)" stroke="var(--line-3, #6b7280)" stroke-width="1" />
          </svg>
        </div>
        <div style="font-size: 10px; color: var(--muted); margin-top: 4px;">Labios / Paladar / Úvula</div>
      </div>

    </div>

    <!-- Matriz de Compromiso de Estructuras -->
    <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 8px;">
      <table style="width: 100%; border-collapse: collapse; min-width: 680px; font-size: 12px;">
        <thead>
          <tr style="background: var(--paper-2); border-bottom: 1px solid var(--line);">
            <th style="padding: 8px 12px; text-align: left; color: var(--faint); width: 190px;">Estructura Evaluada</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 85px;">Compromiso</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 65px;">Eritema</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 65px;">Edema</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 65px;">Infiltración</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 65px;">Úlcera</th>
            <th style="padding: 8px 12px; text-align: left; color: var(--faint);">N.° Lesiones y Características</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          $grupoActual = '';
          foreach ($estructuras as $keyEst => $meta): 
            $valoresFila = $valoresEstructuras[$keyEst] ?? [];
            $compromiso = !empty($valoresFila['compromiso']);
            $eritema = !empty($valoresFila['eritema']);
            $edema = !empty($valoresFila['edema']);
            $infiltracion = !empty($valoresFila['infiltracion']);
            $ulcera = !empty($valoresFila['ulcera']);
            $caracteristicas = $valoresFila['caracteristicas'] ?? '';

            if ($meta['grupo'] !== $grupoActual && $meta['grupo'] !== 'Otros'):
              $grupoActual = $meta['grupo'];
          ?>
            <tr style="background: rgba(0,0,0,0.02); border-top: 1px solid var(--line-2);">
              <td colspan="7" style="padding: 6px 12px; font-weight: 700; font-size: 11.5px; color: var(--ink); text-transform: uppercase; letter-spacing: 0.5px;">
                <?= e($grupoActual) ?>
              </td>
            </tr>
          <?php endif; ?>

            <tr class="fila-estructura" data-key="<?= e($keyEst) ?>" data-region="<?= e($meta['region_svg']) ?>" style="border-bottom: 1px solid var(--line-2); transition: background 0.15s ease;">
              <td style="padding: 6px 12px; font-weight: 500; color: var(--ink);">
                <?= e($meta['label']) ?>
              </td>
              
              <!-- Compromiso (Sí / No) -->
              <td style="padding: 6px 4px; text-align: center;">
                <input type="checkbox" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][compromiso]" value="1" class="chk-compromiso" <?= $compromiso ? 'checked' : '' ?> style="accent-color: var(--accent); cursor: pointer;">
              </td>

              <!-- Eritema -->
              <td style="padding: 6px 4px; text-align: center;">
                <input type="checkbox" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][eritema]" value="1" class="chk-sub" <?= $eritema ? 'checked' : '' ?> style="accent-color: #f59e0b; cursor: pointer;">
              </td>

              <!-- Edema -->
              <td style="padding: 6px 4px; text-align: center;">
                <input type="checkbox" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][edema]" value="1" class="chk-sub" <?= $edema ? 'checked' : '' ?> style="accent-color: #3b82f6; cursor: pointer;">
              </td>

              <!-- Infiltración -->
              <td style="padding: 6px 4px; text-align: center;">
                <input type="checkbox" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][infiltracion]" value="1" class="chk-sub" <?= $infiltracion ? 'checked' : '' ?> style="accent-color: #8b5cf6; cursor: pointer;">
              </td>

              <!-- Úlcera -->
              <td style="padding: 6px 4px; text-align: center;">
                <input type="checkbox" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][ulcera]" value="1" class="chk-sub" <?= $ulcera ? 'checked' : '' ?> style="accent-color: #ef4444; cursor: pointer;">
              </td>

              <!-- Características -->
              <td style="padding: 4px 8px;">
                <input type="text" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][caracteristicas]" value="<?= e($caracteristicas) ?>" placeholder="Descripción o n.° de lesiones..." style="width: 100%; border: 1px solid var(--line); border-radius: 4px; padding: 4px 8px; font-size: 11.5px; background: var(--paper); color: var(--ink);">
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<style>
.leishmaniasis-mucosa-widget .orl-region {
  transition: fill 0.2s ease, stroke 0.2s ease, filter 0.2s ease;
  cursor: pointer;
}
.leishmaniasis-mucosa-widget .orl-region:hover {
  fill: #fed7aa !important;
  stroke: #ea580c !important;
}
.leishmaniasis-mucosa-widget .orl-region.afectada {
  fill: #ef4444 !important;
  stroke: #b91c1c !important;
  filter: drop-shadow(0 0 3px rgba(239, 68, 68, 0.6));
}
.leishmaniasis-mucosa-widget tr.fila-resaltada {
  background: rgba(234, 88, 12, 0.08) !important;
}
</style>

<script>
(function() {
  const filas = document.querySelectorAll('.leishmaniasis-mucosa-widget .fila-estructura');
  const regionesSvg = document.querySelectorAll('.leishmaniasis-mucosa-widget .orl-region');

  function actualizarRegionesAfectadas() {
    regionesSvg.forEach(r => r.classList.remove('afectada'));

    filas.forEach(fila => {
      const chkCompromiso = fila.querySelector('.chk-compromiso');
      const chkUlcera = fila.querySelector('.chk-sub[name*="[ulcera]"]');
      const region = fila.getAttribute('data-region');

      if ((chkCompromiso && chkCompromiso.checked) || (chkUlcera && chkUlcera.checked)) {
        if (region) {
          document.querySelectorAll(`.leishmaniasis-mucosa-widget [data-region="${region}"]`).forEach(el => {
            el.classList.add('afectada');
          });
        }
      }
    });
  }

  // Auto-marcar compromiso si se marca eritema/edema/infiltración/úlcera
  filas.forEach(fila => {
    const chkCompromiso = fila.querySelector('.chk-compromiso');
    const chksSub = fila.querySelectorAll('.chk-sub');

    chksSub.forEach(sub => {
      sub.addEventListener('change', () => {
        if (sub.checked && chkCompromiso && !chkCompromiso.checked) {
          chkCompromiso.checked = true;
        }
        actualizarRegionesAfectadas();
      });
    });

    if (chkCompromiso) {
      chkCompromiso.addEventListener('change', actualizarRegionesAfectadas);
    }

    // Hover sobre fila resalta SVG
    fila.addEventListener('mouseenter', () => {
      const region = fila.getAttribute('data-region');
      if (region) {
        document.querySelectorAll(`.leishmaniasis-mucosa-widget [data-region="${region}"]`).forEach(el => {
          el.style.strokeWidth = '2.5px';
        });
      }
    });
    fila.addEventListener('mouseleave', () => {
      const region = fila.getAttribute('data-region');
      if (region) {
        document.querySelectorAll(`.leishmaniasis-mucosa-widget [data-region="${region}"]`).forEach(el => {
          el.style.strokeWidth = '';
        });
      }
    });
  });

  // Clic en región SVG enfoca la fila
  regionesSvg.forEach(svgEl => {
    svgEl.addEventListener('click', () => {
      const region = svgEl.getAttribute('data-region');
      const fila = document.querySelector(`.leishmaniasis-mucosa-widget .fila-estructura[data-region="${region}"]`);
      if (fila) {
        filas.forEach(f => f.classList.remove('fila-resaltada'));
        fila.classList.add('fila-resaltada');
        fila.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    });
  });

  actualizarRegionesAfectadas();
})();
</script>
