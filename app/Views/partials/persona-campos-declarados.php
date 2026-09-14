<?php
/**
 * "campos_persona" (Z21, pedido del usuario 2026-09-12): campo_def que una
 * ficha declara por CLAVE en el manifiesto para pintarse dentro de la
 * tarjeta de identidad, junto al documento -- hermano de
 * notificacion-campos-declarados.php. En Z21 es el "Código" que el PDF pide
 * en "4. Datos de la gestante" y en "9. Datos del niño": cada caso es una
 * sola persona, así que un solo campo sirve a las dos ramas.
 * secciones-clinicas.php omite estos campos de su render genérico (y la
 * sección entera si todos sus campos se pintan en alguna de las dos
 * tarjetas).
 *
 * Imprime los .field sueltos, sin contenedor: va dentro de la fila .lookup
 * del documento, en el mismo lugar que "N.° de historia clínica". Solo
 * admite campos sin depende_de (cargar_fichas.php lo valida). No imprime nada
 * en las fichas que no lo declaran.
 *
 * Usa el resolvedor AMBIENTE $campo (campos-por-clave.php) sin reasignarlo
 * (memoria partial_a_medida_no_debe_pisar_campo_ambiente).
 *
 * $filaCamposPersona (opcional, P96 2026-09-13): qué fila de la tarjeta se
 * pinta -- 'documento' (por defecto) o 'nacimiento' (junto a Sexo y Fecha de
 * nacimiento). Ver clavesCamposPersona() en ayudantes.php.
 */
foreach (clavesCamposPersona($enfermedad, $filaCamposPersona ?? 'documento') as $claveCampoPersona) {
    (function (array $resuelto): void {
        $campo = $resuelto['campo'];
        $valor = $resuelto['val'];
        $error = $resuelto['err'];
        $opciones = $resuelto['opciones'];
        require __DIR__ . '/campo-dinamico.php';
    })($campo($claveCampoPersona));
}
