<?php
/**
 * Fecha de notificación en la cadena del PDF (2026-10-16, decisión 2 de
 * HOJA_DE_RUTA.md): A37.0, B01, B26, A35, A33, A80 y P35.0 pedían dos veces
 * la misma fecha (la del núcleo en la tarjeta "Notificación" y su "Fecha de
 * notificación EE.SS. a Red/Microred"). Ahora declaran
 * nucleo_ajustes.fecha_notif_en: la del núcleo se pinta en el sitio de la de
 * la cadena y se guarda también en ese campo.
 *
 * Uso: php sql/migraciones/set_fecha_notif_en.php A37.0 B01 B26 A35 A33 A80 P35.0
 *
 * El manifiesto ya lo declara; este script es para las bases donde esas
 * fichas tienen casos y cargar_fichas.php las deja bloqueadas. Por cada
 * ficha copia del manifiesto nucleo_ajustes y en los casos guardados pone
 * en ese campo caso.fecha_notif (la que da la SE), como al editar y
 * guardar. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$fichas = array_slice($argv, 1);
if (!$fichas) {
    fwrite(STDERR, "Uso: php sql/migraciones/set_fecha_notif_en.php A37.0 B01 B26 A35 A33 A80 P35.0\n");
    exit(1);
}

$pdo = Database::conexion();
$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);

foreach ($fichas as $cie10) {
    $ficha = $manifiesto['fichas'][$cie10] ?? null;
    $consulta = $pdo->prepare('SELECT * FROM enfermedad WHERE cie10 = ?');
    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    if (!$ficha || !$enfermedad) {
        fwrite(STDERR, "{$cie10}: no existe en el manifiesto o en esta base (correr antes cargar_fichas.php).\n");
        exit(1);
    }

    // Igual que cargar_fichas.php: las notas "_..." de nucleo_ajustes no se guardan.
    $declarado = array_filter((array) ($ficha['nucleo_ajustes'] ?? []), fn($ajuste) => !str_starts_with((string) $ajuste, '_'), ARRAY_FILTER_USE_KEY);
    if ((json_decode((string) $enfermedad['nucleo_ajustes'], true) ?: null) != ($declarado ?: null)) {
        $pdo->prepare('UPDATE enfermedad SET nucleo_ajustes = ? WHERE id = ?')
            ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
        echo "{$cie10}: nucleo_ajustes copiado del manifiesto.\n";
        $consulta->execute([$cie10]);
        $enfermedad = $consulta->fetch();
    } else {
        echo "{$cie10}: ya tenía declarada su fecha de notificación.\n";
    }

    $clave = claveFechaNotifEn($enfermedad);
    $campo = $clave !== null ? CampoDef::porClave((int) $enfermedad['id'], $clave) : null;
    if (!$campo) {
        fwrite(STDERR, "{$cie10}: no existe el campo de fecha_notif_en en esta base (correr antes cargar_fichas.php).\n");
        exit(1);
    }
    $actualizados = $pdo->prepare(
        'INSERT INTO caso_valor (caso_id, campo_def_id, valor)
         SELECT c.id, ?, c.fecha_notif FROM caso c
          WHERE c.enfermedad_id = ? AND c.fecha_notif IS NOT NULL
             ON DUPLICATE KEY UPDATE valor = VALUES(valor)'
    );
    $actualizados->execute([$campo['id'], $enfermedad['id']]);
    // MySQL cuenta 1 por fila nueva, 2 por fila cambiada y 0 por fila igual.
    echo $actualizados->rowCount() === 0
        ? "{$cie10}: ningún caso cambió.\n"
        : "{$cie10}: casos con \"{$campo['etiqueta']}\" igualada a la fecha de notificación.\n";
}
