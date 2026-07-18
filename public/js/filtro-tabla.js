document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-filtro-tabla]').forEach(function (input) {
    var tabla = document.querySelector(input.getAttribute('data-filtro-tabla'));
    if (!tabla) return;
    var filas = tabla.querySelectorAll('tbody tr');

    input.addEventListener('input', function () {
      var texto = input.value.trim().toLowerCase();
      filas.forEach(function (fila) {
        fila.style.display = fila.textContent.toLowerCase().indexOf(texto) === -1 ? 'none' : '';
      });
    });
  });
});
