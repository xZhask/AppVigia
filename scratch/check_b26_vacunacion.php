<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$fields = $pdo->query("SELECT id, seccion_id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id IN (SELECT id FROM seccion_def WHERE enfermedad_id = 9)")->fetchAll();

echo "TODOS LOS CAMPOS DE B26:\n";
foreach ($fields as $f) {
    echo "ID {$f['id']} | Seccion {$f['seccion_id']} | Clave: {$f['clave']} | Etiqueta: {$f['etiqueta']} | Tipo: {$f['tipo']}\n";
}
