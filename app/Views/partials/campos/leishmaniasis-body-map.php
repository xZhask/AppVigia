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

    <?php
    $generoSilueta = (($valoresFijos['sexo'] ?? '') === 'F') ? 'mujer' : 'hombre';
    $lesionesSilueta = [];
    $soloLecturaSilueta = false;
    require __DIR__ . '/leishmaniasis-siluetas.php';
    ?>

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
            <tr style="background: var(--paper); border-bottom: 1px solid var(--line);">
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
  function mostrarSilueta(genero) {
    const mujer = genero === 'mujer';
    btnMujer.classList.toggle('on', mujer);
    btnHombre.classList.toggle('on', !mujer);
    btnMujer.querySelector('input').checked = mujer;
    btnHombre.querySelector('input').checked = !mujer;
    document.querySelectorAll('.silueta-hombre').forEach(el => el.style.display = mujer ? 'none' : '');
    document.querySelectorAll('.silueta-mujer').forEach(el => el.style.display = mujer ? '' : 'none');
  }
  if (btnHombre && btnMujer) {
    btnHombre.addEventListener('click', () => mostrarSilueta('hombre'));
    btnMujer.addEventListener('click', () => mostrarSilueta('mujer'));
    // La silueta sigue al "Sexo" del paciente (al cargar y al cambiarlo); los
    // botones quedan para corregirla a mano. Sin sexo elegido, Hombre.
    const sexoPaciente = document.querySelector('select[name="sexo"], input[name="sexo"]');
    const seguirSexo = () => {
      const valor = sexoPaciente.type === 'radio'
        ? (document.querySelector('input[name="sexo"]:checked') || {}).value
        : sexoPaciente.value;
      if (valor === 'F' || valor === 'M') mostrarSilueta(valor === 'F' ? 'mujer' : 'hombre');
    };
    if (sexoPaciente) {
      document.addEventListener('change', (e) => { if (e.target && e.target.name === 'sexo') seguirSexo(); });
      seguirSexo();
    }
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
