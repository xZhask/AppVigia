<?php
/**
 * Despachador: dado $campo (fila de campo_def), $valor, $error y $opciones,
 * incluye la plantilla parcial correspondiente a su tipo.
 */
$plantillasPorTipo = [
    'TEXTO'       => 'texto',
    'NUMERO'      => 'numero',
    'FECHA'       => 'fecha',
    'BOOLEANO'    => 'booleano',
    'SELECT'      => 'select',
    'MULTISELECT' => 'multiselect',
    'TEXTAREA'    => 'textarea',
    'GRUPO_SI_NO' => 'grupo-si-no',
    'SI_NO_FECHA' => 'si-no-fecha',
    'MATRIZ'      => 'matriz',
    'CRONOLOGIA'  => 'cronologia',
];

$plantilla = $plantillasPorTipo[$campo['tipo']] ?? null;
if ($plantilla) {
    require __DIR__ . '/campos/' . $plantilla . '.php';
}
