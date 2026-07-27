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
      return;
    }

    var botonQuitar = evento.target.closest('.quitar-fila');
    if (botonQuitar) {
      var fila = botonQuitar.closest('.subrow');
      if (fila) fila.remove();
    }
  });
});
