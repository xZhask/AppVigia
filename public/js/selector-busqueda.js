/**
 * Selector con búsqueda para todo <select> de la aplicación (componente
 * propio en JS vanilla, sin librerías externas). Envuelve el <select>
 * original (que queda oculto pero sigue enviándose con el formulario) con un
 * campo de texto que filtra la lista, sin distinguir tildes, navegable con
 * teclado. Se aplica por igual a listas largas y a las de pocas opciones,
 * para que todos los desplegables de la app se vean y se comporten igual.
 *
 * API pública (window.SelectorBusqueda):
 *   mejorar(select)   — envuelve un <select> (si no está deshabilitado ni ya envuelto).
 *   actualizar(select) — re-sincroniza tras cambiar sus <option> o su value
 *                        por código (encadenados de ubigeo, autocompletado).
 *   escanear(raiz)    — mejora todos los <select> dentro de un contenedor
 *                        (por ejemplo, tras reemplazar HTML por AJAX).
 */
(function () {
  'use strict';

  var abierto = null; // estado del combobox actualmente desplegado (o null)

  function normalizar(texto) {
    return texto
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .toLowerCase();
  }

  function leerOpciones(select) {
    var items = [];
    Array.prototype.forEach.call(select.children, function (child) {
      if (child.tagName === 'OPTGROUP') {
        items.push({ tipo: 'grupo', texto: child.label });
        Array.prototype.forEach.call(child.children, function (op) {
          if (op.tagName === 'OPTION') {
            items.push({
              tipo: 'opcion',
              valor: op.value,
              texto: op.textContent,
              cie10: op.getAttribute('data-cie10') || '',
              claves: op.getAttribute('data-claves') || '',
              deshabilitado: op.disabled
            });
          }
        });
      } else if (child.tagName === 'OPTION') {
        items.push({
          tipo: 'opcion',
          valor: child.value,
          texto: child.textContent,
          cie10: child.getAttribute('data-cie10') || '',
          claves: child.getAttribute('data-claves') || '',
          deshabilitado: child.disabled
        });
      }
    });
    return items;
  }

  function resaltar(contenedor, texto, consulta) {
    if (!consulta) {
      contenedor.appendChild(document.createTextNode(texto));
      return;
    }
    var idx = normalizar(texto).indexOf(normalizar(consulta));
    if (idx === -1) {
      contenedor.appendChild(document.createTextNode(texto));
      return;
    }
    contenedor.appendChild(document.createTextNode(texto.slice(0, idx)));
    var marca = document.createElement('mark');
    marca.textContent = texto.slice(idx, idx + consulta.length);
    contenedor.appendChild(marca);
    contenedor.appendChild(document.createTextNode(texto.slice(idx + consulta.length)));
  }

  function sincronizarTexto(estado) {
    var seleccionada = estado.select.options[estado.select.selectedIndex];
    estado.input.value = seleccionada ? seleccionada.textContent : '';
  }

  function cerrar(estado) {
    estado.lista.hidden = true;
    estado.input.setAttribute('aria-expanded', 'false');
    estado.indiceActivo = -1;
    if (abierto === estado) abierto = null;
  }

  // .sel-list es position:fixed para escapar del overflow:hidden de .section
  // (theme.css, congelado); su posición real se calcula aquí, en vez de con
  // CSS relativo al contenedor.
  function posicionarLista(estado) {
    var r = estado.fila.getBoundingClientRect();
    var margen = 12;
    var minWidth = estado.select.dataset.minWidth ? parseInt(estado.select.dataset.minWidth, 10) : 0;
    var ancho = Math.min(Math.max(r.width, minWidth), window.innerWidth - margen * 2);
    var izquierda = Math.min(r.left, window.innerWidth - ancho - margen);
    izquierda = Math.max(izquierda, margen);

    // Si no hay espacio suficiente debajo, la lista se abre hacia arriba.
    var espacioAbajo = window.innerHeight - r.bottom - margen;
    var espacioArriba = r.top - margen;
    var haciaArriba = espacioAbajo < 160 && espacioArriba > espacioAbajo;
    var alturaDisponible = Math.max(haciaArriba ? espacioArriba : espacioAbajo, 120);

    estado.lista.style.left = izquierda + 'px';
    estado.lista.style.width = ancho + 'px';
    estado.lista.style.maxHeight = Math.min(360, alturaDisponible) + 'px'; // 360px: requerimiento, acotado al espacio real
    if (haciaArriba) {
      estado.lista.style.top = '';
      estado.lista.style.bottom = (window.innerHeight - r.top + 4) + 'px';
    } else {
      estado.lista.style.bottom = '';
      estado.lista.style.top = (r.bottom + 4) + 'px';
    }
  }

  function marcarActivo(estado, indice) {
    // Buscar la verdadera posición en el DOM
    estado.lista.querySelectorAll('.sel-option').forEach(function(el) {
        el.classList.remove('active');
    });
    
    // Limitar al rango de las opciones filtradas
    if (indice < 0 || indice >= estado.filtradas.length) return;
    
    var item = estado.filtradas[indice];
    if (item.tipo === 'grupo' || item.deshabilitado) return;

    if (item.nodoDOM) {
        item.nodoDOM.classList.add('active');
        item.nodoDOM.scrollIntoView({ block: 'nearest' });
        estado.indiceActivo = indice;
    }
  }

  function renderLista(estado) {
    var consulta = estado.input.value.trim();
    var normalizada = normalizar(consulta);

    estado.filtradas = [];
    var grupoActual = null;

    estado.opciones.forEach(function (item) {
      if (item.tipo === 'grupo') {
        grupoActual = { item: item, agregadas: 0 };
      } else {
        var coincideNombre = !normalizada || normalizar(item.texto).indexOf(normalizada) !== -1;
        var coincideCie = !normalizada || (item.cie10 && normalizar(item.cie10).indexOf(normalizada) !== -1);
        var coincideClaves = !normalizada || (item.claves && normalizar(item.claves).indexOf(normalizada) !== -1);

        if (coincideNombre || coincideCie || coincideClaves) {
          if (grupoActual && grupoActual.agregadas === 0) {
            estado.filtradas.push(grupoActual.item);
          }
          if (grupoActual) grupoActual.agregadas++;
          estado.filtradas.push(item);
        }
      }
    });

    estado.lista.innerHTML = '';
    estado.indiceActivo = -1;

    if (!estado.filtradas.length) {
      var vacio = document.createElement('div');
      vacio.className = 'sel-empty';
      vacio.textContent = 'Sin coincidencias';
      estado.lista.appendChild(vacio);
      return;
    }

    estado.filtradas.forEach(function (item, i) {
      if (item.tipo === 'grupo') {
        var div = document.createElement('div');
        div.className = 'eyebrow';
        div.style.padding = '8px 12px 4px';
        div.style.position = 'sticky';
        div.style.top = '0';
        div.style.backgroundColor = 'var(--surface)';
        div.style.zIndex = '1';
        div.textContent = item.texto;
        estado.lista.appendChild(div);
        item.nodoDOM = div;
      } else {
        var div = document.createElement('div');
        div.className = 'sel-option';
        div.style.display = 'flex';
        div.style.justifyContent = 'space-between';
        div.style.alignItems = 'center';
        if (!item.deshabilitado && item.valor === estado.select.value) {
          div.classList.add('seleccionada');
        }

        var spanTexto = document.createElement('span');
        resaltar(spanTexto, item.texto, consulta);
        div.appendChild(spanTexto);

        if (item.cie10) {
          var spanCie = document.createElement('span');
          spanCie.className = 'mono';
          spanCie.style.color = 'var(--faint)';
          spanCie.style.fontSize = '12px';
          resaltar(spanCie, item.cie10, consulta);
          div.appendChild(spanCie);
        }

        if (item.deshabilitado) {
          div.style.color = 'var(--faint)';
          div.style.cursor = 'not-allowed';
        } else {
          div.setAttribute('role', 'option');
          div.addEventListener('mousedown', function (ev) {
            ev.preventDefault(); // evita el blur del input antes del click
            seleccionar(estado, item);
          });
          div.addEventListener('mouseenter', function () {
            marcarActivo(estado, i);
          });
        }
        
        estado.lista.appendChild(div);
        item.nodoDOM = div;
      }
    });
  }

  function abrirLista(estado) {
    if (abierto && abierto !== estado) cerrar(abierto);
    renderLista(estado);
    posicionarLista(estado);
    estado.lista.hidden = false;
    estado.input.setAttribute('aria-expanded', 'true');
    abierto = estado;
  }

  function seleccionar(estado, opcion) {
    estado.select.value = opcion.valor;
    estado.select.dispatchEvent(new Event('change', { bubbles: true }));
    sincronizarTexto(estado);
    cerrar(estado);
    estado.input.blur();
  }

  function mejorarSelector(select) {
    if (!select || select.tagName !== 'SELECT' || select.disabled) return;
    if (select._selEstado) return; // ya mejorado

    var contenedor = select.parentNode;
    contenedor.classList.add('sel-wrap');

    var fila = document.createElement('span');
    fila.className = 'sel-row';
    fila.style.width = '100%';
    fila.style.flex = '1';

    var input = document.createElement('input');
    input.type = 'text';
    input.className = 'sel-input' + (select.className ? ' ' + select.className : '');
    input.autocomplete = 'off';
    input.spellcheck = false;
    var noSearch = select.dataset.nosearch === 'true';
    input.setAttribute('role', 'combobox');
    input.setAttribute('aria-expanded', 'false');
    input.setAttribute('aria-autocomplete', 'list');
    input.setAttribute('placeholder', noSearch ? '' : 'Buscar…');
    input.style.color = 'inherit';
    input.style.backgroundColor = 'transparent';
    if (noSearch) {
      input.readOnly = true;
      input.style.cursor = 'pointer';
    }

    var chev = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    chev.setAttribute('class', 'chev');
    chev.setAttribute('width', '10');
    chev.setAttribute('height', '10');
    chev.setAttribute('viewBox', '0 0 10 10');
    chev.innerHTML = '<path d="m2 4 3 3 3-3" stroke="currentColor" stroke-width="1.3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';

    var lista = document.createElement('div');
    lista.className = 'sel-list';
    lista.setAttribute('role', 'listbox');
    lista.style.overflowY = 'auto';
    lista.hidden = true;

    fila.appendChild(input);
    fila.appendChild(chev);

    // La validación de "obligatorio" ya se hace en el servidor; se retira del
    // <select> oculto para no depender del comportamiento del navegador con
    // required en elementos no renderizados.
    select.required = false;
    select.hidden = true;
    contenedor.appendChild(fila);
    contenedor.appendChild(lista);

    var estado = {
      select: select,
      input: input,
      fila: fila,
      lista: lista,
      opciones: leerOpciones(select),
      filtradas: [],
      indiceActivo: -1,
    };
    select._selEstado = estado;

    sincronizarTexto(estado);

    // Clic en la fila (input o chevron) mientras el input ya tiene el foco:
    // no dispara 'focus' de nuevo (el navegador solo lo hace una vez), así
    // que sin esto no había forma de cerrar la lista volviendo a pulsar el
    // selector, había que hacer clic fuera. mousedown (no click) para poder
    // hacer preventDefault antes de que el navegador mueva el foco.
    fila.addEventListener('mousedown', function (ev) {
      if (document.activeElement === input) {
        ev.preventDefault();
        if (estado.lista.hidden) {
          abrirLista(estado);
        } else {
          cerrar(estado);
          input.blur();
        }
      } else if (ev.target !== input) {
        // clic en el chevron (u otra parte de la fila) sin foco previo: el
        // navegador no enfoca el input solo porque se hizo clic al lado.
        ev.preventDefault();
        input.focus();
      }
    });

    input.addEventListener('focus', function () {
      input.value = '';
      abrirLista(estado);
    });
    input.addEventListener('input', function () {
      abrirLista(estado);
    });
    input.addEventListener('blur', function () {
      cerrar(estado);
      sincronizarTexto(estado);
    });
    input.addEventListener('keydown', function (ev) {
      if (ev.key === 'ArrowDown') {
        ev.preventDefault();
        if (estado.lista.hidden) { abrirLista(estado); return; }
        
        var next = estado.indiceActivo;
        while (next < estado.filtradas.length - 1) {
            next++;
            var it = estado.filtradas[next];
            if (it.tipo === 'opcion' && !it.deshabilitado) {
                marcarActivo(estado, next);
                break;
            }
        }
      } else if (ev.key === 'ArrowUp') {
        ev.preventDefault();
        if (estado.lista.hidden) { abrirLista(estado); return; }
        
        var prev = estado.indiceActivo;
        while (prev > 0) {
            prev--;
            var it = estado.filtradas[prev];
            if (it.tipo === 'opcion' && !it.deshabilitado) {
                marcarActivo(estado, prev);
                break;
            }
        }
      } else if (ev.key === 'Enter') {
        if (!estado.lista.hidden && estado.indiceActivo >= 0 && estado.filtradas[estado.indiceActivo]) {
          ev.preventDefault();
          seleccionar(estado, estado.filtradas[estado.indiceActivo]);
        }
      } else if (ev.key === 'Escape') {
        cerrar(estado);
        sincronizarTexto(estado);
      }
    });
  }

  function actualizarSelector(select) {
    if (!select) return;
    var estado = select._selEstado;
    if (!estado) {
      mejorarSelector(select);
      return;
    }
    estado.opciones = leerOpciones(select);
    sincronizarTexto(estado);
    if (!estado.lista.hidden) renderLista(estado);
  }

  function escanear(raiz) {
    if (!raiz) return;
    if (raiz.tagName === 'SELECT') { mejorarSelector(raiz); return; }
    raiz.querySelectorAll('select').forEach(mejorarSelector);
  }

  document.addEventListener('DOMContentLoaded', function () {
    escanear(document);
  });

  // Con position:fixed, la lista no sigue sola el scroll de la página (ni el
  // de un contenedor interno): se recalcula su posición en cada scroll/resize
  // mientras esté abierta. Captura=true para detectar scroll de cualquier
  // ancestro, no solo el de window.
  window.addEventListener('scroll', function () {
    if (abierto) posicionarLista(abierto);
  }, true);
  window.addEventListener('resize', function () {
    if (abierto) posicionarLista(abierto);
  });

  window.SelectorBusqueda = {
    mejorar: mejorarSelector,
    actualizar: actualizarSelector,
    escanear: escanear,
  };
})();
