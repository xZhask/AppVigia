/**
 * Catálogos rediseñados (2026-10-19): Establecimientos, Usuarios y
 * Enfermedades. El servidor pinta todas las filas; aquí se filtran al
 * instante (texto, selects, Activos/Inactivos y alertas), se ordenan por
 * columna, se abre el detalle en un panel lateral (<template id="det-ID">) y
 * el menú de acciones (<template id="menu-ID">), y se llenan las ventanas
 * de confirmación ([data-abrir-modal]). Las acciones son formularios POST
 * normales: el servidor revalida todo.
 *
 * Marcado esperado:
 *   [data-catalogo]                     contenedor
 *   tr[data-fila="ID"]                  data-buscar, data-estado (A|I), data-flags, data-f-<filtro>, data-o-<orden>
 *   input[data-filtro-q]                búsqueda
 *   select[data-filtro="x"]             compara con data-f-x
 *   .seg [data-estado]                  Activos / Inactivos
 *   [data-flag="x"]                     alertas (data-flag="" = todos); <b> recibe el conteo
 *   th[data-orden="x"] (data-num)       orden
 *   [data-contador]                     "N de M"
 */
(function () {
  var normalizar = function (s) { return (s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, ''); };

  document.addEventListener('DOMContentLoaded', function () {
    var raiz = document.querySelector('[data-catalogo]');
    if (!raiz) return;
    var cuerpo = raiz.querySelector('tbody');
    var filas = Array.prototype.slice.call(raiz.querySelectorAll('tr[data-fila]'));
    var vacio = raiz.querySelector('tr[data-vacio]');
    var busqueda = raiz.querySelector('[data-filtro-q]');
    var selects = Array.prototype.slice.call(raiz.querySelectorAll('select[data-filtro]'));
    var estados = Array.prototype.slice.call(raiz.querySelectorAll('[data-estado-btn]'));
    var alertas = Array.prototype.slice.call(raiz.querySelectorAll('[data-flag]'));
    var contador = raiz.querySelector('[data-contador]');
    var clave = 'vigia-cat-' + location.pathname;
    var S = { q: '', estado: 'A', flag: '', orden: null, dir: 1 };
    try { Object.assign(S, JSON.parse(sessionStorage.getItem(clave) || '{}')); } catch (e) {}
    var params = new URLSearchParams(location.search);
    if (params.get('q') !== null) S.q = params.get('q');
    if (params.get('flag') !== null) { S.flag = params.get('flag'); S.estado = 'A'; }
    if (busqueda) busqueda.value = S.q;
    selects.forEach(function (s) { if (S['f_' + s.dataset.filtro] !== undefined) s.value = S['f_' + s.dataset.filtro]; });

    function pasaBase(fila) {
      if (fila.dataset.estado !== S.estado) return false;
      var q = normalizar(S.q.trim());
      if (q && normalizar(fila.dataset.buscar).indexOf(q) === -1) return false;
      for (var i = 0; i < selects.length; i++) {
        var v = selects[i].value;
        if (v !== '' && (fila.getAttribute('data-f-' + selects[i].dataset.filtro) || '').split(' ').indexOf(v) === -1) return false;
      }
      return true;
    }
    function tieneFlag(fila, flag) { return flag === '' || (' ' + (fila.dataset.flags || '') + ' ').indexOf(' ' + flag + ' ') !== -1; }

    function aplicar() {
      var base = filas.filter(pasaBase);
      var visibles = 0;
      filas.forEach(function (fila) {
        var ver = base.indexOf(fila) !== -1 && (S.estado !== 'A' || tieneFlag(fila, S.flag));
        fila.hidden = !ver;
        if (ver) visibles++;
      });
      if (vacio) vacio.hidden = visibles > 0;
      alertas.forEach(function (a) {
        var n = base.filter(function (f) { return tieneFlag(f, a.dataset.flag); }).length;
        var b = a.querySelector('b');
        if (b) b.textContent = n;
        a.classList.toggle('on', a.dataset.flag === S.flag);
        a.toggleAttribute('data-cero', n === 0 && a.dataset.flag !== '');
        a.hidden = S.estado !== 'A';
      });
      estados.forEach(function (b) {
        b.classList.toggle('on', b.dataset.estadoBtn === S.estado);
        var n = filas.filter(function (f) { var e = S.estado; S.estado = b.dataset.estadoBtn; var r = pasaBase(f); S.estado = e; return r; }).length;
        var span = b.querySelector('span');
        if (span) span.textContent = n;
      });
      if (contador) contador.textContent = visibles + ' de ' + filas.length;
      try {
        var guardar = { q: S.q, estado: S.estado, flag: S.flag, orden: S.orden, dir: S.dir };
        selects.forEach(function (s) { guardar['f_' + s.dataset.filtro] = s.value; });
        sessionStorage.setItem(clave, JSON.stringify(guardar));
      } catch (e) {}
    }

    function ordenar() {
      raiz.querySelectorAll('th[data-orden]').forEach(function (th) {
        th.classList.toggle('asc', th.dataset.orden === S.orden && S.dir === 1);
        th.classList.toggle('desc', th.dataset.orden === S.orden && S.dir === -1);
      });
      if (!S.orden) return;
      var th = raiz.querySelector('th[data-orden="' + S.orden + '"]');
      var numerico = th && th.hasAttribute('data-num');
      var copia = filas.slice().sort(function (a, b) {
        var x = a.getAttribute('data-o-' + S.orden) || '', y = b.getAttribute('data-o-' + S.orden) || '';
        var r = numerico ? (parseFloat(x === '' ? -1 : x) - parseFloat(y === '' ? -1 : y)) : x.localeCompare(y, 'es');
        return r * S.dir;
      });
      copia.forEach(function (f) { cuerpo.appendChild(f); });
      if (vacio) cuerpo.appendChild(vacio);
    }

    if (busqueda) {
      var t;
      busqueda.addEventListener('input', function () { clearTimeout(t); t = setTimeout(function () { S.q = busqueda.value; aplicar(); }, 120); });
    }
    selects.forEach(function (s) { s.addEventListener('change', aplicar); });
    estados.forEach(function (b) { b.addEventListener('click', function () { S.estado = b.dataset.estadoBtn; S.flag = ''; aplicar(); }); });
    alertas.forEach(function (a) { a.addEventListener('click', function () { S.flag = a.dataset.flag; aplicar(); }); });
    raiz.querySelectorAll('th[data-orden]').forEach(function (th) {
      th.addEventListener('click', function () {
        if (S.orden === th.dataset.orden) S.dir = -S.dir; else { S.orden = th.dataset.orden; S.dir = th.hasAttribute('data-num') ? -1 : 1; }
        ordenar(); aplicar();
      });
    });
    raiz.querySelectorAll('[data-limpiar]').forEach(function (b) {
      b.addEventListener('click', function () {
        S.q = ''; S.flag = ''; if (busqueda) busqueda.value = '';
        selects.forEach(function (s) { s.value = ''; s.dispatchEvent(new Event('change')); });
        aplicar();
      });
    });

    // ---------- Panel lateral ----------
    var cajon = document.getElementById('catCajon');
    var cortina = document.getElementById('catCortina');
    var seleccion = null;
    function abrirDetalle(id) {
      var plantilla = document.getElementById('det-' + id);
      if (!plantilla || !cajon) return;
      cerrarMenu();
      cajon.innerHTML = '';
      cajon.appendChild(plantilla.content.cloneNode(true));
      cajon.classList.add('abierto');
      cortina.hidden = false;
      filas.forEach(function (f) { f.classList.toggle('sel', f.dataset.fila === String(id)); });
      seleccion = id;
      var cerrar = cajon.querySelector('[data-cerrar-cajon]');
      if (cerrar) cerrar.focus();
    }
    function cerrarDetalle() {
      if (!cajon || !cajon.classList.contains('abierto')) return;
      cajon.classList.remove('abierto');
      cortina.hidden = true;
      filas.forEach(function (f) { f.classList.remove('sel'); });
      var fila = raiz.querySelector('tr[data-fila="' + seleccion + '"]');
      if (fila) fila.focus();
      seleccion = null;
    }
    filas.forEach(function (fila) {
      fila.tabIndex = 0;
      fila.addEventListener('click', function (e) {
        if (e.target.closest('a, button, form, input, select')) return;
        abrirDetalle(fila.dataset.fila);
      });
      fila.addEventListener('keydown', function (e) { if (e.key === 'Enter' && e.target === fila) abrirDetalle(fila.dataset.fila); });
    });
    if (cortina) cortina.addEventListener('click', cerrarDetalle);

    // ---------- Menú de acciones ----------
    var menu = document.getElementById('catMenu');
    var botonMenu = null;
    function cerrarMenu() {
      if (!menu || menu.hidden) return;
      menu.hidden = true;
      if (botonMenu) botonMenu.setAttribute('aria-expanded', 'false');
      botonMenu = null;
    }
    function abrirMenu(boton) {
      var plantilla = document.getElementById('menu-' + boton.dataset.menu);
      if (!plantilla) return;
      if (botonMenu === boton) { cerrarMenu(); return; }
      cerrarMenu();
      menu.innerHTML = '';
      menu.appendChild(plantilla.content.cloneNode(true));
      menu.hidden = false;
      botonMenu = boton;
      boton.setAttribute('aria-expanded', 'true');
      var r = boton.getBoundingClientRect();
      var alto = menu.offsetHeight, ancho = menu.offsetWidth;
      menu.style.top = (r.bottom + 4 + alto > innerHeight ? Math.max(8, r.top - alto - 4) : r.bottom + 4) + 'px';
      menu.style.left = Math.max(8, Math.min(r.right - ancho, innerWidth - ancho - 8)) + 'px';
      var primero = menu.querySelector('a, button');
      if (primero) primero.focus();
    }

    document.addEventListener('click', function (e) {
      var b = e.target.closest('[data-menu]');
      if (b) { e.stopPropagation(); abrirMenu(b); return; }
      if (e.target.closest('[data-ver-detalle]')) { cerrarMenu(); abrirDetalle(e.target.closest('[data-ver-detalle]').dataset.verDetalle); return; }
      if (e.target.closest('[data-cerrar-cajon]')) { cerrarDetalle(); return; }
      var m = e.target.closest('[data-abrir-modal]');
      if (m) { cerrarMenu(); abrirModal(m); return; }
      var p = e.target.closest('[data-post]');
      if (p) {
        cerrarMenu();
        if (p.dataset.confirmar && !confirm(p.dataset.confirmar)) return;
        var f = document.getElementById('catFormPost');
        f.action = p.dataset.post;
        f.submit();
        return;
      }
      if (menu && !e.target.closest('#catMenu')) cerrarMenu();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (document.querySelector('.modal-fondo:not([hidden])')) { cerrarModales(); return; }
      if (menu && !menu.hidden) { var b = botonMenu; cerrarMenu(); if (b) b.focus(); return; }
      cerrarDetalle();
    });
    addEventListener('scroll', cerrarMenu, { passive: true });

    // ---------- Ventanas ----------
    function cerrarModales() { document.querySelectorAll('.modal-fondo').forEach(function (m) { m.hidden = true; }); }
    function abrirModal(origen) {
      var modal = document.getElementById(origen.dataset.abrirModal);
      if (!modal) return;
      var form = modal.querySelector('form') || modal;
      if (origen.dataset.accion && form.tagName === 'FORM') form.action = origen.dataset.accion;
      Object.keys(origen.dataset).forEach(function (k) {
        if (k.indexOf('texto') === 0 && k.length > 5) {
          var nombre = k.slice(5).toLowerCase();
          modal.querySelectorAll('[data-texto="' + nombre + '"]').forEach(function (el) { el.textContent = origen.dataset[k]; });
        }
        if (k.indexOf('valor') === 0 && k.length > 5) {
          var campo = form.querySelector('[name="' + k.slice(5).replace(/^./, function (c) { return c.toLowerCase(); }).replace(/[A-Z]/g, function (c) { return '_' + c.toLowerCase(); }) + '"]');
          if (campo) campo.value = origen.dataset[k];
        }
        if (k.indexOf('mostrar') === 0 && k.length > 7) {
          var bloque = modal.querySelector('[data-bloque="' + k.slice(7).toLowerCase() + '"]');
          if (bloque) bloque.hidden = origen.dataset[k] !== '1';
        }
      });
      modal.querySelectorAll('textarea').forEach(function (t) { t.value = ''; });
      modal.querySelectorAll('select[data-limpiar-al-abrir]').forEach(function (s) { s.value = ''; });
      modal.querySelectorAll('[data-requiere]').forEach(function (b) { b.disabled = true; });
      modal.hidden = false;
      var foco = modal.querySelector('textarea, select, .btn-primary, .btn-peligro');
      if (foco) foco.focus();
    }
    document.querySelectorAll('.modal-fondo').forEach(function (modal) {
      modal.addEventListener('click', function (e) { if (e.target === modal || e.target.closest('[data-cerrar-modal]')) cerrarModales(); });
      var boton = modal.querySelector('[data-requiere]');
      if (boton) {
        modal.addEventListener('input', function () {
          var ok = true;
          modal.querySelectorAll('[required]').forEach(function (c) { if (!c.value.trim()) ok = false; });
          boton.disabled = !ok;
        });
        modal.addEventListener('change', function () { modal.dispatchEvent(new Event('input')); });
      }
    });

    ordenar();
    aplicar();
  });
})();
