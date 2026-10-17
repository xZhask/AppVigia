<?php
/**
 * Z21, "Observaciones" para las dos ramas (2026-10-16): el formulario de
 * búsqueda activa (pág. 15 del PDF) pide observaciones en cada fila, de
 * gestante o de niño, pero el campo z21_observaciones iba dentro de la sección
 * del niño ("Determinación del estado serológico...", con depende_de a esa
 * rama). Ahora va en su propia sección final "Observaciones", sin depende_de.
 *
 * El manifiesto ya lo declara; este script es para las bases donde Z21 tiene
 * casos y cargar_fichas.php la deja bloqueada. Crea la sección si falta y
 * mueve el campo (mismo id: los valores guardados no cambian). Idempotente.
 *
 * Uso: php sql/migraciones/set_z21_observaciones_ambas_ramas.php
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$enfermedadId = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'Z21'")->fetchColumn();
$campo = $enfermedadId ? $pdo->query(
    "SELECT c.id, c.seccion_id FROM campo_def c JOIN seccion_def s ON s.id = c.seccion_id
      WHERE s.enfermedad_id = " . (int) $enfermedadId . " AND c.clave = 'z21_observaciones'"
)->fetch() : null;
if (!$campo) {
    fwrite(STDERR, "Z21: no existe z21_observaciones en esta base (correr antes cargar_fichas.php).\n");
    exit(1);
}

$consulta = $pdo->prepare('SELECT id FROM seccion_def WHERE enfermedad_id = ? AND nombre = ? AND depende_de IS NULL');
$consulta->execute([$enfermedadId, 'Observaciones']);
$seccionId = $consulta->fetchColumn();

$pdo->beginTransaction();
if (!$seccionId) {
    $orden = (int) $pdo->query('SELECT COALESCE(MAX(orden), 0) + 1 FROM seccion_def WHERE enfermedad_id = ' . (int) $enfermedadId)->fetchColumn();
    $pdo->prepare('INSERT INTO seccion_def (enfermedad_id, nombre, orden) VALUES (?, ?, ?)')
        ->execute([$enfermedadId, 'Observaciones', $orden]);
    $seccionId = (int) $pdo->lastInsertId();
    echo "Z21: sección \"Observaciones\" creada (orden {$orden}).\n";
}
if ((int) $campo['seccion_id'] !== (int) $seccionId) {
    $pdo->prepare('UPDATE campo_def SET seccion_id = ?, orden = 1 WHERE id = ?')->execute([$seccionId, $campo['id']]);
    echo "Z21: z21_observaciones movido a \"Observaciones\" (visible en las dos ramas).\n";
} else {
    echo "Z21: z21_observaciones ya estaba en \"Observaciones\".\n";
}
$pdo->commit();
