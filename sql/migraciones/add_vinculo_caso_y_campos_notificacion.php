<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Motor de la ficha Z21 (Gestante con VIH y niño nacido expuesto, cotejo
// 2026-09-11, "opción A" elegida por el usuario): cada registro -- la
// gestante y cada niño expuesto -- es su propio caso, y el del niño apunta
// al de su madre. Tres declaraciones nuevas del manifiesto, mismo mecanismo
// JSON que nucleo_omitidos/nucleo_incluidos (cargar_fichas.php las valida y
// las persiste acá; NULL = la ficha no declara nada, comportamiento de
// siempre):
//   campos_notificacion -- claves de campo_def que se pintan dentro de la
//                          tarjeta fija "1. Notificación" (versión
//                          declarativa de los notificacion-fechas-<ficha>.php).
//   vinculo_caso        -- enlace de un caso con otro caso de la misma ficha.
//   nucleo_condicional  -- bloques del núcleo (etnia, residencia) que solo
//                          aplican con cierto valor de un campo_def.
foreach (['campos_notificacion', 'vinculo_caso', 'nucleo_condicional'] as $columna) {
    $hasCol = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE '{$columna}'")->fetchColumn();
    if (!$hasCol) {
        $pdo->query("ALTER TABLE enfermedad ADD COLUMN {$columna} LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL CHECK (json_valid({$columna}))");
        echo "Columna {$columna} agregada a la tabla enfermedad.\n";
    } else {
        echo "Columna {$columna} ya existe en enfermedad.\n";
    }
}

// caso.caso_vinculado_id: el caso con el que se enlaza este (en Z21, la
// ficha de la madre). ON DELETE SET NULL: si el caso vinculado llegara a
// borrarse, el hijo conserva sus propios datos (el código/DNI de la madre
// ya quedaron copiados en sus campos) y solo pierde el enlace.
$hasCol = $pdo->query("SHOW COLUMNS FROM caso LIKE 'caso_vinculado_id'")->fetchColumn();
if (!$hasCol) {
    $pdo->query('ALTER TABLE caso ADD COLUMN caso_vinculado_id INT NULL AFTER persona_id, ADD KEY ix_caso_vinculado (caso_vinculado_id), ADD CONSTRAINT fk_caso_vinculado FOREIGN KEY (caso_vinculado_id) REFERENCES caso (id) ON DELETE SET NULL');
    echo "Columna caso_vinculado_id agregada a la tabla caso.\n";
} else {
    echo "Columna caso_vinculado_id ya existe en caso.\n";
}
