/**
 * Notificación semanal (2026-10-17): habilita "Cerrar SE" al confirmar (y con
 * motivo si el cierre es tardío), pide confirmación en una ventana antes de
 * enviar, abre las ventanas de solicitar reapertura y de reabrir, y filtra
 * "Solo con casos". El servidor revalida todo.
 */
document.addEventListener('DOMContentLoaded', function () {
  function abrir(modal) {
    if (!modal) return;
    modal.hidden = false;
    var foco = modal.querySelector('textarea, .btn-primary');
    if (foco) foco.focus();
  }
  function cerrarModales() {
    document.querySelectorAll('.modal-fondo').forEach(function (m) { m.hidden = true; });
  }
  document.querySelectorAll('.modal-fondo').forEach(function (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal || e.target.closest('[data-cerrar-modal]')) cerrarModales();
    });
  });
  document.addEventListener('keydown', function (e) { if (e.key === 'Escape') cerrarModales(); });
  document.querySelectorAll('[data-abrir-modal]').forEach(function (boton) {
    boton.addEventListener('click', function () { abrir(document.getElementById(boton.dataset.abrirModal)); });
  });

  var form = document.getElementById('formCierre');
  if (form) {
    var confirmo = document.getElementById('confirmoCierre');
    var motivo = document.getElementById('motivoTardio');
    var boton = document.getElementById('btnCerrar');
    var listo = function () { return confirmo.checked && (!motivo || motivo.value.trim() !== ''); };
    var actualizar = function () { boton.disabled = !listo(); };
    confirmo.addEventListener('change', actualizar);
    if (motivo) motivo.addEventListener('input', actualizar);
    actualizar();
    var confirmado = false;
    form.addEventListener('submit', function (e) {
      if (confirmado) return;
      e.preventDefault();
      if (listo()) abrir(document.getElementById('modalCierre'));
    });
    document.getElementById('confirmarCierre').addEventListener('click', function () {
      confirmado = true;
      this.disabled = true;
      form.submit();
    });
  }

  var modalReabrir = document.getElementById('modalReabrir');
  document.querySelectorAll('[data-reabrir]').forEach(function (boton) {
    boton.addEventListener('click', function () {
      modalReabrir.querySelector('[name="establecimiento_id"]').value = boton.dataset.reabrir;
      modalReabrir.querySelector('[data-nombre-est]').textContent = boton.dataset.nombre;
      modalReabrir.querySelector('textarea').value = '';
      abrir(modalReabrir);
    });
  });

  document.querySelectorAll('form[data-confirmar]').forEach(function (f) {
    f.addEventListener('submit', function (e) { if (!confirm(f.dataset.confirmar)) e.preventDefault(); });
  });

  var solo = document.getElementById('soloConCasos');
  if (solo) {
    var aplicar = function () {
      document.querySelectorAll('.ns-enf tbody tr.cero').forEach(function (fila) { fila.hidden = solo.checked; });
    };
    try { solo.checked = localStorage.getItem('vigia-ns-solo-casos') === '1'; } catch (e) {}
    aplicar();
    solo.addEventListener('change', function () {
      aplicar();
      try { localStorage.setItem('vigia-ns-solo-casos', solo.checked ? '1' : '0'); } catch (e) {}
    });
  }
});
