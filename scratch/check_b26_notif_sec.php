<?php
require_once __DIR__ . '/../app/Core/Database.php';

$pdo = \App\Core\Database::conexion();
$secB26 = $pdo->query("SELECT id, orden, nombre FROM seccion_def WHERE enfermedad_id = 9 AND nombre LIKE '%notifi%'")->fetch();
print_r($secB26);

if ($secB26) {
    $campos = $pdo->query("SELECT id, clave, etiqueta, tipo FROM campo_def WHERE seccion_id = {$secB26['id']}")->fetchAll();
    print_r($campos);
}
