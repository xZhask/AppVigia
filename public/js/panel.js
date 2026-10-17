/**
 * Panel de vigilancia (2026-10-17): fila de enfermedad clicable (cambia la
 * curva) y "Ver más" de las listas largas. Las celdas del mapa de
 * cumplimiento son enlaces a Notificación semanal.
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
});
