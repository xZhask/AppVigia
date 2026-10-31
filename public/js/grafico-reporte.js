/**
 * Gráfico del consolidado de Reportes (2026-10-17), según la agrupación:
 * barras por semana (filas = SE), pirámide por etapa de vida y sexo, o
 * barras horizontales ordenadas (red, establecimiento, enfermedad...).
 * Apiladas por las columnas elegidas. Desde 2026-10-31 también líneas (por
 * semana) y dona (pocas categorías, sin columnas), a elección en la vista.
 * Datos en #datosGrafico (JSON).
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
  // Semanas rotuladas en el eje X: una cada `cada`, según el ancho (unos 40 px
  // por rótulo), y siempre la última, salvo que quede pegada a la anterior.
  function cadaCuantas(n, ancho) { return Math.ceil(n / Math.max(4, Math.floor(ancho / 40))); }
  function rotular(i, n, cada) { return i === n - 1 || (i % cada === 0 && n - 1 - i >= cada / 2); }
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
    if (D.tipo === 'dona') {
      // Dona (2026-10-31): una porción por fila, de mayor a menor; desde la
      // novena, las menores se juntan en "Otros" para que se pueda leer.
      var partes = D.filas.filter(function (f) { return f.total > 0; }).sort(function (a, b) { return b.total - a.total; });
      if (partes.length > 8) {
        var resto = partes.slice(7).reduce(function (s, f) { return s + f.total; }, 0);
        partes = partes.slice(0, 7).concat([{ etiqueta: 'Otros (' + (D.filas.filter(function (f) { return f.total > 0; }).length - 7) + ')', total: resto, color: 'var(--muted)' }]);
      }
      var total = partes.reduce(function (s, f) { return s + f.total; }, 0), libre = 1;
      partes.forEach(function (f) { if (!f.color) f.color = 'var(--cat' + (libre++) + ')'; });
      var R = Math.min(130, Math.max(90, W * 0.2)), r0 = R * 0.6, c = R + 4, ang = -Math.PI / 2;
      var pct = function (v) { var p = v / total * 100; return (p < 10 ? p.toFixed(1) : Math.round(p)).toString().replace('.', ',') + ' %'; };
      var punto = function (rad, a) { return (c + rad * Math.cos(a)).toFixed(2) + ' ' + (c + rad * Math.sin(a)).toFixed(2); };
      partes.forEach(function (f) {
        var tit = '<title>' + esc(f.etiqueta) + ': ' + f.total + (f.total === 1 ? ' caso' : ' casos') + ' (' + pct(f.total) + ')</title>';
        if (partes.length === 1) {
          g += '<circle cx="' + c + '" cy="' + c + '" r="' + (R + r0) / 2 + '" fill="none" stroke="' + f.color + '" stroke-width="' + (R - r0) + '">' + tit + '</circle>';
          return;
        }
        var a2 = ang + f.total / total * 2 * Math.PI, largo = a2 - ang > Math.PI ? 1 : 0;
        g += '<path d="M' + punto(R, ang) + ' A' + R + ' ' + R + ' 0 ' + largo + ' 1 ' + punto(R, a2) + ' L' + punto(r0, a2) + ' A' + r0 + ' ' + r0 + ' 0 ' + largo + ' 0 ' + punto(r0, ang) + 'Z" fill="' + f.color + '" stroke="var(--surface)" stroke-width="2">' + tit + '</path>';
        ang = a2;
      });
      g += '<text class="dona-total" x="' + c + '" y="' + (c + 4) + '" text-anchor="middle">' + total + '</text>';
      g += '<text x="' + c + '" y="' + (c + 22) + '" text-anchor="middle">' + (total === 1 ? 'caso' : 'casos') + '</text>';
      return '<div class="grafico-dona">' + svg(2 * c, 2 * c, g) + '<ul class="dona-leyenda">' + partes.map(function (f) {
        return '<li><i style="background:' + f.color + '"></i><span>' + esc(f.etiqueta) + '</span><b>' + f.total + '</b><em>' + pct(f.total) + '</em></li>';
      }).join('') + '</ul></div>';
    }
    if (D.tipo === 'lineas') {
      // Líneas por semana (2026-10-31): una por serie, sin apilar, para ver
      // la tendencia de cada una; con dos o más, el total va punteado.
      var H3 = 300, ml3 = 36, mr3 = 16, mt3 = 12, mb3 = 28, iw3 = W - ml3 - mr3, ih3 = H3 - mt3 - mb3, n3 = D.filas.length;
      var series = D.series.map(function (s, k) { return { etiqueta: s.etiqueta, color: s.color, valores: D.filas.map(function (f) { return f.partes[k] || 0; }) }; });
      if (series.length > 1) series.push({ etiqueta: 'Total', color: 'var(--ink-2)', valores: D.filas.map(function (f) { return f.total; }), total: true });
      var max4 = Math.max.apply(null, [1].concat(D.filas.map(function (f) { return f.total; })));
      var st4 = paso(max4); max4 = Math.ceil(max4 / st4) * st4;
      var x4 = function (i) { return ml3 + (n3 === 1 ? iw3 / 2 : i * iw3 / (n3 - 1)); };
      var y4 = function (v) { return mt3 + ih3 - v / max4 * ih3; };
      for (var v4 = 0; v4 <= max4; v4 += st4) {
        g += '<line x1="' + ml3 + '" x2="' + (W - mr3) + '" y1="' + y4(v4) + '" y2="' + y4(v4) + '" stroke="var(--line-2)"/><text x="' + (ml3 - 6) + '" y="' + (y4(v4) + 3) + '" text-anchor="end">' + v4 + '</text>';
      }
      var cada4 = cadaCuantas(n3, iw3);
      D.filas.forEach(function (f, i) {
        if (rotular(i, n3, cada4)) g += '<text x="' + x4(i) + '" y="' + (H3 - 8) + '" text-anchor="middle">' + esc(f.etiqueta.replace('SE ', '')) + '</text>';
      });
      g += '<line x1="' + ml3 + '" x2="' + (W - mr3) + '" y1="' + y4(0) + '" y2="' + y4(0) + '" stroke="var(--line)"/>';
      var puntos = iw3 / Math.max(1, n3 - 1) >= 12;
      series.slice().reverse().forEach(function (s) {
        g += '<polyline points="' + s.valores.map(function (v, i) { return x4(i).toFixed(1) + ',' + y4(v).toFixed(1); }).join(' ') + '" fill="none" stroke="' + s.color + '" stroke-width="' + (s.total ? 1.5 : 2.2) + '"' + (s.total ? ' stroke-dasharray="5 4"' : '') + ' stroke-linejoin="round" stroke-linecap="round"/>';
        if (puntos && !s.total) s.valores.forEach(function (v, i) { if (v) g += '<circle cx="' + x4(i).toFixed(1) + '" cy="' + y4(v).toFixed(1) + '" r="2.8" fill="' + s.color + '"/>'; });
      });
      // Franja invisible por semana con el detalle de todas las series.
      var ancho = n3 === 1 ? iw3 : iw3 / (n3 - 1);
      D.filas.forEach(function (f, i) {
        var det = esc(f.etiqueta) + ': ' + (series.length > 1 ? D.series.map(function (s, k) { return esc(s.etiqueta) + ' ' + (f.partes[k] || 0); }).join(' · ') + ' · total ' + f.total : f.total + (f.total === 1 ? ' caso' : ' casos'));
        g += '<rect x="' + (x4(i) - ancho / 2) + '" y="' + mt3 + '" width="' + ancho + '" height="' + ih3 + '" fill="transparent"><title>' + det + '</title></rect>';
      });
      return svg(W, H3, g) + (series.length > 1 ? '<div class="leyenda grafico-leyenda">' + series.map(function (s) {
        return '<span><i style="' + (s.total ? 'background:none;border-top:2px dashed ' + s.color + ';height:0' : 'background:' + s.color) + '"></i>' + esc(s.etiqueta) + '</span>';
      }).join('') + '</div>' : '');
    }
    if (D.tipo === 'semanas') {
      var H2 = 300, ml = 36, mr = 16, mt = 12, mb = 28, iw = W - ml - mr, ih = H2 - mt - mb, n = D.filas.length, bw = iw / n;
      var max2 = Math.max.apply(null, [1].concat(D.filas.map(function (f) { return f.total; })));
      var st = paso(max2); max2 = Math.ceil(max2 / st) * st;
      var y = function (v) { return mt + ih - v / max2 * ih; };
      for (var v = 0; v <= max2; v += st) {
        g += '<line x1="' + ml + '" x2="' + (W - mr) + '" y1="' + y(v) + '" y2="' + y(v) + '" stroke="var(--line-2)"/><text x="' + (ml - 6) + '" y="' + (y(v) + 3) + '" text-anchor="end">' + v + '</text>';
      }
      var cada = cadaCuantas(n, iw);
      D.filas.forEach(function (f, i) {
        var m = Math.min(3, bw * 0.15), x = ml + i * bw + m, w = bw - 2 * m, acc = 0;
        f.partes.forEach(function (p, k) {
          if (!p) return;
          g += '<rect x="' + x + '" y="' + y(acc + p) + '" width="' + w + '" height="' + (y(acc) - y(acc + p)) + '" fill="' + D.series[k].color + '"><title>' + detalle(f, k) + '</title></rect>';
          acc += p;
        });
        if (rotular(i, n, cada)) g += '<text x="' + (x + w / 2) + '" y="' + (H2 - 8) + '" text-anchor="middle">' + esc(f.etiqueta.replace('SE ', '')) + '</text>';
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

  // Ancho útil: sin el padding de la tarjeta (con clientWidth a secas el
  // borde derecho del gráfico quedaba cortado).
  function dibujar() {
    var cs = getComputedStyle(cont);
    cont.innerHTML = html(Math.max(320, cont.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight)));
  }

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
