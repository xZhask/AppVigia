<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// B24, VIH/SIDA -- notificación individual (cotejo 2026-09-14, pág. 17 del
// PDF; NTS N.° 115-MINSA/DGE V.01, R.M. 117-2015/MINSA).
//
// La ficha no clasifica el caso: la clasificación la calculan las reglas
// "clasificar" de reglas_campos según el motivo de notificación (decisión del
// usuario, como A50). Caso de infección por VIH -> Confirmado; niño nacido
// expuesto -> "Niño nacido expuesto al VIH" (el valor que ya usa Z21); niño
// expuesto no infectado -> Descartado. Son valores que el ENUM de
// caso.clasificacion ya tiene. opciones_clasificacion no lo gestiona
// cargar_fichas.php.
$pdo->prepare('UPDATE enfermedad SET opciones_clasificacion = ? WHERE cie10 = ?')
    ->execute(['CONFIRMADO,DESCARTADO,NINO_EXPUESTO_VIH', 'B24']);
echo "enfermedad.opciones_clasificacion de B24: CONFIRMADO,DESCARTADO,NINO_EXPUESTO_VIH.\n";
