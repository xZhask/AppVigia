document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.agregar-fila').forEach(function (boton) {
    boton.addEventListener('click', function () {
      var plantilla = document.getElementById(boton.getAttribute('data-plantilla'));
      var lista = document.querySelector('[data-lista="' + boton.getAttribute('data-lista') + '"]');
      if (!plantilla || !lista) return;
      lista.appendChild(plantilla.content.cloneNode(true));
    });
  });

  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('.quitar-fila');
    if (!boton) return;
    var fila = boton.closest('.subrow');
    if (fila) fila.remove();
  });
});
