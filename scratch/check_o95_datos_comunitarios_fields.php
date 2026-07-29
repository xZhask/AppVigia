<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$secId = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND (nombre LIKE '%comunitaria%' OR orden = 10)")->fetch(PDO::FETCH_ASSOC);

echo "Seccion ID {$secId['id']} (Orden {$secId['orden']}): {$secId['nombre']}\n";

$campos = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador FROM campo_def WHERE seccion_id = {$secId['id']} ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);

foreach ($campos as $c) {
    echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']}) | depende: {$c['depende_de']} = '{$c['valor_activador']}'\n";
    if ($c['catalogo_id']) {
        $items = $pdo->query("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = {$c['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $i) echo "      * {$i['valor']} => {$i['etiqueta']}\n";
    }
}
