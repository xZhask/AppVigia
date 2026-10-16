<?php
/**
 * O95 (2026-10-15): el departamento y la provincia del ubigeo de
 * fallecimiento y del origen de la referencia se validan contra el padrón,
 * como ya se validaba el distrito. Los tres niveles declaran "formato"
 * (departamento/provincia/distrito) y la provincia y el distrito declaran
 * "dentro_de" (la clave del nivel de arriba): el servidor deriva el nivel de
 * arriba del de abajo, así que un departamento o una provincia que no
 * corresponden no se guardan.
 *
 * El manifiesto ya lo declara; este script es para las bases donde O95 tiene
 * casos y cargar_fichas.php la deja bloqueada. Fija el config de los 6
 * campo_def existentes (no los recrea) y corrige en los casos guardados la
 * provincia y el departamento que no correspondan al distrito (o el
 * departamento que no corresponda a la provincia, si no hay distrito): es lo
 * mismo que haría el servidor al editar y guardar el caso. Idempotente.
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$configs = [];
foreach (['fallecimiento', 'origen_de_la_referencia'] as $lugar) {
    $dep = "o95_departamento_{$lugar}";
    $prov = "o95_provincia_{$lugar}";
    $dist = "o95_distrito_{$lugar}";
    $configs[$dep] = ['formato' => 'departamento'];
    $configs[$prov] = ['formato' => 'provincia', 'dentro_de' => $dep];
    $configs[$dist] = ['formato' => 'distrito', 'dentro_de' => $prov];
}

$buscarCampo = $pdo->prepare(
    "SELECT cd.id, cd.config
       FROM campo_def cd
       JOIN seccion_def sd ON sd.id = cd.seccion_id
       JOIN enfermedad e ON e.id = sd.enfermedad_id
      WHERE e.cie10 = 'O95' AND cd.clave = ?"
);
$actualizar = $pdo->prepare('UPDATE campo_def SET config = ? WHERE id = ?');

$ids = [];
$cambiados = 0;
foreach ($configs as $clave => $config) {
    $buscarCampo->execute([$clave]);
    $campo = $buscarCampo->fetch();
    if (!$campo) {
        fwrite(STDERR, "No existe {$clave} en esta base: correr antes cargar_fichas.php.\n");
        exit(1);
    }
    $ids[$clave] = (int) $campo['id'];
    if ((json_decode((string) $campo['config'], true) ?: []) === $config) {
        continue;
    }
    $actualizar->execute([json_encode($config, JSON_UNESCAPED_UNICODE), $campo['id']]);
    $cambiados++;
}

echo $cambiados === 0
    ? "Los 6 campos de ubigeo de O95 ya traían su formato.\n"
    : "{$cambiados} campo(s) de ubigeo de O95 con formato nuevo.\n";

// Valores guardados: de abajo hacia arriba, como derivarUbigeoDentroDe().
$corregidos = 0;
$fijar = $pdo->prepare(
    'INSERT INTO caso_valor (caso_id, campo_def_id, valor) VALUES (?, ?, ?)'
);
$cambiar = $pdo->prepare('UPDATE caso_valor SET valor = ? WHERE caso_id = ? AND campo_def_id = ?');
foreach (['fallecimiento', 'origen_de_la_referencia'] as $lugar) {
    foreach ([
        ["o95_distrito_{$lugar}", 'distrito', 'provincia_id', "o95_provincia_{$lugar}"],
        ["o95_provincia_{$lugar}", 'provincia', 'departamento_id', "o95_departamento_{$lugar}"],
    ] as [$claveHijo, $tabla, $columnaPadre, $clavePadre]) {
        $filas = $pdo->prepare(
            "SELECT hijo.caso_id, u.{$columnaPadre} AS esperado, padre.valor AS actual, padre.id AS padre_valor_id
               FROM caso_valor hijo
               JOIN {$tabla} u ON u.id = hijo.valor
               LEFT JOIN caso_valor padre ON padre.caso_id = hijo.caso_id AND padre.campo_def_id = ?
              WHERE hijo.campo_def_id = ?"
        );
        $filas->execute([$ids[$clavePadre], $ids[$claveHijo]]);
        foreach ($filas->fetchAll() as $fila) {
            if ((string) $fila['actual'] === (string) $fila['esperado']) {
                continue;
            }
            if ($fila['padre_valor_id'] === null) {
                $fijar->execute([$fila['caso_id'], $ids[$clavePadre], $fila['esperado']]);
            } else {
                $cambiar->execute([$fila['esperado'], $fila['caso_id'], $ids[$clavePadre]]);
            }
            $corregidos++;
        }
    }
}

echo $corregidos === 0
    ? "Ningún caso guardado tenía el ubigeo de O95 incoherente.\n"
    : "{$corregidos} valor(es) de departamento/provincia de O95 corregidos desde el nivel de abajo.\n";
