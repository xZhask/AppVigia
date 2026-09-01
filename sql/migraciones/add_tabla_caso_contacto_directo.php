<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// B04X (cotejo 2026-09-01), Sección IV del PDF ("Contactos directos", ítem
// 35): tabla independiente de caso_contacto (que ya cubre el ítem 33,
// "Contacto con caso probable o confirmado de VM") -- son dos censos
// distintos, gateados por preguntas distintas, y caso_contacto::reemplazarTodos()
// hace DELETE+INSERT de TODAS las filas del caso, así que reusar esa misma
// tabla para ambos censos borraría uno cada vez que se guarda el otro.
// "grupo_poblacion" se guarda como lista de códigos separados por coma
// (mismo patrón que caso_contacto.tipo_exposicion): checklist fijo de 6
// categorías de riesgo (gestante/puérpera/recién nacido/niño <8a/adulto
// mayor/inmunodeprimido), no una tabla de catálogo aparte.
$existe = $pdo->query("SHOW TABLES LIKE 'caso_contacto_directo'")->fetchAll();
if (empty($existe)) {
    $pdo->exec(
        "CREATE TABLE caso_contacto_directo (
            id INT NOT NULL AUTO_INCREMENT,
            caso_id INT NOT NULL,
            nombres VARCHAR(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            parentesco VARCHAR(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            celular VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            doc VARCHAR(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            grupo_poblacion VARCHAR(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
            PRIMARY KEY (id),
            KEY ix_ccd_caso (caso_id),
            CONSTRAINT fk_ccd_caso FOREIGN KEY (caso_id) REFERENCES caso (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "Creada tabla caso_contacto_directo.\n";
} else {
    echo "Tabla caso_contacto_directo ya existe.\n";
}
