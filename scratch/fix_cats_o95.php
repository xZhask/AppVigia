<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$pdo->exec("UPDATE campo_def SET catalogo_id = 494 WHERE clave = 'o95_responsable_de_la_atencion'");

echo "Campo o95_responsable_de_la_atencion actualizado a catalogo 494.\n";
