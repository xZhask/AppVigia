<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$sec = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE id = 2515")->fetch(PDO::FETCH_ASSOC);
echo "ID {$sec['id']} | orden {$sec['orden']} | nombre: {$sec['nombre']}\n";
$campos = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = 2515 ORDER BY orden")->fetchAll(PDO::FETCH_ASSOC);
foreach ($campos as $c) {
    echo "     [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
}
