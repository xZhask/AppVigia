<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id de enfermedad O95
$stmtEnf = $pdo->query("SELECT id FROM enfermedad WHERE cie10 = 'O95'");
$enfId = $stmtEnf->fetchColumn();

// Obtener id de seccion 1 de O95 (Datos del fallecimiento (Anexo 1))
$stmtS1 = $pdo->prepare("SELECT id FROM seccion_def WHERE enfermedad_id = ? AND orden = 1");
$stmtS1->execute([$enfId]);
$sec1Id = $stmtS1->fetchColumn();

// 1. Eliminar campos viejos irrelevantes de Seccion 1 en DB
$pdo->prepare("DELETE FROM campo_def WHERE clave IN ('o95_fecha_y_hora_de_fallecimiento', 'o95_permanencia_estadia_en_el_ee_ss') AND seccion_id = ?")->execute([$sec1Id]);

// 2. Si la seccion "Datos del fallecimiento ampliados (Anexo 2)" existe en DB, la conservamos vacia o limpia para sincronia del manifiesto
$stmtS2 = $pdo->prepare("SELECT id FROM seccion_def WHERE enfermedad_id = ? AND nombre LIKE '%Datos del fallecimiento ampliados%'");
$stmtS2->execute([$enfId]);
$sec2Id = $stmtS2->fetchColumn();
if ($sec2Id) {
    $pdo->prepare("DELETE FROM campo_def WHERE seccion_id = ?")->execute([$sec2Id]);
    $pdo->prepare("DELETE FROM seccion_def WHERE id = ?")->execute([$sec2Id]);
}

// 3. Crear o actualizar catalogos para los campos SELECT de Seccion 1
// Catalogo Momento del fallecimiento (Cat ID 86)
$stmtCat86 = $pdo->query("SELECT id FROM catalogo WHERE id = 86 OR nombre = 'O95 - Momento del fallecimiento'");
$catId86 = $stmtCat86->fetchColumn();
if (!$catId86) {
    $pdo->query("INSERT INTO catalogo (id, nombre) VALUES (86, 'O95 - Momento del fallecimiento')");
    $catId86 = 86;
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId86]);
$insItem = $pdo->prepare("INSERT INTO catalogo_item (catalogo_id, valor, etiqueta, orden) VALUES (?, ?, ?, ?)");
$insItem->execute([$catId86, 'Embarazo', 'Embarazo', 1]);
$insItem->execute([$catId86, 'Parto', 'Parto', 2]);
$insItem->execute([$catId86, 'Puerperio', 'Puerperio', 3]);
$insItem->execute([$catId86, 'Desconocido', 'Desconocido', 4]);

// Catalogo Fase del puerperio (Cat ID 492)
$stmtCat492 = $pdo->query("SELECT id FROM catalogo WHERE id = 492 OR nombre = 'O95 - Fase del puerperio'");
$catId492 = $stmtCat492->fetchColumn();
if (!$catId492) {
    $pdo->query("INSERT INTO catalogo (id, nombre) VALUES (492, 'O95 - Fase del puerperio')");
    $catId492 = 492;
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId492]);
$insItem->execute([$catId492, 'Inmediato', 'Inmediato', 1]);
$insItem->execute([$catId492, 'Mediato', 'Mediato', 2]);
$insItem->execute([$catId492, 'Tardío', 'Tardío', 3]);
$insItem->execute([$catId492, 'Desconocido', 'Desconocido', 4]);

// Catalogo Lugar del fallecimiento (Cat ID 491)
$stmtCat491 = $pdo->query("SELECT id FROM catalogo WHERE id = 491 OR nombre = 'O95 - Lugar del fallecimiento'");
$catId491 = $stmtCat491->fetchColumn();
if (!$catId491) {
    $pdo->query("INSERT INTO catalogo (id, nombre) VALUES (491, 'O95 - Lugar del fallecimiento')");
    $catId491 = 491;
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId491]);
$insItem->execute([$catId491, 'Establecimiento de salud', 'Establecimiento de salud', 1]);
$insItem->execute([$catId491, 'Domicilio', 'Domicilio', 2]);
$insItem->execute([$catId491, 'Trayecto', 'Trayecto', 3]);
$insItem->execute([$catId491, 'Otro', 'Otro', 4]);

// Catalogo Tipo de EE.SS. (Cat ID 538)
$stmtCat538 = $pdo->query("SELECT id FROM catalogo WHERE nombre = 'O95 - Tipo de EE.SS. fallecimiento'");
$catId538 = $stmtCat538->fetchColumn();
if (!$catId538) {
    $pdo->query("INSERT INTO catalogo (nombre) VALUES ('O95 - Tipo de EE.SS. fallecimiento')");
    $catId538 = $pdo->lastInsertId();
}
$pdo->prepare("DELETE FROM catalogo_item WHERE catalogo_id = ?")->execute([$catId538]);
$insItem->execute([$catId538, 'EESS Sanidad FFAA/PNP', 'EESS Sanidad FFAA/PNP', 1]);
$insItem->execute([$catId538, 'EESS MINSA / IGSS', 'EESS MINSA / IGSS', 2]);
$insItem->execute([$catId538, 'EESS EsSalud', 'EESS EsSalud', 3]);
$insItem->execute([$catId538, 'EESS Privado', 'EESS Privado', 4]);

// Obtener ID del campo o95_momento_del_fallecimiento
$idMomento = $pdo->query("SELECT id FROM campo_def WHERE clave = 'o95_momento_del_fallecimiento'")->fetchColumn();

// Asignar catalogo_id a los campos SELECT en DB
$pdo->prepare("UPDATE campo_def SET catalogo_id = ? WHERE clave = 'o95_momento_del_fallecimiento'")->execute([$catId86]);
$pdo->prepare("UPDATE campo_def SET catalogo_id = ?, depende_de = ?, valor_activador = 'Puerperio' WHERE clave = 'o95_fase_del_puerperio_en_que_fallecio'")->execute([$catId492, $idMomento]);
$pdo->prepare("UPDATE campo_def SET catalogo_id = ? WHERE clave = 'o95_lugar_del_fallecimiento'")->execute([$catId491]);
$pdo->prepare("UPDATE campo_def SET catalogo_id = ? WHERE clave = 'o95_tipo_eess_fallecimiento'")->execute([$catId538]);

// Reordenar secciones en DB
$stmtRest = $pdo->prepare("SELECT id FROM seccion_def WHERE enfermedad_id = ? ORDER BY orden, id");
$stmtRest->execute([$enfId]);
$secciones = $stmtRest->fetchAll(PDO::FETCH_COLUMN);

$u = $pdo->prepare("UPDATE seccion_def SET orden = ? WHERE id = ?");
$i = 1;
foreach ($secciones as $sid) {
    $u->execute([$i++, $sid]);
}

echo "Base de datos sincronizada correctamente para O95 Seccion 1.\n";

// Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    $nuevasSecciones = [];
    $ordenIndex = 1;
    foreach ($manifest['fichas']['O95']['secciones'] as $sec) {
        $nombreLower = strtolower($sec['nombre'] ?? '');
        if (stripos($nombreLower, 'fallecimiento ampliados') !== false) {
            continue;
        }
        $sec['orden'] = $ordenIndex++;
        if ($sec['orden'] == 1 || stripos($nombreLower, 'datos del fallecimiento') !== false) {
            $sec['campos'] = [
                ['clave' => 'o95_momento_del_fallecimiento', 'etiqueta' => 'Momento del fallecimiento', 'tipo' => 'SELECT', 'opciones' => ['Embarazo', 'Parto', 'Puerperio', 'Desconocido']],
                ['clave' => 'o95_fase_del_puerperio_en_que_fallecio', 'etiqueta' => 'Fase del puerperio', 'tipo' => 'SELECT', 'opciones' => ['Inmediato', 'Mediato', 'Tardío', 'Desconocido'], 'depende_de' => 'Momento del fallecimiento', 'valor_activador' => 'Puerperio'],
                ['clave' => 'o95_edad_gestacional_al_momento_del_fallecimiento', 'etiqueta' => 'Edad gestacional (Semanas)', 'tipo' => 'NUMERO'],
                ['clave' => 'o95_edad_gestacional_desconocida', 'etiqueta' => 'Edad gestacional desconocida', 'tipo' => 'BOOLEANO'],
                ['clave' => 'o95_fecha_de_fallecimiento', 'etiqueta' => 'Fecha de fallecimiento', 'tipo' => 'FECHA'],
                ['clave' => 'o95_hora_de_fallecimiento', 'etiqueta' => 'Hora de fallecimiento', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_lugar_del_fallecimiento', 'etiqueta' => '¿Dónde ocurrió el fallecimiento?', 'tipo' => 'SELECT', 'opciones' => ['Establecimiento de salud', 'Domicilio', 'Trayecto', 'Otro']],
                ['clave' => 'o95_tipo_eess_fallecimiento', 'etiqueta' => 'Tipo de establecimiento de salud', 'tipo' => 'SELECT', 'opciones' => ['EESS Sanidad FFAA/PNP', 'EESS MINSA / IGSS', 'EESS EsSalud', 'EESS Privado']],
                ['clave' => 'o95_eess_fallecimiento_id', 'etiqueta' => 'Establecimiento Sanidad PNP', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_nombre_eess_fallecimiento', 'etiqueta' => 'Nombre del establecimiento', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_fallecimiento_dep_id', 'etiqueta' => 'Departamento (Fallecimiento)', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_fallecimiento_prov_id', 'etiqueta' => 'Provincia (Fallecimiento)', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_fallecimiento_dist_id', 'etiqueta' => 'Distrito (Fallecimiento)', 'tipo' => 'TEXTO'],
                ['clave' => 'o95_permanencia_dias', 'etiqueta' => 'Permanencia (Días)', 'tipo' => 'NUMERO'],
                ['clave' => 'o95_permanencia_horas', 'etiqueta' => 'Permanencia (Horas)', 'tipo' => 'NUMERO'],
                ['clave' => 'o95_permanencia_minutos', 'etiqueta' => 'Permanencia (Minutos)', 'tipo' => 'NUMERO'],
                ['clave' => 'o95_lugar_fallecimiento_otro_especificar', 'etiqueta' => 'Especificar lugar u observaciones', 'tipo' => 'TEXTO'],
            ];
        }
        $nuevasSecciones[] = $sec;
    }
    $manifest['fichas']['O95']['secciones'] = $nuevasSecciones;
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto guardado correctamente.\n";
