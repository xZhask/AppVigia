<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Obtener id seccion 5 de O95
$stmtS = $pdo->query("SELECT id FROM seccion_def WHERE enfermedad_id = (SELECT id FROM enfermedad WHERE cie10 = 'O95') AND orden = 5");
$seccionId = $stmtS->fetchColumn();

// 1. o95_ocupacion (ID 16112)
$stmt1 = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_ocupacion'");
$campo1 = $stmt1->fetch();

if (!$campo1) {
    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (?, 'o95_ocupacion', 'Ocupación', 'TEXTO', 0, 12)");
    $stmtIns->execute([$seccionId]);
    $idOcup = $pdo->lastInsertId();
} else {
    $idOcup = $campo1['id'];
}

// 2. o95_idioma_otra (ID 16113)
$stmt2 = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_idioma_otra'");
$campo2 = $stmt2->fetch();
if (!$campo2) {
    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, depende_de, valor_activador) VALUES (?, 'o95_idioma_otra', 'Especificar otro idioma', 'TEXTO', 0, 13, 14316, 'OTRA')");
    $stmtIns->execute([$seccionId]);
    $idIdiomaOtra = $pdo->lastInsertId();
} else {
    $idIdiomaOtra = $campo2['id'];
}

// 3. o95_tipo_de_seguro_otro (ID 16114)
$stmt3 = $pdo->query("SELECT * FROM campo_def WHERE clave = 'o95_tipo_de_seguro_otro'");
$campo3 = $stmt3->fetch();
if (!$campo3) {
    $stmtIns = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden, depende_de, valor_activador) VALUES (?, 'o95_tipo_de_seguro_otro', 'Especificar otro tipo de seguro', 'TEXTO', 0, 14, 14319, 'OTROS')");
    $stmtIns->execute([$seccionId]);
    $idSeguroOtro = $pdo->lastInsertId();
} else {
    $idSeguroOtro = $campo3['id'];
}

echo "IDs de campos adicionales: o95_ocupacion={$idOcup}, o95_idioma_otra={$idIdiomaOtra}, o95_tipo_de_seguro_otro={$idSeguroOtro}\n";

// Sincronizar manifiesto_fichas.json
$manifestPath = __DIR__ . '/../manifiesto_fichas.json';
$manifest = json_decode(file_get_contents($manifestPath), true);

$itemsIdioma = ['Español', 'Quechua', 'Aymara', 'Otra'];
$itemsEdu = ['Ninguno', 'Primaria incompleta', 'Primaria completa', 'Secundaria incompleta', 'Secundaria completa', 'Superior universitaria', 'Superior técnica', 'Desconocido'];
$itemsCiv = ['Soltera', 'Casada', 'Conviviente', 'Divorciada', 'Separada', 'Viuda', 'Desconocido'];
$itemsSeguro = ['SIS', 'EsSalud', 'Privado', 'Otros', 'No tiene seguro'];
$itemsGrupo = ['Mestizo', 'Andino', 'Indígena amazónico', 'Afroperuano', 'Asiático descendiente', 'Otro'];
$itemsPueblo = [
    'Quechua', 'Aymara', 'Jaqaru', 'Uro',
    'Asháninka', 'Awajún', 'Shipibo-Konibo', 'Yánesha', 'Kukama Kukamiria', 'Achuar', 'Bora', 'Matsés', 'Ese Eja', 'Harakbut',
    'Afroperuano', 'No aplica', 'Chino-peruano', 'Japonés-peruano', 'Otro'
];

if (isset($manifest['fichas']['O95'])) {
    foreach ($manifest['fichas']['O95']['secciones'] as &$sec) {
        if (($sec['orden'] ?? 0) == 5 || stripos($sec['nombre'] ?? '', 'Datos básicos adicionales') !== false) {
            $sec['campos'] = [
                [
                    'clave' => 'o95_grupo_etnico',
                    'etiqueta' => 'Grupo étnico',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsGrupo
                ],
                [
                    'clave' => 'o95_etnia_pueblo_etnico',
                    'etiqueta' => 'Etnia / Pueblo étnico',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsPueblo
                ],
                [
                    'clave' => 'o95_idioma',
                    'etiqueta' => 'Idioma',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsIdioma
                ],
                [
                    'clave' => 'o95_idioma_otra',
                    'etiqueta' => 'Especificar otro idioma',
                    'tipo' => 'TEXTO',
                    'obligatorio' => 0,
                    'depende_de' => 'Idioma',
                    'valor_activador' => 'OTRA'
                ],
                [
                    'clave' => 'o95_nivel_educativo',
                    'etiqueta' => 'Nivel educativo',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsEdu
                ],
                [
                    'clave' => 'o95_estado_civil',
                    'etiqueta' => 'Estado civil',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsCiv
                ],
                [
                    'clave' => 'o95_ocupacion',
                    'etiqueta' => 'Ocupación',
                    'tipo' => 'TEXTO',
                    'obligatorio' => 0
                ],
                [
                    'clave' => 'o95_tipo_de_seguro',
                    'etiqueta' => 'Tipo de seguro',
                    'tipo' => 'SELECT',
                    'obligatorio' => 0,
                    'opciones' => $itemsSeguro
                ],
                [
                    'clave' => 'o95_tipo_de_seguro_otro',
                    'etiqueta' => 'Especificar otro tipo de seguro',
                    'tipo' => 'TEXTO',
                    'obligatorio' => 0,
                    'depende_de' => 'Tipo de seguro',
                    'valor_activador' => 'OTROS'
                ]
            ];
        }
    }
}

file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Manifiesto actualizado para Seccion 5 O95.\n";
