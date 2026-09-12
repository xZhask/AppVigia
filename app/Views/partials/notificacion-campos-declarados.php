<?php
/**
 * "campos_notificacion" (motor de la ficha Z21, cotejo 2026-09-11): campo_def
 * que una ficha declara por CLAVE en el manifiesto para pintarse dentro de
 * la tarjeta fija "1. Notificación" -- la versión declarativa del patrón
 * notificacion-fechas-<ficha>.php (A00/B57/A95...), sin partial propio ni
 * lista de CIE-10 en código compartido. secciones-clinicas.php omite estos
 * mismos campos de su render genérico (y la sección entera si todos sus
 * campos están acá), igual que con $CLAVES_CUBIERTAS_POR_PARTIAL_A_MEDIDA.
 *
 * Solo admite campos sin depende_de (cargar_fichas.php lo valida): acá no
 * hay envoltura .dep-wrap.
 *
 * Usa el resolvedor AMBIENTE $campo (campos-por-clave.php) sin reasignarlo:
 * cada campo se pinta dentro de una closure con su propio $campo local
 * (memoria partial_a_medida_no_debe_pisar_campo_ambiente).
 */
$clavesCamposNotificacion = jsonDeEnfermedad($enfermedad, 'campos_notificacion');
if ($clavesCamposNotificacion):
    $pintarCampoNotificacion = function (array $resuelto): void {
        $campo = $resuelto['campo'];
        $valor = $resuelto['val'];
        $error = $resuelto['err'];
        $opciones = $resuelto['opciones'];
        require __DIR__ . '/campo-dinamico.php';
    };
?>
<div class="fields thirds" id="notificacionCamposDeclarados" style="margin-top:14px">
  <?php foreach ($clavesCamposNotificacion as $claveCampoNotificacion) { $pintarCampoNotificacion($campo($claveCampoNotificacion)); } ?>
</div>
<?php endif; ?>
