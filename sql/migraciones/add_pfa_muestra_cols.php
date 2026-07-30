<?php
require __DIR__ . '/../../app/Core/Autoload.php';
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

use App\Core\Database;

try {
    $pdo = Database::conexion();

    $columnasNuevas = [
        'fecha_envio_ins' => "DATE NULL",
        'agente_aislado'  => "VARCHAR(120) NULL",
        'observaciones'   => "VARCHAR(255) NULL"
    ];

    foreach ($columnasNuevas as $col => $sqlType) {
        $check = $pdo->query("SHOW COLUMNS FROM caso_muestra LIKE '$col'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE caso_muestra ADD COLUMN `$col` $sqlType");
            echo "Columna `$col` agregada con éxito.\n";
        } else {
            echo "Columna `$col` ya existe.\n";
        }
    }
    echo "Proceso finalizado correctamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
