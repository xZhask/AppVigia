<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$sql = "SELECT c.id, c.etiqueta, c.tipo, s.nombre AS seccion, s.orden AS seccion_orden
        FROM campo_def c
        JOIN seccion_def s ON c.seccion_id = s.id
        JOIN enfermedad e ON s.enfermedad_id = e.id
        WHERE e.cie10 = 'B26'
        ORDER BY s.orden, c.orden";

$stmt = $pdo->query($sql);
$campos = $stmt->fetchAll(PDO::FETCH_ASSOC);

print_r($campos);
