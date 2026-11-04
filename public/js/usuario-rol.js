// Formulario de usuario: el jefe de red elige una red; los demás roles, un establecimiento.
(function () {
  var rol = document.querySelector('select[name="rol"]');
  if (!rol) return;
  function aplicar() {
    var esJefe = rol.value === 'JEFE_RED';
    document.querySelectorAll('[data-solo-jefe-red]').forEach(function (c) { c.hidden = !esJefe; });
    document.querySelectorAll('[data-no-jefe-red]').forEach(function (c) { c.hidden = esJefe; });
  }
  rol.addEventListener('change', aplicar);
  aplicar();
})();
