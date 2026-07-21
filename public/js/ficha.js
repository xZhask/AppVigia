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
    var grupo = document.querySelectorAll('input[name="' + nombre + '[]"]:checked');
    if (grupo.length) return Array.prototype.map.call(grupo, function (el) { return el.value; });

    var checkbox = document.querySelector('input[name="' + nombre + '"][type="checkbox"]');
    if (checkbox) return checkbox.checked ? '1' : '0';

    var el = document.querySelector('[name="' + nombre + '"]');
    return el ? el.value : null;
  }

  function evaluarDependencias() {
    document.querySelectorAll('.dep-wrap[data-depende-de]').forEach(function (wrap) {
      var nombrePadre = wrap.getAttribute('data-depende-de');
      var activador = wrap.getAttribute('data-valor-activador');
      var valorActual = leerValorCampoPorNombre(nombrePadre);
      var visible = Array.isArray(valorActual) ? valorActual.indexOf(activador) !== -1 : valorActual === activador;

      if (visible === !wrap.hidden) return;
      wrap.hidden = !visible;

      if (!visible) {
        wrap.querySelectorAll('input, select, textarea').forEach(function (el) {
          if (el.type === 'checkbox' || el.type === 'radio') {
            el.checked = false;
          } else {
            el.value = '';
          }
          el.dispatchEvent(new Event('change', { bubbles: true }));
          if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(el);
        });
      }
    });
  }
  evaluarDependencias();
  document.addEventListener('input', evaluarDependencias);
  document.addEventListener('change', evaluarDependencias);

  // ---------- Núcleo: gestante solo si sexo=F, semanas solo si gestante=Sí ----------
  var sexoSel = document.querySelector('[name="sexo"]');
  var gestanteSel = document.getElementById('gestanteSel');
  var campoGestante = document.getElementById('campoGestante');
  var campoSemanas = document.getElementById('campoSemanasGestacion');

  function actualizarGestante() {
    if (!campoGestante || !campoSemanas) return;
    var esFemenino = sexoSel && sexoSel.value === 'F';
    campoGestante.hidden = !esFemenino;
    if (!esFemenino && gestanteSel) gestanteSel.value = '';

    var esGestante = esFemenino && gestanteSel && gestanteSel.value === '1';
    campoSemanas.hidden = !esGestante;
    if (!esGestante) {
      var semanas = document.getElementById('semanasGestacion');
      if (semanas) semanas.value = '';
    }
  }
  if (sexoSel) sexoSel.addEventListener('change', actualizarGestante);
  if (gestanteSel) gestanteSel.addEventListener('change', actualizarGestante);
  actualizarGestante();

  if (selectorEnfermedad && contenedorClinico) {
    selectorEnfermedad.addEventListener('change', function () {
      var enfermedadId = selectorEnfermedad.value;
      contenedorClinico.style.opacity = '0.5';

      fetch('/casos/nuevo/secciones-clinicas?enfermedad_id=' + encodeURIComponent(enfermedadId))
        .then(function (resp) { return resp.json(); })
        .then(function (datos) {
          contenedorClinico.innerHTML = datos.html;
          contenedorClinico.style.opacity = '1';
          renumerarSeccionesSiguientes();
          if (window.SelectorBusqueda) window.SelectorBusqueda.escanear(contenedorClinico);
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
          if (inputSeleccionado.value === 'SI') {
            fila.classList.add('is-si');
            var labelText = fila.querySelector('.row-label');
            if (labelText) {
              labelText.style.color = 'var(--ink)';
              labelText.style.fontWeight = '500';
            }
          } else {
            fila.classList.remove('is-si');
            var labelText = fila.querySelector('.row-label');
            if (labelText) {
              labelText.style.color = 'var(--ink-2)';
              labelText.style.fontWeight = 'normal';
            }
          }
        }
      }

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
});
