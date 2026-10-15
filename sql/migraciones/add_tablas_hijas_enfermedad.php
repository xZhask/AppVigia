<?php
/**
 * enfermedad.tablas_hijas (2026-10-15): lista de las tablas hijas que la ficha
 * usa de verdad (caso_contacto, caso_contacto_directo, caso_viaje, caso_vacuna,
 * caso_muestra, caso_lugar_infeccion, caso_evolucion, caso_examen_auxiliar),
 * como la declara "tablas_hijas" en manifiesto_fichas.json. CasosController
 * guarda solo esas: antes las 8 se guardaban siempre y un POST forjado podía
 * agregar filas a una ficha que no tiene esa tabla. NULL = sin declarar
 * (se guardan todas, comportamiento anterior) hasta correr cargar_fichas.php.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$existe = $pdo->query("SHOW COLUMNS FROM enfermedad LIKE 'tablas_hijas'")->fetch();
if ($existe) {
    echo "enfermedad.tablas_hijas ya existe.\n";
    exit(0);
}
$pdo->exec("ALTER TABLE enfermedad ADD COLUMN tablas_hijas TEXT NULL DEFAULT NULL AFTER usa_lugar_infeccion");
echo "Columna enfermedad.tablas_hijas agregada. Corre cargar_fichas.php --apply para llenarla.\n";
