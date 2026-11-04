// Botón del ojo en los campos de contraseña: <button data-mostrar-clave="id-del-input">.
document.querySelectorAll('[data-mostrar-clave]').forEach(function (boton) {
  boton.addEventListener('click', function () {
    var campo = document.getElementById(boton.dataset.mostrarClave);
    if (!campo) return;
    var mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
  });
});
