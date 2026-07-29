<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id de seccion 1 de O95 (Datos del fallecimiento - Anexo 1)
$stmtS = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 1");
$secId = $stmtS->fetchColumn();

echo "Seccion 1 ID para O95: {$secId}\n";

$camposReq = [
    ['clave' => 'o95_momento_del_fallecimiento', 'etiqueta' => 'Momento del fallecimiento', 'tipo' => 'SELECT', 'orden' => 10],
    ['clave' => 'o95_fase_del_puerperio_en_que_fallecio', 'etiqueta' => 'Fase del puerperio', 'tipo' => 'SELECT', 'orden' => 11],
    ['clave' => 'o95_edad_gestacional_al_momento_del_fallecimiento', 'etiqueta' => 'Edad gestacional (Semanas)', 'tipo' => 'NUMERO', 'orden' => 12],
    ['clave' => 'o95_edad_gestacional_desconocida', 'etiqueta' => 'Edad gestacional desconocida', 'tipo' => 'BOOLEANO', 'orden' => 13],
    ['clave' => 'o95_fecha_de_fallecimiento', 'etiqueta' => 'Fecha de fallecimiento', 'tipo' => 'FECHA', 'orden' => 14],
    ['clave' => 'o95_hora_de_fallecimiento', 'etiqueta' => 'Hora de fallecimiento', 'tipo' => 'TEXTO', 'orden' => 15],
    ['clave' => 'o95_lugar_del_fallecimiento', 'etiqueta' => '¿Dónde ocurrió el fallecimiento?', 'tipo' => 'SELECT', 'orden' => 16],
    ['clave' => 'o95_tipo_eess_fallecimiento', 'etiqueta' => 'Tipo de establecimiento de salud', 'tipo' => 'SELECT', 'orden' => 17],
    ['clave' => 'o95_eess_fallecimiento_id', 'etiqueta' => 'Establecimiento Sanidad PNP', 'tipo' => 'TEXTO', 'orden' => 18],
    ['clave' => 'o95_nombre_eess_fallecimiento', 'etiqueta' => 'Nombre del establecimiento', 'tipo' => 'TEXTO', 'orden' => 19],
    ['clave' => 'o95_fallecimiento_dep_id', 'etiqueta' => 'Departamento (Fallecimiento)', 'tipo' => 'TEXTO', 'orden' => 20],
    ['clave' => 'o95_fallecimiento_prov_id', 'etiqueta' => 'Provincia (Fallecimiento)', 'tipo' => 'TEXTO', 'orden' => 21],
    ['clave' => 'o95_fallecimiento_dist_id', 'etiqueta' => 'Distrito (Fallecimiento)', 'tipo' => 'TEXTO', 'orden' => 22],
    ['clave' => 'o95_permanencia_dias', 'etiqueta' => 'Permanencia (Días)', 'tipo' => 'NUMERO', 'orden' => 23],
    ['clave' => 'o95_permanencia_horas', 'etiqueta' => 'Permanencia (Horas)', 'tipo' => 'NUMERO', 'orden' => 24],
    ['clave' => 'o95_permanencia_minutos', 'etiqueta' => 'Permanencia (Minutos)', 'tipo' => 'NUMERO', 'orden' => 25],
    ['clave' => 'o95_lugar_fallecimiento_otro_especificar', 'etiqueta' => 'Especificar lugar u observaciones', 'tipo' => 'TEXTO', 'orden' => 26],
];

foreach ($camposReq as $c) {
    $stmtC = $pdo->prepare("SELECT id FROM campo_def WHERE clave = ?");
    $stmtC->execute([$c['clave']]);
    $cid = $stmtC->fetchColumn();
    if (!$cid) {
        $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, ?, ?, ?, 0, ?)");
        $stmtIns->execute([$secId, $c['clave'], $c['etiqueta'], $c['tipo'], $c['orden']]);
        echo "Creado campo '{$c['clave']}' (ID " . $pdo->lastInsertId() . ")\n";
    } else {
        $pdo->prepare("UPDATE campo_def SET seccion_id = ?, etiqueta = ?, tipo = ?, orden = ? WHERE id = ?")->execute([$secId, $c['etiqueta'], $c['tipo'], $c['orden'], $cid]);
        echo "Actualizado campo '{$c['clave']}' (ID {$cid})\n";
    }
}

// Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 1 || stripos($sec['nombre'] ?? '', 'Datos del fallecimiento') !== false) {
            $camposManifest = [];
            foreach ($camposReq as $c) {
                $item = [
                    'clave' => $c['clave'],
                    'etiqueta' => $c['etiqueta'],
                    'tipo' => $c['tipo'],
                    'obligatorio' => 0
                ];
                if ($c['clave'] === 'o95_momento_del_fallecimiento') {
                    $item['opciones'] = ['Embarazo', 'Parto', 'Puerperio', 'Desconocido'];
                } elseif ($c['clave'] === 'o95_fase_del_puerperio_en_que_fallecio') {
                    $item['opciones'] = ['Inmediato', 'Mediato', 'Tardío', 'Desconocido'];
                } elseif ($c['clave'] === 'o95_lugar_del_fallecimiento') {
                    $item['opciones'] = ['Establecimiento de salud', 'Domicilio', 'Trayecto', 'Otro'];
                } elseif ($c['clave'] === 'o95_tipo_eess_fallecimiento') {
                    $item['opciones'] = ['EESS Sanidad FFAA/PNP', 'EESS MINSA / IGSS', 'EESS EsSalud', 'EESS Privado'];
                }
                $camposManifest[] = $item;
            }
            $sec['campos'] = $camposManifest;
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Campos de Datos del fallecimiento sincronizados en DB y manifiesto.\n";
