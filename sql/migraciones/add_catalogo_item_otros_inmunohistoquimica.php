<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// A97 (dengue/chikungunya/zika/arbovirosis, pág. 49) necesita 2 códigos que
// no existían en el catálogo compartido "tipo_prueba" (catalogo_item.id=5,
// usado por las 11 fichas con caso_muestra=1): "Otros" y "Muestra de tejido
// para inmunohistoquímica" (ítems g y h del PDF, VI. Exámenes de
// laboratorio). Se agregan acá en vez de nombres específicos de dengue
// (ej. "ELISA NS1-Dengue", "qRT-PCR Suero") para no filtrar vocabulario
// ajeno hacia el desplegable de las 3 fichas que usan el catálogo sin
// filtro propio (B01, B24, P35.0) -- decisión del usuario, ver el resto del
// mapeo en columnas_tablas_hija.caso_muestra.opciones.tipo_prueba de A97 en
// manifiesto_fichas.json (reutiliza ELISA/AISL_VIRAL/PCR ya existentes).
$nuevos = [
    ['OTROS', 'Otros'],
    ['INMUNOHISTOQUIMICA', 'Inmunohistoquímica'],
];

$stmtOrden = $pdo->prepare('SELECT COALESCE(MAX(orden), 0) FROM catalogo_item WHERE catalogo_id = 5');
$stmtExiste = $pdo->prepare('SELECT COUNT(*) FROM catalogo_item WHERE catalogo_id = 5 AND valor = ?');
$stmtInsertar = $pdo->prepare('INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (5, ?, ?, ?)');

foreach ($nuevos as [$valor, $etiqueta]) {
    $stmtExiste->execute([$valor]);
    if ($stmtExiste->fetchColumn() > 0) {
        echo "catalogo_item({$valor}) ya existe en catalogo_id=5.\n";
        continue;
    }
    $stmtOrden->execute();
    $orden = (int) $stmtOrden->fetchColumn() + 1;
    $stmtInsertar->execute([$valor, $etiqueta, $orden]);
    echo "catalogo_item({$valor}) agregado a catalogo_id=5 (orden {$orden}).\n";
}
