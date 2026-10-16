<?php
/**
 * O95 (2026-10-16): en el Anexo 2, si la gestante falleció en un EE.SS. de
 * Sanidad FFAA/PNP, la IPRESS se elige del padrón y la "Categoría del
 * EE.SS." no se pide en pantalla; hasta hoy tampoco se guardaba. Ahora el
 * campo declara "categoria_de_ipress" y el servidor guarda la categoría
 * RENIPRESS de esa IPRESS (establecimiento.categoria).
 *
 * El manifiesto ya lo declara; este script es para las bases donde O95 tiene
 * casos y cargar_fichas.php la deja bloqueada. Suma el atributo al config
 * del campo_def y completa la categoría en los casos guardados que tengan
 * una IPRESS del padrón, como al editar y guardar. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$pdo = Database::conexion();

$enfermedad = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'O95'")->fetch();
$categoria = $enfermedad ? CampoDef::porClave((int) $enfermedad['id'], 'o95_categoria_del_ee_ss') : null;
$ipress = $enfermedad ? CampoDef::porClave((int) $enfermedad['id'], 'o95_establecimiento_sanidad_pnp') : null;
if (!$categoria || !$ipress) {
    fwrite(STDERR, "No existe O95 o sus campos de categoría/IPRESS en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}

$config = json_decode((string) $categoria['config'], true) ?: [];
if (($config['categoria_de_ipress'] ?? null) !== 'o95_establecimiento_sanidad_pnp') {
    $config['categoria_de_ipress'] = 'o95_establecimiento_sanidad_pnp';
    $pdo->prepare('UPDATE campo_def SET config = ? WHERE id = ?')->execute([json_encode($config, JSON_UNESCAPED_UNICODE), $categoria['id']]);
    echo "O95: la categoría del EE.SS. ahora sale de la IPRESS del padrón.\n";
} else {
    echo "O95: la categoría del EE.SS. ya salía de la IPRESS del padrón.\n";
}

// Casos con IPRESS del padrón y en Anexo 2 (el depende_de del campo).
$campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
$casos = $pdo->prepare('SELECT c.id, cv.valor AS ipress FROM caso c JOIN caso_valor cv ON cv.caso_id = c.id AND cv.campo_def_id = ? WHERE c.enfermedad_id = ? AND cv.valor <> ""');
$casos->execute([$ipress['id'], $enfermedad['id']]);
$valoresDe = $pdo->prepare('SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = ?');
$guardar = $pdo->prepare('INSERT INTO caso_valor (caso_id, campo_def_id, valor) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)');
$completados = 0;
foreach ($casos->fetchAll() as $caso) {
    $valoresDe->execute([$caso['id']]);
    $valores = $valoresDe->fetchAll(PDO::FETCH_KEY_PAIR);
    $categoriaPadron = categoriaDeIpress((string) $caso['ipress']);
    if ($categoriaPadron === null || !campoVisiblePorDependencia($campos[(int) $categoria['id']], $valores)) {
        continue;
    }
    if ((string) ($valores[(int) $categoria['id']] ?? '') !== $categoriaPadron) {
        $guardar->execute([$caso['id'], $categoria['id'], $categoriaPadron]);
        $completados++;
    }
}
echo $completados === 0
    ? "Ningún caso de O95 necesitaba la categoría de su IPRESS.\n"
    : "{$completados} caso(s) de O95 con la categoría de su IPRESS completada.\n";
