<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// B55 (Leishmaniasis, pág. 46, VI. PRUEBAS DE LABORATORIO) reactiva
// caso_muestra (cotejo 2026-08-25, ver manifiesto_fichas.json
// _corregido_laboratorio) y necesita 3 códigos de "tipo_prueba"
// (catalogo_item.catalogo_id=5) que no existían: "Frotis directo",
// "Histopatología" e "IDR". Nombres genéricos (no "IDR (Montenegro)", que
// es jerga propia de leishmaniasis) a propósito -- mismo criterio ya usado
// para OTROS/INMUNOHISTOQUIMICA (add_catalogo_item_otros_inmunohistoquimica.php):
// el catálogo es compartido y sin filtro por 3 fichas (B01, B24, P35.0), así
// que un código específico de una enfermedad no debe colarse en su
// desplegable con un nombre que solo tiene sentido para esa enfermedad.
// Cultivo/ELISA/PCR ya existían (CULT/ELISA/PCR) -- no se duplican.
$nuevos = [
    ['FROTIS_DIRECTO', 'Frotis directo'],
    ['HISTOPATOLOGIA', 'Histopatología'],
    ['IDR', 'IDR'],
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
