// Guía de uso: índice que marca la sección visible, buscador y Guardar como PDF.
(function () {
  var guia = document.querySelector('[data-guia]');
  if (!guia) return;

  var enlaces = Array.prototype.slice.call(guia.querySelectorAll('[data-seccion]'));
  var marcar = function (id) {
    enlaces.forEach(function (a) { a.classList.toggle('on', a.dataset.seccion === id); });
  };

  // La sección marcada es la última cuyo inicio ya pasó bajo la barra superior.
  var destinos = enlaces.map(function (a) { return document.getElementById(a.dataset.seccion); }).filter(Boolean);
  var alMover = function () {
    var actual = null;
    destinos.forEach(function (d) {
      if (d.offsetParent !== null && d.getBoundingClientRect().top <= 110) actual = d.id;
    });
    marcar(actual || (destinos[0] && destinos[0].id));
  };
  var pendiente = false;
  window.addEventListener('scroll', function () {
    if (pendiente) return;
    pendiente = true;
    requestAnimationFrame(function () { pendiente = false; alMover(); });
  }, { passive: true });
  alMover();

  // Buscador: ignora mayúsculas y tildes. De una sección larga solo se ven las
  // subsecciones que coinciden, no las demás que menciona su tabla inicial.
  var normalizar = function (texto) {
    return texto.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
  };
  var buscador = guia.querySelector('[data-guia-buscar]');
  var resultado = guia.querySelector('[data-guia-resultado]');
  var vacio = guia.querySelector('[data-guia-vacio]');
  var secciones = Array.prototype.slice.call(guia.querySelectorAll('.guia-seccion'));
  var textoPropio = function (seccion) {
    var copia = seccion.cloneNode(true);
    copia.querySelectorAll('.guia-sub').forEach(function (s) { s.remove(); });
    return normalizar(copia.textContent);
  };
  var indiceSecciones = secciones.map(function (s) {
    return {
      nodo: s,
      propio: textoPropio(s),
      subs: Array.prototype.slice.call(s.querySelectorAll('.guia-sub')).map(function (n) {
        return { nodo: n, texto: normalizar(n.textContent) };
      })
    };
  });
  var filtrar = function () {
    var q = normalizar(buscador.value.trim());
    var visibles = 0;
    indiceSecciones.forEach(function (s) {
      var coincidePropio = q === '' || s.propio.indexOf(q) !== -1;
      var hayEnSubs = false;
      s.subs.forEach(function (sub) {
        var coincide = q === '' || sub.texto.indexOf(q) !== -1;
        hayEnSubs = hayEnSubs || coincide;
        sub.nodo.hidden = !coincide;
      });
      var mostrar = coincidePropio || hayEnSubs;
      s.nodo.hidden = !mostrar;
      if (mostrar) visibles++;
    });
    enlaces.forEach(function (a) {
      var d = document.getElementById(a.dataset.seccion);
      var visible = d && !d.hidden && !(d.closest('.guia-seccion') || d).hidden;
      a.parentElement.hidden = !visible;
    });
    vacio.hidden = visibles > 0;
    resultado.hidden = q === '' || visibles === 0;
    if (q !== '') {
      resultado.textContent = 'Mostrando solo lo que coincide con «' + buscador.value.trim() + '». Borre la búsqueda para ver toda la guía.';
      window.scrollTo(0, 0);
    }
    alMover();
  };
  buscador.addEventListener('input', filtrar);
  buscador.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') { buscador.value = ''; filtrar(); }
  });

  // El PDF lleva toda la guía aunque haya una búsqueda, y el título da nombre al archivo.
  var tituloOriginal = document.title;
  window.addEventListener('beforeprint', function () {
    if (buscador.value !== '') { buscador.value = ''; filtrar(); }
    document.title = guia.dataset.guiaTitulo;
  });
  window.addEventListener('afterprint', function () { document.title = tituloOriginal; });
  guia.querySelector('[data-guia-pdf]').addEventListener('click', function () { window.print(); });
})();
