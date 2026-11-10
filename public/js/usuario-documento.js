// Formulario de usuario (2026-11-09): al ingresar el documento se muestran los
// apellidos y nombres (padrón local o RENIEC) para confirmar que es la persona
// correcta. Si no aparece, se abren los campos para digitarlos.
(function () {
  var tipo = document.getElementById('usuarioTipoDoc');
  var num = document.getElementById('usuarioNumDoc');
  var caja = document.getElementById('usuarioPersona');
  if (!tipo || !num || !caja) return;
  var manuales = document.querySelectorAll('[data-manual]');
  // Con el nombre ya pintado por el servidor, no se repite la búsqueda.
  var ultima = caja.dataset.inicial ? caja.dataset.docInicial : '';
  var pedido = 0;

  function mostrarManual(si) {
    manuales.forEach(function (c) {
      c.hidden = !si;
      c.querySelectorAll('[data-requerido]').forEach(function (i) { i.required = si; });
    });
  }
  function pintar(clase, html) {
    caja.className = 'usuario-persona' + (clase ? ' ' + clase : '');
    caja.innerHTML = html;
  }
  function texto(t) { var d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

  function buscar() {
    var t = tipo.value, n = num.value.trim();
    if (t === 'DNI') { n = n.replace(/\D/g, ''); if (n !== num.value) num.value = n; }
    var clave = t + '|' + n;
    if (clave === ultima) return;
    ultima = clave;
    if (n === '' || (t === 'DNI' && n.length !== 8)) {
      mostrarManual(false);
      pintar('', n === '' ? 'Ingresa el documento para ver a quién corresponde.' : 'El DNI tiene 8 dígitos.');
      return;
    }
    var este = ++pedido;
    pintar('buscando', 'Buscando…');
    var url = '/catalogos/usuarios/buscar-persona?' + new URLSearchParams({ tipo_doc: t, num_doc: n, excluir: caja.dataset.excluir || '' });
    fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
      .then(function (d) {
        if (este !== pedido) return;
        if (d.estado === 'ENCONTRADA') {
          mostrarManual(false);
          pintar(d.aviso ? 'aviso' : 'ok', '<b>' + texto(d.nombre) + '</b><small>' + texto(d.aviso || 'Verifica que sea la persona que deseas registrar.') + '</small>');
        } else if (d.estado === 'NO_ENCONTRADA') {
          mostrarManual(true);
          pintar('aviso', 'No se encontró en RENIEC ni en el padrón local. Completa los apellidos y nombres abajo.');
        } else {
          mostrarManual(false);
          pintar('', texto(d.mensaje || 'Revisa el documento.'));
        }
      })
      .catch(function () {
        if (este !== pedido) return;
        ultima = '';
        pintar('aviso', 'No se pudo consultar ahora. Puedes guardar igual: el documento se verifica al registrar.');
      });
  }

  num.addEventListener('input', function () { if (tipo.value === 'DNI' && num.value.replace(/\D/g, '').length === 8) buscar(); });
  num.addEventListener('change', buscar);
  num.addEventListener('blur', buscar);
  tipo.addEventListener('change', buscar);
})();
