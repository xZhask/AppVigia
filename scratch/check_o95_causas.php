<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener ID de Seccion 3 de O95 (Causas de defunción (Anexo 1))
$stmtS3 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 3");
$sec3Id = $stmtS3->fetchColumn();

echo "Seccion 3 ID: $sec3Id\n";

$stmtC = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id FROM campo_def WHERE seccion_id = $sec3Id ORDER BY orden");
$campos = $stmtC->fetchAll();

foreach ($campos as $c) {
    echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
}
