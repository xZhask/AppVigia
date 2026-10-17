/**
 * Reportes (2026-10-17): los filtros del consolidado se aplican al cambiar,
 * sin botón "Aplicar"; la URL queda sin parámetros vacíos.
 */
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('filtrosReporte');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    var pulsado = e.submitter && e.submitter.name === 'periodo';
    Array.prototype.forEach.call(form.elements, function (campo) {
      if (!campo.name || campo === e.submitter) return;
      if (campo.value === '' || (pulsado && campo.name === 'periodo')) campo.disabled = true;
      if (pulsado && e.submitter.value !== 'rango' && /^(se_desde|se_hasta|anio_desde|anio_hasta)$/.test(campo.name)) campo.disabled = true;
    });
    setTimeout(function () { Array.prototype.forEach.call(form.elements, function (c) { c.disabled = false; }); }, 0);
  });

  form.querySelectorAll('[data-auto]').forEach(function (campo) {
    campo.addEventListener('change', function () {
      if (form.requestSubmit) form.requestSubmit(); else form.submit();
    });
  });
});
