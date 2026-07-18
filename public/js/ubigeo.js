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
}
