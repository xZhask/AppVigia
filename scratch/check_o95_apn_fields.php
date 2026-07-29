<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener ID de Seccion Atencion prenatal en O95
$stmtS = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND nombre LIKE '%prenatal%'");
$sec = $stmtS->fetch(PDO::FETCH_ASSOC);

echo "Seccion ID {$sec['id']} (Orden {$sec['orden']}): {$sec['nombre']}\n";

$stmtC = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id FROM campo_def WHERE seccion_id = {$sec['id']} ORDER BY orden");
$campos = $stmtC->fetchAll(PDO::FETCH_ASSOC);

foreach ($campos as $c) {
    echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
    if ($c['catalogo_id']) {
        $items = $pdo->query("SELECT valor, etiqueta FROM catalogo_item WHERE catalogo_id = {$c['catalogo_id']} ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($items as $i) echo "      * {$i['valor']} => {$i['etiqueta']}\n";
    }
}
