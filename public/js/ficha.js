document.addEventListener('DOMContentLoaded', function () {
  var selectorEnfermedad = document.getElementById('diseaseSel');
  var contenedorClinico = document.getElementById('secciones-clinicas');

  if (selectorEnfermedad && contenedorClinico) {
    selectorEnfermedad.addEventListener('change', function () {
      var enfermedadId = selectorEnfermedad.value;
      contenedorClinico.style.opacity = '0.5';

      fetch('/casos/nuevo/secciones-clinicas?enfermedad_id=' + encodeURIComponent(enfermedadId))
        .then(function (resp) { return resp.json(); })
        .then(function (datos) {
          contenedorClinico.innerHTML = datos.html;
          contenedorClinico.style.opacity = '1';

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
  if (fechaNac && edadCalculada) {
    fechaNac.addEventListener('change', function () {
      var partes = fechaNac.value.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
      if (!partes) { edadCalculada.value = ''; return; }

      var nacimiento = new Date(partes[3], partes[2] - 1, partes[1]);
      var hoy = new Date();
      var edad = hoy.getFullYear() - nacimiento.getFullYear();
      var aunNoCumple = (hoy.getMonth() < nacimiento.getMonth()) ||
        (hoy.getMonth() === nacimiento.getMonth() && hoy.getDate() < nacimiento.getDate());
      if (aunNoCumple) edad--;

      edadCalculada.value = edad >= 0 ? edad + ' años' : '';
    });
  }
});
