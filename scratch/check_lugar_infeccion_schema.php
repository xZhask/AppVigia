<?php
require_once __DIR__ . '/../app/Core/Database.php';
$pdo = \App\Core\Database::conexion();
$stmt = $pdo->query("DESCRIBE caso_lugar_infeccion");
print_r($stmt->fetchAll());

echo "\n--- DESCRIBE caso_contacto ---\n";
$stmt2 = $pdo->query("DESCRIBE caso_contacto");
print_r($stmt2->fetchAll());

echo "\n--- DESCRIBE caso ---\n";
$stmt3 = $pdo->query("DESCRIBE caso");
$colsCaso = array_column($stmt3->fetchAll(), 'Field');
foreach ($colsCaso as $c) {
    if (str_contains($c, 'infeccion') || str_contains($c, 'lugar') || str_contains($c, 'gestante') || str_contains($c, 'contacto')) {
        echo "  caso column: $c\n";
    }
}
