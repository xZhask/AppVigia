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

  // Al imprimir o guardar como PDF, siempre en tema claro: con el oscuro la
  // hoja salía con fondo negro. Al cerrar la impresión vuelve el del usuario.
  var oscuroAntesDeImprimir = false;
  window.addEventListener('beforeprint', function () {
    oscuroAntesDeImprimir = document.documentElement.getAttribute('data-theme') === 'dark';
    if (oscuroAntesDeImprimir) document.documentElement.removeAttribute('data-theme');
  });
  window.addEventListener('afterprint', function () {
    if (oscuroAntesDeImprimir) document.documentElement.setAttribute('data-theme', 'dark');
  });
});

var _toastTimeout;
function toast(mensaje) {
  var elemento = document.getElementById('toast');
  if (!elemento) return;
  elemento.textContent = mensaje;
  elemento.classList.add('show');
  clearTimeout(_toastTimeout);
  // Un mensaje largo (un aviso sumado al de "Ficha registrada") se queda
  // más tiempo: ~60 ms por carácter, entre 2,2 y 12 s.
  _toastTimeout = setTimeout(function () { elemento.classList.remove('show'); }, Math.min(12000, Math.max(2200, String(mensaje).length * 60)));
}
