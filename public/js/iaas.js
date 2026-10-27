/**
 * Módulo IAAS (2026-10-26): ronda diaria, vista del mes, ficha del Anexo 5,
 * exposición laboral, indicadores y servicios. Todo formulario funciona sin
 * JavaScript (el servidor valida y redirige); aquí se agrega lo que hace la
 * ronda rápida: marcar sin recargar, ventanas que se llenan solas y la ficha
 * que muestra solo lo que corresponde.
 */
(function () {
  'use strict';

  function $(sel, raiz) { return (raiz || document).querySelector(sel); }
  function $$(sel, raiz) { return Array.prototype.slice.call((raiz || document).querySelectorAll(sel)); }
  function avisar(mensaje) { if (typeof window.toast === 'function') window.toast(mensaje); }
  function sumarDias(iso, n) {
    var d = new Date(iso + 'T12:00:00');
    d.setDate(d.getDate() + n);
    return d.toISOString().slice(0, 10);
  }

  /** POST con fetch; el servidor responde JSON {ok, mensaje, ...} con la cabecera X-Requested-With: fetch. */
  function enviar(url, datos) {
    return fetch(url, { method: 'POST', body: datos, headers: { 'X-Requested-With': 'fetch' }, credentials: 'same-origin' })
      .then(function (r) {
        return r.json().catch(function () { return { ok: false, mensaje: 'Respuesta inesperada del servidor.' }; });
      })
      .catch(function () { return { ok: false, mensaje: 'Sin conexión. Vuelve a intentarlo.' }; });
  }

  // ------------------------------------------------------------ ventanas
  function cerrarModales() { $$('.modal-fondo').forEach(function (m) { m.hidden = true; }); }

  function abrirModal(modal, boton) {
    if (!modal) return;
    var datos = boton ? boton.dataset : {};
    var json = datos.datos ? JSON.parse(datos.datos) : null;
    $$('[data-campo]', modal).forEach(function (campo) {
      var clave = campo.dataset.campo;
      var valor = json ? json[clave] : datos[clave];
      if (valor !== undefined) campo.value = valor === null ? '' : valor;
    });
    $$('[data-campo-txt]', modal).forEach(function (el) {
      var clave = el.dataset.campoTxt;
      var valor = json ? json[clave] : datos[clave];
      if (valor !== undefined) el.textContent = valor;
    });
    // Fechas mínimas: no antes del ingreso, la instalación o la IAAS; el retiro, al día siguiente de la instalación.
    $$('[data-min]', modal).forEach(function (input) { if (datos[input.dataset.min]) input.min = datos[input.dataset.min]; });
    $$('[data-min-despues]', modal).forEach(function (input) { if (datos[input.dataset.minDespues]) input.min = sumarDias(datos[input.dataset.minDespues], 1); });
    modal.hidden = false;
    var foco = $('input:not([type=hidden]):not([disabled]), select, textarea', modal);
    if (foco) setTimeout(function () { foco.focus(); }, 30);
  }

  document.addEventListener('click', function (e) {
    var abre = e.target.closest('[data-modal]');
    if (abre) { abrirModal(document.getElementById(abre.dataset.modal), abre); return; }
    var fondo = e.target.classList && e.target.classList.contains('modal-fondo');
    if (fondo || e.target.closest('[data-cerrar-modal]')) cerrarModales();
    if (e.target.closest('[data-imprimir]')) window.print();
    var fila = e.target.closest('tr[data-href]');
    if (fila && !e.target.closest('a, button, input, select, label')) window.location.href = fila.dataset.href;
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarModales(); });

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (form.dataset.confirmar && !window.confirm(form.dataset.confirmar)) e.preventDefault();
  });

  // ------------------------------------------------------------ ronda del día
  function actualizarProgreso() {
    var disps = $$('.iaas-pac:not(.egresado) .iaas-disp:not(.retirado)');
    if (!disps.length) return;
    var marcados = disps.filter(function (li) { return !li.classList.contains('pend'); }).length;
    var pendientes = disps.length - marcados;
    var m = $('[data-marcados]');
    if (m) m.textContent = marcados;
    var barra = $('.iaas-progreso-barra i');
    if (barra) barra.style.width = Math.round(marcados / disps.length * 100) + '%';
    var txt = $('[data-pendientes-txt]');
    if (txt) {
      txt.innerHTML = pendientes
        ? '<b data-pendientes>' + pendientes + '</b> por marcar'
        : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l5 5L20 7"/></svg> Sala al día';
    }
    var btn = $('[data-btn-confirmar]');
    if (btn) btn.disabled = pendientes === 0;
  }

  function pintarMarca(li, estado) {
    var boton = $('.iaas-marca', li);
    var marcado = $('input[name=marcado]', li);
    li.classList.remove('pend', 'on', 'tr');
    li.classList.add(estado === 'E' ? 'on' : (estado === 'T' ? 'tr' : 'pend'));
    boton.setAttribute('aria-pressed', estado ? 'true' : 'false');
    $('.iaas-marca-ico', boton).textContent = estado === 'T' ? '×' : '✓';
    $('[data-etq]', boton).textContent = estado === 'E' ? 'Marcado' : (estado === 'T' ? 'En tratamiento' : 'Sigue');
    boton.title = estado ? 'Quitar la marca de este día' : 'Marcar: el dispositivo sigue instalado este día';
    marcado.value = estado ? '0' : '1';
  }

  $$('form[data-marca]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var boton = $('.iaas-marca', form);
      if (boton.classList.contains('cargando')) return;
      boton.classList.add('cargando');
      enviar(form.action, new FormData(form)).then(function (r) {
        boton.classList.remove('cargando');
        if (!r.ok) { avisar(r.mensaje); return; }
        pintarMarca(form.closest('.iaas-disp'), r.estado || null);
        actualizarProgreso();
      });
    });
  });

  var formConfirmar = $('form[data-confirmar-sala]');
  if (formConfirmar) {
    formConfirmar.addEventListener('submit', function (e) {
      e.preventDefault();
      var btn = $('[data-btn-confirmar]');
      btn.disabled = true;
      enviar(formConfirmar.action, new FormData(formConfirmar)).then(function (r) {
        avisar(r.mensaje);
        if (!r.ok) { btn.disabled = false; return; }
        // Mismo criterio que el servidor: × si el dispositivo tiene una IAAS en tratamiento.
        $$('.iaas-pac:not(.egresado) .iaas-disp.pend').forEach(function (li) {
          pintarMarca(li, $('.iaas-caso-chip', li) ? 'T' : 'E');
        });
        actualizarProgreso();
      });
    });
  }

  // ------------------------------------------------------------ vista del mes
  // En pantallas angostas la grilla se desplaza sola hasta hoy (las columnas fijas quedan a la vista).
  $$('.iaas-grilla-wrap').forEach(function (caja) {
    var hoy = $('th.hoy', caja);
    var fijas = $('th.c2', caja);
    if (hoy && caja.scrollWidth > caja.clientWidth) {
      caja.scrollLeft = Math.max(0, hoy.offsetLeft - (fijas ? fijas.offsetLeft + fijas.offsetWidth : 0) - hoy.offsetWidth * 4);
    }
  });

  var formCelda = document.getElementById('formCelda');
  if (formCelda) {
    document.addEventListener('click', function (e) {
      var boton = e.target.closest('button[data-celda]');
      if (!boton || boton.disabled) return;
      var marcar = boton.dataset.on !== '1';
      formCelda.dispositivo_id.value = boton.dataset.disp;
      formCelda.fecha.value = boton.dataset.fecha;
      formCelda.marcado.value = marcar ? '1' : '0';
      boton.disabled = true;
      enviar(formCelda.action, new FormData(formCelda)).then(function (r) {
        boton.disabled = false;
        if (!r.ok) { avisar(r.mensaje); return; }
        var td = boton.parentNode;
        var antes = td.classList.contains('E');
        var estado = r.estado || 'F';
        td.classList.remove('E', 'T', 'F');
        td.classList.add(estado);
        $('.s', boton).textContent = estado === 'E' ? '✓' : (estado === 'T' ? '×' : '');
        boton.dataset.on = r.estado ? '1' : '0';
        var delta = (estado === 'E' ? 1 : 0) - (antes ? 1 : 0);
        if (delta) {
          var total = $('[data-total]', td.parentNode);
          total.textContent = parseInt(total.textContent, 10) + delta;
          var totalTipo = $('[data-total-tipo]', td.closest('table'));
          if (totalTipo) totalTipo.textContent = parseInt(totalTipo.textContent, 10) + delta;
        }
      });
    });
  }

  // ------------------------------------------------------------ pasos (+/−)
  $$('[data-stepper]').forEach(function (caja) {
    var input = $('input', caja);
    $$('button[data-paso]', caja).forEach(function (b) {
      b.addEventListener('click', function () {
        var n = (parseInt(input.value, 10) || 0) + parseInt(b.dataset.paso, 10);
        input.value = Math.max(0, Math.min(500, n));
      });
    });
  });

  // ------------------------------------------------------------ servicios
  var formServicios = document.getElementById('formServicios');
  if (formServicios) {
    var grid = document.getElementById('gridUpss');
    $$('input[name=modo]', formServicios).forEach(function (r) {
      r.addEventListener('change', function () { grid.hidden = formServicios.modo.value === 'sin'; });
    });
    $$('[data-upss-check]', formServicios).forEach(function (check) {
      check.addEventListener('change', function () {
        var tarjeta = check.closest('[data-upss]');
        tarjeta.classList.toggle('on', check.checked);
        $('[data-upss-salas]', tarjeta).hidden = !check.checked;
      });
    });
  }

  // ------------------------------------------------------------ búsqueda de paciente
  $$('[data-buscar-persona]').forEach(function (boton) {
    var form = boton.closest('form');
    var tipo = form.tipo_doc;
    var num = form.num_doc;
    var salida = $('[data-encontrado]', form);
    function mostrar(clase, html) { salida.className = 'iaas-encontrado ' + clase; salida.innerHTML = html; salida.hidden = false; }
    function escapar(t) { var d = document.createElement('div'); d.textContent = t || ''; return d.innerHTML; }
    function buscar() {
      if (tipo.value === 'SIN_DOCUMENTO' || !num.value.trim()) return;
      boton.disabled = true;
      fetch('/iaas/paciente?' + new URLSearchParams({ tipo_doc: tipo.value, num_doc: num.value.trim() }), { credentials: 'same-origin' })
        .then(function (r) { return r.json(); })
        .then(function (r) {
          boton.disabled = false;
          if (!r.persona) { mostrar('info', 'No está en el padrón ni en RENIEC. Escribe sus datos abajo.'); return; }
          ['apellido_paterno', 'apellido_materno', 'nombres', 'fecha_nac', 'historia_clinica'].forEach(function (c) {
            if (form[c] && r.persona[c]) form[c].value = r.persona[c];
          });
          if (r.persona.sexo) $$('input[name=sexo]', form).forEach(function (s) { s.checked = s.value === r.persona.sexo; });
          if (r.abierta) mostrar('warn', '<b>' + escapar(r.persona.nombre) + '</b> ya está vigilado en ' + escapar(r.abierta) + '. Regístrale el egreso allí antes de agregarlo aquí.');
          else mostrar('', 'Encontrado: <b>' + escapar(r.persona.nombre) + '</b>. Revisa y completa lo que falte.');
        })
        .catch(function () { boton.disabled = false; mostrar('info', 'No se pudo buscar. Escribe sus datos abajo.'); });
    }
    boton.addEventListener('click', buscar);
    num.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); buscar(); } });
    tipo.addEventListener('change', function () {
      var sin = tipo.value === 'SIN_DOCUMENTO';
      $('[data-num-doc]', form).hidden = sin;
      boton.hidden = sin;
      if (sin) { num.value = ''; mostrar('info', 'Sin documento: se registra como paciente nuevo (por ejemplo, un recién nacido). Identifícalo con la historia clínica.'); }
      else salida.hidden = true;
    });
  });

  var formPaciente = $('[data-form-paciente]');
  if (formPaciente) {
    var ingreso = $('[data-fecha-ingreso]', formPaciente);
    $$('[data-disp-op]', formPaciente).forEach(function (op) {
      var check = $('[data-disp-check]', op);
      var caja = $('[data-disp-fecha]', op);
      check.addEventListener('change', function () { caja.hidden = !check.checked; });
    });
    ingreso.addEventListener('change', function () {
      $$('[data-disp-fecha] input', formPaciente).forEach(function (f) {
        f.min = ingreso.value;
        if (!f.value || f.value < ingreso.value) f.value = ingreso.value;
      });
    });
    var peso = $('[data-peso]', formPaciente);
    if (peso) {
      peso.addEventListener('input', function () {
        var g = parseInt(peso.value, 10);
        var cat = !g ? '' : g <= 750 ? '≤ 750 g' : g <= 1000 ? '751 – 1000 g' : g <= 1500 ? '1001 – 1500 g' : g <= 2500 ? '1501 – 2500 g' : '> 2500 g';
        $('[data-cat-peso]', formPaciente).textContent = cat ? 'Categoría para los indicadores: ' + cat + '.' : 'Se ubica en su categoría de peso para los indicadores.';
      });
    }
    formPaciente.addEventListener('submit', function (e) {
      var algunDisp = $$('[data-disp-check]', formPaciente).some(function (c) { return c.checked; });
      if ($$('[data-disp-check]', formPaciente).length && !algunDisp) {
        e.preventDefault();
        avisar('Marca al menos un dispositivo: en esta sala se vigilan pacientes con dispositivo.');
      }
    });
  }

  // ------------------------------------------------------------ ficha IAAS (Anexo 5)
  var ficha = document.getElementById('fichaIaas');
  if (ficha) {
    var horasT = JSON.parse(ficha.dataset.horasT || '{}');
    var dispositivosPorTipo = { ITS: ['CVC', 'CVP', 'NPT', 'HD'], ITU: ['CUP'], NEU: ['VM'] };
    var factorPorTipo = { ITS: 'dispositivo', ITU: 'dispositivo', NEU: 'dispositivo', ISQ_SUP: 'cirugia', ISQ_PROF: 'cirugia', END: 'parto' };
    var etiquetaFactor = { dispositivo: 'Dispositivo médico', cirugia: 'Cirugía', parto: 'Parto' };

    var aplicarTipo = function (inicial) {
      var marcado = $('input[name=tipo_iaas]:checked', ficha);
      var tipo = marcado ? marcado.value : '';
      var factor = factorPorTipo[tipo] || '';
      $$('[data-criterios]', ficha).forEach(function (g) {
        var activo = g.dataset.criterios === tipo;
        g.hidden = !activo;
        $$('input', g).forEach(function (i) { i.disabled = !activo; if (!activo) i.checked = false; });
      });
      $('[data-sin-tipo]', ficha).hidden = !!tipo;
      $$('[data-factor]', ficha).forEach(function (b) { b.hidden = b.dataset.factor !== factor; });
      $('[data-factor-vacio]', ficha).hidden = !!factor;
      $('[data-factor-etq]', ficha).textContent = etiquetaFactor[factor] || 'Según el tipo de IAAS';
      $$('[data-solo-tipo]', ficha).forEach(function (el) { el.hidden = el.dataset.soloTipo !== tipo; });
      var permitidos = dispositivosPorTipo[tipo] || [];
      $$('[data-disp-tipo]', ficha).forEach(function (op) {
        var ok = permitidos.indexOf(op.dataset.dispTipo) !== -1;
        op.hidden = !ok;
        if (!ok) $('input', op).checked = false;
      });
      if (!inicial && permitidos.length === 1) {
        var unico = $('[data-disp-tipo="' + permitidos[0] + '"] input', ficha);
        if (unico) unico.checked = true;
      }
    };
    $$('input[name=tipo_iaas]', ficha).forEach(function (r) { r.addEventListener('change', function () { aplicarTipo(false); }); });
    aplicarTipo(true);

    var sala = $('[data-sala]', ficha);
    if (sala) {
      sala.addEventListener('change', function () {
        var opcion = sala.options[sala.selectedIndex];
        $('[data-solo-neonatal]', ficha).hidden = !(opcion && opcion.dataset.neonatal === '1');
      });
    }

    var dispRonda = $('[data-disp-ronda]', ficha);
    if (dispRonda) {
      dispRonda.addEventListener('change', function () {
        var op = dispRonda.options[dispRonda.selectedIndex];
        if (!op || !op.value) return;
        var radio = $('[data-disp-tipo="' + op.dataset.tipo + '"] input', ficha);
        if (radio) radio.checked = true;
        ficha.fecha_instalacion.value = op.dataset.instalacion || '';
        ficha.fecha_retiro.value = op.dataset.retiro || '';
      });
    }

    // Índice de riesgo de ISQ (ítem 32): herida C/I, ASA 3-5 y duración > t, un punto cada uno.
    var salidaIndice = $('[data-indice-salida]', ficha);
    var tipoCir = $('[data-tipo-cirugia]', ficha);
    var calcularIndice = function () {
      var herida = $('input[name=clase_herida]:checked', ficha);
      var asa = $('input[name=asa]:checked', ficha);
      var dur = parseInt(ficha.duracion_min.value, 10);
      var t = horasT[tipoCir.value];
      var implante = $('[data-implante]', ficha);
      implante.hidden = !(tipoCir.value === 'PROTESIS_CADERA' || tipoCir.value === 'OTRA');
      salidaIndice.className = 'iaas-indice';
      if (!herida || !asa || !dur || !tipoCir.value) {
        salidaIndice.innerHTML = '<b>—</b><small>Completa tipo de cirugía, duración, herida y ASA.</small>';
        return;
      }
      if (t === undefined) {
        salidaIndice.innerHTML = '<b>—</b><small>Esta cirugía no tiene horas t definidas: el índice no se puede calcular.</small>';
        return;
      }
      var pH = herida.value === 'C' || herida.value === 'I' ? 1 : 0;
      var pA = parseInt(asa.value, 10) >= 3 ? 1 : 0;
      var pD = dur > t * 60 ? 1 : 0;
      var indice = pH + pA + pD;
      salidaIndice.classList.add('r' + indice);
      salidaIndice.innerHTML = '<b>' + indice + '</b><small>herida ' + pH + ' + ASA ' + pA + ' + duración ' + pD +
        ' (' + dur + ' min ' + (pD ? '>' : '≤') + ' ' + (t * 60) + ' min = ' + String(t).replace('.', ',') + ' h)</small>';
    };
    $$('[data-indice]', ficha).forEach(function (i) { i.addEventListener('input', calcularIndice); i.addEventListener('change', calcularIndice); });
    tipoCir.addEventListener('change', calcularIndice);
    calcularIndice();

    // Cultivos en pestañas.
    $$('[data-tab]', ficha).forEach(function (tab) {
      tab.addEventListener('click', function () {
        $$('[data-tab]', ficha).forEach(function (t) { t.classList.toggle('on', t === tab); t.setAttribute('aria-selected', t === tab ? 'true' : 'false'); });
        $$('[data-panel]', ficha).forEach(function (p) { p.hidden = p.dataset.panel !== tab.dataset.tab; });
        if (tab.hasAttribute('data-tab-opcional')) { tab.removeAttribute('data-tab-opcional'); tab.textContent = 'Cultivo ' + tab.dataset.tab; }
      });
    });

    // Antibiograma: la fila marcada se resalta y se puede limpiar.
    ficha.addEventListener('change', function (e) {
      var fila = e.target.closest('.iaas-atb-fila');
      if (!fila) return;
      fila.classList.add('con');
      $('[data-limpiar-atb]', fila).hidden = false;
    });
    $$('[data-limpiar-atb]', ficha).forEach(function (b) {
      b.addEventListener('click', function () {
        var fila = b.closest('.iaas-atb-fila');
        $$('input', fila).forEach(function (i) { i.checked = false; });
        fila.classList.remove('con');
        b.hidden = true;
      });
    });

    // CIE-10: sugerencias del catálogo MINSA y validación del código escrito.
    $$('[data-cie10]', ficha).forEach(function (caja) {
      var input = $('input', caja);
      var lista = $('[data-cie10-lista]', caja);
      var desc = $('[data-cie10-desc]', caja);
      var temporizador;
      var elegir = function (codigo, descripcion) {
        input.value = codigo; desc.textContent = descripcion; desc.classList.remove('err'); lista.hidden = true;
      };
      input.addEventListener('input', function () {
        clearTimeout(temporizador);
        var q = input.value.trim();
        if (q.length < 2) { lista.hidden = true; return; }
        temporizador = setTimeout(function () {
          fetch('/cie10/buscar?' + new URLSearchParams({ q: q }), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (filas) {
              lista.innerHTML = '';
              (filas || []).forEach(function (f) {
                var li = document.createElement('li');
                var b = document.createElement('b'); b.textContent = f.codigo;
                li.appendChild(b); li.appendChild(document.createTextNode(f.descripcion));
                li.addEventListener('mousedown', function (ev) { ev.preventDefault(); elegir(f.codigo, f.descripcion); });
                lista.appendChild(li);
              });
              lista.hidden = !lista.children.length;
            });
        }, 220);
      });
      input.addEventListener('blur', function () {
        setTimeout(function () { lista.hidden = true; }, 120);
        var v = input.value.trim();
        if (!v) { desc.textContent = ''; return; }
        fetch('/cie10/buscar?' + new URLSearchParams({ codigo: v }), { credentials: 'same-origin' })
          .then(function (r) { return r.json(); })
          .then(function (r) {
            if (r.codigo) { input.value = r.codigo; desc.textContent = r.descripcion; desc.classList.remove('err'); }
            else { desc.textContent = r.error || 'Código no válido.'; desc.classList.add('err'); }
          });
      });
    });

    if (ficha.hasAttribute('data-solo-lectura')) {
      $$('input, select, textarea, button[data-limpiar-atb]', ficha).forEach(function (el) { if (el.type !== 'hidden') el.disabled = true; });
    }
  }

  // ------------------------------------------------------------ exposición laboral (Anexo 8)
  var expo = document.getElementById('formExposicion');
  if (expo) {
    var aplicarExpo = function () {
      var factor = $('input[name=factor]:checked', expo);
      $('[data-solo-punzo]', expo).hidden = !!factor && factor.value === 'SALPICADURA';
      $$('[data-otro]', expo).forEach(function (caja) {
        var elegido = $('input[name=' + caja.dataset.otro + ']:checked', expo);
        caja.hidden = !(elegido && elegido.value === 'OTRO');
      });
      var prof = $('input[name=profilaxis]:checked', expo);
      $('[data-medicamento]', expo).hidden = !(prof && prof.value === '1');
    };
    expo.addEventListener('change', aplicarExpo);
    aplicarExpo();
  }

  // ------------------------------------------------------------ personal (Anexo 9)
  var totalPersonal = $('[data-personal-total]');
  if (totalPersonal) {
    var sumar = function () {
      totalPersonal.textContent = $$('[data-personal]').reduce(function (s, i) { return s + (parseInt(i.value, 10) || 0); }, 0);
    };
    $$('[data-personal]').forEach(function (i) { i.addEventListener('input', sumar); });
  }
})();
