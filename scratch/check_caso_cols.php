<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();
$cols = $pdo->query("SHOW COLUMNS FROM caso")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo $c['Field'] . " (" . $c['Type'] . ")\n";
}
