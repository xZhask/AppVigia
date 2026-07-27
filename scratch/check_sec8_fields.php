<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT * FROM campo_def WHERE seccion_id = 2778 ORDER BY orden ASC");
$stmt->execute();
$campos = $stmt->fetchAll();

echo "Campos actuales en Seccion 2778 (Investigación epidemiológica):\n";
foreach ($campos as $c) {
    echo "  - ID {$c['id']} | orden: {$c['orden']} | clave: '{$c['clave']}' | '{$c['etiqueta']}' | tipo: {$c['tipo']} | cat: {$c['catalogo_id']} | dep: {$c['depende_de']} => val: {$c['valor_activador']}\n";
}
