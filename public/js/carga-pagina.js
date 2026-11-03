/* Indicadores de carga: barra de progreso y descargas.
 *
 * Barra fina arriba de la pantalla mientras se abre otra página, venga de un
 * enlace, un filtro, un guardado o un window.location. Se engancha en
 * beforeunload porque ese evento los cubre a todos. Solo aparece si la espera
 * pasa de ESPERA_MS: con buena red no se ve y no parpadea en cada clic.
 *
 * Descargas: un enlace con data-descarga se baja por fetch, porque un enlace
 * normal a un adjunto no cambia de página y el navegador no avisa cuándo
 * terminó. Así el botón dice "Generando…" justo hasta que llega el archivo.
 * Si en vez del archivo llega otra cosa (sesión vencida, aviso, error), se
 * abre el mismo enlace de forma normal para que se vea esa respuesta. */
(function () {
  'use strict';
  var ESPERA_MS = 300;
  // Por si la navegación se cancela (Esc) y la página sigue aquí.
  var MAX_MS = 60000;
  var barra = null;
  var tEspera, tMax;

  function mostrar() {
    clearTimeout(tEspera);
    clearTimeout(tMax);
    tEspera = setTimeout(function () {
      if (!barra) {
        barra = document.createElement('div');
        barra.className = 'carga-barra';
        barra.setAttribute('role', 'progressbar');
        barra.setAttribute('aria-label', 'Cargando');
        barra.appendChild(document.createElement('i'));
        document.documentElement.appendChild(barra);
      }
      barra.classList.add('activa');
    }, ESPERA_MS);
    tMax = setTimeout(ocultar, MAX_MS);
  }

  function ocultar() {
    clearTimeout(tEspera);
    clearTimeout(tMax);
    if (barra) barra.classList.remove('activa');
  }

  window.addEventListener('beforeunload', mostrar);
  // Volver con "Atrás" puede restaurar la página tal como quedó (bfcache).
  window.addEventListener('pageshow', function (e) { if (e.persisted) ocultar(); });

  function nombreArchivo(respuesta, url) {
    var m = /filename="?([^";]+)"?/i.exec(respuesta.headers.get('Content-Disposition') || '');
    return m ? m[1] : new URL(url, location.href).pathname.split('/').pop() + '.xlsx';
  }

  function guardar(blob, nombre) {
    var url = URL.createObjectURL(blob);
    var a = document.createElement('a');
    a.href = url;
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(function () { URL.revokeObjectURL(url); }, 10000);
  }

  document.addEventListener('click', function (e) {
    var enlace = e.target.closest ? e.target.closest('a[data-descarga]') : null;
    // Ctrl/Shift/clic central: que el navegador lo abra como siempre.
    if (!enlace || !window.fetch || e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) return;
    e.preventDefault();
    if (enlace.getAttribute('aria-busy') === 'true') return;

    var html = enlace.innerHTML;
    var anchoMin = enlace.style.minWidth;
    enlace.style.minWidth = enlace.offsetWidth + 'px';
    enlace.textContent = 'Generando…';
    enlace.setAttribute('aria-busy', 'true');
    mostrar();

    function restaurar() {
      ocultar();
      enlace.innerHTML = html;
      enlace.style.minWidth = anchoMin;
      enlace.removeAttribute('aria-busy');
    }

    // redirect: 'manual': una redirección (con su aviso en la sesión) no se
    // consume aquí; la sigue la navegación normal de abajo.
    fetch(enlace.href, { credentials: 'same-origin', redirect: 'manual' }).then(function (r) {
      var esArchivo = r.ok && /attachment/i.test(r.headers.get('Content-Disposition') || '');
      if (!esArchivo) {
        restaurar();
        window.location.href = enlace.href;
        return null;
      }
      return r.blob().then(function (blob) {
        guardar(blob, nombreArchivo(r, enlace.href));
        restaurar();
      });
    }).catch(function () {
      restaurar();
      if (window.toast) window.toast('No se pudo descargar el archivo. Revisa tu conexión e inténtalo de nuevo.');
    });
  });
})();
