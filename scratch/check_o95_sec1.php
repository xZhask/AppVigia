<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->prepare("SELECT id, clave, etiqueta, tipo, catalogo_id FROM campo_def WHERE seccion_id = (SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 1) ORDER BY orden ASC");
$stmt->execute();
$campos = $stmt->fetchAll();

echo "Campos en Seccion 1 de O95:\n";
foreach ($campos as $c) {
    echo "  - ID {$c['id']} | clave: '{$c['clave']}' | '{$c['etiqueta']}' | tipo: {$c['tipo']} | cat: {$c['catalogo_id']}\n";
}
