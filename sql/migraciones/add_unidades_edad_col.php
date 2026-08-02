<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Mismo patrón exacto que nucleo_omitidos/columnas_sujeto/titulo_sujeto:
// declarativo por ficha en el manifiesto, persistido acá como JSON, NULL si
// la ficha no lo declara. A diferencia de esos tres, es opt-in en vez de
// opt-out -- ausente equivale al comportamiento actual (edad solo en años,
// derivada de persona.fecha_nac), así que las fichas que no lo necesitan no
// se tocan. Ver PETICION_MAPEO_Y_EDAD.md, Parte 2 (entrada F).
$hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'unidades_edad'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE enfermedad ADD COLUMN unidades_edad LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid(unidades_edad)) AFTER titulo_sujeto");
    echo "Columna unidades_edad agregada a la tabla enfermedad.\n";
} else {
    echo "Columna unidades_edad ya existe en enfermedad.\n";
}
