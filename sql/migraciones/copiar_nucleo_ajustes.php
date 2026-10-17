<?php
/**
 * Copia del manifiesto el nucleo_ajustes de las fichas indicadas
 * (2026-10-16). Para ajustes que solo cambian cómo se muestra o valida la
 * ficha, sin tocar casos guardados (P96: edad_a_la_fecha), en las bases donde
 * esas fichas tienen casos y cargar_fichas.php las deja bloqueadas.
 *
 * Uso: php sql/migraciones/copiar_nucleo_ajustes.php P96
 *
 * Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$fichas = array_slice($argv, 1);
if (!$fichas) {
    fwrite(STDERR, "Uso: php sql/migraciones/copiar_nucleo_ajustes.php P96\n");
    exit(1);
}

$pdo = Database::conexion();
$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);

foreach ($fichas as $cie10) {
    $ficha = $manifiesto['fichas'][$cie10] ?? null;
    $consulta = $pdo->prepare('SELECT id, nucleo_ajustes FROM enfermedad WHERE cie10 = ?');
    $consulta->execute([$cie10]);
    $enfermedad = $consulta->fetch();
    if (!$ficha || !$enfermedad) {
        fwrite(STDERR, "{$cie10}: no existe en el manifiesto o en esta base (correr antes cargar_fichas.php).\n");
        exit(1);
    }

    // Igual que cargar_fichas.php: las notas "_..." de nucleo_ajustes no se guardan.
    $declarado = array_filter((array) ($ficha['nucleo_ajustes'] ?? []), fn($ajuste) => !str_starts_with((string) $ajuste, '_'), ARRAY_FILTER_USE_KEY);
    if ((json_decode((string) $enfermedad['nucleo_ajustes'], true) ?: null) == ($declarado ?: null)) {
        echo "{$cie10}: nucleo_ajustes ya coincide con el manifiesto.\n";
        continue;
    }
    $pdo->prepare('UPDATE enfermedad SET nucleo_ajustes = ? WHERE id = ?')
        ->execute([$declarado ? json_encode($declarado, JSON_UNESCAPED_UNICODE) : null, $enfermedad['id']]);
    echo "{$cie10}: nucleo_ajustes copiado del manifiesto.\n";
}
