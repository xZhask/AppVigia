<?php
use App\Core\Csrf;

$esEdicion = !empty($enfermedad['id']);
$accion = $esEdicion ? '/catalogos/enfermedades/' . (int) $enfermedad['id'] : '/catalogos/enfermedades';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar enfermedad' : 'Nueva enfermedad' ?></div>
    <div class="page-desc">Datos base de la enfermedad o evento bajo vigilancia</div>
  </div>
</div>

<div class="card section">
  <div class="section-body">
    <form method="post" action="<?= e($accion) ?>">
      <?= Csrf::campoOculto() ?>
      <div class="fields">
        <div class="field wide">
          <label class="fl">Nombre <span class="req">*</span></label>
          <div class="control <?= isset($errores['nombre']) ? 'err' : '' ?>">
            <input type="text" name="nombre" value="<?= e($enfermedad['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">CIE-10</label>
          <div class="control mono">
            <input type="text" name="cie10" value="<?= e($enfermedad['cie10']) ?>" placeholder="A97">
          </div>
        </div>
        <div class="field">
          <label class="fl">Tipo de notificación <span class="req">*</span></label>
          <div class="control <?= isset($errores['tipo_notif']) ? 'err' : '' ?>">
            <select name="tipo_notif">
              <option value="INMEDIATA" <?= seleccionado($enfermedad['tipo_notif'], 'INMEDIATA') ?>>Inmediata</option>
              <option value="SEMANAL" <?= seleccionado($enfermedad['tipo_notif'], 'SEMANAL') ?>>Semanal</option>
            </select>
          </div>
          <?php if (isset($errores['tipo_notif'])): ?><span class="hint err"><?= e($errores['tipo_notif']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Grupo</label>
          <div class="control">
            <input type="text" name="grupo" value="<?= e($enfermedad['grupo']) ?>" placeholder="A">
          </div>
          <span class="hint">A: caso estándar · B: binomio · C: evento</span>
        </div>
      </div>

      <div style="margin-top:16px">
        <label class="sym"><input type="checkbox" name="activo" <?= marcado($enfermedad['activo']) ?>> Enfermedad activa</label>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar enfermedad' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/enfermedades">Cancelar</a>
      </div>
    </form>
  </div>
</div>
