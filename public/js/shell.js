document.addEventListener('DOMContentLoaded', function () {
  var toggle = document.getElementById('menuToggle');
  var sidebar = document.getElementById('sidebar');

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      sidebar.classList.toggle('open');
    });

    document.addEventListener('click', function (evento) {
      if (!sidebar.classList.contains('open')) return;
      if (sidebar.contains(evento.target) || toggle.contains(evento.target)) return;
      sidebar.classList.remove('open');
    });
  }

  var mensajeFlash = document.body.getAttribute('data-flash');
  if (mensajeFlash) toast(mensajeFlash);
});

var _toastTimeout;
function toast(mensaje) {
  var elemento = document.getElementById('toast');
  if (!elemento) return;
  elemento.textContent = mensaje;
  elemento.classList.add('show');
  clearTimeout(_toastTimeout);
  _toastTimeout = setTimeout(function () { elemento.classList.remove('show'); }, 2200);
}
