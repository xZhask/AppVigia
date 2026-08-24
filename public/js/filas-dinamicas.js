document.addEventListener('DOMContentLoaded', function () {
  // Reasigna ids de selectores de ubigeo (selector-ubigeo.php) que vienen con
  // un prefijo placeholder terminado en "-nueva" dentro de una fila clonada
  // desde <template>: los ids deben ser únicos en el documento, y el
  // <script> inline de selector-ubigeo.php no vuelve a correr después de
  // DOMContentLoaded, así que hay que llamar inicializarUbigeo() a mano.
  // Devuelve la lista de nuevos prefijos generados (los ids ya quedan
  // reescritos en el fragmento, pero inicializarUbigeo() usa
  // document.getElementById, así que hay que llamarla recién después de
  // insertar el fragmento en el documento).
  var contadorUbigeo = 0;
  function reindexarSelectoresUbigeo(fragmento) {
    var prefijosNuevos = [];
    var vistos = {};
    fragmento.querySelectorAll('select[id$="-departamento"]').forEach(function (selDep) {
      var prefijoViejo = selDep.id.slice(0, -('-departamento'.length));
      if (!/-nueva$/.test(prefijoViejo) || vistos[prefijoViejo]) return;
      vistos[prefijoViejo] = true;

      var prefijoNuevo = prefijoViejo + '-' + (contadorUbigeo++);
      ['-departamento', '-provincia', '-distrito'].forEach(function (sufijo) {
        var el = fragmento.querySelector('#' + CSS.escape(prefijoViejo + sufijo));
        if (el) el.id = prefijoNuevo + sufijo;
      });
      prefijosNuevos.push(prefijoNuevo);
    });
    return prefijosNuevos;
  }

  // "Único por columna" (2026-08-23, pedido del usuario en el cotejo de A95
  // Laboratorio): capacidad genérica, no específica de muestras -- cualquier
  // .subrows con data-unico-por-selector="<selector CSS>" deja de permitir
  // elegir en ese <select> un valor que YA esté elegido en OTRA fila del
  // mismo contenedor (se deshabilita la <option>, no se oculta -- el
  // usuario ve que existe pero no puede duplicarla). El servidor revalida
  // aparte (no hay que confiar solo en esto) -- ver
  // CasosController::filasMuestras() para el primer uso (A95, tipo_muestra
  // restringido a Biopsia/Serología/Hígado/Cultivos, 1 registro máximo por
  // tipo).
  function actualizarUnicoPorColumna(contenedor) {
    var listaContenedores = contenedor
      ? [contenedor.closest('[data-unico-por-selector]')].filter(Boolean)
      : Array.prototype.slice.call(document.querySelectorAll('[data-unico-por-selector]'));
    listaContenedores.forEach(function (subrows) {
      var selectorCampo = subrows.getAttribute('data-unico-por-selector');
      if (!selectorCampo) return;
      var campos = Array.prototype.slice.call(subrows.querySelectorAll(selectorCampo));
      var usados = campos.map(function (c) { return c.value; }).filter(function (v) { return v !== ''; });
      campos.forEach(function (campo) {
        Array.prototype.forEach.call(campo.options || [], function (opt) {
          if (!opt.value) { opt.disabled = false; return; }
          opt.disabled = (usados.indexOf(opt.value) !== -1 && opt.value !== campo.value);
        });
      });
    });
  }

  document.addEventListener('click', function (evento) {
    var boton = evento.target.closest('.agregar-fila');
    if (boton) {
      var idPlantilla = boton.getAttribute('data-plantilla');
      var idLista = boton.getAttribute('data-lista');
      if (!idPlantilla || !idLista) return;

      var contenedorSeccion = boton.closest('.section, .card') || document;
      var plantilla = (contenedorSeccion.querySelector ? contenedorSeccion.querySelector('#' + CSS.escape(idPlantilla)) : null) || document.getElementById(idPlantilla);
      var lista = (contenedorSeccion.querySelector ? contenedorSeccion.querySelector('[data-lista="' + idLista + '"]') : null) || document.querySelector('[data-lista="' + idLista + '"]');

      if (!plantilla || !lista) return;
      var fragmento = plantilla.content.cloneNode(true);
      var prefijosNuevos = reindexarSelectoresUbigeo(fragmento);
      lista.appendChild(fragmento);
      prefijosNuevos.forEach(function (prefijo) {
        if (window.inicializarUbigeo) window.inicializarUbigeo(prefijo);
      });
      actualizarUnicoPorColumna(lista);
      return;
    }

    var botonQuitar = evento.target.closest('.quitar-fila');
    if (botonQuitar) {
      var fila = botonQuitar.closest('.subrow');
      var contenedorFila = fila ? fila.parentElement : null;
      if (fila) fila.remove();
      actualizarUnicoPorColumna(contenedorFila);
    }
  });

  document.addEventListener('change', function (evento) {
    if (evento.target && evento.target.closest('[data-unico-por-selector]')) {
      actualizarUnicoPorColumna(evento.target);
    }
  });

  actualizarUnicoPorColumna(); // estado inicial (editar ficha con filas ya guardadas)
});
