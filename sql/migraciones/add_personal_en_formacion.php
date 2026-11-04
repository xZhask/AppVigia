<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Personal en formación (2026-11-04, decisión del área): cadetes y alumnos son
// una condición del paciente propia, no un grado de "Efectivo PNP" ni titulares
// de un derechohabiente. Cadete y Alumno siguen en grado_pnp por dentro (de ahí
// salen la abreviatura y el nivel de los reportes), pero ya no se ofrecen como
// grado. Se puede correr más de una vez.
$columna = $pdo->query("SHOW COLUMNS FROM persona LIKE 'condicion'")->fetch();
if (!str_contains($columna['Type'], 'EN_FORMACION')) {
    $pdo->exec("ALTER TABLE persona MODIFY condicion
                enum('EFECTIVO','DERECHOHABIENTE','PARTICULAR','EN_FORMACION')
                COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PARTICULAR'");
    echo "persona.condicion: agregada EN_FORMACION.\n";
} else {
    echo "persona.condicion: ya tiene EN_FORMACION.\n";
}

// Sin titular: quien dependía de un cadete o alumno queda como derechohabiente
// sin titular vinculado (es opcional); conserva su vínculo.
$desvinculados = $pdo->exec(
    "UPDATE persona h
       JOIN persona t ON t.id = h.titular_id
       JOIN grado_pnp g ON g.id = t.grado_id
        SET h.titular_id = NULL
      WHERE g.nivel IN ('CADETE', 'ALUMNO')"
);
echo "Derechohabientes desvinculados de un cadete o alumno: {$desvinculados}.\n";

// Situación y categoría no aplican a personal en formación.
$pasadas = $pdo->exec(
    "UPDATE persona p
       JOIN grado_pnp g ON g.id = p.grado_id
        SET p.condicion = 'EN_FORMACION', p.situacion_pnp = NULL, p.categoria_pnp = NULL
      WHERE p.condicion = 'EFECTIVO' AND g.nivel IN ('CADETE', 'ALUMNO')"
);
echo "Personas pasadas de Efectivo PNP a Personal en formación: {$pasadas}.\n";
