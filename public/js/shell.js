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

  var btnTema = document.getElementById('themeToggle');
  if (btnTema) {
    btnTema.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      var nuevo = isDark ? 'light' : 'dark';
      if (nuevo === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
      document.cookie = 'theme=' + nuevo + '; path=/; max-age=31536000';
    });
  }
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
