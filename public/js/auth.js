document.addEventListener('DOMContentLoaded', function () {
  // ---------- Mostrar / ocultar contraseña ----------
  var pwBtn = document.getElementById('pwBtn');
  var pw = document.getElementById('password');
  if (pwBtn && pw) {
    pwBtn.addEventListener('click', function () {
      var showing = pw.type === 'text';
      pw.type = showing ? 'password' : 'text';
      pwBtn.setAttribute('aria-label', showing ? 'Mostrar contraseña' : 'Ocultar contraseña');
      pw.focus();
    });
  }

  // ---------- Aviso de Bloq Mayús ----------
  var caps = document.getElementById('capsHint');
  if (pw && caps) {
    ['keyup', 'keydown'].forEach(function (ev) {
      pw.addEventListener(ev, function (e) {
        caps.hidden = !(e.getModifierState && e.getModifierState('CapsLock'));
      });
    });
    pw.addEventListener('blur', function () { caps.hidden = true; });
  }

  // ---------- Envío: deshabilita el botón y cambia la etiqueta ----------
  var form = document.querySelector('.auth-form');
  var submitBtn = document.getElementById('submitBtn');
  var submitLabel = document.getElementById('submitLabel');
  if (form && submitBtn && submitLabel) {
    form.addEventListener('submit', function () {
      if (submitBtn.disabled) return;
      submitBtn.disabled = true;
      submitLabel.textContent = submitBtn.getAttribute('data-loading-text') || 'Enviando…';
    });
  }

  // ---------- Alternar tema (antes de iniciar sesión: se guarda en cookie, nunca en localStorage) ----------
  var themeToggle = document.getElementById('themeToggle');
  if (themeToggle) {
    themeToggle.addEventListener('click', function () {
      var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      var nuevo = isDark ? 'light' : 'dark';
      if (nuevo === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.removeAttribute('data-theme');
      }
      document.cookie = 'theme=' + nuevo + '; path=/; max-age=31536000';
    });
  }
});
