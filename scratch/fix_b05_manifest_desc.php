<?php
$jsonPath = __DIR__ . '/../manifiesto_fichas.json';
$manifiesto = json_decode(file_get_contents($jsonPath), true);

$b05 = &$manifiesto['fichas']['B05']['secciones'];

$idxCuadro = null;
$idxCron = null;

foreach ($b05 as $idx => $sec) {
    if (stripos($sec['nombre'], 'cuadro') !== false) $idxCuadro = $idx;
    if (stripos($sec['nombre'], 'cronologia') !== false || stripos($sec['nombre'], 'cronología') !== false) $idxCron = $idx;
}

echo "idxCuadro: $idxCuadro, idxCron: $idxCron\n";

$campoDesc = null;
$nuevosCuadro = [];

foreach ($b05[$idxCuadro]['campos'] as $c) {
    if ($c['etiqueta'] === 'Descripción de la erupción cutánea') {
        $campoDesc = $c;
    } else {
        $nuevosCuadro[] = $c;
    }
}

if ($campoDesc) {
    $b05[$idxCuadro]['campos'] = $nuevosCuadro;
    $b05[$idxCron]['campos'][] = $campoDesc;
    file_put_contents($jsonPath, json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "manifiesto_fichas.json actualizado exitosamente.\n";
} else {
    echo "Campo no encontrado en manifiesto.\n";
}
