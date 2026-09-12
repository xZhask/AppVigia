<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Z21 deja de ser multi_sujeto (cotejo 2026-09-11, "opción A" del usuario):
// antes UN caso guardaba a la gestante (rol MADRE) y al niño (NINO_EXPUESTO)
// juntos; ahora cada uno es su propio caso, con su propia persona como
// sujeto principal (CASO_INDICE), y el del niño se enlaza al de su madre por
// caso.caso_vinculado_id (add_vinculo_caso_y_campos_notificacion.php).
// multi_sujeto/roles_sujeto no los gestiona cargar_fichas.php (viven solo en
// la BD), por eso va como migración -- mismo criterio que
// set_opciones_clasificacion_a00.php. Z21 no tenía ningún caso guardado.
$pdo->prepare("UPDATE enfermedad SET multi_sujeto = 0, roles_sujeto = NULL WHERE cie10 = 'Z21'")->execute();
echo "Z21: multi_sujeto = 0, roles_sujeto = NULL.\n";
