document.addEventListener('DOMContentLoaded', function () {
  var selectorEnfermedad = document.getElementById('diseaseSel');
  var contenedorClinico = document.getElementById('secciones-clinicas');

  // Entrada F: mismas etiquetas que $etiquetasUnidadEdad en
  // datos-paciente-nucleo.php -- repuebla el <select> de unidad al cambiar
  // de ficha en "Nueva ficha" (ver datos.unidadesEdad más abajo).
  var ETIQUETAS_UNIDAD_EDAD = { ANIOS: 'Años', MESES: 'Meses', DIAS: 'Días', HORAS: 'Horas', MINUTOS: 'Minutos' };

  // ---------- Peticion 2, Fase 4: resolucion de campos por clave ----------
  // cargar_fichas.php regenera campo_def.id en cada recarga; ficha.js no
  // puede seguir teniendo el numero pegado en el codigo. El servidor emite
  // <script type="application/json" id="mapaCampos"> con [clave => name] de
  // la enfermedad activa (ver campos-por-clave.php), y este mismo mapa se
  // reemplaza entero cada vez que cambia de enfermedad (mas abajo, en el
  // .then() del fetch de secciones-clinicas) para no quedarse con el de la
  // ficha anterior.
  var mapaCampos = {};
  var mapaCamposEl = document.getElementById('mapaCampos');
  if (mapaCamposEl) {
    try {
      mapaCampos = JSON.parse(mapaCamposEl.textContent || '{}');
    } catch (e) {
      mapaCampos = {};
    }
  }

  function campoPorClave(clave) {
    return mapaCampos[clave] || '';
  }

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
    var tagCie = document.getElementById('cieTag');
    var cieText = tagCie ? tagCie.textContent : '';
    if (cieText.indexOf('A80') === -1) return;

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

  function actualizarEtniaOtra(focusInp) {
    var sel = document.querySelector('[name="etnia"], #etniaSel');
    var wrap = document.getElementById('campoEtniaOtraWrap');
    var inp = document.getElementById('etniaOtraInput');
    if (!sel || !wrap) return;
    var esOtro = (sel.value === 'OTRO');
    wrap.hidden = !esOtro;
    wrap.style.display = esOtro ? '' : 'none';
    if (inp) {
      inp.disabled = !esOtro;
      if (!esOtro) {
        inp.value = '';
      } else if (focusInp === true) {
        inp.focus();
      }
    }
  }
  actualizarEtniaOtra(false);

  function actualizarGestante() {
    var campoGestante = document.getElementById('campoGestante');
    if (!campoGestante) return;

    var sexoSel = document.querySelector('[name="sexo"], #sexo');
    var gestanteSel = document.getElementById('gestanteSel');

    var enfermedadSel = document.getElementById('diseaseSel');
    var opcionEnfermedad = enfermedadSel && enfermedadSel.selectedOptions[0];
    var textoEnfermedad = opcionEnfermedad ? (opcionEnfermedad.text || '') : '';
    var tagCie = document.getElementById('cieTag');
    var textoCie = tagCie ? (tagCie.textContent || '') : '';
    
    var campoSemanas = document.getElementById('campoSemanasGestacion');
    var campoTrimestre = document.getElementById('campoTrimestreGestacion');

    if (textoEnfermedad.indexOf('A80') !== -1 || textoCie.indexOf('A80') !== -1 || textoEnfermedad.indexOf('O95') !== -1 || textoCie.indexOf('O95') !== -1) {
      campoGestante.hidden = true;
      campoGestante.style.display = 'none';
      if (campoSemanas) { campoSemanas.hidden = true; campoSemanas.style.display = 'none'; }
      if (campoTrimestre) { campoTrimestre.hidden = true; campoTrimestre.style.display = 'none'; }
      if (gestanteSel) gestanteSel.value = '';
      return;
    }

    var valSexo = sexoSel ? (sexoSel.value || '') : '';
    var esFemenino = (valSexo === 'F' || valSexo === 'FEMENINO');

    campoGestante.hidden = !esFemenino;
    campoGestante.style.display = esFemenino ? '' : 'none';

    if (!esFemenino && gestanteSel) gestanteSel.value = '';

    var esGestante = esFemenino && gestanteSel && gestanteSel.value === '1';
    var esB26 = (textoEnfermedad.indexOf('B26') !== -1 || textoCie.indexOf('B26') !== -1);

    if (campoSemanas) {
      campoSemanas.hidden = !esGestante || esB26;
      campoSemanas.style.display = (!esGestante || esB26) ? 'none' : '';
    }
    if (campoTrimestre) {
      campoTrimestre.hidden = !esGestante || !esB26;
      campoTrimestre.style.display = (!esGestante || !esB26) ? 'none' : '';
    }

    var wrapPartoB05 = document.getElementById('wrapLugarPartoB05');
    if (wrapPartoB05) {
      wrapPartoB05.hidden = !esGestante;
      wrapPartoB05.style.display = esGestante ? '' : 'none';
    }

    if (!esGestante) {
      var semanas = document.getElementById('semanasGestacion');
      if (semanas) semanas.value = '';
      var trimestreSel = document.getElementById('trimestreGestacionSel');
      if (trimestreSel) trimestreSel.value = '';
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

  function actualizarLugarInfeccionB26(forcedCie10) {
    var card = document.getElementById('cardLugarProbableInfeccionB26');
    if (!card) return;

    var esB26 = false;
    if (typeof forcedCie10 === 'string' && forcedCie10 !== '') {
      esB26 = (forcedCie10 === 'B26' || forcedCie10.indexOf('B26') !== -1);
    } else {
      var enfermedadSel = document.getElementById('diseaseSel');
      var opcionEnfermedad = enfermedadSel && enfermedadSel.selectedOptions && enfermedadSel.selectedOptions[0];
      var valEnf = enfermedadSel ? (enfermedadSel.value || '') : '';
      var cieOpt = opcionEnfermedad ? (opcionEnfermedad.dataset.cie10 || '') : '';
      var textoEnfermedad = opcionEnfermedad ? (opcionEnfermedad.text || '') : '';
      var tagCie = document.getElementById('cieTag');
      var textoCie = tagCie ? (tagCie.textContent || '') : '';

      esB26 = (cieOpt === 'B26' || textoEnfermedad.indexOf('B26') !== -1 || textoCie.indexOf('B26') !== -1 || valEnf === '9');
    }

    card.hidden = !esB26;
    card.style.display = esB26 ? '' : 'none';

    if (!esB26) return;

    var radContacto = document.querySelector('input[name="' + campoPorClave('b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con') + '"]:checked');
    var valContacto = radContacto ? radContacto.value : '';
    var wrapDetalle = document.getElementById('wrapDetalleContactosB26');
    var esSiContacto = (valContacto === 'SI');

    if (wrapDetalle) {
      wrapDetalle.hidden = !esSiContacto;
      wrapDetalle.style.display = esSiContacto ? '' : 'none';
    }

    var radGestante = document.querySelector('input[name="' + campoPorClave('b26_tuvo_contacto_con_gestante') + '"]:checked');
    var valGestante = radGestante ? radGestante.value : '';
    var wrapGestanteDetalle = document.getElementById('wrapGestanteDetalleB26');
    var esSiGestante = esSiContacto && (valGestante === 'SI');

    if (wrapGestanteDetalle) {
      wrapGestanteDetalle.hidden = !esSiGestante;
      wrapGestanteDetalle.style.display = esSiGestante ? '' : 'none';
    }
  }
  actualizarLugarInfeccionB26();

  function actualizarCuadroClinicoB26(forcedCie10) {
    var card = document.getElementById('cardCuadroClinicoB26');
    if (!card) return;

    var esB26 = false;
    if (typeof forcedCie10 === 'string' && forcedCie10 !== '') {
      esB26 = (forcedCie10 === 'B26' || forcedCie10.indexOf('B26') !== -1);
    } else {
      var enfermedadSel = document.getElementById('diseaseSel');
      var opcionEnfermedad = enfermedadSel && enfermedadSel.selectedOptions && enfermedadSel.selectedOptions[0];
      var valEnf = enfermedadSel ? (enfermedadSel.value || '') : '';
      var cieOpt = opcionEnfermedad ? (opcionEnfermedad.dataset.cie10 || '') : '';
      var textoEnfermedad = opcionEnfermedad ? (opcionEnfermedad.text || '') : '';
      var tagCie = document.getElementById('cieTag');
      var textoCie = tagCie ? (tagCie.textContent || '') : '';

      esB26 = (cieOpt === 'B26' || textoEnfermedad.indexOf('B26') !== -1 || textoCie.indexOf('B26') !== -1 || valEnf === '9');
    }

    card.hidden = !esB26;
    card.style.display = esB26 ? '' : 'none';

    if (!esB26) return;

    // 1. Inflamación de glándulas parótidas
    var radParotidas = document.querySelector('input[name="' + campoPorClave('b26_presento_inflamacion_de_glandulas_parotidas') + '"]:checked');
    var valParotidas = radParotidas ? radParotidas.value : '';
    var wrapParotidasDetalle = document.getElementById('wrapParotidasDetalleB26');
    var esSiParotidas = (valParotidas === 'SI');

    if (wrapParotidasDetalle) {
      wrapParotidasDetalle.hidden = !esSiParotidas;
      wrapParotidasDetalle.style.display = esSiParotidas ? '' : 'none';
    }

    // 2. Complicaciones
    document.querySelectorAll('.box-complicacion-b26').forEach(function(box) {
      var chk = box.querySelector('.chk-complicacion-b26');
      var wrap = box.querySelector('.wrap-fecha-complicacion');
      var inps = box.querySelectorAll('.inp-fecha-complicacion-b26');
      var activo = !!(chk && chk.checked);
      if (wrap) wrap.style.display = activo ? '' : 'none';
      inps.forEach(function(inp) {
        inp.disabled = !activo;
      });
    });

    // 3. Hospitalización
    var radHosp = document.querySelector('input[name="' + campoPorClave('b26_hospitalizacion') + '"]:checked');
    var valHosp = radHosp ? radHosp.value : '';
    var wrapHospDetalle = document.getElementById('wrapHospitalizacionDetalleB26');
    var esSiHosp = (valHosp === 'SI');

    if (wrapHospDetalle) {
      wrapHospDetalle.hidden = !esSiHosp;
      wrapHospDetalle.style.display = esSiHosp ? '' : 'none';
    }

    // 4. Condición de egreso
    var radEgreso = document.querySelector('input[name="' + campoPorClave('b26_condicion_de_egreso') + '"]:checked');
    var valEgreso = radEgreso ? radEgreso.value : '';
    var wrapEgresoRef = document.getElementById('wrapEgresoReferidoB26');
    var wrapEgresoFall = document.getElementById('wrapEgresoFallecidoB26');

    if (wrapEgresoRef) {
      var esRef = (valEgreso === 'REFERIDO');
      wrapEgresoRef.hidden = !esRef;
      wrapEgresoRef.style.display = esRef ? '' : 'none';
    }

    if (wrapEgresoFall) {
      var esFall = (valEgreso === 'FALLECIDO');
      wrapEgresoFall.hidden = !esFall;
      wrapEgresoFall.style.display = esFall ? '' : 'none';
    }

    // 5. Sincronizar restricciones min de fechas
    var inpInicioSintomas = document.getElementById('fechaInicioSintomasB26');
    var fSintomasVal = inpInicioSintomas ? inpInicioSintomas.value : '';

    if (fSintomasVal) {
      var fParotiditis = document.getElementById('fechaInicioParotiditisB26');
      if (fParotiditis) fParotiditis.min = fSintomasVal;

      document.querySelectorAll('.box-complicacion-b26 input[type="date"]').forEach(function(inpDate) {
        inpDate.min = fSintomasVal;
      });

      var fHosp = document.getElementById('fechaHospitalizacionB26');
      if (fHosp) fHosp.min = fSintomasVal;
    }

    var fHospVal = document.getElementById('fechaHospitalizacionB26') ? document.getElementById('fechaHospitalizacionB26').value : '';
    var fEgreso = document.getElementById('fechaEgresoB26');
    if (fEgreso) {
      if (fHospVal) fEgreso.min = fHospVal;
      else if (fSintomasVal) fEgreso.min = fSintomasVal;
    }
  }
  actualizarCuadroClinicoB26();

  function actualizarVacunacionB26(forcedCie10) {
    var card = document.getElementById('cardVacunacionB26');
    if (!card) return;

    var esB26 = false;
    if (typeof forcedCie10 === 'string' && forcedCie10 !== '') {
      esB26 = (forcedCie10 === 'B26' || forcedCie10.indexOf('B26') !== -1);
    } else {
      var enfermedadSel = document.getElementById('diseaseSel');
      var opcionEnfermedad = enfermedadSel && enfermedadSel.selectedOptions && enfermedadSel.selectedOptions[0];
      var valEnf = enfermedadSel ? (enfermedadSel.value || '') : '';
      var cieOpt = opcionEnfermedad ? (opcionEnfermedad.dataset.cie10 || '') : '';
      var textoEnfermedad = opcionEnfermedad ? (opcionEnfermedad.text || '') : '';
      var tagCie = document.getElementById('cieTag');
      var textoCie = tagCie ? (tagCie.textContent || '') : '';

      esB26 = (cieOpt === 'B26' || textoEnfermedad.indexOf('B26') !== -1 || textoCie.indexOf('B26') !== -1 || valEnf === '9');
    }

    card.hidden = !esB26;
    card.style.display = esB26 ? '' : 'none';

    var antecedentesCard = document.getElementById('cardAntecedentesEpidemiologicos');
    if (antecedentesCard && esB26) {
      antecedentesCard.hidden = true;
      antecedentesCard.style.display = 'none';
    }

    var clasifCard = document.getElementById('cardClasificacionCaso');
    if (clasifCard && esB26) {
      clasifCard.hidden = true;
      clasifCard.style.display = 'none';
    }

    if (!esB26) return;

    var radVac = document.querySelector('input[name="b26_vacunacion_spr"]:checked');
    var valVac = radVac ? radVac.value : '';
    var wrapDetalle = document.getElementById('wrapVacunaSprDetalleB26');
    var esSiVac = (valVac === 'SI');

    if (wrapDetalle) {
      wrapDetalle.hidden = !esSiVac;
      wrapDetalle.style.display = esSiVac ? '' : 'none';
    }
  }
  actualizarVacunacionB26();
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
          mapaCampos = datos.mapaCampos || {};
          // El nodo <script id="mapaCampos"> queda en el DOM despues del
          // primer render; si no se reescribe aca, miente sobre la
          // enfermedad activa para cualquier lector futuro (misma trampa
          // que un campo_ID regenerado, ver Fase 7).
          if (mapaCamposEl) mapaCamposEl.textContent = JSON.stringify(mapaCampos);
          contenedorClinico.innerHTML = datos.html;
          contenedorClinico.style.opacity = '1';

          if (datos.htmlClasificacionChips) {
            var chipSelect = document.querySelector('.chip-select');
            if (chipSelect) {
              var fieldParent = chipSelect.closest('.field');
              if (fieldParent) {
                fieldParent.outerHTML = datos.htmlClasificacionChips;
              }
            }
          }

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

          var esB26 = (datos.cie10 === 'B26');
          var b26FechasWrap = document.getElementById('notificacionFechasB26Wrap');
          if (b26FechasWrap) {
            b26FechasWrap.hidden = !esB26;
            b26FechasWrap.style.display = esB26 ? '' : 'none';
          }

          var esP35 = (datos.cie10 === 'P35.0');
          var p35FechasWrap = document.getElementById('notificacionFechasP35Wrap');
          if (p35FechasWrap) {
            p35FechasWrap.hidden = !esP35;
            p35FechasWrap.style.display = esP35 ? '' : 'none';
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
          document.querySelectorAll('.b26-hide').forEach(function(el) {
            el.hidden = esB26;
            el.style.display = esB26 ? 'none' : '';
          });

          // nucleo_omitidos: la tarjeta "Datos del paciente" no se vuelve a
          // renderizar en este fetch (solo la seccion clinica), asi que hay
          // que reocultar/mostrar a mano los campos del nucleo declarados
          // como omitidos por la ficha nueva. datos.nucleoOmitidos ya viene
          // decodificado del manifiesto (via enfermedad.nucleo_omitidos).
          var nucleoOmitidos = datos.nucleoOmitidos || [];
          document.querySelectorAll('[data-nucleo-campo]').forEach(function (el) {
            var omitido = nucleoOmitidos.indexOf(el.getAttribute('data-nucleo-campo')) !== -1;
            el.hidden = omitido;
            el.style.display = omitido ? 'none' : '';
          });

          // nucleo_incluidos: al revés que nucleo_omitidos -- opt-in, el
          // campo se muestra solo si está en la lista. "N.° de historia
          // clínica" vive en el shell (nueva/index.php), no en esta
          // tarjeta, pero tampoco se re-renderiza al cambiar de ficha.
          var nucleoIncluidos = datos.nucleoIncluidos || [];
          document.querySelectorAll('[data-nucleo-incluido]').forEach(function (el) {
            var incluido = nucleoIncluidos.indexOf(el.getAttribute('data-nucleo-incluido')) !== -1;
            el.hidden = !incluido;
            el.style.display = incluido ? '' : 'none';
          });

          // unidades_edad: al revés que nucleo_omitidos, es opt-in -- el
          // bloque solo se muestra si la ficha nueva declara unidades, y
          // hay que repoblar las <option> del select (no hay una cascada
          // genérica existente para reusar, a diferencia de Pueblo étnico).
          var unidadesEdad = datos.unidadesEdad || [];
          var bloqueEdadUnidad = document.querySelector('[data-edad-unidad-bloque]');
          if (bloqueEdadUnidad) {
            var mostrarEdadUnidad = unidadesEdad.length > 0;
            bloqueEdadUnidad.hidden = !mostrarEdadUnidad;
            bloqueEdadUnidad.style.display = mostrarEdadUnidad ? '' : 'none';
            var selectEdadUnidad = bloqueEdadUnidad.querySelector('select[name="edad_unidad"]');
            if (selectEdadUnidad) {
              var valorUnidadActual = selectEdadUnidad.value;
              selectEdadUnidad.innerHTML = '<option value="">Seleccionar…</option>';
              unidadesEdad.forEach(function (unidad) {
                var opt = document.createElement('option');
                opt.value = unidad;
                opt.textContent = ETIQUETAS_UNIDAD_EDAD[unidad] || unidad;
                if (unidad === valorUnidadActual) opt.selected = true;
                selectEdadUnidad.appendChild(opt);
              });
            }
            if (!mostrarEdadUnidad) {
              var inputEdadValor = bloqueEdadUnidad.querySelector('input[name="edad_valor"]');
              if (inputEdadValor) inputEdadValor.value = '';
            }
          }

          // detalle_domicilio (Entrada J acotada al bloque de domicilio):
          // opt-in campo por campo, igual mecánica que nucleo_omitidos pero
          // con la polaridad invertida (se muestra si ESTÁ en la lista, no
          // si falta).
          var detalleDomicilio = datos.detalleDomicilio || [];
          document.querySelectorAll('[data-detalle-domicilio-campo]').forEach(function (el) {
            var visible = detalleDomicilio.indexOf(el.getAttribute('data-detalle-domicilio-campo')) !== -1;
            el.hidden = !visible;
            el.style.display = visible ? '' : 'none';
          });
          var captGrid = document.querySelector('.b26-capt-grid');
          if (captGrid) {
            if (esB26) {
              captGrid.classList.remove('thirds');
              captGrid.classList.add('halves');
            } else {
              captGrid.classList.remove('halves');
              captGrid.classList.add('thirds');
            }
          }
          if (!esB05) {
            var chkMenor = document.getElementById('chkEsMenorEdadB05');
            if (chkMenor) chkMenor.checked = false;
          }
          if (!esO95) {
            var chkCom = document.getElementById('o95HabilitarDatosComunitariosChk');
            if (chkCom) {
              chkCom.checked = false;
              delete chkCom.dataset.userToggled;
            }
          }
          actualizarTutorB05();
          actualizarEtapaFichaO95();
          actualizarEtniaOtra(false);
          actualizarPuebloEtnicoB05();
          actualizarGestante();
          actualizarLugarInfeccionB26(datos.cie10);
          actualizarCuadroClinicoB26(datos.cie10);
          actualizarVacunacionB26(datos.cie10);

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

          // Bloques condicionales de tabla hija (capacidad 6): siblings
          // planos de labCard (no un wrapper) para que
          // renumerarSeccionesSiguientes() los cuente igual que cualquier
          // otra tarjeta ".section" al recorrer contenedorClinico.nextElementSibling.
          document.querySelectorAll('.bloque-condicional-muestra').forEach(function (el) { el.remove(); });
          if (labCard && datos.htmlBloquesMuestra && datos.htmlBloquesMuestra.length) {
            labCard.insertAdjacentHTML('afterend', datos.htmlBloquesMuestra.join(''));
          }

          renumerarSeccionesSiguientes();
          if (window.SelectorBusqueda) {
            window.SelectorBusqueda.escanear(contenedorClinico);
            if (labBody) window.SelectorBusqueda.escanear(labBody);
          }
          if (typeof inicializarGruposSiNo === 'function') inicializarGruposSiNo();
          evaluarDependencias();
          if (esO95) sincronizarClasificacionO95();

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

  // Tabla hija muestras: alterna visibilidad de una columna según el valor
  // de OTRA columna de la misma fila (data-depende-columna/
  // data-valores-activadores, PETICION_HC_Y_LABORATORIO.md Parte 2,
  // capacidad 5). Reemplaza al toggle hardcodeado de B05
  // (actualizarCamposMuestraB05/.b05-select-tipo-muestra/.b05-pcr-group/
  // .b05-serologia-group) por uno genérico: cualquier ficha que declare
  // columnas_tablas_hija.caso_muestra.depende_de_columna en el manifiesto
  // queda cubierta sin tocar este archivo. Mismo idioma que
  // evaluarDependencias() (depende_de/valor_activador de campo_def), pero
  // resuelto DENTRO de una fila de tabla hija en vez de contra el documento
  // entero.
  function evaluarDependenciasMuestra() {
    document.querySelectorAll('[data-depende-columna]').forEach(function(campo) {
      var subrow = campo.closest('.subrow');
      if (!subrow) return;
      var columna = campo.getAttribute('data-depende-columna');
      var activadores = (campo.getAttribute('data-valores-activadores') || '').split(',').map(function(s) { return s.trim(); });
      var disparador = subrow.querySelector('[name="muestra_' + columna + '[]"]');
      var valorActual = disparador ? disparador.value : '';
      campo.style.display = activadores.indexOf(valorActual) !== -1 ? '' : 'none';
    });
  }

  document.addEventListener('change', evaluarDependenciasMuestra);
  document.addEventListener('input', evaluarDependenciasMuestra);
  evaluarDependenciasMuestra();

  // B05 Antecedentes vacunales: Mostrar/ocultar y desplegar vacunas según Estado vacunal
  function actualizarBloqueVacunasB05() {
    var wrapperVacunas = document.getElementById('b05-wrapper-vacunas-registradas');
    if (!wrapperVacunas) return;

    var val = leerValorCampoPorNombre(campoPorClave('b05_estado_vacunal'));
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
    if (e.target && e.target.name === campoPorClave('b05_estado_vacunal')) {
      actualizarBloqueVacunasB05();
    }
  });

  actualizarBloqueVacunasB05();

  // B05 Lugar probable de infección: Mostrar/ocultar y desplegar viajes según Paciente viajó
  //
  // Ítem Z.2 (PENDIENTES.md): la clave 'paciente_viajo_7_30_dias' quedó
  // obsoleta -- mapaCampos usa la clave real
  // "b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic" (prefijo b05_
  // de "clave ahora autoritativa"), así que campoPorClave() siempre
  // devolvía '' y este bloque nunca corría de verdad: el .hidden lo
  // limpiaba el evaluarDependencias() genérico (por data-depende-de del
  // propio campo), pero el style.display:none inline con el que el
  // servidor renderiza la tarjeta al inicio quedaba pegado para siempre
  // -- la tabla de viajes de B05 nunca llegaba a verse aunque el atributo
  // "hidden" ya estuviera en false. Corregido a la clave real.
  function actualizarBloqueViajesB05() {
    var wrapperViajes = document.getElementById('b05-wrapper-viajes-registrados');
    if (!wrapperViajes) return;

    var val = leerValorCampoPorNombre(campoPorClave('b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic'));
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
    if (e.target && e.target.name === campoPorClave('b05_paciente_viajo_entre_los_7_a_30_dias_antes_del_inic')) {
      actualizarBloqueViajesB05();
    }
  });

  // P35.0 Antecedentes de la madre: mismo mecanismo que B05, pero el
  // campo es BOOLEANO (valor '1'/'0', no catálogo SI/NO/DESCONOCIDO).
  function actualizarBloqueViajesP350() {
    var wrapperViajes = document.getElementById('p350-wrapper-viajes-registrados');
    if (!wrapperViajes) return;

    var val = leerValorCampoPorNombre(campoPorClave('p35_0_durante_el_embarazo_viajo_fuera_del_pais'));
    var esViajo = (val === '1');

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
    if (e.target && e.target.name === campoPorClave('p35_0_durante_el_embarazo_viajo_fuera_del_pais')) {
      actualizarBloqueViajesP350();
    }
  });

  document.addEventListener('click', function(e) {
    if (e.target && e.target.closest('.quitar-fila')) {
      setTimeout(function() {
        actualizarBloqueVacunasB05();
        actualizarBloqueViajesB05();
        actualizarBloqueViajesP350();
      }, 50);
    }
  });

  actualizarBloqueViajesB05();
  actualizarBloqueViajesP350();

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

  // Dependencia de Grupo étnico -> Etnia / Pueblo étnico. Declarado ANTES de
  // actualizarEtapaFichaO95() (bug preexistente, no introducido por la
  // Petición 2: la llamada incondicional de abajo, actualizarEtapaFichaO95(),
  // ya se ejecutaba antes de que este `var` se asignara, así que
  // MAPA_GRUPO_ETNICO era undefined y actualizarPuebloEtnicoO95() tiraba
  // TypeError cada vez que O95 era la enfermedad activa al cargar la
  // página -- lo que además abortaba en silencio el resto de
  // actualizarEtapaFichaO95() (fallecimiento, referencia, antecedentes,
  // etc. nunca se inicializaban). Se movió acá arriba, sin cambiar su
  // contenido, al descubrirlo verificando la Fase 7 en el navegador.
  //
  // Renombrado de O95_MAPA_ETNIAS: reutilizado por B05 (cascada Etnia/raza
  // del núcleo -> Pueblo étnico o etnia), no es exclusivo de O95. Las claves
  // AFRODESCENDIENTE/ASIÁTICO DESCENDIENTE ya estaban acá desde antes --
  // son los valores que usa #etniaSel del núcleo, distintos a los de
  // #o95GrupoEtnicoSel (AFROPERUANO/ASIATICO_DESCENDIENTE) -- así que esta
  // misma tabla ya servía para las dos convenciones sin tocarla.
  var MAPA_GRUPO_ETNICO = {
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

  // O95 Conmutación de etapas: Anexo 1 (Notificación inmediata) vs Anexo 2 (Investigación epidemiológica)
  function actualizarEtapaFichaO95() {
    var cie10 = obtenerCie10Actual();
    var esO95 = (cie10 === 'O95');

    var o95FechasWrap = document.getElementById('notificacionFechasO95Wrap');
    if (o95FechasWrap) {
      o95FechasWrap.hidden = !esO95;
      o95FechasWrap.style.display = esO95 ? '' : 'none';
    }

    // Ocultar Sexo cuando es O95 (Celular/Nacionalidad/Localidad/Etnia/
    // Gestante/Tutor ya se manejan por nucleo_omitidos, ver mas arriba)
    document.querySelectorAll('.o95-hide').forEach(function(el) {
      el.hidden = esO95;
      el.style.display = esO95 ? 'none' : '';
    });
    actualizarEtniaOtra(false);

    // "N.° de historia clínica" ya no es .o95-elem: se muestra/oculta con
    // nucleo_incluidos (ver más arriba), no con un toggle hardcodeado a O95.

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
      if (sec.id !== 'campoIdiomaOtraO95' && sec.id !== 'campoSeguroOtroO95' && sec.id !== 'campoCategoriaEessO95' && sec.id !== 'campoFechaHoraIngresoO95') {
        sec.hidden = !esAnexo2;
        sec.style.display = esAnexo2 ? '' : 'none';
      }
    });

    actualizarPuebloEtnicoO95();
    actualizarOtrosCamposO95();
    actualizarDatosFallecimientoO95();
    actualizarReferenciaO95();
    actualizarAntecedentesPatologicosObstetricosO95();
    actualizarAtencionPrenatalO95();
    actualizarComplicacionesO95();
    actualizarHospitalizacionesO95();
    actualizarPartoAbortoO95();
    actualizarEntornoSocialO95();
    actualizarDatosComunitariosO95();

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

  function normalizarClaveGrupo(val) {
    if (!val) return '';
    return val.toString().trim().toUpperCase()
      .replace(/Á/g, 'A').replace(/É/g, 'E').replace(/Í/g, 'I').replace(/Ó/g, 'O').replace(/Ú/g, 'U')
      .replace(/\s+/g, '_');
  }

  // Compartida por O95 (#o95GrupoEtnicoSel -> #o95PuebloEtnicoSel) y B05
  // (#etniaSel del núcleo -> #b05PuebloEtnicoSel). Mismo contrato: arranca
  // el <select> destino vacío, lo repuebla según MAPA_GRUPO_ETNICO, e
  // intenta preseleccionar el valor ya guardado (vía data-valor-actual en
  // la primera pasada, o el .value actual en cambios posteriores).
  function actualizarCascadaEtnica(grupoSelId, puebloSelId) {
    var grupoSel = document.getElementById(grupoSelId);
    var puebloSel = document.getElementById(puebloSelId);
    if (!grupoSel || !puebloSel) return;

    var grupoRaw = grupoSel.value || '';
    var grupoNorm = normalizarClaveGrupo(grupoRaw);
    var valorPrevio = puebloSel.getAttribute('data-valor-actual') || puebloSel.value || '';

    puebloSel.innerHTML = '<option value="">Seleccionar…</option>';

    var opciones = MAPA_GRUPO_ETNICO[grupoRaw] || MAPA_GRUPO_ETNICO[grupoNorm] || null;

    if (opciones && opciones.length > 0) {
      // 2026-07-30: la condición original de autoselección de opción
      // única era "!valorPrevio" -- si el grupo anterior también tenía
      // una sola opción distinta (ej. Mestizo="No aplica" ->
      // Afrodescendiente="Afroperuano"), valorPrevio quedaba con el
      // valor del grupo VIEJO (no vacío) y bloqueaba la autoselección
      // del nuevo, aunque ese valor viejo no exista en la lista nueva.
      // Ahora depende de si valorPrevio de verdad coincidió con alguna
      // opción real de ESTE grupo, no de si había cualquier valor previo.
      var seleccionoAlgo = false;
      opciones.forEach(function(opt) {
        var el = document.createElement('option');
        el.value = opt;
        el.textContent = opt;
        if (opt === valorPrevio) {
          el.selected = true;
          seleccionoAlgo = true;
        }
        puebloSel.appendChild(el);
      });
      if (opciones.length === 1 && !seleccionoAlgo) {
        puebloSel.value = opciones[0];
      }
    }

    if (window.SelectorBusqueda) {
      window.SelectorBusqueda.actualizar(puebloSel);
    }
  }

  function actualizarPuebloEtnicoO95() {
    actualizarCascadaEtnica('o95GrupoEtnicoSel', 'o95PuebloEtnicoSel');
  }

  function actualizarPuebloEtnicoB05() {
    actualizarCascadaEtnica('etniaSel', 'b05PuebloEtnicoSel');
  }

  document.addEventListener('change', function(e) {
    if (e.target && (e.target.id === 'o95GrupoEtnicoSel' || e.target.name === campoPorClave('o95_grupo_etnico'))) {
      puebloSelAttrReset('o95PuebloEtnicoSel');
      actualizarPuebloEtnicoO95();
    }
    if (e.target && (e.target.id === 'etniaSel' || e.target.name === 'etnia')) {
      puebloSelAttrReset('b05PuebloEtnicoSel');
      actualizarPuebloEtnicoB05();
    }
  });

  function puebloSelAttrReset(puebloSelId) {
    var puebloSel = document.getElementById(puebloSelId);
    if (puebloSel) puebloSel.removeAttribute('data-valor-actual');
  }

  function actualizarOtrosCamposO95() {
    var radioAnexo2 = document.querySelector('input[name="o95_tipo_ficha"][value="ANEXO_2"]');
    var esAnexo2 = radioAnexo2 && radioAnexo2.checked;

    var selIdioma = document.getElementById('o95IdiomaSel');
    var wrapIdiomaOtra = document.getElementById('campoIdiomaOtraO95');
    if (wrapIdiomaOtra) {
      var valInd = selIdioma ? selIdioma.value : '';
      var esOtra = esAnexo2 && (valInd === 'OTRA' || valInd === 'Otra');
      wrapIdiomaOtra.hidden = !esOtra;
      wrapIdiomaOtra.style.display = esOtra ? '' : 'none';
    }

    var selSeguro = document.getElementById('o95TipoSeguroSel');
    var wrapSeguroOtro = document.getElementById('campoSeguroOtroO95');
    if (wrapSeguroOtro) {
      var valSeg = selSeguro ? selSeguro.value : '';
      var esOtroS = esAnexo2 && (valSeg === 'OTROS' || valSeg === 'Otros');
      wrapSeguroOtro.hidden = !esOtroS;
      wrapSeguroOtro.style.display = esOtroS ? '' : 'none';
    }
  }

  function calcularFechaHoraIngresoO95() {
    var fechaFall = document.querySelector('input[name="' + campoPorClave('o95_fecha_de_fallecimiento') + '"]');
    var horaFall = document.querySelector('input[name="' + campoPorClave('o95_hora_de_fallecimiento') + '"]');
    var diasInput = document.querySelector('input[name="' + campoPorClave('o95_permanencia_dias') + '"]');
    var horasInput = document.querySelector('input[name="' + campoPorClave('o95_permanencia_horas') + '"]');
    var minsInput = document.querySelector('input[name="' + campoPorClave('o95_permanencia_minutos') + '"]');
    var inputIngreso = document.getElementById('o95FechaHoraIngresoInput');

    if (!fechaFall || !fechaFall.value || !inputIngreso) return;

    var strFecha = fechaFall.value;
    var strHora = (horaFall && horaFall.value) ? horaFall.value : '00:00';

    var dtFall = new Date(strFecha + 'T' + strHora + ':00');
    if (isNaN(dtFall.getTime())) return;

    var dias = parseInt(diasInput ? diasInput.value : '0', 10) || 0;
    var horas = parseInt(horasInput ? horasInput.value : '0', 10) || 0;
    var mins = parseInt(minsInput ? minsInput.value : '0', 10) || 0;

    var totalMins = (dias * 24 * 60) + (horas * 60) + mins;
    if (totalMins <= 0) return;

    var dtIngreso = new Date(dtFall.getTime() - (totalMins * 60 * 1000));

    var yyyy = dtIngreso.getFullYear();
    var mm = String(dtIngreso.getMonth() + 1).padStart(2, '0');
    var dd = String(dtIngreso.getDate()).padStart(2, '0');
    var hh = String(dtIngreso.getHours()).padStart(2, '0');
    var mi = String(dtIngreso.getMinutes()).padStart(2, '0');

    inputIngreso.value = yyyy + '-' + mm + '-' + dd + 'T' + hh + ':' + mi;
  }

  function actualizarDatosFallecimientoO95() {
    var radioAnexo2 = document.querySelector('input[name="o95_tipo_ficha"][value="ANEXO_2"]');
    var esAnexo2 = radioAnexo2 && radioAnexo2.checked;

    var selMomento = document.getElementById('o95MomentoFallecimientoSel');
    var wrapPuerperio = document.getElementById('campoFasePuerperioO95');
    if (selMomento && wrapPuerperio) {
      var esPuerperio = (selMomento.value === 'Puerperio' || selMomento.value === 'PUERPERIO');
      wrapPuerperio.hidden = !esPuerperio;
      wrapPuerperio.style.display = esPuerperio ? '' : 'none';
    }

    var chkEG = document.getElementById('o95EdadGestacionalDesconocidaChk');
    var inputEG = document.getElementById('o95EdadGestacionalInput');
    if (chkEG && inputEG) {
      inputEG.disabled = chkEG.checked;
      if (chkEG.checked) inputEG.value = '';
    }

    var radioLugar = document.querySelector('input[name="' + campoPorClave('o95_lugar_del_fallecimiento') + '"]:checked');
    var valLugar = radioLugar ? radioLugar.value : 'Establecimiento de salud';

    var wrapEess = document.getElementById('bloqueEessFallecimientoO95');
    var wrapOtro = document.getElementById('bloqueEspecificarOtroLugar');
    var wrapTrayecto = document.getElementById('bloqueTrayectoFallecimiento');
    var wrapUbigeoFall = document.getElementById('bloqueUbigeoFallecimientoO95');

    if (wrapEess) {
      var esEess = (valLugar === 'Establecimiento de salud');
      wrapEess.hidden = !esEess;
      wrapEess.style.display = esEess ? '' : 'none';
    }
    if (wrapOtro) {
      var esOtro = (valLugar === 'Otro');
      wrapOtro.hidden = !esOtro;
      wrapOtro.style.display = esOtro ? '' : 'none';
    }
    if (wrapTrayecto) {
      var esTrayecto = (valLugar === 'Trayecto');
      wrapTrayecto.hidden = !esTrayecto;
      wrapTrayecto.style.display = esTrayecto ? '' : 'none';
    }

    var selTipoEess = document.getElementById('o95TipoEessSel');
    var valTipoEess = selTipoEess ? selTipoEess.value : 'EESS Sanidad FFAA/PNP';

    var subSanidad = document.getElementById('subBloqueSanidadPnp');
    var subOtroEess = document.getElementById('subBloqueOtroEess');
    if (selTipoEess) {
      var esSanidad = (valTipoEess === 'EESS Sanidad FFAA/PNP' || valTipoEess === 'SANIDAD_PNP');
      if (subSanidad) {
        subSanidad.hidden = !esSanidad;
        subSanidad.style.display = esSanidad ? '' : 'none';
      }
      if (subOtroEess) {
        subOtroEess.hidden = esSanidad;
        subOtroEess.style.display = !esSanidad ? '' : 'none';
      }
    }

    // Ubigeo del fallecimiento sólo aparecerá cuando se seleccione MINSA, EsSalud, Privado u Otro.
    if (wrapUbigeoFall) {
      var mostrarUbigeo = (valLugar === 'Otro') || (valLugar === 'Establecimiento de salud' && (valTipoEess !== 'EESS Sanidad FFAA/PNP' && valTipoEess !== 'SANIDAD_PNP'));
      wrapUbigeoFall.hidden = !mostrarUbigeo;
      wrapUbigeoFall.style.display = mostrarUbigeo ? '' : 'none';
      if (mostrarUbigeo) {
        if (typeof inicializarUbigeo === 'function') {
          inicializarUbigeo('o95-fallecimiento-ubigeo');
        }
        if (window.SelectorBusqueda) {
          var sDep = document.getElementById('o95-fallecimiento-ubigeo-departamento');
          var sProv = document.getElementById('o95-fallecimiento-ubigeo-provincia');
          var sDist = document.getElementById('o95-fallecimiento-ubigeo-distrito');
          if (sDep) window.SelectorBusqueda.actualizar(sDep);
          if (sProv) window.SelectorBusqueda.actualizar(sProv);
          if (sDist) window.SelectorBusqueda.actualizar(sDist);
        }
      }
    }

    // Anexo 2 especificos de Fallecimiento
    var wrapCategoria = document.getElementById('campoCategoriaEessO95');
    var wrapIngreso = document.getElementById('campoFechaHoraIngresoO95');
    var wrapResponsable = document.getElementById('campoResponsableAtencionO95');

    if (wrapCategoria) {
      var esCat = esAnexo2 && (valLugar === 'Establecimiento de salud') && (valTipoEess !== 'EESS Sanidad FFAA/PNP' && valTipoEess !== 'SANIDAD_PNP');
      wrapCategoria.hidden = !esCat;
      wrapCategoria.style.display = esCat ? '' : 'none';
    }

    if (wrapIngreso) {
      var esIng = esAnexo2 && (valLugar === 'Establecimiento de salud');
      wrapIngreso.hidden = !esIng;
      wrapIngreso.style.display = esIng ? '' : 'none';
    }

    if (wrapResponsable) {
      wrapResponsable.hidden = !esAnexo2;
      wrapResponsable.style.display = esAnexo2 ? '' : 'none';
    }

    sincronizarIpessPnpUbigeoO95();
    calcularFechaHoraIngresoO95();
  }

  function sincronizarIpessPnpUbigeoO95() {
    var selIpress = document.getElementById('o95IpressPnpSel');
    var inputHiddenDep = document.getElementById('o95-fallecimiento-ubigeo-dep-hidden');
    var inputHiddenProv = document.getElementById('o95-fallecimiento-ubigeo-prov-hidden');
    var selDist = document.getElementById('o95-fallecimiento-ubigeo-distrito');

    if (!selIpress) return;

    var opt = selIpress.options[selIpress.selectedIndex];
    var depId = opt ? opt.getAttribute('data-dep-id') : '';
    var provId = opt ? opt.getAttribute('data-prov-id') : '';
    var distId = opt ? opt.getAttribute('data-dist-id') : '';

    var selTipoEess = document.getElementById('o95TipoEessSel');
    var radioLugar = document.querySelector('input[name="' + campoPorClave('o95_lugar_del_fallecimiento') + '"]:checked');
    var valLugar = radioLugar ? radioLugar.value : '';
    var esSanidadPnp = (valLugar === 'Establecimiento de salud' && selTipoEess && (selTipoEess.value === 'EESS Sanidad FFAA/PNP' || selTipoEess.value === 'SANIDAD_PNP') && selIpress.value !== '');

    if (esSanidadPnp && depId) {
      if (inputHiddenDep) inputHiddenDep.value = depId;
      if (inputHiddenProv) inputHiddenProv.value = provId;
      if (selDist && distId) {
        if (!Array.from(selDist.options).some(function(o) { return o.value == distId; })) {
          var el = document.createElement('option');
          el.value = distId;
          el.textContent = distId;
          selDist.appendChild(el);
        }
        selDist.value = distId;
      }
    }
  }

  document.addEventListener('change', function(e) {
    if (!e.target) return;
    if (e.target.id === 'o95IdiomaSel' || e.target.name === campoPorClave('o95_idioma') || e.target.id === 'o95TipoSeguroSel' || e.target.name === campoPorClave('o95_tipo_de_seguro')) {
      actualizarOtrosCamposO95();
    }
    if (e.target.id === 'o95MomentoFallecimientoSel' ||
        e.target.id === 'o95EdadGestacionalDesconocidaChk' ||
        e.target.name === campoPorClave('o95_lugar_del_fallecimiento') ||
        e.target.id === 'o95TipoEessSel' ||
        e.target.id === 'o95IpressPnpSel' ||
        e.target.name === campoPorClave('o95_fecha_de_fallecimiento') ||
        e.target.name === campoPorClave('o95_hora_de_fallecimiento')) {
      actualizarDatosFallecimientoO95();
    }
    if (e.target.id === 'o95ReferidaSel' || e.target.name === campoPorClave('o95_referida')) {
      actualizarReferenciaO95();
    }
    if (e.target.id === 'o95-fallecimiento-ubigeo-departamento') {
      var hiddenDep = document.getElementById('o95-fallecimiento-ubigeo-dep-hidden');
      if (hiddenDep) hiddenDep.value = e.target.value;
    }
    if (e.target.id === 'o95-fallecimiento-ubigeo-provincia') {
      var hiddenProv = document.getElementById('o95-fallecimiento-ubigeo-prov-hidden');
      if (hiddenProv) hiddenProv.value = e.target.value;
    }
    if (e.target.id === 'o95-referencia-ubigeo-departamento') {
      var hiddenDepR = document.getElementById('o95-referencia-ubigeo-dep-hidden');
      if (hiddenDepR) hiddenDepR.value = e.target.value;
    }
    if (e.target.id === 'o95-referencia-ubigeo-provincia') {
      var hiddenProvR = document.getElementById('o95-referencia-ubigeo-prov-hidden');
      if (hiddenProvR) hiddenProvR.value = e.target.value;
    }
  });

  document.addEventListener('input', function(e) {
    if (!e.target) return;
    if (e.target.name === campoPorClave('o95_permanencia_dias') || e.target.name === campoPorClave('o95_permanencia_horas') || e.target.name === campoPorClave('o95_permanencia_minutos')) {
      calcularFechaHoraIngresoO95();
    }
  });

  function actualizarReferenciaO95() {
    var selReferida = document.getElementById('o95ReferidaSel');
    var wrapAnexo1 = document.getElementById('bloqueReferenciaAnexo1O95');
    var wrapAnexo2 = document.getElementById('bloqueReferenciaAnexo2O95');

    if (!selReferida) return;

    var valRef = (selReferida.value || '').toUpperCase().trim();
    var esSi = (valRef === 'SI' || valRef === '1');

    var radioAnexo2 = document.querySelector('input[name="o95_tipo_ficha"][value="ANEXO_2"]');
    var esAnexo2 = radioAnexo2 && radioAnexo2.checked;

    if (wrapAnexo1) {
      wrapAnexo1.hidden = !esSi;
      wrapAnexo1.style.display = esSi ? '' : 'none';
      if (esSi) {
        if (typeof inicializarUbigeo === 'function') {
          inicializarUbigeo('o95-referencia-ubigeo');
        }
        if (window.SelectorBusqueda) {
          var sDep = document.getElementById('o95-referencia-ubigeo-departamento');
          var sProv = document.getElementById('o95-referencia-ubigeo-provincia');
          var sDist = document.getElementById('o95-referencia-ubigeo-distrito');
          if (sDep) window.SelectorBusqueda.actualizar(sDep);
          if (sProv) window.SelectorBusqueda.actualizar(sProv);
          if (sDist) window.SelectorBusqueda.actualizar(sDist);
        }
      }
    }

    if (wrapAnexo2) {
      var mostrarAnexo2Ref = (esSi && esAnexo2);
      wrapAnexo2.hidden = !mostrarAnexo2Ref;
      wrapAnexo2.style.display = mostrarAnexo2Ref ? '' : 'none';
    }

    var selRespOrig = document.getElementById('o95RespOrigenSel');
    var wrapRespOrigOtro = document.getElementById('bloqueRespOrigenOtroO95');
    if (wrapRespOrigOtro && selRespOrig) {
      var valRO = (selRespOrig.value || '').toUpperCase().trim();
      var esRespOtro = (valRO === 'OTRO');
      wrapRespOrigOtro.hidden = !esRespOtro;
      wrapRespOrigOtro.style.display = esRespOtro ? '' : 'none';
    }

    calcularTiempoDemoraO95();
  }

  function calcularTiempoDemoraO95() {
    var inFechaIng = document.getElementById('o95FechaIngOrigenInput');
    var inHoraIng  = document.getElementById('o95HoraIngOrigenInput');
    var inFechaEgr = document.getElementById('o95FechaEgrOrigenInput');
    var inHoraEgr  = document.getElementById('o95HoraEgrOrigenInput');

    var inDemoraDias  = document.getElementById('o95DemoraDiasInput');
    var inDemoraHoras = document.getElementById('o95DemoraHorasInput');

    if (!inFechaIng || !inFechaEgr) return;

    var vIng = inFechaIng.value;
    var vEgr = inFechaEgr.value;

    // 1. Establecer límites min/max recíprocos entre fecha de ingreso y egreso
    if (vIng) {
      inFechaEgr.min = vIng;
    } else {
      inFechaEgr.removeAttribute('min');
    }

    if (vEgr) {
      inFechaIng.max = vEgr;
    }

    // 2. Validar inconsistencia donde egreso < ingreso
    if (vIng && vEgr) {
      var hIng = (inHoraIng && inHoraIng.value) ? inHoraIng.value : '00:00';
      var hEgr = (inHoraEgr && inHoraEgr.value) ? inHoraEgr.value : '00:00';

      var dtIng = new Date(vIng + 'T' + hIng);
      var dtEgr = new Date(vEgr + 'T' + hEgr);

      if (dtEgr < dtIng) {
        // Si el egreso es menor que el ingreso, se resetea la fecha de egreso para mantener consistencia
        inFechaEgr.value = '';
        if (inDemoraDias) inDemoraDias.value = 0;
        if (inDemoraHoras) inDemoraHoras.value = 0;
        return;
      }

      // 3. Calcular diferencia en milisegundos y desglosar días y horas transcurridas
      var diffMs = dtEgr.getTime() - dtIng.getTime();
      if (!isNaN(diffMs) && diffMs >= 0) {
        var totalMinutos = Math.floor(diffMs / (1000 * 60));
        var totalHoras   = Math.floor(totalMinutos / 60);
        var dias  = Math.floor(totalHoras / 24);
        var horas = totalHoras % 24;

        if (inDemoraDias) inDemoraDias.value = dias;
        if (inDemoraHoras) inDemoraHoras.value = horas;
      }
    }
  }

  function sincronizarClasificacionO95(origenElemento) {
    var selClasifFinal   = document.querySelector('[name="' + campoPorClave('o95_clasificacion_final_de_la_muerte') + '"]');
    var selClasifInicial = document.querySelector('[name="' + campoPorClave('o95_clasificacion_inicial') + '"]');
    var radiosClasif     = Array.from(document.querySelectorAll('input[name="clasificacion"]'));
    if (!radiosClasif.length) return;

    if (origenElemento && origenElemento.name === 'clasificacion') {
      var valRadio = origenElemento.value;
      if (selClasifFinal) selClasifFinal.value = valRadio;
      if (selClasifInicial && !selClasifInicial.value) selClasifInicial.value = valRadio;
    } else {
      var valSelect = (selClasifFinal && selClasifFinal.value) ? selClasifFinal.value : (selClasifInicial ? selClasifInicial.value : '');
      if (valSelect) {
        radiosClasif.forEach(function(r) {
          r.checked = (r.value === valSelect);
        });
      }
    }
  }

  function actualizarCausasDefuncionO95() {
    var selGen = document.getElementById('o95CausaGenericaSel');
    var wrapOtra = document.getElementById('bloqueCausaGenericaOtraO95');
    if (selGen && wrapOtra) {
      var valGen = (selGen.value || '').toUpperCase().trim();
      var esOtra = (valGen === 'OTRA' || valGen === 'OTRA CAUSA');

      wrapOtra.hidden = !esOtra;
      wrapOtra.style.display = esOtra ? '' : 'none';
    }
    sincronizarClasificacionO95();
  }

  function actualizarAntecedentesPatologicosObstetricosO95() {
    var container = document.getElementById('bloqueAntecedentesPatologicosObstetricosO95');
    if (!container) return;

    // --- A. ANTECEDENTES PATOLÓGICOS (Reglas 1, 2, 3) ---
    var patChks = Array.from(container.querySelectorAll('.o95PatologiaChk'));
    var chkNinguno = patChks.find(function(c) { return c.value === 'NINGUNO' || c.getAttribute('data-codigo') === 'NINGUNO'; });
    var chkDesconocido = patChks.find(function(c) { return c.value === 'DESCONOCIDO' || c.getAttribute('data-codigo') === 'DESCONOCIDO'; });
    var chkPatOtra = patChks.find(function(c) { return c.value === 'OTRA' || c.getAttribute('data-codigo') === 'OTRA'; });
    var wrapPatOtra = document.getElementById('bloquePatologiaOtraO95');

    if (chkNinguno && chkNinguno.checked) {
      patChks.forEach(function(c) {
        if (c !== chkNinguno) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else if (chkDesconocido && chkDesconocido.checked) {
      patChks.forEach(function(c) {
        if (c !== chkDesconocido) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else {
      patChks.forEach(function(c) {
        c.disabled = false;
      });
    }

    if (wrapPatOtra && chkPatOtra) {
      wrapPatOtra.hidden = !chkPatOtra.checked;
      wrapPatOtra.style.display = chkPatOtra.checked ? '' : 'none';
    }

    // --- B & C. ANTECEDENTES GINECO OBSTÉTRICOS (Reglas 4, 5, 6, 7, 8) ---
    var inpGest = document.getElementById('o95GestacionesInput');
    var inpPartos = document.getElementById('o95PartosInput');
    var inpCesareas = document.getElementById('o95CesareasInput');
    var inpAbortos = document.getElementById('o95AbortosInput');
    var inpNacVivos = document.getElementById('o95NacVivosInput');
    var inpNacMuertos = document.getElementById('o95NacMuertosInput');
    var inpHijosViven = document.getElementById('o95HijosVivenInput');
    var inpInterAnios = document.getElementById('o95IntergenesicoAniosInput');
    var inpInterMeses = document.getElementById('o95IntergenesicoMesesInput');
    var blockIntergenesico = document.getElementById('o95IntergenesicoBlock');
    var alertGineco = document.getElementById('o95GinecoValidacionAlerta');

    if (inpGest) {
      var nGest = parseInt(inpGest.value, 10);
      if (isNaN(nGest) || nGest < 0) nGest = 0;

      if (nGest === 0) {
        // Regla 4: Gestaciones = 0 -> todos 0 y bloqueados
        [inpPartos, inpCesareas, inpAbortos, inpNacVivos, inpNacMuertos, inpHijosViven].forEach(function(inp) {
          if (inp) {
            inp.value = 0;
            inp.disabled = true;
          }
        });
        if (inpInterAnios) inpInterAnios.value = 0;
        if (inpInterMeses) inpInterMeses.value = 0;
        if (blockIntergenesico) {
          blockIntergenesico.hidden = true;
          blockIntergenesico.style.display = 'none';
        }
      } else if (nGest === 1) {
        // Regla 5: Gestaciones = 1 -> Habilitar partos, cesareas, etc., Periodo intergenesico oculto
        [inpPartos, inpCesareas, inpAbortos, inpNacVivos, inpNacMuertos, inpHijosViven].forEach(function(inp) {
          if (inp) inp.disabled = false;
        });
        if (inpInterAnios) inpInterAnios.value = 0;
        if (inpInterMeses) inpInterMeses.value = 0;
        if (blockIntergenesico) {
          blockIntergenesico.hidden = true;
          blockIntergenesico.style.display = 'none';
        }
      } else {
        // Regla 6: Gestaciones >= 2 -> Habilitar todos y mostrar Periodo intergenesico
        [inpPartos, inpCesareas, inpAbortos, inpNacVivos, inpNacMuertos, inpHijosViven].forEach(function(inp) {
          if (inp) inp.disabled = false;
        });
        if (blockIntergenesico) {
          blockIntergenesico.hidden = false;
          blockIntergenesico.style.display = 'flex';
        }
      }

      // Validaciones C: 4 Condiciones Duras
      var nPartos = parseInt(inpPartos ? inpPartos.value : 0, 10) || 0;
      var nCesareas = parseInt(inpCesareas ? inpCesareas.value : 0, 10) || 0;
      var nAbortos = parseInt(inpAbortos ? inpAbortos.value : 0, 10) || 0;
      var nNacVivos = parseInt(inpNacVivos ? inpNacVivos.value : 0, 10) || 0;
      var nNacMuertos = parseInt(inpNacMuertos ? inpNacMuertos.value : 0, 10) || 0;
      var nHijosViven = parseInt(inpHijosViven ? inpHijosViven.value : 0, 10) || 0;

      var msgs = [];
      if (nPartos > nGest) {
        msgs.push('⚠ El N.° de partos (' + nPartos + ') no puede superar el N.° de gestaciones previas (' + nGest + ').');
      }
      if (nAbortos > nGest) {
        msgs.push('⚠ El N.° de abortos (' + nAbortos + ') no puede superar el N.° de gestaciones previas (' + nGest + ').');
      }
      if (nCesareas > nPartos) {
        msgs.push('⚠ El N.° de cesáreas (' + nCesareas + ') no puede ser mayor que el N.° de partos (' + nPartos + ').');
      }
      if (nHijosViven > nNacVivos) {
        msgs.push('⚠ El N.° de hijos que viven (' + nHijosViven + ') no puede superar el N.° de nacidos vivos (' + nNacVivos + ').');
      }

      if (alertGineco) {
        if (msgs.length > 0) {
          alertGineco.innerHTML = msgs.join('<br>');
          alertGineco.style.display = 'block';
        } else {
          alertGineco.style.display = 'none';
          alertGineco.innerHTML = '';
        }
      }
    }

    // --- D. USO DE MÉTODO ANTICONCEPTIVO (Reglas 10, 11, 12) ---
    var metChks = Array.from(container.querySelectorAll('.o95MetodoChk'));
    var chkNoUso = metChks.find(function(c) { return c.value === 'NO_USO' || c.getAttribute('data-codigo') === 'NO_USO'; });
    var chkMetDesconocido = metChks.find(function(c) { return c.value === 'DESCONOCIDO' || c.getAttribute('data-codigo') === 'DESCONOCIDO'; });
    var chkMetOtro = metChks.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapMetOtro = document.getElementById('bloqueMetodoAnticonceptivoOtroO95');

    if (chkNoUso && chkNoUso.checked) {
      metChks.forEach(function(c) {
        if (c !== chkNoUso) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else if (chkMetDesconocido && chkMetDesconocido.checked) {
      metChks.forEach(function(c) {
        if (c !== chkMetDesconocido) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else {
      metChks.forEach(function(c) {
        c.disabled = false;
      });
    }

    if (wrapMetOtro && chkMetOtro) {
      wrapMetOtro.hidden = !chkMetOtro.checked;
      wrapMetOtro.style.display = chkMetOtro.checked ? '' : 'none';
    }
  }

  document.addEventListener('change', function(e) {
    if (!e.target) return;
    if (e.target.id === 'o95CausaGenericaSel' || e.target.name === campoPorClave('o95_causa_generica')) {
      actualizarCausasDefuncionO95();
    }
    if (e.target.classList.contains('o95PatologiaChk') ||
        e.target.classList.contains('o95MetodoChk') ||
        e.target.id === 'o95GestacionesInput' ||
        e.target.id === 'o95PartosInput' ||
        e.target.id === 'o95CesareasInput' ||
        e.target.id === 'o95AbortosInput' ||
        e.target.id === 'o95NacVivosInput' ||
        e.target.id === 'o95NacMuertosInput' ||
        e.target.id === 'o95HijosVivenInput') {
      actualizarAntecedentesPatologicosObstetricosO95();
    }
  });

  document.addEventListener('input', function(e) {
    if (!e.target) return;
    if (e.target.id === 'o95GestacionesInput' ||
        e.target.id === 'o95PartosInput' ||
        e.target.id === 'o95CesareasInput' ||
        e.target.id === 'o95AbortosInput' ||
        e.target.id === 'o95NacVivosInput' ||
        e.target.id === 'o95NacMuertosInput' ||
        e.target.id === 'o95HijosVivenInput') {
      actualizarAntecedentesPatologicosObstetricosO95();
    }
  });

  // Sanitización de entradas numéricas enteras: Bloqueo de 'e', 'E', '.', ',', '+', '-'
  document.addEventListener('keydown', function(e) {
    if (!e.target) return;
    var isNumInput = (e.target.type === 'number' || e.target.getAttribute('inputmode') === 'numeric' || e.target.classList.contains('solo-enteros'));
    var isDecimalAllowed = e.target.classList.contains('permite-decimales');

    if (isNumInput && !isDecimalAllowed) {
      if (['e', 'E', '.', ',', '+', '-'].includes(e.key)) {
        e.preventDefault();
      }
    }
  });

  document.addEventListener('input', function(e) {
    if (!e.target) return;
    var isNumInput = (e.target.type === 'number' || e.target.getAttribute('inputmode') === 'numeric' || e.target.classList.contains('solo-enteros'));
    var isDecimalAllowed = e.target.classList.contains('permite-decimales');

    if (isNumInput && !isDecimalAllowed) {
      if (/[eE\.\,\+\-]/.test(e.target.value)) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
      }
    }
  });

  function actualizarAtencionPrenatalO95() {
    var container = document.getElementById('bloqueAtencionPrenatalO95');
    if (!container) return;

    // 1. ¿Recibió APN?
    var rApnSi = document.getElementById('o95RecibioApnSi');
    var wrapApnDetalles = document.getElementById('bloqueApnDetallesO95');
    if (wrapApnDetalles) {
      var esApnSi = (rApnSi && rApnSi.checked);
      wrapApnDetalles.hidden = !esApnSi;
      wrapApnDetalles.style.display = esApnSi ? '' : 'none';
    }

    // Responsable = Otro
    var selResp = document.getElementById('o95ResponsableApnSel');
    var wrapRespOtro = document.getElementById('bloqueResponsableApnOtroO95');
    if (wrapRespOtro && selResp) {
      var valR = (selResp.value || '').toUpperCase().trim();
      var esRespOtro = (valR === 'OTRO');
      wrapRespOtro.hidden = !esRespOtro;
      wrapRespOtro.style.display = esRespOtro ? '' : 'none';
    }

    // 2. ¿Visitas domiciliarias?
    var rVisitasSi = document.getElementById('o95VisitasDomSi');
    var wrapVisitasDetalles = document.getElementById('bloqueVisitasDomDetallesO95');
    if (wrapVisitasDetalles) {
      var esVisitasSi = (rVisitasSi && rVisitasSi.checked);
      wrapVisitasDetalles.hidden = !esVisitasSi;
      wrapVisitasDetalles.style.display = esVisitasSi ? '' : 'none';
    }
  }

  function actualizarComplicacionesO95() {
    var container = document.getElementById('bloqueComplicacionesO95');
    if (!container) return;

    // 1. ¿Tuvo complicaciones? (Reglas 1, 2, 3)
    var rTuvoSi = document.getElementById('o95TuvoCompSi');
    var wrapGrupos = document.getElementById('bloqueGruposComplicacionesO95');
    if (wrapGrupos) {
      var esTuvoSi = (rTuvoSi && rTuvoSi.checked);
      wrapGrupos.hidden = !esTuvoSi;
      wrapGrupos.style.display = esTuvoSi ? '' : 'none';
    }

    // 2. Grupo 1: Complicaciones del embarazo
    var chksEmb = Array.from(container.querySelectorAll('.o95CompEmbChk'));
    var chkNingunaEmb = chksEmb.find(function(c) { return c.value === 'NINGUNA' || c.getAttribute('data-codigo') === 'NINGUNA'; });
    var chkOtroEmb = chksEmb.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapOtroEmb = document.getElementById('bloqueCompEmbOtroO95');

    if (chkNingunaEmb && chkNingunaEmb.checked) {
      chksEmb.forEach(function(c) {
        if (c !== chkNingunaEmb) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else {
      chksEmb.forEach(function(c) { c.disabled = false; });
    }
    if (wrapOtroEmb && chkOtroEmb) {
      wrapOtroEmb.hidden = !chkOtroEmb.checked;
      wrapOtroEmb.style.display = chkOtroEmb.checked ? '' : 'none';
    }

    // 3. Grupo 2: Complicaciones del parto
    var chksPart = Array.from(container.querySelectorAll('.o95CompPartChk'));
    var chkNingunaPart = chksPart.find(function(c) { return c.value === 'NINGUNA' || c.getAttribute('data-codigo') === 'NINGUNA'; });
    var chkOtroPart = chksPart.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapOtroPart = document.getElementById('bloqueCompPartOtroO95');

    if (chkNingunaPart && chkNingunaPart.checked) {
      chksPart.forEach(function(c) {
        if (c !== chkNingunaPart) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else {
      chksPart.forEach(function(c) { c.disabled = false; });
    }
    if (wrapOtroPart && chkOtroPart) {
      wrapOtroPart.hidden = !chkOtroPart.checked;
      wrapOtroPart.style.display = chkOtroPart.checked ? '' : 'none';
    }

    // 4. Grupo 3: Complicaciones del puerperio
    var chksPuer = Array.from(container.querySelectorAll('.o95CompPuerChk'));
    var chkNingunaPuer = chksPuer.find(function(c) { return c.value === 'NINGUNA' || c.getAttribute('data-codigo') === 'NINGUNA'; });
    var chkOtroPuer = chksPuer.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapOtroPuer = document.getElementById('bloqueCompPuerOtroO95');

    if (chkNingunaPuer && chkNingunaPuer.checked) {
      chksPuer.forEach(function(c) {
        if (c !== chkNingunaPuer) {
          c.checked = false;
          c.disabled = true;
        }
      });
    } else {
      chksPuer.forEach(function(c) { c.disabled = false; });
    }
    if (wrapOtroPuer && chkOtroPuer) {
      wrapOtroPuer.hidden = !chkOtroPuer.checked;
      wrapOtroPuer.style.display = chkOtroPuer.checked ? '' : 'none';
    }
  }

  function actualizarHospitalizacionesO95() {
    var container = document.getElementById('bloqueHospitalizacionesO95');
    if (!container) return;

    var rHospSi = document.getElementById('o95HospGestSi');
    var wrapCuantasHosp = document.getElementById('bloqueCuantasHospO95');
    if (wrapCuantasHosp) {
      var esHospSi = (rHospSi && rHospSi.checked);
      wrapCuantasHosp.hidden = !esHospSi;
      wrapCuantasHosp.style.display = esHospSi ? '' : 'none';
    }
  }

  function actualizarPartoAbortoO95() {
    var container = document.getElementById('bloquePartoAbortoO95');
    if (!container) return;

    // 6. Mejora del sistema: Si Momento del fallecimiento = EMBARAZO -> Establecer Tipo de parto = NO_APLICA, Fecha = No aplica
    var selMomento = document.getElementById('o95MomentoFallecimientoSel') || document.querySelector('[name="' + campoPorClave('o95_momento_del_fallecimiento') + '"]');
    var valMomento = selMomento ? (selMomento.value || '').toUpperCase().trim() : '';

    var chkDescon = document.getElementById('o95FechaPartoDesconChk');
    var chkNA = document.getElementById('o95FechaPartoNoAplicaChk');
    var inFecha = document.getElementById('o95FechaPartoInput');

    var selLugar = document.getElementById('o95LugarPartoSel');
    var wrapEess = document.getElementById('bloqueLugarPartoEessO95');
    var wrapLugarOtro = document.getElementById('bloqueLugarPartoOtroO95');

    var selTipo = document.getElementById('o95TipoPartoSel');
    var selResp = document.getElementById('o95RespPartoSel');
    var wrapRespOtro = document.getElementById('bloqueRespPartoOtroO95');

    var rNecroSi = document.getElementById('o95NecropsiaSi');
    var wrapCausaNecro = document.getElementById('bloqueNecropsiaCausaO95');

    if (valMomento === 'EMBARAZO' || valMomento === 'DURANTE EL EMBARAZO') {
      if (chkNA && !chkNA.disabled && !chkNA.checked && (!inFecha || !inFecha.value)) {
        chkNA.checked = true;
        if (chkDescon) chkDescon.checked = false;
      }
      if (selTipo && (!selTipo.value || selTipo.value === 'VAGINAL' || selTipo.value === 'CESAREA')) {
        selTipo.value = 'NO_APLICA';
      }
      if (selLugar && (!selLugar.value || selLugar.value === 'DOMICILIO' || selLugar.value === 'EESS')) {
        selLugar.value = 'NO_APLICA';
      }
    }

    // 1. Fecha de parto o aborto (Desconocida / No aplica)
    if (inFecha) {
      var deshabilitarFecha = (chkDescon && chkDescon.checked) || (chkNA && chkNA.checked);
      inFecha.disabled = deshabilitarFecha;
      if (deshabilitarFecha) {
        inFecha.value = '';
      }
    }

    // 2. Lugar del parto o aborto
    if (selLugar) {
      var vLugar = (selLugar.value || '').toUpperCase().trim();
      var esEess = (vLugar === 'EESS' || vLugar === 'EN EESS');
      var esLugarOtro = (vLugar === 'OTRO');

      if (wrapEess) {
        wrapEess.hidden = !esEess;
        wrapEess.style.display = esEess ? '' : 'none';
      }
      if (wrapLugarOtro) {
        wrapLugarOtro.hidden = !esLugarOtro;
        wrapLugarOtro.style.display = esLugarOtro ? '' : 'none';
      }
    }

    // 4. Responsable de la atención
    if (selResp) {
      var vResp = (selResp.value || '').toUpperCase().trim();
      var esRespOtro = (vResp === 'OTRO');
      if (wrapRespOtro) {
        wrapRespOtro.hidden = !esRespOtro;
        wrapRespOtro.style.display = esRespOtro ? '' : 'none';
      }
    }

    // 5. Necropsia
    if (wrapCausaNecro) {
      var esNecroSi = (rNecroSi && rNecroSi.checked);
      wrapCausaNecro.hidden = !esNecroSi;
      wrapCausaNecro.style.display = esNecroSi ? '' : 'none';
    }
  }

  function actualizarEntornoSocialO95() {
    var container = document.getElementById('bloqueEntornoSocialO95');
    if (!container) return;

    // 1. Identificaron signos de peligro
    var rIdentSi = document.getElementById('o95IdentSignosSi');
    var wrapIdent = document.getElementById('bloqueIdentificaronSignosO95');
    var selPersIdent = document.getElementById('o95PersonaIdentificoSel');
    var wrapPersIdentOtro = document.getElementById('bloquePersonaIdentificoOtroO95');

    var esIdentSi = (rIdentSi && rIdentSi.checked);
    if (wrapIdent) {
      wrapIdent.hidden = !esIdentSi;
      wrapIdent.style.display = esIdentSi ? '' : 'none';
    }
    if (wrapPersIdentOtro && selPersIdent) {
      var vPI = (selPersIdent.value || '').toUpperCase().trim();
      var esPIOtro = (vPI === 'OTRO');
      wrapPersIdentOtro.hidden = !esPIOtro;
      wrapPersIdentOtro.style.display = esPIOtro ? '' : 'none';
    }

    // 2. Buscaron ayuda
    var rAyudaSi = document.getElementById('o95BuscaronAyudaSi');
    var wrapAyuda = document.getElementById('bloqueBuscaronAyudaO95');
    var selDecis = document.getElementById('o95DecisionBuscarAyudaSel');
    var wrapDecisOtro = document.getElementById('bloqueDecisionBuscarAyudaOtroO95');

    var esAyudaSi = (rAyudaSi && rAyudaSi.checked);
    if (wrapAyuda) {
      wrapAyuda.hidden = !esAyudaSi;
      wrapAyuda.style.display = esAyudaSi ? '' : 'none';
    }
    if (wrapDecisOtro && selDecis) {
      var vDec = (selDecis.value || '').toUpperCase().trim();
      var esDecOtro = (vDec === 'OTRO');
      wrapDecisOtro.hidden = !esDecOtro;
      wrapDecisOtro.style.display = esDecOtro ? '' : 'none';
    }

    // 3. Dificultad con acceso
    var rAccesoSi = document.getElementById('o95DificultadAccesoSi');
    var wrapAcceso = document.getElementById('bloqueDificultadAccesoO95');
    var wrapAccesoOtro = document.getElementById('bloqueDificultadAccesoOtroO95');
    var chksAcceso = Array.from(container.querySelectorAll('.o95DifAccesoChk'));
    var chkAccesoOtro = chksAcceso.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });

    var esAccesoSi = (rAccesoSi && rAccesoSi.checked);
    if (wrapAcceso) {
      wrapAcceso.hidden = !esAccesoSi;
      wrapAcceso.style.display = esAccesoSi ? '' : 'none';
    }
    if (wrapAccesoOtro && chkAccesoOtro) {
      var esAccOtro = chkAccesoOtro.checked;
      wrapAccesoOtro.hidden = !esAccOtro;
      wrapAccesoOtro.style.display = esAccOtro ? '' : 'none';
    }

    // 4. Dificultad con atencion
    var rAtencSi = document.getElementById('o95DificultadAtencionSi');
    var wrapAtenc = document.getElementById('bloqueDificultadAtencionO95');
    var wrapAtencOtro = document.getElementById('bloqueDificultadAtencionOtroO95');
    var chksAtenc = Array.from(container.querySelectorAll('.o95DifAtencChk'));
    var chkAtencOtro = chksAtenc.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });

    var esAtencSi = (rAtencSi && rAtencSi.checked);
    if (wrapAtenc) {
      wrapAtenc.hidden = !esAtencSi;
      wrapAtenc.style.display = esAtencSi ? '' : 'none';
    }
    if (wrapAtencOtro && chkAtencOtro) {
      var esAtOtro = chkAtencOtro.checked;
      wrapAtencOtro.hidden = !esAtOtro;
      wrapAtencOtro.style.display = esAtOtro ? '' : 'none';
    }

    // 5. Persona que brindo informacion
    var selPersInfo = document.getElementById('o95PersonaBrindoInfoSel');
    var wrapPersInfoOtro = document.getElementById('bloquePersonaBrindoInfoOtroO95');
    if (wrapPersInfoOtro && selPersInfo) {
      var vInfo = (selPersInfo.value || '').toUpperCase().trim();
      var esInfoOtro = (vInfo === 'OTRO');
      wrapPersInfoOtro.hidden = !esInfoOtro;
      wrapPersInfoOtro.style.display = esInfoOtro ? '' : 'none';
    }

    // Validar limites de minutos (0-59)
    ['o95TiempoBuscarAyudaMinutosInput', 'o95TiempoLlegarEessMinutosInput', 'o95TiempoHastaAtendidaMinutosInput'].forEach(function(id) {
      var elem = document.getElementById(id);
      if (elem && elem.value !== '') {
        var v = parseInt(elem.value, 10);
        if (isNaN(v) || v < 0) elem.value = 0;
        else if (v > 59) elem.value = 59;
      }
    });
  }

  function actualizarDatosComunitariosO95() {
    var container = document.getElementById('bloqueDatosComunitariosO95');
    if (!container) return;

    var chkEnable = document.getElementById('o95HabilitarDatosComunitariosChk');
    var wrapContenido = document.getElementById('bloqueContenidoComunitarioO95');

    // 0. Autodetectar si Lugar de fallecimiento es extrainstitucional
    // Antes: 'campo_14300' es v99_aseguradora (otra ficha, ver MAPA_IDS_CAMPOS.md),
    // nunca calzaba con nada del DOM -- esta autodeteccion nunca se disparaba.
    // "Lugar del fallecimiento" son radios (o95-lugar-radio), no un <select>:
    // hace falta :checked para leer el que el usuario realmente marco, si no
    // siempre devuelve el primer radio del DOM sin importar cual esta activo.
    var selLugarDef = document.getElementById('o95LugarFallecimientoSel') || document.querySelector('[name="' + campoPorClave('o95_lugar_del_fallecimiento') + '"]:checked');
    if (selLugarDef) {
      var vLugarDef = (selLugarDef.value || '').toUpperCase().trim();
      if (vLugarDef === 'DOMICILIO' || vLugarDef === 'TRAYECTO' || vLugarDef === 'OTRO') {
        if (chkEnable && !chkEnable.checked && !chkEnable.dataset.userToggled) {
          chkEnable.checked = true;
        }
      }
    }

    var esHabilitado = (chkEnable && chkEnable.checked);
    if (wrapContenido) {
      wrapContenido.hidden = !esHabilitado;
      wrapContenido.style.display = esHabilitado ? '' : 'none';
    }

    // 1. Sintomatologia
    var chksSintom = Array.from(container.querySelectorAll('.o95SintomChk'));
    var chkSintomOtro = chksSintom.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapSintomOtro = document.getElementById('bloqueSintomatologiaOtroO95');
    if (wrapSintomOtro && chkSintomOtro) {
      var esSOtro = chkSintomOtro.checked;
      wrapSintomOtro.hidden = !esSOtro;
      wrapSintomOtro.style.display = esSOtro ? '' : 'none';
    }

    // 2. Maniobras durante el parto
    var chksManPart = Array.from(container.querySelectorAll('.o95ManPartChk'));
    var chkManPartNoUso = chksManPart.find(function(c) { return c.value === 'NO_SE_USO' || c.getAttribute('data-codigo') === 'NO_SE_USO'; });
    var chkManPartOtro  = chksManPart.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapManPartOtro = document.getElementById('bloqueManiobrasPartoOtroO95');

    if (chkManPartNoUso && chkManPartNoUso.checked) {
      chksManPart.forEach(function(c) {
        if (c !== chkManPartNoUso) {
          c.checked = false;
          c.disabled = true;
        }
      });
      if (wrapManPartOtro) {
        wrapManPartOtro.hidden = true;
        wrapManPartOtro.style.display = 'none';
      }
    } else {
      chksManPart.forEach(function(c) { c.disabled = false; });
      if (wrapManPartOtro && chkManPartOtro) {
        var esMPOtro = chkManPartOtro.checked;
        wrapManPartOtro.hidden = !esMPOtro;
        wrapManPartOtro.style.display = esMPOtro ? '' : 'none';
      }
    }

    // 3. Maniobras para retirar placenta
    var chksManPlac = Array.from(container.querySelectorAll('.o95ManPlacChk'));
    var chkManPlacNoUso = chksManPlac.find(function(c) { return c.value === 'NO_SE_USO' || c.getAttribute('data-codigo') === 'NO_SE_USO'; });
    var chkManPlacOtro  = chksManPlac.find(function(c) { return c.value === 'OTRO' || c.getAttribute('data-codigo') === 'OTRO'; });
    var wrapManPlacOtro = document.getElementById('bloqueManiobrasPlacentaOtroO95');

    if (chkManPlacNoUso && chkManPlacNoUso.checked) {
      chksManPlac.forEach(function(c) {
        if (c !== chkManPlacNoUso) {
          c.checked = false;
          c.disabled = true;
        }
      });
      if (wrapManPlacOtro) {
        wrapManPlacOtro.hidden = true;
        wrapManPlacOtro.style.display = 'none';
      }
    } else {
      chksManPlac.forEach(function(c) { c.disabled = false; });
      if (wrapManPlacOtro && chkManPlacOtro) {
        var esMPlOtro = chkManPlacOtro.checked;
        wrapManPlacOtro.hidden = !esMPlOtro;
        wrapManPlacOtro.style.display = esMPlOtro ? '' : 'none';
      }
    }

    // Validar limites de minutos (0-59)
    var inMinDom = document.getElementById('o95TiempoDomicilioEessMinutosInput');
    if (inMinDom && inMinDom.value !== '') {
      var vM = parseInt(inMinDom.value, 10);
      if (isNaN(vM) || vM < 0) inMinDom.value = 0;
      else if (vM > 59) inMinDom.value = 59;
    }
  }

  function actualizarInvestigadorProfesion() {
    var selProf = document.getElementById('investigadorProfesionSel');
    var wrapOtra = document.getElementById('bloqueInvestigadorProfesionOtra');
    if (!selProf || !wrapOtra) return;

    var valP = (selProf.value || '').trim();
    var esOtro = (valP === 'Otro');

    wrapOtra.hidden = !esOtro;
    wrapOtra.style.display = esOtro ? '' : 'none';
  }

  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.demora-help-btn');
    document.querySelectorAll('.demora-row').forEach(function(r) { r.classList.remove('has-active-tooltip'); });
    if (btn) {
      var wasActive = btn.classList.contains('active');
      document.querySelectorAll('.demora-help-btn.active').forEach(function(b) { b.classList.remove('active'); });
      if (!wasActive) {
        btn.classList.add('active');
        var row = btn.closest('.demora-row');
        if (row) row.classList.add('has-active-tooltip');
      }
    } else {
      document.querySelectorAll('.demora-help-btn.active').forEach(function(b) { b.classList.remove('active'); });
    }
  });

  document.addEventListener('change', function(e) {
    if (!e.target) return;
    if (e.target.name === campoPorClave('b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con') || e.target.name === campoPorClave('b26_tuvo_contacto_con_gestante') || e.target.classList.contains('radio-contacto-caso-b26') || e.target.classList.contains('radio-contacto-gestante-b26')) {
      actualizarLugarInfeccionB26();
    }
    if (e.target.name === campoPorClave('b26_presento_inflamacion_de_glandulas_parotidas') || e.target.name === campoPorClave('b26_hospitalizacion') || e.target.name === campoPorClave('b26_condicion_de_egreso') ||
        e.target.classList.contains('chk-complicacion-b26') || e.target.classList.contains('radio-parotidas-b26') ||
        e.target.classList.contains('radio-hospitalizacion-b26') || e.target.classList.contains('radio-egreso-b26')) {
      actualizarCuadroClinicoB26();
    }
    if (e.target.name === 'b26_vacunacion_spr' || e.target.classList.contains('radio-vacuna-spr-b26')) {
      actualizarVacunacionB26();
    }
    if (e.target.classList.contains('sel-lugar-tipo-b26')) {
      var row = e.target.closest('.row-lugar-b26');
      if (row) {
        var inpNombre = row.querySelector('.inp-lugar-nombre-b26');
        if (inpNombre) {
          var esCasa = (e.target.value === 'CASA');
          inpNombre.disabled = esCasa;
          inpNombre.placeholder = esCasa ? '— No aplica —' : 'Nombre del lugar…';
          if (esCasa) inpNombre.value = '';
        }
      }
    }
    if (e.target.name === 'sexo' || e.target.id === 'sexo' || e.target.name === 'gestante' || e.target.id === 'gestanteSel') {
      actualizarGestante();
    }
    if (e.target.name === 'etnia' || e.target.id === 'etniaSel') {
      actualizarEtniaOtra(true);
    }
    if (e.target.name === 'clasificacion' || e.target.name === campoPorClave('o95_clasificacion_final_de_la_muerte') || e.target.name === campoPorClave('o95_clasificacion_inicial')) {
      sincronizarClasificacionO95(e.target);
    }
    if (e.target.id === 'investigadorProfesionSel') {
      actualizarInvestigadorProfesion();
    }
    if (e.target.id === 'o95HabilitarDatosComunitariosChk') {
      e.target.dataset.userToggled = 'true';
      actualizarDatosComunitariosO95();
    }
    if (e.target.classList.contains('o95SintomChk') ||
        e.target.classList.contains('o95ManPartChk') ||
        e.target.classList.contains('o95ManPlacChk') ||
        e.target.name === campoPorClave('o95_lugar_del_fallecimiento')) {
      actualizarDatosComunitariosO95();
    }
    if (e.target.name === campoPorClave('o95_identificaron_signos_de_peligro') ||
        e.target.id === 'o95PersonaIdentificoSel' ||
        e.target.name === campoPorClave('o95_buscaron_ayuda') ||
        e.target.id === 'o95DecisionBuscarAyudaSel' ||
        e.target.name === campoPorClave('o95_hubo_dificultad_con_el_acceso_a_servicios_de_salud') ||
        e.target.classList.contains('o95DifAccesoChk') ||
        e.target.name === campoPorClave('o95_tuvo_dificultades_para_ser_atendida_en_el_ee_ss') ||
        e.target.classList.contains('o95DifAtencChk') ||
        e.target.id === 'o95PersonaBrindoInfoSel') {
      actualizarEntornoSocialO95();
    }
    if (e.target.id === 'o95FechaPartoDesconChk') {
      var cNA = document.getElementById('o95FechaPartoNoAplicaChk');
      if (e.target.checked && cNA) cNA.checked = false;
      actualizarPartoAbortoO95();
    }
    if (e.target.id === 'o95FechaPartoNoAplicaChk') {
      var cD = document.getElementById('o95FechaPartoDesconChk');
      if (e.target.checked && cD) cD.checked = false;
      actualizarPartoAbortoO95();
    }
    if (e.target.id === 'o95LugarPartoSel' ||
        e.target.id === 'o95TipoPartoSel' ||
        e.target.id === 'o95RespPartoSel' ||
        e.target.name === campoPorClave('o95_necropsia') ||
        e.target.name === campoPorClave('o95_momento_del_fallecimiento')) {
      actualizarPartoAbortoO95();
    }
    if (e.target.id === 'o95ReferidaSel' ||
        e.target.name === campoPorClave('o95_referida') ||
        e.target.id === 'o95RespOrigenSel' ||
        e.target.name === campoPorClave('o95_responsable_atencion_eess_origen') ||
        e.target.id === 'o95FechaIngOrigenInput' ||
        e.target.id === 'o95HoraIngOrigenInput' ||
        e.target.id === 'o95FechaEgrOrigenInput' ||
        e.target.id === 'o95HoraEgrOrigenInput') {
      actualizarReferenciaO95();
    }
    if (e.target.name === campoPorClave('o95_hospitalizaciones_en_la_gestacion_puerperio')) {
      actualizarHospitalizacionesO95();
    }
    if (e.target.name === campoPorClave('o95_recibio_apn') ||
        e.target.name === campoPorClave('o95_se_realizaron_visitas_domiciliarias') ||
        e.target.id === 'o95ResponsableApnSel' ||
        e.target.name === campoPorClave('o95_responsable_de_la_apn')) {
      actualizarAtencionPrenatalO95();
    }
    if (e.target.name === campoPorClave('o95_tuvo_complicaciones') ||
        e.target.classList.contains('o95CompEmbChk') ||
        e.target.classList.contains('o95CompPartChk') ||
        e.target.classList.contains('o95CompPuerChk')) {
      actualizarComplicacionesO95();
    }
  });

  document.addEventListener('input', function(e) {
    if (!e.target) return;
    if (e.target.id === 'o95TiempoDomicilioEessMinutosInput') {
      actualizarDatosComunitariosO95();
    }
    if (e.target.id === 'o95TiempoBuscarAyudaMinutosInput' ||
        e.target.id === 'o95TiempoLlegarEessMinutosInput' ||
        e.target.id === 'o95TiempoHastaAtendidaMinutosInput') {
      actualizarEntornoSocialO95();
    }
    if (e.target.id === 'o95FechaIngOrigenInput' ||
        e.target.id === 'o95HoraIngOrigenInput' ||
        e.target.id === 'o95FechaEgrOrigenInput' ||
        e.target.id === 'o95HoraEgrOrigenInput') {
      actualizarReferenciaO95();
    }
  });

  actualizarPuebloEtnicoO95();
  actualizarPuebloEtnicoB05();
  actualizarOtrosCamposO95();
  actualizarDatosFallecimientoO95();
  actualizarReferenciaO95();
  actualizarCausasDefuncionO95();
  actualizarAntecedentesPatologicosObstetricosO95();
  actualizarAtencionPrenatalO95();
  actualizarComplicacionesO95();
  actualizarHospitalizacionesO95();
  actualizarPartoAbortoO95();
  actualizarEntornoSocialO95();
  actualizarDatosComunitariosO95();
  actualizarInvestigadorProfesion();

  // B05 Clasificación final -> Sincronizar clasificación del caso al final del formulario
  function actualizarClasificacionCasoB05() {
    var selectClasif = document.querySelector('[name="' + campoPorClave('b05_clasificacion') + '"]');
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
    if (e.target && (e.target.name === campoPorClave('b05_clasificacion') || (e.target.tagName === 'SELECT' && e.target.querySelector('option[value="SARAMPION"]')))) {
      actualizarClasificacionCasoB05();
    }
  });

  actualizarClasificacionCasoB05();

  // B05 Cálculos automáticos para Sección VIII (Investigación epidemiológica)
  function calcularTotalesB05() {
    // 1. Total casas = Casas abiertas + Casas cerradas + Casas abandonadas
    var inputAbiertas = document.querySelector('[name="' + campoPorClave('b05_casas_abiertas') + '"]');
    var inputCerradas = document.querySelector('[name="' + campoPorClave('b05_casas_cerradas') + '"]');
    var inputAbandonadas = document.querySelector('[name="' + campoPorClave('b05_casas_abandonadas') + '"]');
    var inputTotalCasas = document.querySelector('[name="' + campoPorClave('b05_total_de_casas') + '"]');

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
    var inputVacMenor1 = document.querySelector('[name="' + campoPorClave('vacunados_bloqueo_menor_1') + '"]');
    var inputVac14 = document.querySelector('[name="' + campoPorClave('vacunados_bloqueo_1_4') + '"]');
    var inputVac514 = document.querySelector('[name="' + campoPorClave('vacunados_bloqueo_5_14') + '"]');
    var inputVacMayor15 = document.querySelector('[name="' + campoPorClave('vacunados_bloqueo_mayor_15') + '"]');
    var inputTotalVac = document.querySelector('[name="' + campoPorClave('b05_numero_de_vacunados_en_el_bloqueo') + '"]');

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
    if (e.target && (e.target.id === 'fechaInicioSintomasB26' || e.target.id === 'fechaHospitalizacionB26')) {
      actualizarCuadroClinicoB26();
    }
    if (e.target && e.target.type === 'number' && e.target.classList.contains('inp-dias-pos-b26')) {
      var valPos = parseInt(e.target.value, 10);
      if (isNaN(valPos) || valPos < 1) {
        e.target.value = (e.target.value === '' ? '' : '1');
      }
    }
    if (e.target && e.target.type === 'number' && (e.target.classList.contains('inp-lugar-sanos-b26') || e.target.classList.contains('inp-lugar-enfermos-b26'))) {
      var val = parseInt(e.target.value, 10);
      if (isNaN(val) || val < 0) {
        e.target.value = (e.target.value === '' ? '' : '0');
      }
    }
    if (e.target && e.target.name && (
      e.target.name === campoPorClave('b05_casas_abiertas') || e.target.name === campoPorClave('b05_casas_cerradas') || e.target.name === campoPorClave('b05_casas_abandonadas') ||
      e.target.name === campoPorClave('vacunados_bloqueo_menor_1') || e.target.name === campoPorClave('vacunados_bloqueo_1_4') || e.target.name === campoPorClave('vacunados_bloqueo_5_14') || e.target.name === campoPorClave('vacunados_bloqueo_mayor_15')
    )) {
      calcularTotalesB05();
    }
  });

  document.addEventListener('change', function(e) {
    if (e.target && e.target.name && (
      e.target.name === campoPorClave('b05_casas_abiertas') || e.target.name === campoPorClave('b05_casas_cerradas') || e.target.name === campoPorClave('b05_casas_abandonadas') ||
      e.target.name === campoPorClave('vacunados_bloqueo_menor_1') || e.target.name === campoPorClave('vacunados_bloqueo_1_4') || e.target.name === campoPorClave('vacunados_bloqueo_5_14') || e.target.name === campoPorClave('vacunados_bloqueo_mayor_15')
    )) {
      calcularTotalesB05();
    }
  });

  calcularTotalesB05();
});



