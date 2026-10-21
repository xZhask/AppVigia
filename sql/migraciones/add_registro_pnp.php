<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Registros PNP (2026-10-21): formatos del Área de Epidemiología de la
// Dirección de Salud Policial ("FICHAS NOTIFICACION EPIDEMIOLOGIA 2026.xlsx").
//   - enfermedad.modalidad: FICHA (las 24 fichas MINSA) o REGISTRO (registro
//     ligero del formato PNP). Los registros no suman en los indicadores de
//     casos (Panel, Consolidado, badge de fichas abiertas).
//   - caso.cie10_dx: el diagnóstico propio de un registro (en las fichas sale
//     de la enfermedad). Lo escribe el campo que declara
//     nucleo_ajustes.cie10_dx_desde.
//   - caso.caso_origen_id: la ficha MINSA abierta desde un registro ("Pasar a
//     ficha MINSA") apunta a él.
//   - cie10_vigilancia: hoja "LISTADO DE ENF SUJ A VIG" del Excel, con la
//     ficha MINSA que cubre cada código (enfermedad_cie10) o NULL si se
//     registra en el registro ligero.
//   - Dos enfermedades nuevas: REG-PNP (otras enfermedades sujetas a
//     vigilancia) y REG-FEB (vigilancia de febriles). Sus secciones las carga
//     cargar_fichas.php.
$agregar = [
    'enfermedad' => [
        'modalidad' => "ADD COLUMN modalidad enum('FICHA','REGISTRO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FICHA' AFTER tipo_notif",
    ],
    'caso' => [
        'cie10_dx'       => 'ADD COLUMN cie10_dx varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER enfermedad_id',
        'caso_origen_id' => 'ADD COLUMN caso_origen_id int DEFAULT NULL AFTER caso_vinculado_id',
    ],
];
foreach ($agregar as $tabla => $columnas) {
    $existentes = array_column($pdo->query("SHOW COLUMNS FROM $tabla")->fetchAll(), 'Field');
    $faltan = array_diff_key($columnas, array_flip($existentes));
    if ($faltan) {
        $pdo->exec("ALTER TABLE $tabla " . implode(', ', $faltan));
        echo "$tabla: columnas agregadas: " . implode(', ', array_keys($faltan)) . ".\n";
    } else {
        echo "$tabla: columnas ya existen.\n";
    }
}

$fk = $pdo->query("SELECT COUNT(*) FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME = 'fk_caso_origen'")->fetchColumn();
if (!$fk) {
    $pdo->exec('ALTER TABLE caso ADD KEY ix_caso_cie10_dx (cie10_dx), ADD CONSTRAINT fk_caso_origen FOREIGN KEY (caso_origen_id) REFERENCES caso (id) ON DELETE SET NULL');
    echo "caso: índice de cie10_dx y fk_caso_origen agregados.\n";
}

$pdo->exec(
    "CREATE TABLE IF NOT EXISTS cie10_vigilancia (
        id int NOT NULL AUTO_INCREMENT,
        codigo varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
        prefijos varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
        descripcion varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
        notificacion enum('INMEDIATA','SEMANAL') COLLATE utf8mb4_unicode_ci NOT NULL,
        enfermedad_cie10 varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        orden smallint NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_cie10_vigilancia_codigo (codigo)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

// [código como lo escribe el Excel, prefijos (código sin puntos; varios
// separados por coma), descripción, notificación, ficha MINSA que lo cubre].
// No se cargan "Violencia" ni "Cáncer" (sin código en la hoja; violencia tiene
// la ficha Y07), "P00-P96 Q00-Q99" (es la ficha P96, que se elige como
// ficha), el P35.0 repetido ni la notificación colectiva (EDA, IRA,
// neumonías, SOB/asma), que es un consolidado aparte. B08.4 viene como
// "#REF!" en la hoja: va con la descripción del catálogo CIE-10.
$filas = [
    ['A00', 'A00', 'Cólera (brotes epidémicos)', 'INMEDIATA', 'A00'],
    ['A20.0', 'A200', 'Peste bubónica', 'INMEDIATA', null],
    ['A20.1', 'A201', 'Peste cutánea', 'INMEDIATA', null],
    ['A20.2', 'A202', 'Peste neumónica', 'INMEDIATA', null],
    ['A20.3', 'A203', 'Meningitis por peste', 'INMEDIATA', null],
    ['A20.7', 'A207', 'Peste septicémica', 'INMEDIATA', null],
    ['A20.8', 'A208', 'Otras formas de peste', 'INMEDIATA', null],
    ['A22', 'A22', 'Carbunco (ántrax)', 'INMEDIATA', null],
    ['A33', 'A33', 'Tétanos neonatal', 'INMEDIATA', 'A33'],
    ['A35', 'A35', 'Tétanos', 'INMEDIATA', 'A35'],
    ['A36', 'A36', 'Difteria', 'INMEDIATA', 'A36'],
    ['A37', 'A37', 'Tos ferina', 'INMEDIATA', 'A37.0'],
    ['A39.0', 'A390', 'Meningitis meningocócica', 'INMEDIATA', null],
    ['A75.0', 'A750', 'Tifus exantemático', 'INMEDIATA', null],
    ['A80.1-A80.2', 'A801,A802', 'Poliomielitis por poliovirus salvaje', 'INMEDIATA', 'A80'],
    ['A80.3', 'A803', 'Poliomielitis aguda (parálisis flácida aguda)', 'INMEDIATA', 'A80'],
    ['A82.0', 'A820', 'Rabia humana silvestre', 'INMEDIATA', null],
    ['A82.1', 'A821', 'Rabia humana urbana', 'INMEDIATA', null],
    ['A92.5', 'A925', 'Fiebre de chikungunya grave', 'INMEDIATA', 'A97'],
    ['A95.0', 'A950', 'Fiebre amarilla selvática', 'INMEDIATA', 'A95'],
    ['A97.2', 'A972', 'Dengue grave', 'INMEDIATA', 'A97'],
    ['B03', 'B03', 'Viruela', 'INMEDIATA', null],
    ['B05', 'B05', 'Sarampión', 'INMEDIATA', 'B05'],
    ['B06', 'B06', 'Rubéola', 'INMEDIATA', 'B05'],
    ['B26.0', 'B260', 'Parotiditis con orquitis', 'INMEDIATA', 'B26'],
    ['B26.1', 'B261', 'Parotiditis infecciosa con meningitis', 'INMEDIATA', 'B26'],
    ['B26.2', 'B262', 'Parotiditis infecciosa con encefalitis, meningoencefalitis', 'INMEDIATA', 'B26'],
    ['B26.3', 'B263', 'Parotiditis infecciosa con pancreatitis', 'INMEDIATA', 'B26'],
    ['B26.8', 'B268', 'Parotiditis infecciosa con otras complicaciones', 'INMEDIATA', 'B26'],
    ['B26.9', 'B269', 'Parotiditis infecciosa sin complicaciones', 'INMEDIATA', null],
    ['GVI', 'GVI', 'Gestante vacunada inadvertidamente', 'INMEDIATA', null],
    ['O95', 'O95', 'Muerte materna directa', 'INMEDIATA', 'O95'],
    ['O96', 'O96', 'Muerte materna indirecta', 'INMEDIATA', 'O95'],
    ['O97', 'O97', 'Muerte materna incidental', 'INMEDIATA', 'O95'],
    ['P35.0', 'P350', 'Síndrome de rubéola congénita', 'INMEDIATA', 'P35.0'],
    ['T88.1', 'T881', 'ESAVI (eventos supuestamente atribuidos a la vacunación o inmunización)', 'INMEDIATA', 'Y59.0'],
    ['U04.9', 'U049', 'Síndrome respiratorio agudo severo', 'INMEDIATA', null],
    ['B04', 'B04', 'Viruela de los monos', 'INMEDIATA', 'B04X'],
    ['A15', 'A15', 'TBC pulmonar con confirmación bacteriológica', 'SEMANAL', null],
    ['A16', 'A16', 'TBC pulmonar sin confirmación bacteriológica', 'SEMANAL', null],
    ['A17', 'A17', 'Tuberculosis meníngea', 'SEMANAL', null],
    ['A17.0', 'A170', 'Meningitis tuberculosa en menores de 5 años', 'SEMANAL', null],
    ['A18', 'A18', 'TBC extrapulmonar', 'SEMANAL', null],
    ['A27', 'A27', 'Leptospirosis', 'SEMANAL', null],
    ['A44.0', 'A440', 'Enfermedad de Carrión aguda', 'SEMANAL', 'A44'],
    ['A44.1', 'A441', 'Enfermedad de Carrión eruptiva', 'SEMANAL', 'A44'],
    ['A50', 'A50', 'Sífilis congénita', 'SEMANAL', 'A50'],
    ['A51', 'A51', 'Sífilis', 'SEMANAL', null],
    ['A54', 'A54', 'Gonorrea', 'SEMANAL', null],
    ['A92.0', 'A920', 'Fiebre de chikungunya', 'SEMANAL', 'A97'],
    ['A97.0', 'A970', 'Dengue sin señales de alarma', 'SEMANAL', 'A97'],
    ['A97.1', 'A971', 'Dengue con señales de alarma', 'SEMANAL', 'A97'],
    ['B01.0', 'B010', 'Meningitis debida a varicela', 'SEMANAL', 'B01'],
    ['B01.1', 'B011', 'Encefalitis debida a varicela', 'SEMANAL', 'B01'],
    ['B01.2', 'B012', 'Neumonía debida a varicela', 'SEMANAL', 'B01'],
    ['B01.8', 'B018', 'Varicela con otras complicaciones', 'SEMANAL', 'B01'],
    ['B01.9', 'B019', 'Varicela sin otras complicaciones', 'SEMANAL', null],
    ['B16', 'B16', 'Hepatitis B', 'SEMANAL', null],
    ['B50', 'B50', 'Malaria por P. falciparum', 'SEMANAL', null],
    ['B51', 'B51', 'Malaria por P. vivax', 'SEMANAL', null],
    ['B52', 'B52', 'Malaria por P. malariae', 'SEMANAL', null],
    ['B55.1', 'B551', 'Leishmaniasis cutánea', 'SEMANAL', 'B55'],
    ['B55.2', 'B552', 'Leishmaniasis mucocutánea', 'SEMANAL', 'B55'],
    ['B57', 'B57', 'Enfermedad de Chagas (tripanosomiasis)', 'SEMANAL', 'B57'],
    ['E10', 'E10', 'Diabetes mellitus insulinodependiente (tipo 1)', 'SEMANAL', null],
    ['E11', 'E11', 'Diabetes mellitus no insulinodependiente (tipo 2)', 'SEMANAL', null],
    ['E13', 'E13', 'Otras diabetes mellitus especificadas', 'SEMANAL', null],
    ['E14', 'E14', 'Diabetes mellitus no especificada', 'SEMANAL', null],
    ['G61.0', 'G610', 'Síndrome de Guillain-Barré', 'SEMANAL', null],
    ['J10', 'J10', 'Influenza H1N1', 'SEMANAL', null],
    ['O24', 'O24', 'Diabetes mellitus en el embarazo', 'SEMANAL', null],
    ['O98.1', 'O981', 'Sífilis materna', 'SEMANAL', 'A50'],
    ['O98.3', 'O983', 'Gestante con VIH', 'SEMANAL', 'Z21'],
    ['Q02', 'Q02', 'Microcefalia', 'SEMANAL', null],
    ['T56', 'T56', 'Efecto tóxico de metales', 'SEMANAL', null],
    ['T56.0', 'T560', 'Plomo y sus compuestos', 'SEMANAL', null],
    ['T56.1', 'T561', 'Mercurio y sus compuestos', 'SEMANAL', null],
    ['T56.2', 'T562', 'Cromo y sus compuestos', 'SEMANAL', null],
    ['T56.3', 'T563', 'Cadmio y sus compuestos', 'SEMANAL', null],
    ['T56.4', 'T564', 'Cobre y sus compuestos', 'SEMANAL', null],
    ['T56.5', 'T565', 'Zinc y sus compuestos', 'SEMANAL', null],
    ['T56.6', 'T566', 'Estaño y sus compuestos', 'SEMANAL', null],
    ['T56.7', 'T567', 'Berilio y sus compuestos', 'SEMANAL', null],
    ['T56.8', 'T568', 'Otros metales', 'SEMANAL', null],
    ['T56.9', 'T569', 'Metal no especificado', 'SEMANAL', null],
    ['T57', 'T57', 'Efecto tóxico de otras sustancias inorgánicas', 'SEMANAL', null],
    ['T57.0', 'T570', 'Arsénico y sus compuestos', 'SEMANAL', null],
    ['T60.0', 'T600', 'Insecticidas organofosforados y carbamatos', 'SEMANAL', null],
    ['T60.1', 'T601', 'Insecticidas halogenados', 'SEMANAL', null],
    ['T60.2', 'T602', 'Otros insecticidas', 'SEMANAL', null],
    ['T60.3', 'T603', 'Herbicidas y fungicidas', 'SEMANAL', null],
    ['T60.4', 'T604', 'Rodenticidas', 'SEMANAL', null],
    ['T60.8', 'T608', 'Otros plaguicidas', 'SEMANAL', null],
    ['T60.9', 'T609', 'Plaguicida no especificado', 'SEMANAL', null],
    ['U06.9', 'U069', 'Zika', 'SEMANAL', 'A97'],
    ['U06.AG', 'U06AG', 'Zika asintomático en gestantes', 'SEMANAL', 'A97'],
    ['U06.AT', 'U06AT', 'Zika asintomático', 'SEMANAL', 'A97'],
    ['U06.G', 'U06G', 'Zika en gestantes', 'SEMANAL', 'A97'],
    ['U20.2', 'U202', 'TBC multidrogorresistente (MDR)', 'SEMANAL', null],
    ['U20.3', 'U203', 'TBC monorresistente', 'SEMANAL', null],
    ['U20.4', 'U204', 'TBC polirresistente', 'SEMANAL', null],
    ['U20.5', 'U205', 'TBC extensamente resistente', 'SEMANAL', null],
    ['U32.4', 'U324', 'TBC abandono recuperado', 'SEMANAL', null],
    ['U32.6', 'U326', 'TBC recaída', 'SEMANAL', null],
    ['X20', 'X20', 'Ofidismo', 'SEMANAL', null],
    ['X21', 'X21', 'Loxocelismo', 'SEMANAL', null],
    ['Z20.6', 'Z206', 'Niño expuesto al VIH', 'SEMANAL', 'Z21'],
    ['B08.4', 'B084', 'Estomatitis vesicular enteroviral con exantema', 'SEMANAL', null],
    // No está en la hoja, pero el VIH tiene su propio registro individual
    // (hoja "INDIVIDUAL VIH_SIDA") y su ficha B24.
    ['B20-B24', 'B20,B21,B22,B23,B24', 'Enfermedad por VIH / SIDA', 'SEMANAL', 'B24'],
];
$insertar = $pdo->prepare(
    'INSERT INTO cie10_vigilancia (codigo, prefijos, descripcion, notificacion, enfermedad_cie10, orden) VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE prefijos = VALUES(prefijos), descripcion = VALUES(descripcion), notificacion = VALUES(notificacion),
                             enfermedad_cie10 = VALUES(enfermedad_cie10), orden = VALUES(orden)'
);
foreach ($filas as $orden => [$codigo, $prefijos, $descripcion, $notificacion, $enfermedadCie10]) {
    $insertar->execute([$codigo, $prefijos, $descripcion, $notificacion, $enfermedadCie10, $orden + 1]);
}
echo 'cie10_vigilancia: ' . count($filas) . " códigos cargados.\n";

$enfermedades = [
    ['REG-PNP', 'Otras enfermedades sujetas a vigilancia (registro PNP)', 'Otras enfermedades', 'tbc tuberculosis malaria leptospirosis hepatitis ofidismo loxocelismo diabetes plaguicidas metales gonorrea influenza', 'SEMANAL'],
    ['REG-FEB', 'Registro diario de febriles', 'Febriles', 'fiebre febril temperatura', 'SEMANAL'],
];
foreach ($enfermedades as [$cie10, $nombre, $corto, $claves, $notif]) {
    $existe = $pdo->prepare('SELECT id FROM enfermedad WHERE cie10 = ?');
    $existe->execute([$cie10]);
    if ($existe->fetchColumn()) {
        $pdo->prepare("UPDATE enfermedad SET modalidad = 'REGISTRO' WHERE cie10 = ?")->execute([$cie10]);
        echo "enfermedad {$cie10}: ya existe.\n";
        continue;
    }
    $pdo->prepare(
        "INSERT INTO enfermedad (nombre, nombre_corto, palabras_clave, cie10, tipo_notif, modalidad, grupo, familia, activo)
         VALUES (?, ?, ?, ?, ?, 'REGISTRO', 'R', 'Registros PNP', 1)"
    )->execute([$nombre, $corto, $claves, $cie10, $notif]);
    echo "enfermedad {$cie10}: creada.\n";
}
