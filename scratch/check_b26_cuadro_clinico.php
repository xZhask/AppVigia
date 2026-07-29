<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$secciones = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = 9 ORDER BY orden")->fetchAll();

echo "SECCIONES B26:\n";
foreach ($secciones as $s) {
    echo "--- SECCIÓN {$s['id']} (orden {$s['orden']}): {$s['nombre']} ---\n";
    $campos = $pdo->query("SELECT id, clave, etiqueta, tipo, catalogo_id, depende_de, valor_activador FROM campo_def WHERE seccion_id = {$s['id']} ORDER BY orden")->fetchAll();
    foreach ($campos as $c) {
        echo "  ID {$c['id']} | clave: {$c['clave']} | etiqueta: {$c['etiqueta']} | tipo: {$c['tipo']}\n";
    }
}
