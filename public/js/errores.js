/* Errores del navegador (2026-10-27): los envía a /errores/js para que el
 * ADMIN los vea en Errores del sistema (App\Core\RegistroErrores). Se carga
 * en el <head> del shell, antes que los demás scripts, para alcanzar sus
 * errores. Un mismo error se envía una vez por página, y hasta 10 por página;
 * el servidor además limita por sesión y hora. */
(function () {
  'use strict';
  var meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta || !window.FormData) return;

  var MAX_POR_PAGINA = 10;
  // Fallas de red, no del sistema: el usuario ya ve "Sin conexión".
  var IGNORAR = /^(Failed to fetch|NetworkError|Load failed|Script error\.?)/i;
  var enviados = {};
  var total = 0;

  function enviar(mensaje, archivo, linea, traza) {
    mensaje = String(mensaje || '').trim();
    if (!mensaje || IGNORAR.test(mensaje)) return;
    var clave = mensaje + '|' + archivo + '|' + linea;
    if (enviados[clave] || total >= MAX_POR_PAGINA) return;
    enviados[clave] = true;
    total++;

    var datos = new FormData();
    datos.append('csrf_token', meta.content);
    datos.append('mensaje', mensaje.slice(0, 1000));
    datos.append('archivo', archivo || '');
    datos.append('linea', linea || '');
    datos.append('traza', traza ? String(traza).slice(0, 8000) : '');
    datos.append('pagina', location.pathname + location.search);
    try {
      if (navigator.sendBeacon && navigator.sendBeacon('/errores/js', datos)) return;
      fetch('/errores/js', { method: 'POST', body: datos, credentials: 'same-origin', keepalive: true }).catch(function () {});
    } catch (e) { /* nunca romper la página por informar un error */ }
  }

  window.addEventListener('error', function (ev) {
    // Sin message: falló la carga de una imagen o un script, no el código.
    if (!ev.message) return;
    var archivo = ev.filename || '';
    // Extensiones del navegador y scripts de otros dominios no son del sistema.
    if (archivo && archivo.indexOf(location.origin) !== 0) return;
    var traza = ev.error && ev.error.stack ? ev.error.stack : (ev.colno ? 'columna ' + ev.colno : '');
    enviar(ev.message, archivo, ev.lineno, traza);
  });

  window.addEventListener('unhandledrejection', function (ev) {
    var razon = ev.reason;
    var mensaje = razon && razon.message ? razon.message : String(razon);
    if (IGNORAR.test(mensaje)) return;
    enviar('Promesa rechazada: ' + mensaje, '', '', razon && razon.stack ? razon.stack : '');
  });
})();
