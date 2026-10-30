/**
 * Gráfico del consolidado de Reportes (2026-10-17), según la agrupación:
 * barras por semana (filas = SE), pirámide por etapa de vida y sexo, o
 * barras horizontales ordenadas (red, establecimiento, enfermedad...).
 * Apiladas por las columnas elegidas. Datos en #datosGrafico (JSON).
 */
(function () {
  var cont = document.getElementById('graficoReporte');
  var fuente = document.getElementById('datosGrafico');
  if (!cont || !fuente) return;
  var D = JSON.parse(fuente.textContent);
  var NS = 'http://www.w3.org/2000/svg';

  function esc(s) {
    return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; });
  }
  function leyenda() {
    if (D.series.length < 2) return '';
    return '<div class="leyenda grafico-leyenda">' + D.series.map(function (s) {
      return '<span><i style="background:' + s.color + '"></i>' + esc(s.etiqueta) + '</span>';
    }).join('') + '</div>';
  }
  function detalle(fila, i) {
    var s = D.series[i];
    var v = fila.partes[i];
    return esc(fila.etiqueta) + (D.series.length > 1 ? ' · ' + esc(s.etiqueta) : '') + ': ' + v + (v === 1 ? ' caso' : ' casos')
      + (D.series.length > 1 ? ' (total ' + fila.total + ')' : '');
  }
  function paso(max) { return max <= 8 ? 1 : max <= 20 ? 2 : max <= 50 ? 5 : max <= 100 ? 10 : Math.ceil(max / 50) * 10; }
  // Ancho y alto fijos en píxeles; el viewBox solo sirve para que, al
  // imprimir en una hoja algo más angosta (carta en vez de A4), el CSS de
  // impresión lo achique en vez de cortarlo (max-width:100%).
  function svg(W, H, g) {
    return '<svg xmlns="' + NS + '" viewBox="0 0 ' + W + ' ' + H + '" width="' + W + '" height="' + H + '">' + g + '</svg>';
  }

  // El gráfico completo (SVG + leyenda) a W píxeles de ancho.
  function html(W) {
    var g = '';
    if (D.tipo === 'piramide') {
      var edades = D.piramide.slice().reverse();
      var H = edades.length * 38 + 34, mid = W / 2, lw = Math.min(170, W * 0.3), half = (W - lw) / 2 - 34;
      var max = Math.max.apply(null, [1].concat(edades.map(function (a) { return Math.max(a.M, a.F); })));
      g += '<text class="lab fuerte" x="' + (mid - lw / 2 - 8) + '" y="14" text-anchor="end">Masculino</text>';
      g += '<text class="lab fuerte" x="' + (mid + lw / 2 + 8) + '" y="14">Femenino</text>';
      edades.forEach(function (a, i) {
        var y = 24 + i * 38, bh = 26;
        g += '<text class="lab" x="' + mid + '" y="' + (y + bh / 2 + 4) + '" text-anchor="middle">' + esc(a.etapa) + '</text>';
        [['M', 'var(--cat1)'], ['F', 'var(--cat2)']].forEach(function (sx, j) {
          var v = a[sx[0]], w = v / max * half, x = j ? mid + lw / 2 : mid - lw / 2 - w;
          if (v) g += '<rect x="' + x + '" y="' + y + '" width="' + w + '" height="' + bh + '" rx="4" fill="' + sx[1] + '"><title>' + esc(a.etapa) + ' · ' + (j ? 'Femenino' : 'Masculino') + ': ' + v + '</title></rect>';
          g += '<text class="val" x="' + (j ? mid + lw / 2 + w + 6 : mid - lw / 2 - w - 6) + '" y="' + (y + bh / 2 + 4) + '" text-anchor="' + (j ? 'start' : 'end') + '">' + v + '</text>';
        });
      });
      return svg(W, H, g)
        + '<div class="leyenda grafico-leyenda"><span><i style="background:var(--cat1)"></i>Masculino</span><span><i style="background:var(--cat2)"></i>Femenino</span></div>';
    }
    if (D.tipo === 'semanas') {
      var H2 = 300, ml = 36, mr = 16, mt = 12, mb = 28, iw = W - ml - mr, ih = H2 - mt - mb, n = D.filas.length, bw = iw / n;
      var max2 = Math.max.apply(null, [1].concat(D.filas.map(function (f) { return f.total; })));
      var st = paso(max2); max2 = Math.ceil(max2 / st) * st;
      var y = function (v) { return mt + ih - v / max2 * ih; };
      for (var v = 0; v <= max2; v += st) {
        g += '<line x1="' + ml + '" x2="' + (W - mr) + '" y1="' + y(v) + '" y2="' + y(v) + '" stroke="var(--line-2)"/><text x="' + (ml - 6) + '" y="' + (y(v) + 3) + '" text-anchor="end">' + v + '</text>';
      }
      var cada = Math.ceil(n / 14);
      D.filas.forEach(function (f, i) {
        var m = Math.min(3, bw * 0.15), x = ml + i * bw + m, w = bw - 2 * m, acc = 0;
        f.partes.forEach(function (p, k) {
          if (!p) return;
          g += '<rect x="' + x + '" y="' + y(acc + p) + '" width="' + w + '" height="' + (y(acc) - y(acc + p)) + '" fill="' + D.series[k].color + '"><title>' + detalle(f, k) + '</title></rect>';
          acc += p;
        });
        if (i % cada === 0 || i === n - 1) g += '<text x="' + (x + w / 2) + '" y="' + (H2 - 8) + '" text-anchor="middle">' + esc(f.etiqueta.replace('SE ', '')) + '</text>';
      });
      g += '<line x1="' + ml + '" x2="' + (W - mr) + '" y1="' + y(0) + '" y2="' + y(0) + '" stroke="var(--line)"/>';
      return svg(W, H2, g) + leyenda();
    }
    // Barras horizontales, de más a menos casos. Un SVG por barra: un SVG no
    // se parte entre páginas, y con muchas filas (enfermedad × sexo, 28) el
    // gráfico entero no cabía en una hoja horizontal: el navegador lo pasaba
    // a la página siguiente (dejando una en blanco) y cortaba lo que sobraba.
    var lista = D.filas.filter(function (f) { return f.total > 0; }).sort(function (a, b) { return b.total - a.total; });
    var lw2 = Math.min(W * 0.38, Math.max(110, Math.max.apply(null, lista.map(function (f) { return f.etiqueta.length; })) * 7));
    var bh2 = 22, gap = 10, alto = bh2 + gap, iw2 = W - lw2 - 52;
    var max3 = Math.max.apply(null, [1].concat(lista.map(function (f) { return f.total; })));
    var filas = lista.map(function (f) {
      var y0 = gap / 2, x = lw2 + 8, gf = '';
      var cab = Math.floor((lw2 - 8) / 6.6), etq = f.etiqueta.length > cab ? f.etiqueta.slice(0, cab - 1) + '…' : f.etiqueta;
      gf += '<text class="lab" x="' + lw2 + '" y="' + (y0 + bh2 / 2 + 4) + '" text-anchor="end"><title>' + esc(f.etiqueta) + '</title>' + esc(etq) + '</text>';
      f.partes.forEach(function (p, k) {
        if (!p) return;
        var w = p / max3 * iw2;
        gf += '<rect x="' + x + '" y="' + y0 + '" width="' + Math.max(1, w - 1.5) + '" height="' + bh2 + '" rx="2" fill="' + D.series[k].color + '"><title>' + detalle(f, k) + '</title></rect>';
        x += w;
      });
      gf += '<text class="val" x="' + (x + 6) + '" y="' + (y0 + bh2 / 2 + 4) + '">' + f.total + '</text>';
      gf += '<line x1="' + (lw2 + 8) + '" x2="' + (lw2 + 8) + '" y1="0" y2="' + alto + '" stroke="var(--line)"/>';
      return svg(W, alto, gf);
    });
    return '<div class="grafico-filas">' + filas.join('') + '</div>' + leyenda();
  }

  function dibujar() { cont.innerHTML = html(Math.max(320, cont.clientWidth)); }

  dibujar();
  var espera;
  window.addEventListener('resize', function () { clearTimeout(espera); espera = setTimeout(dibujar, 150); });
  // Al imprimir, dos versiones: al ancho útil de un A4 vertical (190 mm ≈
  // 718 px) y de uno horizontal (277 mm ≈ 1047 px), menos el margen de la
  // tarjeta. El navegador no avisa a la página cuando se cambia la
  // orientación en la vista previa, pero sí reevalúa el CSS: theme.css
  // muestra la que corresponde (@media print and (orientation:landscape)).
  window.addEventListener('beforeprint', function () {
    cont.innerHTML = '<div class="grafico-hoja-v">' + html(680) + '</div><div class="grafico-hoja-h">' + html(1000) + '</div>';
  });
  window.addEventListener('afterprint', dibujar);
})();
