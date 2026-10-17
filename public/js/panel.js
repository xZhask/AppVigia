/**
 * Panel de vigilancia (2026-10-17): fila de enfermedad clicable (cambia la
 * curva), "Ver más" de las listas largas y las celdas del mapa de
 * cumplimiento, que registran o anulan la notificación semanal negativa.
 */
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.tabla-enf tr[data-href]').forEach(function (fila) {
    fila.addEventListener('click', function (e) {
      if (e.target.closest('a')) return;
      window.location.href = fila.dataset.href;
    });
  });

  document.querySelectorAll('[data-mostrar]').forEach(function (boton) {
    var textoMostrar = boton.textContent;
    boton.addEventListener('click', function () {
      var bloque = document.getElementById(boton.dataset.mostrar);
      if (!bloque) return;
      bloque.hidden = !bloque.hidden;
      boton.textContent = bloque.hidden ? textoMostrar : boton.dataset.textoOcultar;
    });
  });

  var form = document.getElementById('formNotifSemanal');
  document.querySelectorAll('[data-celda]').forEach(function (celda) {
    celda.addEventListener('click', function () {
      if (!form) return;
      var anular = celda.dataset.celda === 'anular';
      var pregunta = anular
        ? '¿Anular la notificación «sin casos»?\n\n' + celda.dataset.texto
        : '¿Registrar que el establecimiento NO tuvo casos esa semana (notificación negativa)?\n\n' + celda.dataset.texto;
      if (!confirm(pregunta)) return;
      form.accion.value = celda.dataset.celda;
      form.establecimiento_id.value = celda.dataset.est;
      form.anio.value = celda.dataset.anio;
      form.semana.value = celda.dataset.semana;
      form.submit();
    });
  });
});
