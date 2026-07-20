function inicializarUbigeo(prefijo) {
  var selDep = document.getElementById(prefijo + '-departamento');
  var selProv = document.getElementById(prefijo + '-provincia');
  var selDist = document.getElementById(prefijo + '-distrito');
  if (!selDep || !selProv || !selDist) return;

  function llenarOpciones(select, items, placeholder) {
    select.innerHTML = '';
    var vacia = document.createElement('option');
    vacia.value = '';
    vacia.textContent = placeholder;
    select.appendChild(vacia);
    items.forEach(function (item) {
      var opcion = document.createElement('option');
      opcion.value = item.id;
      opcion.textContent = item.nombre;
      select.appendChild(opcion);
    });
    if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(select);
  }

  selDep.addEventListener('change', function () {
    llenarOpciones(selProv, [], 'Seleccionar…');
    llenarOpciones(selDist, [], 'Seleccionar…');
    if (!selDep.value) return;

    fetch('/api/provincias?departamento=' + encodeURIComponent(selDep.value))
      .then(function (resp) { return resp.json(); })
      .then(function (provincias) { llenarOpciones(selProv, provincias, 'Seleccionar…'); });
  });

  selProv.addEventListener('change', function () {
    llenarOpciones(selDist, [], 'Seleccionar…');
    if (!selProv.value) return;

    fetch('/api/distritos?provincia=' + encodeURIComponent(selProv.value))
      .then(function (resp) { return resp.json(); })
      .then(function (distritos) { llenarOpciones(selDist, distritos, 'Seleccionar…'); });
  });

  window['_ubigeoLlenar_' + prefijo] = llenarOpciones;
}

/**
 * Selecciona en cascada departamento → provincia → distrito de forma
 * programática (por ejemplo, tras autocompletar un paciente encontrado).
 * Requiere que inicializarUbigeo(prefijo) ya se haya ejecutado.
 */
function establecerUbigeo(prefijo, departamentoId, provinciaId, distritoId) {
  var selDep = document.getElementById(prefijo + '-departamento');
  var selProv = document.getElementById(prefijo + '-provincia');
  var selDist = document.getElementById(prefijo + '-distrito');
  var llenarOpciones = window['_ubigeoLlenar_' + prefijo];
  if (!selDep || !selProv || !selDist || !llenarOpciones || !departamentoId) return;

  selDep.value = departamentoId;
  if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(selDep);

  fetch('/api/provincias?departamento=' + encodeURIComponent(departamentoId))
    .then(function (resp) { return resp.json(); })
    .then(function (provincias) {
      llenarOpciones(selProv, provincias, 'Seleccionar…');
      selProv.value = provinciaId || '';
      if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(selProv);
      if (!provinciaId) return Promise.reject('sin-provincia');

      return fetch('/api/distritos?provincia=' + encodeURIComponent(provinciaId))
        .then(function (resp) { return resp.json(); })
        .then(function (distritos) {
          llenarOpciones(selDist, distritos, 'Seleccionar…');
          selDist.value = distritoId || '';
          if (window.SelectorBusqueda) window.SelectorBusqueda.actualizar(selDist);
        });
    })
    .catch(function () { /* sin provincia/distrito previo: se deja el departamento nada más */ });
}
