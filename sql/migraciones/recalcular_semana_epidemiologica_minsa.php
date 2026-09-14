<?php
require __DIR__ . '/../../app/Core/Autoload.php';
require __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;

$pdo = Database::conexion();

// Semana epidemiológica según el calendario del MINSA (2026-09-14).
//
// caso.anio_epi/semana_epi se calculaban con la semana ISO-8601 (lunes a
// domingo). El calendario epidemiológico de la DGE va de domingo a sábado y su
// SE 1 es la que contiene el 4 de enero, así que en 2026 la app numeraba casi
// todos los días una semana de más (el 14/09/2026 decía SE 38; el MINSA, 37).
// semanaEpidemiologica() (app/Core/ayudantes.php) ya calcula la del MINSA;
// esto recalcula las semanas guardadas a partir de caso.fecha_notif con esa
// misma función, la que usan los casos nuevos. Idempotente: una segunda
// corrida no cambia nada.
//
// actualizado_en se deja como estaba: el cambio no es una edición de la
// ficha. notificacion_negativa no se toca: se creó en la misma tanda que este
// arreglo y solo guarda semanas elegidas ya con el calendario del MINSA.
$casos = $pdo->query('SELECT id, codigo, fecha_notif, anio_epi, semana_epi FROM caso ORDER BY id')->fetchAll();
$actualizar = $pdo->prepare('UPDATE caso SET anio_epi = ?, semana_epi = ?, actualizado_en = actualizado_en WHERE id = ?');

$recalculados = 0;
$pdo->beginTransaction();
foreach ($casos as $caso) {
    $semana = semanaEpidemiologica((string) $caso['fecha_notif']);
    if ((int) $caso['anio_epi'] === $semana['anio'] && (int) $caso['semana_epi'] === $semana['semana']) {
        continue;
    }
    $actualizar->execute([$semana['anio'], $semana['semana'], (int) $caso['id']]);
    $recalculados++;
    printf(
        "%s (notificada el %s): SE %s · %s -> SE %d · %d\n",
        $caso['codigo'],
        $caso['fecha_notif'],
        $caso['semana_epi'] ?? '-',
        $caso['anio_epi'] ?? '-',
        $semana['semana'],
        $semana['anio']
    );
}
$pdo->commit();

printf("Casos revisados: %d · semana recalculada: %d\n", count($casos), $recalculados);
