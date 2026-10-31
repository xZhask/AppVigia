/* Un solo envío por formulario (2026-10-31): con la red lenta, la gente hace
 * clic varias veces en "Registrar ficha" y cada clic era un envío más. Al
 * enviarse de verdad un formulario POST, sus botones quedan desactivados y los
 * envíos siguientes se ignoran hasta que cargue la página de respuesta.
 *
 * - Se engancha en captura sobre window, antes que los demás scripts, para
 *   cortar el segundo envío antes de que corran sus validaciones.
 * - Bloquea en un setTimeout: para entonces el navegador ya armó los datos del
 *   envío (un botón desactivado antes no mandaría su name/value) y ya se sabe
 *   si otro script lo canceló (validación, confirm, envío por fetch).
 * - data-envio-libre en el <form> lo deja fuera; data-enviando-texto en el
 *   botón cambia el "Guardando…".
 * El servidor además rechaza el token repetido al crear (App\Core\EnvioUnico). */
(function () {
  'use strict';
  // Si la respuesta nunca reemplaza la página (descarga, red caída), el
  // formulario vuelve a quedar usable.
  var LIBERAR_MS = 30000;

  function botones(form) {
    var lista = Array.prototype.slice.call(form.querySelectorAll('button:not([type="button"]):not([type="reset"]), input[type="submit"]'));
    if (form.id) {
      lista = lista.concat(Array.prototype.slice.call(document.querySelectorAll('[type="submit"][form="' + form.id + '"]')));
    }
    return lista;
  }

  function bloquear(form, pulsado) {
    form.dataset.enviando = '1';
    var lista = botones(form);
    var principal = pulsado && lista.indexOf(pulsado) !== -1 ? pulsado : lista[0];
    lista.forEach(function (b) {
      b.dataset.envioDisabled = b.disabled ? '1' : '';
      b.disabled = true;
    });
    if (principal && principal.tagName === 'BUTTON') {
      principal.dataset.envioHtml = principal.innerHTML;
      principal.textContent = principal.getAttribute('data-enviando-texto') || 'Guardando…';
      principal.setAttribute('aria-busy', 'true');
    }
    form._envioTimer = setTimeout(function () { liberar(form); }, LIBERAR_MS);
  }

  function liberar(form) {
    if (!form.dataset.enviando) return;
    clearTimeout(form._envioTimer);
    delete form.dataset.enviando;
    botones(form).forEach(function (b) {
      if (b.dataset.envioHtml !== undefined) {
        b.innerHTML = b.dataset.envioHtml;
        delete b.dataset.envioHtml;
        b.removeAttribute('aria-busy');
      }
      if (b.dataset.envioDisabled !== undefined) {
        b.disabled = b.dataset.envioDisabled === '1';
        delete b.dataset.envioDisabled;
      }
    });
  }

  window.addEventListener('submit', function (e) {
    var form = e.target;
    if (!(form instanceof HTMLFormElement) || form.method !== 'post' || form.hasAttribute('data-envio-libre')) return;
    if (form.dataset.enviando) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return;
    }
    // Marcado ya, por si llega otro clic antes del setTimeout; si otro script
    // canceló este envío, se desmarca.
    form.dataset.enviando = '1';
    var pulsado = e.submitter || null;
    setTimeout(function () {
      if (e.defaultPrevented) delete form.dataset.enviando;
      else bloquear(form, pulsado);
    }, 0);
  }, true);

  // Volver con "Atrás" puede restaurar la página tal como quedó (bfcache).
  window.addEventListener('pageshow', function (e) {
    if (!e.persisted) return;
    Array.prototype.forEach.call(document.querySelectorAll('form[data-enviando]'), liberar);
  });
})();
