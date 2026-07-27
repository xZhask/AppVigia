<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$stmt = $pdo->query("SELECT * FROM catalogo WHERE nombre LIKE '%vigilancia%' OR nombre LIKE '%identificado%' OR nombre LIKE '%notificacion%'");
echo "Catalogos relacionados:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - ID {$row['id']} | '{$row['nombre']}'\n";
}
