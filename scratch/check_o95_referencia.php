<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener ID de Seccion 2 de O95 (Referencia (Anexo 1))
$stmtS2 = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 2");
$sec2Id = $stmtS2->fetchColumn();

echo "Seccion 2 ID: $sec2Id\n";

$stmtC = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id FROM campo_def WHERE seccion_id = $sec2Id ORDER BY orden");
$campos = $stmtC->fetchAll();

foreach ($campos as $c) {
    echo " - [ID {$c['id']}] {$c['clave']} => {$c['etiqueta']} ({$c['tipo']})\n";
}
