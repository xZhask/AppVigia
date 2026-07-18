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

          var tagCie = document.getElementById('cieTag');
          if (tagCie) tagCie.textContent = 'CIE-10 · ' + datos.cie10;
        })
        .catch(function () {
          contenedorClinico.style.opacity = '1';
          toast('No se pudo cargar el cuadro clínico. Intenta de nuevo.');
        });
    });
  }

  var fechaNac = document.getElementById('fechaNac');
  var edadCalculada = document.getElementById('edadCalculada');
  function calcularEdad() {
    if (!fechaNac || !edadCalculada) return;
    var partes = fechaNac.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!partes) { edadCalculada.value = ''; return; }

    var nacimiento = new Date(partes[3], partes[2] - 1, partes[1]);
    var hoy = new Date();
    var edad = hoy.getFullYear() - nacimiento.getFullYear();
    var aunNoCumple = (hoy.getMonth() < nacimiento.getMonth()) ||
      (hoy.getMonth() === nacimiento.getMonth() && hoy.getDate() < nacimiento.getDate());
    if (aunNoCumple) edad--;

    edadCalculada.value = edad >= 0 ? edad + ' años' : '';
  }
  if (fechaNac) fechaNac.addEventListener('change', calcularEdad);
  calcularEdad();

  // ---------- Es efectivo PNP: mostrar/ocultar campos ----------
  var esPnp = document.getElementById('esPnp');
  var pnpFields = document.getElementById('pnpFields');
  if (esPnp && pnpFields) {
    esPnp.addEventListener('change', function () {
      pnpFields.hidden = !esPnp.checked;
    });
  }

  // ---------- Buscar en padrón + duplicados ----------
  var btnBuscar = document.getElementById('btnBuscarPaciente');
  if (btnBuscar) {
    btnBuscar.addEventListener('click', function () {
      var tipoDoc = document.getElementById('tipoDoc').value;
      var numDoc = document.getElementById('numDoc').value.trim();
      if (!numDoc) { toast('Ingresa el número de documento primero.'); return; }

      var parametros = new URLSearchParams({
        tipo_doc: tipoDoc,
        num_doc: numDoc,
        enfermedad_id: document.getElementById('diseaseSel') ? document.getElementById('diseaseSel').value : '',
        fecha_notif: document.getElementById('fechaNotif') ? document.getElementById('fechaNotif').value : '',
      });

      fetch('/casos/nuevo/paciente?' + parametros.toString())
        .then(function (resp) { return resp.json(); })
        .then(pintarBusquedaPaciente)
        .catch(function () { toast('No se pudo consultar el padrón. Intenta de nuevo.'); });
    });
  }

  function iniciales(nombreCompleto) {
    var palabras = nombreCompleto.trim().split(/\s+/).filter(Boolean);
    if (!palabras.length) return '';
    if (palabras.length === 1) return palabras[0].substring(0, 2).toUpperCase();
    return (palabras[0].charAt(0) + palabras[palabras.length - 1].charAt(0)).toUpperCase();
  }

  function pintarBusquedaPaciente(datos) {
    var dupe = document.getElementById('dupe');
    var dupeTexto = document.getElementById('dupeTexto');
    if (dupe && dupeTexto) {
      dupeTexto.innerHTML = '';
      if (datos.duplicado) {
        var d = datos.duplicado;
        var negrita1 = document.createElement('b');
        negrita1.textContent = 'Posible duplicado.';
        var enlace = document.createElement('a');
        enlace.href = d.url;
        enlace.textContent = 'Ver ficha ' + d.codigo;
        dupeTexto.appendChild(negrita1);
        dupeTexto.appendChild(document.createTextNode(
          ' Ya existe una ficha (' + d.codigo + ') para este documento y esta enfermedad, notificada en la SE ' +
          d.semana_epi + ' · ' + d.anio_epi + ' desde ' + d.establecimiento_nombre + '. Revisa antes de continuar. '
        ));
        dupeTexto.appendChild(enlace);
        dupe.hidden = false;
      } else {
        dupe.hidden = true;
      }
    }

    var found = document.getElementById('found');
    if (!datos.paciente) {
      if (found) found.style.display = 'none';
      toast('No se encontró un paciente con ese documento. Completa los datos manualmente.');
      return;
    }

    var p = datos.paciente;
    document.getElementById('apellidosNombres').value = p.apellidos_nombres || '';
    document.getElementById('sexo').value = p.sexo || '';
    if (fechaNac) { fechaNac.value = p.fecha_nac || ''; calcularEdad(); }

    document.getElementById('esPnp').checked = !!p.es_pnp;
    if (pnpFields) pnpFields.hidden = !p.es_pnp;
    document.getElementById('cip').value = p.cip || '';
    document.getElementById('situacionPnp').value = p.situacion_pnp || '';
    document.getElementById('gradoId').value = p.grado_id || '';
    document.getElementById('unidadId').value = p.unidad_id || '';
    document.getElementById('tipoBeneficiario').value = p.tipo_beneficiario || '';

    if (p.departamento_id && typeof establecerUbigeo === 'function') {
      establecerUbigeo('pac-ubigeo', p.departamento_id, p.provincia_id, p.distrito_id);
    }

    if (found) {
      document.getElementById('foundIniciales').textContent = iniciales(p.apellidos_nombres || '');
      document.getElementById('foundNombre').textContent = p.apellidos_nombres || '';
      var detalle = [document.getElementById('tipoDoc').value + ' ' + document.getElementById('numDoc').value];
      if (p.sexo) detalle.push(p.sexo);
      if (p.edad !== null && p.edad !== undefined) detalle.push(p.edad + 'a');
      if (p.es_pnp) detalle.push('Efectivo PNP');
      document.getElementById('foundDetalle').textContent = detalle.join(' · ');
      found.style.display = 'flex';
    }
  }
});
