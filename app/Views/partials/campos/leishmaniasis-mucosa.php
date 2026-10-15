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
  <div style="background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 18px; box-shadow: var(--shadow-soft);">

    <div style="margin-bottom: 14px; border-bottom: 1px solid var(--line); padding-bottom: 12px;">
      <h4 style="margin: 0; font-size: 14px; font-weight: 700; color: var(--ink);">Diagramas ORL y compromiso de estructuras mucosas</h4>
      <div style="font-size: 12px; color: var(--muted); margin-top: 3px;">
        Examen otorrinolaringológico: marque por estructura el compromiso y si hay eritema, edema, infiltración o úlcera. La estructura marcada se resalta en los diagramas; al pasar el cursor por una fila se señala dónde está.
      </div>
    </div>

    <!-- 4 vistas del PDF (pág. 46): corte sagital, laringe, rinoscopia anterior y cavidad oral.
         Cada .orl-region lleva el data-region de su fila de la tabla. -->
    <div class="orl-diagramas">

      <figure class="orl-vista">
        <figcaption>Corte sagital</figcaption>
        <svg viewBox="0 0 160 120" role="img" aria-label="Corte sagital de nariz, boca, faringe y laringe">
          <!-- Perfil de cabeza y cuello (mirando a la izquierda) -->
          <path class="orl-base" d="M118 8 C88 2 58 8 50 30 L47 44 L32 60 Q30 63 34 64 L42 65 L41 70 L43 74 L41 79 L45 86 C49 94 60 97 70 94 L76 95 L78 118 M118 8 C140 16 146 46 136 70 C130 86 120 98 118 118" />
          <!-- Lengua (referencia, sin región) -->
          <path class="orl-tejido" d="M47 75 Q62 67 82 70 Q92 74 93 84 L86 90 Q66 92 49 84 Z" />
          <!-- Fosa nasal: tercio anterior y resto -->
          <path class="orl-region" data-region="septo_anterior" d="M47 47 L60 46 L60 61 L46 62 Z" />
          <path class="orl-region" data-region="septo" d="M60 46 L84 44 Q92 48 92 54 L86 60 L60 61 Z" />
          <!-- Cornetes -->
          <path class="orl-region orl-linea" data-region="cornetes" d="M64 50 Q74 47 84 50 M63 55 Q75 52 87 56" />
          <!-- Paladar duro y blando -->
          <path class="orl-region" data-region="paladar" d="M46 63 L84 61 Q93 62 97 69 L94 71 Q89 66 83 66 L46 67 Z" />
          <!-- Úvula -->
          <path class="orl-region" data-region="uvula" d="M95 69 Q100 75 95 77 Q92 73 94 70 Z" />
          <!-- Labios -->
          <path class="orl-region" data-region="labios" d="M41 69 Q45 68 47 70 L47 72 Q43 73 41 72 Z M41 76 Q45 75 47 76 L47 79 Q43 80 41 78 Z" />
          <!-- Rinofaringe y faringe (pared posterior) -->
          <path class="orl-region" data-region="faringe" d="M93 44 Q106 46 108 60 L108 98 L101 98 L101 62 Q99 54 93 51 Z" />
          <!-- Epiglotis -->
          <path class="orl-region" data-region="epiglotis" d="M95 87 Q98 79 101 86 Q99 91 96 92 Z" />
          <!-- Laringe / cuerdas vocales -->
          <path class="orl-region" data-region="cuerdas" d="M89 96 L101 96 L101 109 L89 109 Z" />
          <path class="orl-detalle" d="M89 102 L101 102" />
        </svg>
        <div class="orl-pie">Fosas · paladar · faringe · laringe</div>
      </figure>

      <figure class="orl-vista">
        <figcaption>Laringe</figcaption>
        <svg viewBox="0 0 160 120" role="img" aria-label="Laringe vista desde arriba">
          <ellipse class="orl-tejido" cx="80" cy="62" rx="58" ry="50" />
          <!-- Bandas ventriculares (falsas cuerdas) -->
          <path class="orl-detalle" d="M75 38 L50 92 M85 38 L110 92" />
          <!-- Glotis -->
          <path class="orl-hueco" d="M80 42 L64 93 Q80 99 96 93 Z" />
          <!-- Cuerdas vocales -->
          <path class="orl-region" data-region="cuerdas" d="M80 40 L59 93 L66 95 L80 47 Z M80 40 L101 93 L94 95 L80 47 Z" />
          <!-- Aritenoides -->
          <circle class="orl-tejido" cx="64" cy="102" r="7" />
          <circle class="orl-tejido" cx="96" cy="102" r="7" />
          <!-- Epiglotis -->
          <path class="orl-region" data-region="epiglotis" d="M42 36 Q80 2 118 36 Q80 22 42 36 Z" />
        </svg>
        <div class="orl-pie">Epiglotis · cuerdas vocales</div>
      </figure>

      <figure class="orl-vista">
        <figcaption>Rinoscopia anterior</figcaption>
        <svg viewBox="0 0 160 120" role="img" aria-label="Rinoscopia anterior: fosas nasales, tabique y cornetes">
          <path class="orl-tejido" d="M18 106 Q20 40 80 12 Q140 40 142 106 Z" />
          <!-- Fosas nasales -->
          <path class="orl-hueco" d="M71 24 Q42 40 32 104 L71 104 Z M89 24 Q118 40 128 104 L89 104 Z" />
          <!-- Cornetes medio e inferior -->
          <path class="orl-region" data-region="cornetes" d="M40 42 Q60 44 62 52 Q58 58 38 56 Z M33 66 Q60 66 64 78 Q58 88 30 86 Z M120 42 Q100 44 98 52 Q102 58 122 56 Z M127 66 Q100 66 96 78 Q102 88 130 86 Z" />
          <!-- Tabique: resto y tercio anterior -->
          <path class="orl-region" data-region="septo" d="M74 20 L86 20 L87 84 L73 84 Z" />
          <path class="orl-region" data-region="septo_anterior" d="M73 84 L87 84 L88 104 L72 104 Z" />
          <!-- Narinas -->
          <path class="orl-region" data-region="narinas" d="M30 104 Q50 94 70 104 Q50 112 30 104 Z M90 104 Q110 94 130 104 Q110 112 90 104 Z" />
        </svg>
        <div class="orl-pie">Narinas · tabique · cornetes</div>
      </figure>

      <figure class="orl-vista">
        <figcaption>Cavidad oral</figcaption>
        <svg viewBox="0 0 160 120" role="img" aria-label="Cavidad oral abierta">
          <!-- Labios -->
          <path class="orl-region" data-region="labios" fill-rule="evenodd" d="M12 60 A68 50 0 1 0 148 60 A68 50 0 1 0 12 60 Z M24 60 A56 38 0 1 1 136 60 A56 38 0 1 1 24 60 Z" />
          <ellipse class="orl-hueco" cx="80" cy="60" rx="56" ry="38" />
          <!-- Pared posterior de la faringe -->
          <ellipse class="orl-region" data-region="faringe" cx="80" cy="58" rx="17" ry="12" />
          <!-- Paladar -->
          <path class="orl-region" data-region="paladar" d="M30 46 Q80 8 130 46 Q104 36 80 36 Q56 36 30 46 Z" />
          <!-- Úvula -->
          <path class="orl-region" data-region="uvula" d="M76 37 Q80 56 84 37 Z" />
          <!-- Lengua -->
          <path class="orl-tejido" d="M38 84 Q80 62 122 84 Q80 96 38 84 Z" />
          <!-- Arcadas dentales -->
          <path class="orl-encia" d="M30 40 Q80 18 130 40 M32 82 Q80 102 128 82" />
          <path class="orl-region orl-dientes" data-region="arcada" d="M30 40 Q80 18 130 40 M32 82 Q80 102 128 82" />
        </svg>
        <div class="orl-pie">Labios · arcada · paladar · úvula</div>
      </figure>

    </div>

    <!-- Matriz de Compromiso de Estructuras -->
    <div style="overflow-x: auto; background: var(--surface); border: 1px solid var(--line); border-radius: 8px;">
      <table style="width: 100%; border-collapse: collapse; min-width: 520px; font-size: 12px; table-layout: fixed;" class="tabla-orl">
        <thead>
          <tr style="background: var(--paper); border-bottom: 1px solid var(--line);">
            <th style="padding: 8px 12px; text-align: left; color: var(--faint); width: 140px;">Estructura</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 82px;">Compromiso</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 56px;">Eritema</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 54px;">Edema</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 64px;">Infiltr.</th>
            <th style="padding: 8px 6px; text-align: center; color: var(--faint); width: 54px;">Úlcera</th>
            <th style="padding: 8px 12px; text-align: left; color: var(--faint);">N.° de lesiones y características</th>
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
                <input type="text" name="<?= e($nombreCampo) ?>[<?= e($keyEst) ?>][caracteristicas]" value="<?= e($caracteristicas) ?>" placeholder="N.° y detalle…" style="width: 100%; border: 1px solid var(--line); border-radius: 4px; padding: 4px 8px; font-size: 11.5px; background: var(--paper); color: var(--ink);">
              </td>
            </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

<style>
.leishmaniasis-mucosa-widget .orl-diagramas {
  display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; margin-bottom: 16px;
}
.leishmaniasis-mucosa-widget .orl-vista {
  margin: 0; background: var(--paper); border: 1px solid var(--line); border-radius: 8px; padding: 8px 8px 6px; text-align: center;
}
.leishmaniasis-mucosa-widget .orl-vista figcaption { font-size: 11px; font-weight: 700; color: var(--ink-2); margin-bottom: 4px; }
.leishmaniasis-mucosa-widget .orl-vista svg { display: block; width: 100%; height: auto; max-height: 120px; }
.leishmaniasis-mucosa-widget .orl-pie { font-size: 10px; color: var(--muted); margin-top: 2px; }
.leishmaniasis-mucosa-widget .orl-base { fill: none; stroke: var(--muted); stroke-width: 1.3; stroke-linejoin: round; stroke-linecap: round; }
.leishmaniasis-mucosa-widget .orl-tejido { fill: var(--line-2); stroke: var(--muted); stroke-width: .8; }
.leishmaniasis-mucosa-widget .orl-hueco { fill: var(--line); stroke: var(--muted); stroke-width: .8; }
.leishmaniasis-mucosa-widget .orl-detalle { fill: none; stroke: var(--muted); stroke-width: .8; stroke-dasharray: 2 2; }
.leishmaniasis-mucosa-widget .orl-region {
  fill: var(--surface); stroke: var(--ink-2); stroke-width: 1; stroke-linejoin: round;
  transition: fill .15s ease, stroke .15s ease; cursor: pointer;
}
.leishmaniasis-mucosa-widget .orl-region.orl-linea { fill: none; stroke-width: 1.6; stroke-linecap: round; }
.leishmaniasis-mucosa-widget .orl-encia { fill: none; stroke: var(--muted); stroke-width: 6; }
.leishmaniasis-mucosa-widget .orl-region.orl-dientes { fill: none; stroke: var(--surface); stroke-width: 4.2; stroke-dasharray: 4.2 1.3; }
.leishmaniasis-mucosa-widget .orl-region:hover,
.leishmaniasis-mucosa-widget .orl-region.senalada { fill: var(--accent-soft); stroke: var(--accent); stroke-width: 1.6; }
.leishmaniasis-mucosa-widget .orl-region.orl-linea:hover,
.leishmaniasis-mucosa-widget .orl-region.orl-linea.senalada,
.leishmaniasis-mucosa-widget .orl-region.orl-dientes:hover,
.leishmaniasis-mucosa-widget .orl-region.orl-dientes.senalada { fill: none; stroke: var(--accent); }
.leishmaniasis-mucosa-widget .orl-region.afectada { fill: var(--s-confirmado); fill-opacity: .55; stroke: var(--s-confirmado); }
.leishmaniasis-mucosa-widget .orl-region.orl-linea.afectada,
.leishmaniasis-mucosa-widget .orl-region.orl-dientes.afectada { fill: none; }
.leishmaniasis-mucosa-widget .tabla-orl thead th { text-transform: none; letter-spacing: 0; white-space: normal; font-size: 11px; line-height: 1.25; vertical-align: bottom; }
.leishmaniasis-mucosa-widget tr.fila-resaltada { background: var(--accent-soft) !important; }
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
          el.classList.add('senalada');
        });
      }
    });
    fila.addEventListener('mouseleave', () => {
      const region = fila.getAttribute('data-region');
      if (region) {
        document.querySelectorAll(`.leishmaniasis-mucosa-widget [data-region="${region}"]`).forEach(el => {
          el.classList.remove('senalada');
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
