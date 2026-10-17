/**
 * Listado de fichas (2026-10-17): los filtros se aplican al cambiar (sin
 * botón "Filtrar"), la fila entera abre el detalle y el botón "⋯" despliega
 * Ver / Editar / Anular. El filtrado en sí es del servidor (GET).
 */
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('filtrosListado');
  if (!form) return;

  // URL limpia: sin los filtros vacíos y con un solo "periodo" (el del botón
  // pulsado manda sobre el vigente, que va oculto antes de los botones).
  form.addEventListener('submit', function (e) {
    var pulsado = e.submitter && e.submitter.name === 'periodo';
    Array.prototype.forEach.call(form.elements, function (campo) {
      if (!campo.name || campo === e.submitter) return;
      if (campo.value === '' || (pulsado && campo.name === 'periodo')) campo.disabled = true;
      if (pulsado && e.submitter.value !== 'rango' && /^(se_desde|se_hasta|anio)$/.test(campo.name)) campo.disabled = true;
    });
    // Si se vuelve (bfcache), los campos tienen que estar habilitados.
    setTimeout(function () { Array.prototype.forEach.call(form.elements, function (c) { c.disabled = false; }); }, 0);
  });

  function aplicar() {
    // Al aplicar un filtro se vuelve a la página 1 (el form no lleva "page").
    if (form.requestSubmit) form.requestSubmit(); else form.submit();
  }

  // Selects y rango de SE: al cambiar. Cambiar la red limpia el
  // establecimiento (puede no pertenecer a la red nueva).
  form.querySelectorAll('[data-auto]').forEach(function (campo) {
    campo.addEventListener('change', function () {
      var limpia = campo.getAttribute('data-limpia');
      if (limpia && form.elements[limpia]) form.elements[limpia].value = '';
      aplicar();
    });
  });

  // Buscador: espera a que se deje de escribir. Tras recargar, el foco y el
  // cursor vuelven al final del texto para poder seguir escribiendo.
  var buscador = form.querySelector('[data-auto-busqueda]');
  if (buscador) {
    var espera = null;
    var inicial = buscador.value;
    buscador.addEventListener('input', function () {
      clearTimeout(espera);
      espera = setTimeout(function () {
        if (buscador.value.trim() === inicial.trim()) return;
        try { sessionStorage.setItem('listadoFichasFoco', '1'); } catch (e) {}
        aplicar();
      }, 450);
    });
    try {
      if (sessionStorage.getItem('listadoFichasFoco')) {
        sessionStorage.removeItem('listadoFichasFoco');
        buscador.focus();
        var fin = buscador.value.length;
        buscador.setSelectionRange(fin, fin);
      }
    } catch (e) {}
  }

  // Fila: clic (o Enter) abre el detalle, salvo que el clic sea en un
  // enlace o botón propio, o que se esté seleccionando texto.
  document.querySelectorAll('.fila-ficha[data-href]').forEach(function (fila) {
    fila.addEventListener('click', function (e) {
      if (e.target.closest('a, button')) return;
      if (String(window.getSelection && window.getSelection()) !== '') return;
      if (e.ctrlKey || e.metaKey) { window.open(fila.dataset.href, '_blank'); return; }
      window.location.href = fila.dataset.href;
    });
    fila.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target === fila) window.location.href = fila.dataset.href;
    });
  });

  // Menú "⋯": uno solo, posicionado junto al botón que lo abrió.
  var menu = document.getElementById('menuFicha');
  var formAnular = document.getElementById('formAnularFicha');
  var actual = null;

  function cerrarMenu() {
    if (!menu || menu.hidden) return;
    menu.hidden = true;
    if (actual) actual.setAttribute('aria-expanded', 'false');
  }

  document.querySelectorAll('[data-menu-ficha]').forEach(function (boton) {
    boton.addEventListener('click', function (e) {
      e.stopPropagation();
      if (!menu.hidden && actual === boton) { cerrarMenu(); return; }
      actual = boton;
      var id = boton.dataset.id;
      menu.querySelector('[data-accion="ver"]').href = '/casos/' + id;
      var editar = menu.querySelector('[data-accion="editar"]');
      editar.href = '/casos/' + id + '/editar';
      editar.hidden = !boton.dataset.editar;
      menu.querySelector('[data-accion="anular"]').hidden = !boton.dataset.anular || !formAnular;
      menu.hidden = false;
      boton.setAttribute('aria-expanded', 'true');
      var r = boton.getBoundingClientRect();
      var alto = menu.offsetHeight;
      var arriba = r.bottom + 4 + alto > window.innerHeight ? r.top - alto - 4 : r.bottom + 4;
      menu.style.top = Math.max(8, arriba) + 'px';
      menu.style.left = Math.max(8, Math.min(r.right - menu.offsetWidth, window.innerWidth - menu.offsetWidth - 8)) + 'px';
      menu.querySelector('[role="menuitem"]:not([hidden])').focus();
    });
  });

  if (menu) {
    menu.querySelector('[data-accion="anular"]').addEventListener('click', function () {
      if (!actual || !formAnular) return;
      var codigo = actual.dataset.codigo;
      cerrarMenu();
      var motivo = prompt('Motivo de anulación de la ficha ' + codigo + ':');
      if (!motivo || !motivo.trim()) return;
      formAnular.action = '/casos/' + actual.dataset.id + '/anular';
      formAnular.motivo.value = motivo.trim();
      formAnular.submit();
    });
    document.addEventListener('click', function (e) {
      if (!e.target.closest('#menuFicha')) cerrarMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && !menu.hidden) { cerrarMenu(); if (actual) actual.focus(); }
    });
    window.addEventListener('scroll', cerrarMenu, { passive: true });
    window.addEventListener('resize', cerrarMenu);
    var desplazable = document.querySelector('.tabla-desplazable');
    if (desplazable) desplazable.addEventListener('scroll', cerrarMenu, { passive: true });
  }
});
