<?php
/**
 * B26 (2026-10-15): los bloques que cuadro-clinico-b26.php,
 * lugar-probable-infeccion-b26.php y antecedentes-vacunacion-b26.php ocultan
 * en el navegador (detalle de parótidas, de hospitalización, egreso referido o
 * fallecido, contactos y gestante, vacunación SPR) ya no se guardan cuando no
 * corresponden: 15 campos declaran depende_de. "¿Tuvo contacto con gestante?"
 * pasa de BOOLEANO a SELECT Sí/No/Ignorado (el formulario ya ofrecía las tres
 * opciones y el BOOLEANO perdía "Ignorado").
 *
 * El manifiesto ya lo declara; este script es para las bases donde B26 tiene
 * casos y cargar_fichas.php la deja bloqueada. Toma los depende_de del
 * manifiesto, cambia el tipo de ese campo (con el catálogo Sí/No/Ignorado de
 * "¿Estuvo en contacto con otro caso?"); de sus valores viejos, '1' pasa a SI
 * y '0' se borra: hasta hoy todo BOOLEANO de B26 se guardaba '0' aunque se
 * marcara Sí, así que no dice nada. Después borra en los casos los valores que ya no aplican, con las
 * mismas reglas que "ver" (camposOcultosDelCaso). Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';
require_once __DIR__ . '/../../app/Core/ayudantes.php';

use App\Core\Database;
use App\Models\CampoDef;

$pdo = Database::conexion();

$manifiesto = json_decode(file_get_contents(__DIR__ . '/../../manifiesto_fichas.json'), true);
$ficha = $manifiesto['fichas']['B26'] ?? null;
$enfermedad = $pdo->query("SELECT * FROM enfermedad WHERE cie10 = 'B26'")->fetch();
if (!$ficha || !$enfermedad) {
    fwrite(STDERR, "No existe B26 en el manifiesto o en esta base: correr antes cargar_fichas.php.\n");
    exit(1);
}
$campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
$idPorClave = [];
foreach ($campos as $id => $campo) {
    $idPorClave[$campo['clave']] = (int) $id;
}
$exigir = function (string $clave) use ($idPorClave): int {
    if (!isset($idPorClave[$clave])) {
        fwrite(STDERR, "No existe {$clave} en esta base: correr antes cargar_fichas.php.\n");
        exit(1);
    }
    return $idPorClave[$clave];
};

$cambios = 0;

// "¿Tuvo contacto con gestante?": BOOLEANO -> SELECT Sí/No/Ignorado.
$gestanteId = $exigir('b26_tuvo_contacto_con_gestante');
$catalogoSiNoIgnorado = (int) $campos[$exigir('b26_en_las_ultimas_2_a_4_semanas_estuvo_en_contacto_con')]['catalogo_id'];
if ($campos[$gestanteId]['tipo'] !== 'SELECT') {
    $pdo->prepare("UPDATE campo_def SET tipo = 'SELECT', catalogo_id = ? WHERE id = ?")->execute([$catalogoSiNoIgnorado, $gestanteId]);
    $cambios++;
}
// Valores del BOOLEANO: '1' es Sí; '0' no distingue No de Sí (se guardaba
// '0' para todo) y se borra. Lo que ya es SI/NO/IGNORADO se deja.
$pdo->prepare("UPDATE caso_valor SET valor = 'SI' WHERE campo_def_id = ? AND valor = '1'")->execute([$gestanteId]);
$pdo->prepare("DELETE FROM caso_valor WHERE campo_def_id = ? AND valor = '0'")->execute([$gestanteId]);

// depende_de del manifiesto (clave del padre).
$actualizar = $pdo->prepare('UPDATE campo_def SET depende_de = ?, valor_activador = ? WHERE id = ?');
foreach ($ficha['secciones'] as $seccion) {
    foreach ($seccion['campos'] as $declarado) {
        $id = $exigir($declarado['clave']);
        $padreId = !empty($declarado['depende_de']) ? $exigir($declarado['depende_de']) : null;
        $activador = $declarado['valor_activador'] ?? null;
        if ((int) $campos[$id]['depende_de'] === (int) $padreId && $campos[$id]['valor_activador'] === $activador) {
            continue;
        }
        $actualizar->execute([$padreId, $activador, $id]);
        $cambios++;
    }
}

echo $cambios === 0
    ? "B26 ya tenía sus condiciones.\n"
    : "{$cambios} cambio(s) de definición en B26 (depende_de y tipo de \"¿Tuvo contacto con gestante?\").\n";

// Valores guardados que ya no aplican.
$campos = CampoDef::porEnfermedad((int) $enfermedad['id']);
$casos = $pdo->prepare('SELECT * FROM caso WHERE enfermedad_id = ?');
$casos->execute([$enfermedad['id']]);
$valoresDe = $pdo->prepare('SELECT campo_def_id, valor FROM caso_valor WHERE caso_id = ?');
$borrar = $pdo->prepare('DELETE FROM caso_valor WHERE caso_id = ? AND campo_def_id = ?');
$borrados = 0;
foreach ($casos->fetchAll() as $caso) {
    $valoresDe->execute([$caso['id']]);
    $valores = $valoresDe->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach (camposOcultosDelCaso($campos, $valores, jsonDeEnfermedad($enfermedad, 'reglas_campos'), $caso) as $id => $_) {
        if ((string) ($valores[$id] ?? '') !== '') {
            $borrar->execute([$caso['id'], $id]);
            $borrados++;
        }
    }
}

echo $borrados === 0
    ? "Ningún caso de B26 tenía guardados datos que no corresponden.\n"
    : "{$borrados} valor(es) de B26 borrados: eran de un bloque que no aplica al caso.\n";
