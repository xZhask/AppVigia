<?php
use App\Core\Csrf;

$esEdicion = !empty($grado['id']);
$accion = $esEdicion ? '/catalogos/grados/' . (int) $grado['id'] : '/catalogos/grados';
?>
<div class="page-head">
  <div>
    <div class="page-title"><?= $esEdicion ? 'Editar grado PNP' : 'Nuevo grado PNP' ?></div>
  </div>
</div>

<div class="card section">
  <div class="section-body">
    <form method="post" action="<?= e($accion) ?>">
      <?= Csrf::campoOculto() ?>
      <div class="fields thirds">
        <div class="field">
          <label class="fl">Abreviatura <span class="req">*</span></label>
          <div class="control <?= isset($errores['abreviatura']) ? 'err' : '' ?>">
            <input type="text" name="abreviatura" value="<?= e($grado['abreviatura']) ?>" required>
          </div>
          <?php if (isset($errores['abreviatura'])): ?><span class="hint err"><?= e($errores['abreviatura']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Nombre <span class="req">*</span></label>
          <div class="control <?= isset($errores['nombre']) ? 'err' : '' ?>">
            <input type="text" name="nombre" value="<?= e($grado['nombre']) ?>" required>
          </div>
          <?php if (isset($errores['nombre'])): ?><span class="hint err"><?= e($errores['nombre']) ?></span><?php endif; ?>
        </div>
        <div class="field">
          <label class="fl">Jerarquía <span class="req">*</span></label>
          <div class="control mono <?= isset($errores['jerarquia']) ? 'err' : '' ?>">
            <input type="number" name="jerarquia" min="1" value="<?= e($grado['jerarquia']) ?>" required>
          </div>
          <span class="hint">1 = mayor rango</span>
          <?php if (isset($errores['jerarquia'])): ?><span class="hint err"><?= e($errores['jerarquia']) ?></span><?php endif; ?>
        </div>
        <div class="field wide">
          <label class="fl">Categoría <span class="req">*</span></label>
          <div class="control <?= isset($errores['categoria']) ? 'err' : '' ?>">
            <select name="categoria">
              <option value="OFICIAL_GENERAL" <?= seleccionado($grado['categoria'], 'OFICIAL_GENERAL') ?>>Oficial general</option>
              <option value="OFICIAL_SUPERIOR" <?= seleccionado($grado['categoria'], 'OFICIAL_SUPERIOR') ?>>Oficial superior</option>
              <option value="OFICIAL_SUBALTERNO" <?= seleccionado($grado['categoria'], 'OFICIAL_SUBALTERNO') ?>>Oficial subalterno</option>
              <option value="SUBOFICIAL" <?= seleccionado($grado['categoria'], 'SUBOFICIAL') ?>>Suboficial</option>
              <option value="EMPLEADO_CIVIL" <?= seleccionado($grado['categoria'], 'EMPLEADO_CIVIL') ?>>Empleado civil</option>
            </select>
          </div>
        </div>
      </div>

      <div style="display:flex;gap:10px;margin-top:20px">
        <button class="btn btn-primary" type="submit">
          <svg width="14" height="14" viewBox="0 0 14 14"><path d="M2.5 7.5 6 11l5.5-6.5" stroke="currentColor" stroke-width="1.6" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          <?= $esEdicion ? 'Guardar cambios' : 'Registrar grado' ?>
        </button>
        <a class="btn btn-ghost" href="/catalogos/grados">Cancelar</a>
      </div>
    </form>
  </div>
</div>
