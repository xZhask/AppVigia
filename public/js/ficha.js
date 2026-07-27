document.addEventListener('DOMContentLoaded', function () {
  var selectorEnfermedad = document.getElementById('diseaseSel');
  var contenedorClinico = document.getElementById('secciones-clinicas');

  function renumerarSeccionesSiguientes() {
    if (!contenedorClinico) return;
    var siguiente = document.getElementById('numeroSiguienteSeccion');
    var numero = siguiente ? parseInt(siguiente.value, 10) : NaN;
    if (isNaN(numero)) return;

    var el = contenedorClinico.nextElementSibling;
    while (el) {
      if (el.classList && el.classList.contains('section')) {
        var num = el.querySelector('.section-num');
        if (num) num.textContent = numero++;
      }
      el = el.nextElementSibling;
    }
  }
  renumerarSeccionesSiguientes();

  // ---------- Motor de dependencias condicionales entre campos ----------
  // (AUDITORIA_FICHA_DIFTERIA.md, punto 4): un .dep-wrap con
  // data-depende-de="campo_ID" se oculta mientras ese campo padre no tenga
  // el valor de data-valor-activador. Al ocultarse, limpia su valor.
  function leerValorCampoPorNombre(nombre) {
    var allRadios = document.querySelectorAll('input[name="' + nombre + '"][type="radio"]');
    if (allRadios.length) {
      var checkedRadio = document.querySelector('input[name="' + nombre + '"][type="radio"]:checked');
      return checkedRadio ? checkedRadio.value : '';
    }

    var grupoArray = document.querySelectorAll('input[name="' + nombre + '[]"]:checked');
    if (grupoArray.length) return Array.prototype.map.call(grupoArray, function (el) { return el.value; });

    var subKeys = document.querySelectorAll('input[name^="' + nombre + '[' + '"]:checked');
    if (subKeys.length) {
      return Array.prototype.map.call(subKeys, function (el) { return el.value; });
    }

    var checkbox = document.querySelector('input[name="' + nombre + '"][type="checkbox"]');
    if (checkbox) return checkbox.checked ? (checkbox.value || '1') : '0';

    var el = document.querySelector('[name="' + nombre + '"]');
    return el ? el.value : null;
  }

  function evaluarDependencias() {
    var cambio = false;
    var pasadas = 0;
    do {
      cambio = false;
      pasadas++;
      document.querySelectorAll('.dep-wrap[data-depende-de]').forEach(function (wrap) {
        var nombrePadre = wrap.getAttribute('data-depende-de');
        var activador = wrap.getAttribute('data-valor-activador');
        
        // Si la sección o grupo dependiente ancestro está oculto, este subcampo debe ocultarse obligatoriamente
        var padreOculto = wrap.parentElement && wrap.parentElement.closest('.dep-wrap[hidden], .section.dep-wrap[hidden]');
        var visible = false;

        if (!padreOculto) {
          var valorActual = leerValorCampoPorNombre(nombrePadre);
          var activadores = (activador || '').split(',').map(function(s) { return s.trim(); });
          if (Array.isArray(valorActual)) {
            visible = valorActual.some(function(v) { return activadores.indexOf(v) !== -1; });
          } else {
            visible = activadores.indexOf(valorActual) !== -1;
          }
        }

        if (visible === !wrap.hidden) return;
        wrap.hidden = !visible;
        cambio = true;

        if (!visible) {
          wrap.querySelectorAll('input, select, textarea').forEach(function (el) {
            if (el.type === 'checkbox' || el.type === 'radio') {
              el.checked = false;
            } else {
              el.value = '';
            }
            if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(el);
          });
        }
      });
    } while (cambio && pasadas < 10);
  }
  evaluarDependencias();
  document.addEventListener('input', evaluarDependencias);
  document.addEventListener('change', evaluarDependencias);

  // ---------- PFA: Clasificación final -> Criterios y Clasificación del caso inferior ----------
  function sincronizarDescartadoPfa() {
    var selectClasificacion = null;
    document.querySelectorAll('.field').forEach(function (f) {
      var fl = f.querySelector('.fl');
      if (fl && /Clasificación final/i.test(fl.textContent)) {
        var sel = f.querySelector('select');
        if (sel) selectClasificacion = sel;
      }
    });

    if (!selectClasificacion) return;

    var valRaw = selectClasificacion.value || '';
    if (!valRaw) return;

    var valSel = valRaw.toUpperCase();
    var esDescartado = valSel.indexOf('DESCARTADO') !== -1;

    // 1. Sincronizar Criterio de clasificación (casillas)
    document.querySelectorAll('.field').forEach(function (f) {
      var fl = f.querySelector('.fl');
      if (fl && /Criterio de clasificación/i.test(fl.textContent)) {
        var chks = f.querySelectorAll('input[type="checkbox"]');
        chks.forEach(function (chk) {
          var label = chk.closest('label');
          var labelText = label ? label.textContent.trim().toUpperCase() : chk.value.toUpperCase();
          var isDescartadoOption = labelText.indexOf('DESCARTADO') !== -1 || (chk.value || '').toUpperCase().indexOf('DESCARTADO') !== -1;

          if (isDescartadoOption) {
            if (esDescartado) {
              chk.checked = true;
              chk.style.pointerEvents = 'none';
              if (label) label.style.opacity = '1';
            } else {
              chk.checked = false;
              chk.style.pointerEvents = 'none';
              if (label) label.style.opacity = '0.5';
            }
          } else {
            if (esDescartado) {
              chk.checked = false;
              chk.style.pointerEvents = 'none';
              if (label) label.style.opacity = '0.5';
            } else {
              chk.style.pointerEvents = '';
              if (label) label.style.opacity = '1';
            }
          }
        });
      }
    });

    // 2. Sincronizar Clasificación del caso (chips de radio al final del formulario)
    var radioAbajoTarget = esDescartado ?
      document.querySelector('input[name="clasificacion"][value="DESCARTADO"]') :
      document.querySelector('input[name="clasificacion"][value="CONFIRMADO"]');

    if (radioAbajoTarget) {
      radioAbajoTarget.checked = true;
    }
  }

  sincronizarDescartadoPfa();
  document.addEventListener('change', sincronizarDescartadoPfa);

  // ---------- Núcleo: gestante solo si sexo=F, semanas solo si gestante=Sí ----------
  var sexoSel = document.querySelector('[name="sexo"]');
  var gestanteSel = document.getElementById('gestanteSel');
  var campoGestante = document.getElementById('campoGestante');
  var campoSemanas = document.getElementById('campoSemanasGestacion');

  function actualizarGestante() {
    if (!campoGestante || !campoSemanas) return;
    var enfermedadSel = document.getElementById('diseaseSel');
    var opcionEnfermedad = enfermedadSel && enfermedadSel.selectedOptions[0];
    var textoEnfermedad = opcionEnfermedad ? (opcionEnfermedad.text || '') : '';
    var tagCie = document.getElementById('cieTag');
    var textoCie = tagCie ? (tagCie.textContent || '') : '';
    
    if (textoEnfermedad.indexOf('A80') !== -1 || textoCie.indexOf('A80') !== -1 || textoEnfermedad.indexOf('O95') !== -1 || textoCie.indexOf('O95') !== -1) {
      campoGestante.hidden = true;
      campoSemanas.hidden = true;
      if (gestanteSel) gestanteSel.value = '';
      return;
    }

    var esFemenino = sexoSel && sexoSel.value === 'F';
    campoGestante.hidden = !esFemenino;
    if (!esFemenino && gestanteSel) gestanteSel.value = '';

    var esGestante = esFemenino && gestanteSel && gestanteSel.value === '1';
    campoSemanas.hidden = !esGestante;
    var wrapPartoB05 = document.getElementById('wrapLugarPartoB05');
    if (wrapPartoB05) wrapPartoB05.hidden = !esGestante;

    if (!esGestante) {
      var semanas = document.getElementById('semanasGestacion');
      if (semanas) semanas.value = '';
    }
  }
  if (sexoSel) sexoSel.addEventListener('change', actualizarGestante);
  if (gestanteSel) gestanteSel.addEventListener('change', actualizarGestante);
  actualizarGestante();

  function actualizarTutorB05() {
    var chk = document.getElementById('chkEsMenorEdadB05');
    var wrap = document.getElementById('wrapTutorB05');
    if (wrap) {
      var mostrar = !!(chk && chk.checked);
      wrap.hidden = !mostrar;
      wrap.style.display = mostrar ? '' : 'none';
    }
  }
  var chkEsMenorB05 = document.getElementById('chkEsMenorEdadB05');
  if (chkEsMenorB05) {
    chkEsMenorB05.addEventListener('change', actualizarTutorB05);
  }
  actualizarTutorB05();

  // ---------- Censo de contactos: fecha de vacunación solo si vacunado = Sí ----------
  // (comparacion_ficha_ficha, hallazgo difteria #6): "vacunado" y "fecha_vacunacion"
  // son columnas fijas de caso_contacto, no pasan por el motor .dep-wrap de arriba
  // (ese motor no está escopado por fila repetible). Caso puntual, mismo estilo ad-hoc
  // que el bloque de gestante.
  function actualizarFechaVacunacionContacto(fila) {
    var selectVacunado = fila.querySelector('select[name="contacto_vacunado[]"]');
    var campoFecha = fila.querySelector('input[name="contacto_fecha_vacunacion[]"]');
    if (!selectVacunado || !campoFecha) return;
    var contenedorFecha = campoFecha.closest('.field');
    var visible = selectVacunado.value === 'SI';
    if (contenedorFecha) contenedorFecha.hidden = !visible;
    if (!visible) campoFecha.value = '';
  }

  var listaContactos = document.querySelector('[data-lista="contactos"]');
  if (listaContactos) {
    listaContactos.querySelectorAll('.subrow').forEach(actualizarFechaVacunacionContacto);

    listaContactos.addEventListener('change', function (evento) {
      if (!evento.target.matches('select[name="contacto_vacunado[]"]')) return;
      var fila = evento.target.closest('.subrow');
      if (fila) actualizarFechaVacunacionContacto(fila);
    });

    // Filas agregadas dinámicamente (filas-dinamicas.js clona un <template>,
    // que no ejecuta <script> ni dispara 'change' por sí solo).
    new MutationObserver(function (mutaciones) {
      mutaciones.forEach(function (mutacion) {
        mutacion.addedNodes.forEach(function (nodo) {
          if (nodo.nodeType === 1 && nodo.classList && nodo.classList.contains('subrow')) {
            actualizarFechaVacunacionContacto(nodo);
          }
        });
      });
    }).observe(listaContactos, { childList: true });
  }

  if (selectorEnfermedad && contenedorClinico) {
    selectorEnfermedad.addEventListener('change', function () {
      var enfermedadId = selectorEnfermedad.value;
      contenedorClinico.style.opacity = '0.5';

      fetch('/casos/nuevo/secciones-clinicas?enfermedad_id=' + encodeURIComponent(enfermedadId))
        .then(function (resp) { return resp.json(); })
        .then(function (datos) {
          contenedorClinico.innerHTML = datos.html;
          contenedorClinico.style.opacity = '1';

          var captacionWrap = document.getElementById('notificacionCaptacionWrap');
          if (captacionWrap) {
            captacionWrap.hidden = !datos.usaCaptacion;
          }

          var pfaFechasWrap = document.getElementById('notificacionFechasPfaWrap');
          if (pfaFechasWrap) {
            pfaFechasWrap.hidden = (datos.cie10 !== 'A80');
          }

          var esB05 = (datos.cie10 === 'B05');
          var b05FechasWrap = document.getElementById('notificacionFechasB05Wrap');
          if (b05FechasWrap) {
            b05FechasWrap.hidden = !esB05;
          }

          var esO95 = (datos.cie10 === 'O95');
          var o95FechasWrap = document.getElementById('notificacionFechasO95Wrap');
          if (o95FechasWrap) {
            o95FechasWrap.hidden = !esO95;
          }

          document.querySelectorAll('.b05-field-wrap, .b05-elem').forEach(function(el) {
            el.hidden = !esB05;
          });
          document.querySelectorAll('.o95-elem').forEach(function(el) {
            el.hidden = !esO95;
          });
          document.querySelectorAll('.o95-hide').forEach(function(el) {
            el.hidden = esO95;
            el.style.display = esO95 ? 'none' : '';
          });
          actualizarTutorB05();
          actualizarEtapaFichaO95();

          var antecedentesCard = document.getElementById('cardAntecedentesEpidemiologicos');
          if (antecedentesCard) {
            antecedentesCard.hidden = esB05 ? true : !datos.tieneAntecedentesEpi;
          }

          var labBody = document.getElementById('seccionLaboratorioBody');
          var labCard = document.getElementById('seccionLaboratorioCard');
          if (labBody && datos.htmlMuestras) {
            labBody.innerHTML = datos.htmlMuestras;
          }
          if (labCard) {
            labCard.hidden = !datos.usaMuestras;
          }

          renumerarSeccionesSiguientes();
          if (window.SelectorBusqueda) {
            window.SelectorBusqueda.escanear(contenedorClinico);
            if (labBody) window.SelectorBusqueda.escanear(labBody);
          }
          if (typeof inicializarGruposSiNo === 'function') inicializarGruposSiNo();
          evaluarDependencias();

          var tagCie = document.getElementById('cieTag');
          if (tagCie) tagCie.textContent = 'CIE-10 · ' + datos.cie10;

          var opcion = selectorEnfermedad.selectedOptions[0];
          var esInmediata = opcion && opcion.dataset.tipoNotif === 'INMEDIATA';
          var textoTipoNotif = esInmediata ? 'Notificación inmediata' : 'Notificación semanal';
          var tagTipoNotif = document.getElementById('tipoNotifTag');
          if (tagTipoNotif) tagTipoNotif.textContent = textoTipoNotif;

          var resumenEnfermedad = document.getElementById('resumenEnfermedad');
          if (resumenEnfermedad && opcion) resumenEnfermedad.textContent = opcion.dataset.nombreCorto || opcion.text;
          var resumenTipoNotif = document.getElementById('resumenTipoNotif');
          if (resumenTipoNotif) resumenTipoNotif.textContent = esInmediata ? 'Inmediata' : 'Semanal';

          actualizarProgreso();
        })
        .catch(function () {
          contenedorClinico.style.opacity = '1';
          toast('No se pudo cargar el cuadro clínico. Intenta de nuevo.');
        });
    });
  }

  // Los nombres de establecimiento vienen en mayúsculas sostenidas del
  // padrón RENIPRESS (ver capitalizarNombre() en ayudantes.php, su
  // equivalente en servidor para el render inicial).
  function capitalizarNombre(texto) {
    return texto.toLowerCase().replace(/(^|[\s\-])\p{L}/gu, function (c) { return c.toUpperCase(); });
  }

  var selectEstablecimiento = document.querySelector('select[name="establecimiento_id"]');
  var resumenEstablecimiento = document.getElementById('resumenEstablecimiento');
  if (selectEstablecimiento && resumenEstablecimiento) {
    selectEstablecimiento.addEventListener('change', function () {
      var opcion = selectEstablecimiento.selectedOptions[0];
      resumenEstablecimiento.textContent = (opcion && opcion.value) ? capitalizarNombre(opcion.text) : '—';
    });
  }

  // Panel "Avance de la ficha": una entrada por cada .card.section real del
  // formulario (su número varía según la enfermedad), marcada "done" cuando
  // todos sus campos obligatorios (marcados con .req) tienen algún control
  // con valor, y "cur" cuando contiene el campo con foco.
  var formProgreso = document.querySelector('form');
  var progresoFicha = document.getElementById('progresoFicha');

  function campoTieneValor(campo) {
    // GRUPO_SI_NO / SI_NO_FECHA / CRONOLOGIA marcan cada fila como
    // "respondido" o "pendiente"; el campo solo cuenta como lleno cuando
    // todas sus filas están respondidas, no con que una lo esté.
    var filas = campo.querySelectorAll('.grupo-si-no-row');
    if (filas.length > 0) {
      return campo.querySelectorAll('.grupo-si-no-row.pendiente').length === 0;
    }

    var controles = campo.querySelectorAll('input, select, textarea');
    for (var i = 0; i < controles.length; i++) {
      var el = controles[i];
      if (el.type === 'checkbox' || el.type === 'radio') {
        if (el.checked) return true;
      } else if (el.value && el.value.trim() !== '') {
        return true;
      }
    }
    return false;
  }

  function seccionCompleta(seccion) {
    var campos = seccion.querySelectorAll('.field');
    for (var i = 0; i < campos.length; i++) {
      if (campos[i].querySelector('.req') && !campoTieneValor(campos[i])) return false;
    }
    return true;
  }

  function actualizarProgreso() {
    if (!progresoFicha || !formProgreso) return;
    var secciones = formProgreso.querySelectorAll('.card.section');
    var enFoco = document.activeElement ? document.activeElement.closest('.card.section') : null;

    var html = '';
    secciones.forEach(function (seccion) {
      var titulo = seccion.querySelector('h3');
      if (!titulo) return;
      var completa = seccionCompleta(seccion);
      var clase = 'pstep' + (completa ? ' done' : (seccion === enFoco ? ' cur' : ''));
      var marca = completa
        ? '<svg width="9" height="9" viewBox="0 0 9 9"><path d="M1.5 4.5 4 7l3.5-4.5" stroke="#fff" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        : '';
      html += '<div class="' + clase + '"><span class="pd">' + marca + '</span> ' + titulo.textContent.trim() + '</div>';
    });
    progresoFicha.innerHTML = html;
  }

  if (progresoFicha && formProgreso) {
    actualizarProgreso();
    formProgreso.addEventListener('input', actualizarProgreso);
    formProgreso.addEventListener('change', actualizarProgreso);
    formProgreso.addEventListener('focusin', actualizarProgreso);
    formProgreso.addEventListener('focusout', actualizarProgreso);
  }

  var fechaNac = document.getElementById('fechaNac');
  var edadCalculada = document.getElementById('edadCalculada');
  function calcularEdad() {
    if (!fechaNac || !edadCalculada) return;
    // fechaNac es <input type="date">: su .value siempre llega en aaaa-mm-dd,
    // sin importar el formato con que el navegador lo muestre al usuario.
    var partes = fechaNac.value.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!partes) { edadCalculada.textContent = '—'; return; }

    var nacimiento = new Date(partes[1], partes[2] - 1, partes[3]);
    var hoy = new Date();
    var edad = hoy.getFullYear() - nacimiento.getFullYear();
    var aunNoCumple = (hoy.getMonth() < nacimiento.getMonth()) ||
      (hoy.getMonth() === nacimiento.getMonth() && hoy.getDate() < nacimiento.getDate());
    if (aunNoCumple) edad--;

    edadCalculada.textContent = edad >= 0 ? edad + ' años' : '—';

    if (edad >= 0) {
      document.querySelectorAll('label.sym').forEach(function(lbl) {
        if (lbl.textContent.toLowerCase().indexOf('menor de edad') !== -1) {
          var chk = lbl.querySelector('input[type="checkbox"]');
          if (chk && chk.checked !== (edad < 18)) {
            chk.checked = (edad < 18);
            evaluarDependencias();
          }
        }
      });
    }
  }
  if (fechaNac) fechaNac.addEventListener('change', calcularEdad);
  calcularEdad();

  // ---------- Condición del paciente: EFECTIVO / DERECHOHABIENTE / PARTICULAR ----------
  var radiosCondicion = document.querySelectorAll('input[name="condicion"]');
  var gradoId = document.getElementById('gradoId');
  var cCategoria = document.getElementById('campoCategoria');
  var cCip = document.getElementById('campoCip');

  var panelesCondicion = {
    EFECTIVO: document.getElementById('p-efectivo'),
    DERECHOHABIENTE: document.getElementById('p-derecho'),
    PARTICULAR: document.getElementById('p-particular'),
  };
  var etiquetasCondicion = {
    EFECTIVO: 'efectivo', DERECHOHABIENTE: 'derechohabiente', PARTICULAR: 'particular',
  };

  function limpiarPanel(panel) {
    if (!panel) return false;
    var huboDatos = false;
    panel.querySelectorAll('input, select').forEach(function (el) {
      if (el.type === 'radio' || el.type === 'hidden') return;
      if (el.value !== '') {
        huboDatos = true;
        el.value = '';
        el.dispatchEvent(new Event('change', { bubbles: true }));
        if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(el);
      }
    });
    var titularId = document.getElementById('titularId');
    if (titularId && titularId.value !== '') { huboDatos = true; titularId.value = ''; }
    var titularHint = document.getElementById('titularEncontrado');
    if (titularHint) titularHint.textContent = 'Vincular al titular permite detectar conglomerados familiares. Es opcional: si no se conoce, puede dejarse vacío.';
    return huboDatos;
  }

  function actualizarCategoriaCip() {
    if (!gradoId || !cCategoria || !cCip) return;
    var selectedOption = gradoId.options[gradoId.selectedIndex];
    var nivel = selectedOption && selectedOption.value ? selectedOption.getAttribute('data-nivel') : '';

    if (nivel === 'CADETE' || nivel === 'ALUMNO') {
      cCategoria.hidden = true;
      cCip.hidden = false;
    } else if (nivel === 'EMPLEADO_CIVIL') {
      cCategoria.hidden = true;
      cCip.hidden = true;
    } else {
      cCategoria.hidden = false;
      cCip.hidden = false;
    }
  }

  var condicionAnterior = null;
  function actCondicion(esCambioDeUsuario) {
    var seleccionado = document.querySelector('input[name="condicion"]:checked');
    var valor = seleccionado ? seleccionado.value : 'PARTICULAR';

    if (esCambioDeUsuario && condicionAnterior && condicionAnterior !== valor) {
      var huboDatos = limpiarPanel(panelesCondicion[condicionAnterior]);
      if (huboDatos) toast('Se descartaron los datos de ' + etiquetasCondicion[condicionAnterior] + '.');
    }
    condicionAnterior = valor;

    Object.keys(panelesCondicion).forEach(function (key) {
      if (panelesCondicion[key]) panelesCondicion[key].hidden = (key !== valor);
    });

    if (valor === 'EFECTIVO') actualizarCategoriaCip();
  }

  if (radiosCondicion.length) {
    radiosCondicion.forEach(function (r) {
      r.addEventListener('change', function () { actCondicion(true); });
    });
    if (gradoId) gradoId.addEventListener('change', actualizarCategoriaCip);
    actCondicion(false);
  }

  // ---------- Buscar titular (derechohabiente) ----------
  var btnBuscarTitular = document.getElementById('btnBuscarTitular');
  if (btnBuscarTitular) {
    btnBuscarTitular.addEventListener('click', function () {
      var doc = document.getElementById('docTitular').value.trim();
      var hint = document.getElementById('titularEncontrado');
      var titularId = document.getElementById('titularId');
      if (!doc) { toast('Ingresa el documento del titular primero.'); return; }

      btnBuscarTitular.disabled = true;
      fetch('/casos/nuevo/titular?' + new URLSearchParams({ tipo_doc: 'DNI', num_doc: doc }).toString())
        .then(function (resp) { return resp.json(); })
        .then(function (datos) {
          if (datos.encontrado) {
            titularId.value = datos.titular_id;
            if (hint) { hint.textContent = 'Vinculado a: ' + datos.nombre; hint.style.color = 'var(--accent, #0E7A6E)'; }
          } else {
            titularId.value = '';
            if (hint) { hint.textContent = 'No se encontró un efectivo PNP con ese documento. Puedes dejarlo vacío.'; hint.style.color = 'var(--s-confirmado, #B23B3B)'; }
          }
        })
        .catch(function () {
          if (hint) { hint.textContent = 'No se pudo consultar el titular. Puedes dejarlo vacío.'; hint.style.color = 'var(--s-confirmado, #B23B3B)'; }
        })
        .then(function () { btnBuscarTitular.disabled = false; });
    });
  }

  // ---------- Buscar en padrón + RENIEC + duplicados ----------
  // Orden de búsqueda (lo resuelve el servidor): 1) padrón local, 2) si es
  // DNI de 8 dígitos y no está en el padrón, RENIEC. Nunca bloquea: si nada
  // responde, los campos quedan editables y sin error.
  var btnBuscar = document.getElementById('btnBuscarPaciente');
  var numDocInput = document.getElementById('numDoc');
  var buscandoHint = document.getElementById('buscandoPacienteHint');
  var ultimoDocBuscado = null;

  function buscarPaciente(manual) {
    var tipoDoc = document.getElementById('tipoDoc').value;
    var numDoc = numDocInput.value.trim();
    if (!numDoc) { toast('Ingresa el número de documento primero.'); return; }

    ultimoDocBuscado = tipoDoc + ':' + numDoc;
    if (buscandoHint) {
      buscandoHint.textContent = 'Consultando padrón y RENIEC…';
      buscandoHint.style.color = '';
      buscandoHint.hidden = false;
    }
    if (btnBuscar) btnBuscar.disabled = true;

    var parametros = new URLSearchParams({
      tipo_doc: tipoDoc,
      num_doc: numDoc,
      enfermedad_id: document.getElementById('diseaseSel') ? document.getElementById('diseaseSel').value : '',
      fecha_notif: document.getElementById('fechaNotif') ? document.getElementById('fechaNotif').value : '',
    });

    fetch('/casos/nuevo/paciente?' + parametros.toString())
      .then(function (resp) { return resp.json(); })
      .then(function (datos) { pintarBusquedaPaciente(datos, manual); })
      .catch(function () {
        if (buscandoHint) buscandoHint.hidden = true;
        if (manual) toast('No se pudo consultar el padrón ni RENIEC. Completa los datos manualmente.');
      })
      .then(function () {
        if (btnBuscar) btnBuscar.disabled = false;
      });
  }

  if (btnBuscar) {
    btnBuscar.addEventListener('click', function () { buscarPaciente(true); });
  }

  // Automático: al completar los 8 dígitos de un DNI, sin esperar al botón.
  // Silencioso si no encuentra nada (es lo esperado la primera vez que se
  // notifica a alguien): no bloquea ni interrumpe con avisos.
  if (numDocInput) {
    var tipoDocSelect = document.getElementById('tipoDoc');
    
    function aplicarMascaraDoc() {
      var tipo = tipoDocSelect.value;
      if (tipo === 'DNI' || tipo === 'PTP' || tipo === 'CPT') {
        numDocInput.value = numDocInput.value.replace(/\D/g, '').substring(0, 8);
      } else if (tipo === 'CE') {
        numDocInput.value = numDocInput.value.replace(/\D/g, '').substring(0, 9);
      }

      var apPaterno = document.getElementById('apellidoPaterno');
      var apMaterno = document.getElementById('apellidoMaterno');
      var nom = document.getElementById('nombres');
      if (apPaterno && apMaterno && nom) {
        var esDni = (tipo === 'DNI');
        apPaterno.readOnly = esDni;
        apMaterno.readOnly = esDni;
        nom.readOnly = esDni;
      }
    }

    tipoDocSelect.addEventListener('change', aplicarMascaraDoc);
    aplicarMascaraDoc();

    numDocInput.addEventListener('input', function () {
      aplicarMascaraDoc();
      var tipoDoc = tipoDocSelect.value;
      var numDoc = numDocInput.value.trim();
      if (tipoDoc !== 'DNI' || !/^\d{8}$/.test(numDoc)) return;
      if (ultimoDocBuscado === tipoDoc + ':' + numDoc) return; // ya buscado
      buscarPaciente(false);
    });

    ['apellidoPaterno', 'apellidoMaterno', 'nombres'].forEach(function(id) {
      var el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', function() {
          if (!this.readOnly) {
            this.value = this.value.replace(/[^a-zA-ZñÑáéíóúÁÉÍÓÚ\s]/g, '');
          }
        });
      }
    });
  }

  function iniciales(nombreCompleto) {
    var palabras = nombreCompleto.trim().split(/\s+/).filter(Boolean);
    if (!palabras.length) return '';
    if (palabras.length === 1) return palabras[0].substring(0, 2).toUpperCase();
    return (palabras[0].charAt(0) + palabras[palabras.length - 1].charAt(0)).toUpperCase();
  }

  function pintarBusquedaPaciente(datos, manual) {
    var dupeSlot = document.getElementById('dupeSlot');
    if (dupeSlot) {
      dupeSlot.innerHTML = '';
      if (datos.duplicado) {
        var d = datos.duplicado;

        var dupe = document.createElement('div');
        dupe.className = 'dupe';
        dupe.id = 'dupe';

        var icono = document.createElement('span');
        icono.className = 'di';
        icono.innerHTML = '<svg width="17" height="17" viewBox="0 0 17 17"><path d="M8.5 1.5 16 15H1L8.5 1.5Z" stroke="currentColor" stroke-width="1.3" fill="none" stroke-linejoin="round"/><path d="M8.5 6.5v3.5M8.5 12.3v.1" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>';

        var cuerpo = document.createElement('div');
        cuerpo.className = 'body';
        var negrita1 = document.createElement('b');
        negrita1.textContent = 'Posible duplicado.';
        var enlace = document.createElement('a');
        enlace.href = d.url;
        enlace.textContent = 'Ver ficha ' + d.codigo;
        cuerpo.appendChild(negrita1);
        cuerpo.appendChild(document.createTextNode(
          ' Ya existe una ficha (' + d.codigo + ') para este documento y esta enfermedad, notificada en la SE ' +
          d.semana_epi + ' · ' + d.anio_epi + ' desde ' + d.establecimiento_nombre + '. Revisa antes de continuar. '
        ));
        cuerpo.appendChild(enlace);

        dupe.appendChild(icono);
        dupe.appendChild(cuerpo);
        dupeSlot.appendChild(dupe);
      }
    }

    var found = document.getElementById('found');
    if (!datos.paciente) {
      if (found) found.style.display = 'none';

      var apPaterno = document.getElementById('apellidoPaterno');
      var apMaterno = document.getElementById('apellidoMaterno');
      var nom = document.getElementById('nombres');
      if (apPaterno && apMaterno && nom) {
        apPaterno.readOnly = false;
        apMaterno.readOnly = false;
        nom.readOnly = false;
      }

      if (buscandoHint) {
        buscandoHint.textContent = 'No se encontraron datos de paciente, registre manualmente.';
        buscandoHint.style.color = 'var(--s-confirmado, #B23B3B)';
        buscandoHint.hidden = false;
      }
      return;
    }

    var p = datos.paciente;
    var foundFuente = document.getElementById('foundFuente');
    if (foundFuente) {
      foundFuente.textContent = p.fuente === 'RENIEC' ? 'Autocompletado desde RENIEC' : 'Autocompletado del padrón';
    }
    document.getElementById('apellidoPaterno').value = p.apellido_paterno || '';
    document.getElementById('apellidoMaterno').value = p.apellido_materno || '';
    document.getElementById('nombres').value = p.nombres || '';
    document.getElementById('sexo').value = p.sexo || '';
    if (fechaNac) { fechaNac.value = p.fecha_nac || ''; calcularEdad(); }

    var condicion = p.condicion || 'PARTICULAR';
    var radioCondicion = document.querySelector('input[name="condicion"][value="' + condicion + '"]');
    if (radioCondicion) { radioCondicion.checked = true; actCondicion(false); }

    document.getElementById('cip').value = p.cip || '';
    document.getElementById('situacionPnp').value = p.situacion_pnp || '';
    document.getElementById('categoriaPnp').value = p.categoria_pnp || '';
    var gradoId = document.getElementById('gradoId');
    gradoId.value = p.grado_id || '';
    var vinculoTitular = document.getElementById('vinculoTitular');
    if (vinculoTitular) vinculoTitular.value = p.vinculo_titular || '';
    var titularId = document.getElementById('titularId');
    if (titularId) titularId.value = p.titular_id || '';
    if (window.SelectorBusqueda) {
      window.SelectorBusqueda.actualizar(gradoId);
      window.SelectorBusqueda.actualizar(document.getElementById('categoriaPnp'));
      window.SelectorBusqueda.actualizar(document.getElementById('situacionPnp'));
      if (vinculoTitular) window.SelectorBusqueda.actualizar(vinculoTitular);
    }
    actualizarCategoriaCip();

    if (p.departamento_id && typeof establecerUbigeo === 'function') {
      establecerUbigeo('pac-ubigeo', p.departamento_id, p.provincia_id, p.distrito_id);
    }

    if (found) {
      found.style.display = 'none';
    }

    if (buscandoHint) {
      buscandoHint.textContent = '✓ Datos obtenidos correctamente.';
      buscandoHint.style.color = 'var(--accent, #0E7A6E)';
      buscandoHint.hidden = false;
    }

    var apPaterno2 = document.getElementById('apellidoPaterno');
    var apMaterno2 = document.getElementById('apellidoMaterno');
    var nom2 = document.getElementById('nombres');
    if (apPaterno2 && apMaterno2 && nom2 && document.getElementById('tipoDoc').value === 'DNI') {
      apPaterno2.readOnly = true;
      apMaterno2.readOnly = true;
      nom2.readOnly = true;
    }
  }

  // ---------- Lógica de GRUPO_SI_NO y SI_NO_FECHA ----------
  window.inicializarGruposSiNo = function() {
    var grupos = document.querySelectorAll('.grupo-si-no-field');
    grupos.forEach(function(grupo) {
      if (grupo.dataset.inicializado) return;
      grupo.dataset.inicializado = '1';

      var esSiNoFecha = grupo.classList.contains('si-no-fecha-field');
      
      function actualizarEstadoFila(fila, inputSeleccionado) {
        var labels = fila.querySelectorAll('.seg-label');
        labels.forEach(function(l) { l.classList.remove('on'); });
        if (inputSeleccionado) {
          inputSeleccionado.closest('.seg-label').classList.add('on');
          fila.classList.remove('pendiente');
          fila.classList.add('respondido');
          fila.classList.remove('has-error');
          var depOtros = fila.querySelector('.otros-especificar-dep');
          var depFecha = fila.querySelector('.fecha-dep');
          if (inputSeleccionado.value === 'SI') {
            fila.classList.add('is-si');
            var labelText = fila.querySelector('.row-label');
            if (labelText) {
              labelText.style.color = 'var(--ink)';
              labelText.style.fontWeight = '500';
            }
            if (depOtros) depOtros.style.display = 'block';
            if (depFecha) depFecha.style.display = 'block';
          } else {
            fila.classList.remove('is-si');
            var labelText = fila.querySelector('.row-label');
            if (labelText) {
              labelText.style.color = 'var(--ink-2)';
              labelText.style.fontWeight = 'normal';
            }
            if (depOtros) {
              depOtros.style.display = 'none';
              var inputOtros = depOtros.querySelector('input');
              if (inputOtros) inputOtros.value = '';
            }
            if (depFecha) {
              depFecha.style.display = 'none';
              var inputFecha = depFecha.querySelector('input');
              if (inputFecha) inputFecha.value = '';
              var tablaCron = fila.querySelector('.tabla-cronologia-wrap');
              if (tablaCron) tablaCron.style.display = 'none';
            }
          }
        }
      }

      function actualizarCronologiaDiaCero(inputCero) {
        if (!inputCero) return;
        var cronWrap = inputCero.closest('.cronologia-field');
        if (!cronWrap) return;

        var tablaWrap = cronWrap.querySelector('.tabla-cronologia-wrap');
        if (!tablaWrap) return;

        var valCero = inputCero.value;
        if (!valCero) {
          tablaWrap.style.display = 'none';
          return;
        }

        var partes = valCero.split('-');
        if (partes.length !== 3) {
          tablaWrap.style.display = 'none';
          return;
        }

        var y = parseInt(partes[0], 10);
        var m = parseInt(partes[1], 10) - 1;
        var d = parseInt(partes[2], 10);
        var f0 = new Date(y, m, d);

        if (isNaN(f0.getTime())) {
          tablaWrap.style.display = 'none';
          return;
        }

        var fMin = new Date(y, m, d - 10);
        var fMax10 = new Date(y, m, d + 10);

        var hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        var fMax = fMax10 < hoy ? fMax10 : hoy;

        function pad(n) { return String(n).padStart(2, '0'); }
        var strMin = fMin.getFullYear() + '-' + pad(fMin.getMonth() + 1) + '-' + pad(fMin.getDate());
        var strMax = fMax.getFullYear() + '-' + pad(fMax.getMonth() + 1) + '-' + pad(fMax.getDate());

        var inputsFechas = tablaWrap.querySelectorAll('input[type="date"]');
        inputsFechas.forEach(function(inp) {
          inp.min = strMin;
          inp.max = strMax;

          if (inp.value) {
            if (inp.value < strMin || inp.value > strMax) {
              inp.value = '';
            }
          }
        });

        tablaWrap.style.display = 'block';
        actualizarGraficoCronologia(cronWrap);
      }

      function actualizarGraficoCronologia(cronWrap) {
        if (!cronWrap) return;
        var chartWrap = cronWrap.querySelector('.cronologia-grafico-wrap');
        if (!chartWrap) return;

        var inputCero = cronWrap.querySelector('.input-fecha-dia-cero');
        var valCero = inputCero ? inputCero.value : '';
        if (!valCero) {
          chartWrap.style.display = 'none';
          return;
        }

        var p0 = valCero.split('-');
        if (p0.length !== 3) { chartWrap.style.display = 'none'; return; }
        var f0 = new Date(parseInt(p0[0], 10), parseInt(p0[1], 10) - 1, parseInt(p0[2], 10));
        if (isNaN(f0.getTime())) { chartWrap.style.display = 'none'; return; }

        chartWrap.style.display = 'block';

        var rows = cronWrap.querySelectorAll('.tabla-cronologia tbody tr[data-signo-key]');
        rows.forEach(function(row) {
          var key = row.getAttribute('data-signo-key');
          var inpInicio = row.querySelector('.cron-fecha-inicio');
          var inpFin = row.querySelector('.cron-fecha-fin');
          
          var valIni = inpInicio ? inpInicio.value : '';
          var valFin = inpFin ? inpFin.value : '';

          var dayIni = null;
          var dayFin = null;

          if (valIni) {
            var pi = valIni.split('-');
            if (pi.length === 3) {
              var fi = new Date(parseInt(pi[0], 10), parseInt(pi[1], 10) - 1, parseInt(pi[2], 10));
              if (!isNaN(fi.getTime())) {
                dayIni = Math.round((fi - f0) / (1000 * 60 * 60 * 24));
              }
            }
          }

          if (valFin) {
            var pf = valFin.split('-');
            if (pf.length === 3) {
              var ff = new Date(parseInt(pf[0], 10), parseInt(pf[1], 10) - 1, parseInt(pf[2], 10));
              if (!isNaN(ff.getTime())) {
                dayFin = Math.round((ff - f0) / (1000 * 60 * 60 * 24));
              }
            }
          }

          if (dayIni !== null && dayFin === null) dayFin = dayIni;
          if (dayFin !== null && dayIni === null) dayIni = dayIni;

          var chartRow = chartWrap.querySelector('.chart-row[data-signo-key="' + key + '"]');
          if (!chartRow) return;

          var cells = chartRow.querySelectorAll('.chart-cell[data-day]');
          cells.forEach(function(cell) {
            var day = parseInt(cell.getAttribute('data-day'), 10);
            if (dayIni !== null && dayFin !== null && day >= dayIni && day <= dayFin) {
              cell.classList.add('in-range');
              cell.classList.toggle('range-start', day === dayIni);
              cell.classList.toggle('range-end', day === dayFin);
            } else {
              cell.classList.remove('in-range', 'range-start', 'range-end');
            }
          });
        });
      }

      document.addEventListener('input', function(e) {
        if (e.target) {
          if (e.target.classList.contains('input-fecha-dia-cero')) {
            actualizarCronologiaDiaCero(e.target);
          } else if (e.target.classList.contains('cron-fecha-inicio') || e.target.classList.contains('cron-fecha-fin')) {
            var cw = e.target.closest('.cronologia-field');
            if (cw) actualizarGraficoCronologia(cw);
          }
        }
      });
      document.addEventListener('change', function(e) {
        if (e.target) {
          if (e.target.classList.contains('input-fecha-dia-cero')) {
            actualizarCronologiaDiaCero(e.target);
          } else if (e.target.classList.contains('cron-fecha-inicio') || e.target.classList.contains('cron-fecha-fin')) {
            var cw = e.target.closest('.cronologia-field');
            if (cw) actualizarGraficoCronologia(cw);
          }
        }
      });

      // ---------- Exantema Body Map Interaction ----------
      document.addEventListener('click', function(e) {
        var bodyPart = e.target.closest('.exantema-body-map-section .body-part');
        if (!bodyPart) return;

        var region = bodyPart.getAttribute('data-region');
        var cardDia = bodyPart.closest('.card-dia-exantema');
        if (!region || !cardDia) return;

        var chk = cardDia.querySelector('.chk-zona-input[data-region="' + region + '"]');
        if (!chk) return;

        chk.checked = !chk.checked;
        chk.dispatchEvent(new Event('change', { bubbles: true }));
      });

      document.addEventListener('change', function(e) {
        if (e.target && e.target.classList.contains('chk-zona-input')) {
          var chk = e.target;
          var region = chk.getAttribute('data-region');
          var cardDia = chk.closest('.card-dia-exantema');
          if (!cardDia || !region) return;

          var bodyPart = cardDia.querySelector('.body-part[data-region="' + region + '"]');
          if (bodyPart) {
            bodyPart.classList.toggle('sombreado', chk.checked);
          }

          var itemLabel = chk.closest('.chk-zona-item');
          if (itemLabel) {
            itemLabel.classList.toggle('active', chk.checked);
          }
        }
      });

      function actualizarContador() {
        if (esSiNoFecha) return;
        var totalInput = grupo.querySelectorAll('.grupo-si-no-row').length;
        if (totalInput === 0) return;
        var respondidas = grupo.querySelectorAll('.grupo-si-no-row.respondido').length;
        var contador = grupo.querySelector('.contador-grupo');
        var btnMarcarNo = grupo.querySelector('.btn-marcar-no');
        
        if (contador) {
          contador.querySelector('.respondidas').textContent = respondidas;
          if (respondidas === totalInput) {
            contador.style.color = 'var(--accent)';
          } else {
            contador.style.color = '';
          }
        }
        if (btnMarcarNo) {
          btnMarcarNo.style.display = respondidas === totalInput ? 'none' : '';
        }
      }

      var radios = grupo.querySelectorAll('input[type="radio"]');
      radios.forEach(function(radio) {
        radio.addEventListener('change', function() {
          var fila = this.closest('.grupo-si-no-row');
          actualizarEstadoFila(fila, this);
          actualizarContador();
        });
      });

      var btnMarcarNo = grupo.querySelector('.btn-marcar-no');
      if (btnMarcarNo) {
        btnMarcarNo.addEventListener('click', function() {
          var pendientes = grupo.querySelectorAll('.grupo-si-no-row.pendiente');
          if (pendientes.length === 0) return;
          
          var cambiados = [];
          pendientes.forEach(function(fila) {
            var radioNo = fila.querySelector('input[type="radio"][value="NO"]');
            if (radioNo) {
              radioNo.checked = true;
              actualizarEstadoFila(fila, radioNo);
              cambiados.push(radioNo);
            }
          });
          actualizarContador();

          // Simple undo toast
          var undoToast = document.createElement('div');
          undoToast.style.position = 'fixed';
          undoToast.style.bottom = '24px';
          undoToast.style.left = '50%';
          undoToast.style.transform = 'translateX(-50%)';
          undoToast.style.background = 'var(--ink)';
          undoToast.style.color = 'white';
          undoToast.style.padding = '12px 16px';
          undoToast.style.borderRadius = '8px';
          undoToast.style.zIndex = '9999';
          undoToast.style.display = 'flex';
          undoToast.style.alignItems = 'center';
          undoToast.style.gap = '16px';
          undoToast.innerHTML = '<span>Se marcaron ' + cambiados.length + ' ítems como No.</span> <button type="button" style="background:none; border:none; color:var(--accent); font-weight:600; cursor:pointer; padding:0;">Deshacer</button>';
          
          var deshacerBtn = undoToast.querySelector('button');
          var timer = setTimeout(function() { if (undoToast.parentNode) undoToast.remove(); }, 5000);
          
          deshacerBtn.addEventListener('click', function() {
            clearTimeout(timer);
            cambiados.forEach(function(radio) {
              radio.checked = false;
              var fila = radio.closest('.grupo-si-no-row');
              fila.classList.remove('respondido');
              fila.classList.add('pendiente');
              fila.classList.remove('is-si');
              var labels = fila.querySelectorAll('.seg-label');
              labels.forEach(function(l) { l.classList.remove('on'); });
              var labelText = fila.querySelector('.row-label');
              if (labelText) {
                labelText.style.color = 'var(--ink)';
                labelText.style.fontWeight = 'normal';
              }
            });
            actualizarContador();
            undoToast.remove();
          });
          
          document.body.appendChild(undoToast);
        });
      }

      // Navegación por teclado
      var filas = grupo.querySelectorAll('.grupo-si-no-row');
      filas.forEach(function(fila, index) {
        fila.addEventListener('keydown', function(e) {
          var key = e.key.toLowerCase();
          var targetVal = null;
          if (key === 's') targetVal = 'SI';
          if (key === 'n') targetVal = 'NO';
          if (key === 'i') targetVal = 'IGNORADO';
          
          if (targetVal) {
            var radio = fila.querySelector('input[type="radio"][value="' + targetVal + '"]');
            if (radio) {
              radio.checked = true;
              radio.dispatchEvent(new Event('change'));
              
              // Avanzar a la siguiente fila
              if (index + 1 < filas.length) {
                filas[index + 1].focus();
              } else {
                // Si es el último, avanzar al siguiente grupo si existe
                var nextRow = null;
                var currentGrupoIdx = Array.from(grupos).indexOf(grupo);
                if (currentGrupoIdx >= 0 && currentGrupoIdx + 1 < grupos.length) {
                  var nextGrupoRows = grupos[currentGrupoIdx + 1].querySelectorAll('.grupo-si-no-row');
                  if (nextGrupoRows.length > 0) nextRow = nextGrupoRows[0];
                }
                if (nextRow) nextRow.focus();
              }
              e.preventDefault();
            }
          }
          
          if (key === 'arrowright' || key === 'arrowleft') {
            var checked = fila.querySelector('input[type="radio"]:checked');
            var vals = ['SI', 'NO', 'IGNORADO'];
            var currentIdx = checked ? vals.indexOf(checked.value) : -1;
            var nextIdx = currentIdx;
            if (key === 'arrowright') nextIdx = currentIdx < 2 ? currentIdx + 1 : 0;
            if (key === 'arrowleft') nextIdx = currentIdx > 0 ? currentIdx - 1 : 2;
            
            var targetRadio = fila.querySelector('input[type="radio"][value="' + vals[nextIdx] + '"]');
            if (targetRadio) {
              targetRadio.checked = true;
              targetRadio.dispatchEvent(new Event('change'));
            }
            e.preventDefault();
          }
        });
      });
    });
  };

  inicializarGruposSiNo();

  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function(e) {
      var grupos = document.querySelectorAll('.grupo-si-no-field');
      var firstPending = null;
      var hasError = false;
      
      grupos.forEach(function(grupo) {
        var req = grupo.querySelector('.req');
        if (req) {
          // El campo es obligatorio
          var pendientes = grupo.querySelectorAll('.grupo-si-no-row.pendiente');
          if (pendientes.length > 0) {
            hasError = true;
            pendientes.forEach(function(p) {
              p.classList.add('has-error');
              if (!firstPending) firstPending = p;
            });
            var errHint = grupo.querySelector('.hint.err');
            if (!errHint) {
              errHint = document.createElement('span');
              errHint.className = 'hint err';
              errHint.style.marginTop = '8px';
              errHint.style.display = 'block';
              grupo.appendChild(errHint);
            }
            errHint.textContent = 'Faltan ' + pendientes.length + ' signos por responder.';
          }
        }
      });
      
      if (hasError && firstPending) {
        e.preventDefault();
        firstPending.scrollIntoView({ behavior: 'smooth', block: 'center' });
        firstPending.focus();
      }
    });
  }

  // B05 Laboratorio: alternar visibilidad de campos PCR/Genotipo vs IgM/IgG según Tipo de Muestra
  function actualizarCamposMuestraB05(subrow) {
    var selectTipo = subrow.querySelector('.b05-select-tipo-muestra');
    if (!selectTipo) return;
    var val = selectTipo.value;

    var pcrGroup = subrow.querySelectorAll('.b05-pcr-group');
    var seroGroup = subrow.querySelectorAll('.b05-serologia-group');

    if (val === 'SUERO') {
      pcrGroup.forEach(function(el) { el.style.display = 'none'; });
      seroGroup.forEach(function(el) { el.style.display = ''; });
    } else if (val === 'HNF_FAR' || val === 'ORINA') {
      pcrGroup.forEach(function(el) { el.style.display = ''; });
      seroGroup.forEach(function(el) { el.style.display = 'none'; });
    } else {
      pcrGroup.forEach(function(el) { el.style.display = 'none'; });
      seroGroup.forEach(function(el) { el.style.display = 'none'; });
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('b05-select-tipo-muestra')) {
      var subrow = e.target.closest('.subrow');
      if (subrow) {
        actualizarCamposMuestraB05(subrow);
      }
    }
  });

  document.querySelectorAll('.b05-subrow-wrapper').forEach(function(subrow) {
    actualizarCamposMuestraB05(subrow);
  });

  // B05 Antecedentes vacunales: Mostrar/ocultar y desplegar vacunas según Estado vacunal
  function actualizarBloqueVacunasB05() {
    var wrapperVacunas = document.getElementById('b05-wrapper-vacunas-registradas');
    if (!wrapperVacunas) return;

    var val = leerValorCampoPorNombre('campo_16052');
    var esVacunado = (val === 'VACUNADO' || val === 'VACUNADO_INCOMPLETO');

    if (esVacunado) {
      wrapperVacunas.hidden = false;
      wrapperVacunas.style.display = '';

      var subrows = wrapperVacunas.querySelector('.subrows');
      if (subrows && subrows.children.length === 0) {
        var btnAgregar = wrapperVacunas.querySelector('.agregar-fila[data-lista="vacunas"]');
        if (btnAgregar) {
          btnAgregar.click();
        }
      }
    } else {
      wrapperVacunas.hidden = true;
      wrapperVacunas.style.display = 'none';
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'campo_16052') {
      actualizarBloqueVacunasB05();
    }
  });

  actualizarBloqueVacunasB05();

  // B05 Lugar probable de infección: Mostrar/ocultar y desplegar viajes según Paciente viajó
  function actualizarBloqueViajesB05() {
    var wrapperViajes = document.getElementById('b05-wrapper-viajes-registrados');
    if (!wrapperViajes) return;

    var val = leerValorCampoPorNombre('campo_16095');
    var esViajo = (val === 'SI');

    if (esViajo) {
      wrapperViajes.hidden = false;
      wrapperViajes.style.display = '';

      var subrows = wrapperViajes.querySelector('.subrows');
      if (subrows && subrows.children.length === 0) {
        var btnAgregar = wrapperViajes.querySelector('.agregar-fila[data-lista="viajes"]');
        if (btnAgregar) {
          btnAgregar.click();
        }
      }
    } else {
      wrapperViajes.hidden = true;
      wrapperViajes.style.display = 'none';
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'campo_16095') {
      actualizarBloqueViajesB05();
    }
  });

  document.addEventListener('click', function(e) {
    if (e.target && e.target.closest('.quitar-fila')) {
      setTimeout(function() {
        actualizarBloqueVacunasB05();
        actualizarBloqueViajesB05();
      }, 50);
    }
  });

  actualizarBloqueViajesB05();

  function obtenerCie10Actual() {
    var enfermedadSel = document.getElementById('diseaseSel');
    if (enfermedadSel && enfermedadSel.selectedOptions[0]) {
      var opt = enfermedadSel.selectedOptions[0];
      var cie = opt.getAttribute('data-cie10');
      if (cie) return cie.trim().toUpperCase();
      var txt = opt.text || '';
      var m = txt.match(/\(([A-Z0-9\.]+)\)/);
      if (m && m[1]) return m[1].trim().toUpperCase();
    }
    var tagCie = document.getElementById('cieTag');
    if (tagCie) {
      var text = tagCie.textContent || '';
      var m = text.match(/CIE-10\s*·\s*([A-Z0-9\.]+)/i);
      if (m && m[1]) return m[1].trim().toUpperCase();
    }
    return '';
  }

  // O95 Conmutación de etapas: Anexo 1 (Notificación inmediata) vs Anexo 2 (Investigación epidemiológica)
  function actualizarEtapaFichaO95() {
    var cie10 = obtenerCie10Actual();
    var esO95 = (cie10 === 'O95');

    var o95FechasWrap = document.getElementById('notificacionFechasO95Wrap');
    if (o95FechasWrap) {
      o95FechasWrap.hidden = !esO95;
      o95FechasWrap.style.display = esO95 ? '' : 'none';
    }

    // Ocultar Sexo, Celular, Nacionalidad, Localidad cuando es O95
    document.querySelectorAll('.o95-hide').forEach(function(el) {
      el.hidden = esO95;
      el.style.display = esO95 ? 'none' : '';
    });

    // Mostrar elementos exclusivos de O95 (como N.° de historia clínica) solo cuando es O95
    document.querySelectorAll('.o95-elem').forEach(function(el) {
      if (el.id !== 'notificacionFechasO95Wrap') {
        el.hidden = !esO95;
        el.style.display = esO95 ? '' : 'none';
      }
    });

    // Ocultar Gestante cuando es O95
    var campoGestante = document.getElementById('campoGestante');
    var campoSemanas = document.getElementById('campoSemanasGestacion');
    if (esO95) {
      if (campoGestante) { campoGestante.hidden = true; campoGestante.style.display = 'none'; }
      if (campoSemanas) { campoSemanas.hidden = true; campoSemanas.style.display = 'none'; }
    }

    if (!esO95) {
      // Para enfermedades generales: mostrar etnia general, ocultar grupo/pueblo O95
      document.querySelectorAll('.o95-anexo-2-section, .o95-anexo-2-elem').forEach(function(sec) {
        sec.hidden = true;
        sec.style.display = 'none';
      });
      return;
    }

    // Para O95:
    var radioAnexo2 = document.querySelector('input[name="o95_tipo_ficha"][value="ANEXO_2"]');
    var esAnexo2 = radioAnexo2 && radioAnexo2.checked;

    document.querySelectorAll('.o95-anexo-2-section, .o95-anexo-2-elem').forEach(function(sec) {
      sec.hidden = !esAnexo2;
      sec.style.display = esAnexo2 ? '' : 'none';
    });

    actualizarPuebloEtnicoO95();

    if (typeof renumerarSeccionesSiguientes === 'function') {
      renumerarSeccionesSiguientes();
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && e.target.name === 'o95_tipo_ficha') {
      actualizarEtapaFichaO95();
    }
  });

  actualizarEtapaFichaO95();

  // Dependencia de Grupo étnico -> Etnia / Pueblo étnico en O95
  var O95_MAPA_ETNIAS = {
    'ANDINO': ['Quechua', 'Aymara', 'Jaqaru', 'Uro', 'Otro'],
    'INDIGENA_AMAZONICO': ['Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut', 'Otro'],
    'INDÍGENA AMAZÓNICO': ['Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut', 'Otro'],
    'AFROPERUANO': ['Afroperuano'],
    'AFRODESCENDIENTE': ['Afroperuano'],
    'MESTIZO': ['No aplica'],
    'ASIATICO_DESCENDIENTE': ['Chino-peruano', 'Japonés-peruano', 'Otro'],
    'ASIÁTICO DESCENDIENTE': ['Chino-peruano', 'Japonés-peruano', 'Otro'],
    'OTRO': ['Otro']
  };

  function normalizarClaveGrupo(val) {
    if (!val) return '';
    return val.toString().trim().toUpperCase()
      .replace(/Á/g, 'A').replace(/É/g, 'E').replace(/Í/g, 'I').replace(/Ó/g, 'O').replace(/Ú/g, 'U')
      .replace(/\s+/g, '_');
  }

  function actualizarPuebloEtnicoO95() {
    var grupoSel = document.getElementById('o95GrupoEtnicoSel');
    var puebloSel = document.getElementById('o95PuebloEtnicoSel');
    if (!grupoSel || !puebloSel) return;

    var grupoRaw = grupoSel.value || '';
    var grupoNorm = normalizarClaveGrupo(grupoRaw);
    var valorPrevio = puebloSel.getAttribute('data-valor-actual') || puebloSel.value || '';

    puebloSel.innerHTML = '<option value="">Seleccionar…</option>';

    var opciones = O95_MAPA_ETNIAS[grupoRaw] || O95_MAPA_ETNIAS[grupoNorm] || null;

    if (opciones && opciones.length > 0) {
      opciones.forEach(function(opt) {
        var el = document.createElement('option');
        el.value = opt;
        el.textContent = opt;
        if (opt === valorPrevio) {
          el.selected = true;
        }
        puebloSel.appendChild(el);
      });
      if (opciones.length === 1 && !valorPrevio) {
        puebloSel.value = opciones[0];
      }
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && (e.target.id === 'o95GrupoEtnicoSel' || e.target.name === 'campo_16110')) {
      puebloSelAttrReset();
      actualizarPuebloEtnicoO95();
    }
  });

  function puebloSelAttrReset() {
    var puebloSel = document.getElementById('o95PuebloEtnicoSel');
    if (puebloSel) puebloSel.removeAttribute('data-valor-actual');
  }

  function actualizarOtrosCamposO95() {
    var selIdioma = document.getElementById('o95IdiomaSel');
    var wrapIdiomaOtra = document.getElementById('campoIdiomaOtraO95');
    if (selIdioma && wrapIdiomaOtra) {
      var esOtra = (selIdioma.value === 'OTRA');
      wrapIdiomaOtra.hidden = !esOtra;
      wrapIdiomaOtra.style.display = esOtra ? '' : 'none';
    }

    var selSeguro = document.getElementById('o95TipoSeguroSel');
    var wrapSeguroOtro = document.getElementById('campoSeguroOtroO95');
    if (selSeguro && wrapSeguroOtro) {
      var esOtroS = (selSeguro.value === 'OTROS');
      wrapSeguroOtro.hidden = !esOtroS;
      wrapSeguroOtro.style.display = esOtroS ? '' : 'none';
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && (e.target.id === 'o95IdiomaSel' || e.target.id === 'o95TipoSeguroSel')) {
      actualizarOtrosCamposO95();
    }
  });

  actualizarPuebloEtnicoO95();
  actualizarOtrosCamposO95();

  // B05 Clasificación final -> Sincronizar clasificación del caso al final del formulario
  function actualizarClasificacionCasoB05() {
    var selectClasif = document.querySelector('[name="campo_16084"]');
    if (!selectClasif) {
      selectClasif = Array.from(document.querySelectorAll('select')).find(function(sel) {
        return Array.from(sel.options).some(function(opt) { return opt.value === 'SARAMPION'; });
      });
    }
    if (!selectClasif) return;

    var valClasif = selectClasif.value;
    var radioDescartado = document.querySelector('input[name="clasificacion"][value="DESCARTADO"]');
    var radioConfirmado = document.querySelector('input[name="clasificacion"][value="CONFIRMADO"]');

    if (valClasif === 'DESCARTADO') {
      if (radioDescartado) radioDescartado.checked = true;
    } else if (valClasif === 'SARAMPION' || valClasif === 'RUBEOLA') {
      if (radioConfirmado) radioConfirmado.checked = true;
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target && (e.target.name === 'campo_16084' || (e.target.tagName === 'SELECT' && e.target.querySelector('option[value="SARAMPION"]')))) {
      actualizarClasificacionCasoB05();
    }
  });

  actualizarClasificacionCasoB05();

  // B05 Cálculos automáticos para Sección VIII (Investigación epidemiológica)
  function calcularTotalesB05() {
    // 1. Total casas = Casas abiertas + Casas cerradas + Casas abandonadas
    var inputAbiertas = document.querySelector('[name="campo_16072"]');
    var inputCerradas = document.querySelector('[name="campo_16073"]');
    var inputAbandonadas = document.querySelector('[name="campo_16074"]');
    var inputTotalCasas = document.querySelector('[name="campo_16075"]');

    if (inputTotalCasas && (inputAbiertas || inputCerradas || inputAbandonadas)) {
      var abiertas = parseInt(inputAbiertas ? inputAbiertas.value : 0, 10) || 0;
      var cerradas = parseInt(inputCerradas ? inputCerradas.value : 0, 10) || 0;
      var abandonadas = parseInt(inputAbandonadas ? inputAbandonadas.value : 0, 10) || 0;

      var tieneCasas = (inputAbiertas && inputAbiertas.value !== '') || (inputCerradas && inputCerradas.value !== '') || (inputAbandonadas && inputAbandonadas.value !== '');
      if (tieneCasas) {
        inputTotalCasas.value = abiertas + cerradas + abandonadas;
        inputTotalCasas.readOnly = true;
      } else {
        inputTotalCasas.readOnly = false;
      }
    }

    // 2. Total VAC = (<1 año) + (1-4 años) + (5-14 años) + (>15 años)
    var inputVacMenor1 = document.querySelector('[name="campo_16104"]');
    var inputVac14 = document.querySelector('[name="campo_16105"]');
    var inputVac514 = document.querySelector('[name="campo_16106"]');
    var inputVacMayor15 = document.querySelector('[name="campo_16107"]');
    var inputTotalVac = document.querySelector('[name="campo_16080"]');

    if (inputTotalVac && (inputVacMenor1 || inputVac14 || inputVac514 || inputVacMayor15)) {
      var vMenor1 = parseInt(inputVacMenor1 ? inputVacMenor1.value : 0, 10) || 0;
      var v14 = parseInt(inputVac14 ? inputVac14.value : 0, 10) || 0;
      var v514 = parseInt(inputVac514 ? inputVac514.value : 0, 10) || 0;
      var vMayor15 = parseInt(inputVacMayor15 ? inputVacMayor15.value : 0, 10) || 0;

      var tieneVac = (inputVacMenor1 && inputVacMenor1.value !== '') || (inputVac14 && inputVac14.value !== '') || (inputVac514 && inputVac514.value !== '') || (inputVacMayor15 && inputVacMayor15.value !== '');
      if (tieneVac) {
        inputTotalVac.value = vMenor1 + v14 + v514 + vMayor15;
        inputTotalVac.readOnly = true;
      } else {
        inputTotalVac.readOnly = false;
      }
    }
  }

  document.addEventListener('input', function(e) {
    if (e.target && e.target.name && (
      e.target.name === 'campo_16072' || e.target.name === 'campo_16073' || e.target.name === 'campo_16074' ||
      e.target.name === 'campo_16104' || e.target.name === 'campo_16105' || e.target.name === 'campo_16106' || e.target.name === 'campo_16107'
    )) {
      calcularTotalesB05();
    }
  });

  document.addEventListener('change', function(e) {
    if (e.target && e.target.name && (
      e.target.name === 'campo_16072' || e.target.name === 'campo_16073' || e.target.name === 'campo_16074' ||
      e.target.name === 'campo_16104' || e.target.name === 'campo_16105' || e.target.name === 'campo_16106' || e.target.name === 'campo_16107'
    )) {
      calcularTotalesB05();
    }
  });

  calcularTotalesB05();
});



