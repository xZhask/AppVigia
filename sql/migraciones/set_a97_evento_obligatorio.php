<?php
/**
 * A97: "Enfermedad / evento a notificar" obligatorio (2026-11-04, decisión del
 * usuario): sin él, la ficha no dice qué enfermedad notifica ni muestra su
 * clasificación. Las fichas ya guardadas sin este dato no cambian, pero lo
 * pedirán al editarlas. Es para las bases donde A97 tiene casos y
 * cargar_fichas.php la deja bloqueada. Idempotente.
 *
 * Uso: php sql/migraciones/set_a97_evento_obligatorio.php
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$cambio = Database::conexion()->exec(
    "UPDATE campo_def cd
       JOIN seccion_def s ON s.id = cd.seccion_id
       JOIN enfermedad e ON e.id = s.enfermedad_id
        SET cd.obligatorio = 1
      WHERE e.cie10 = 'A97' AND cd.clave = 'a97_enfermedad_evento' AND cd.obligatorio = 0"
);
echo $cambio ? "a97_enfermedad_evento: ahora es obligatorio.\n" : "a97_enfermedad_evento: ya era obligatorio.\n";
