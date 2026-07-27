<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$stmt = $pdo->query("SELECT CHECK_CLAUSE FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS WHERE CONSTRAINT_NAME = 'columnas_contacto'");
echo "Constraint clause:\n";
foreach ($stmt->fetchAll() as $row) {
    echo "  - " . $row['CHECK_CLAUSE'] . "\n";
}
