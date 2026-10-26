<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Registros PNP (2026-10-21): formatos del Área de Epidemiología de la
// Dirección de Salud Policial (docs/fuentes/FICHAS NOTIFICACION EPIDEMIOLOGIA 2026.xlsx).
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
//   - caso.descanso1_inicio/_fin, descanso2_inicio/_fin y fecha_alta:
//     "Observaciones adicionales (PNP)" del registro individual (descanso
//     médico del efectivo y alta). Las fichas que no las piden (muerte
//     materna, fetal y neonatal...) declaran nucleo_omitidos
//     "observaciones_pnp".
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
        'descanso1_inicio' => 'ADD COLUMN descanso1_inicio date DEFAULT NULL AFTER fallecido',
        'descanso1_fin'    => 'ADD COLUMN descanso1_fin date DEFAULT NULL AFTER descanso1_inicio',
        'descanso2_inicio' => 'ADD COLUMN descanso2_inicio date DEFAULT NULL AFTER descanso1_fin',
        'descanso2_fin'    => 'ADD COLUMN descanso2_fin date DEFAULT NULL AFTER descanso2_inicio',
        'fecha_alta'       => 'ADD COLUMN fecha_alta date DEFAULT NULL AFTER descanso2_fin',
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
    ['A51', 'A51', 'Sífilis (no materna ni congénita)', 'SEMANAL', null],
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

// "Observaciones adicionales (PNP)": las fichas que no la piden la declaran en
// nucleo_omitidos (manifiesto_fichas.json). Se agrega acá a la columna para no
// recargar esas fichas con cargar_fichas.php (O95, P96, P35.0 y Z21 tienen
// migraciones propias que corren después del cargador).
$omitir = $pdo->prepare(
    "UPDATE enfermedad SET nucleo_omitidos = JSON_ARRAY_APPEND(COALESCE(nucleo_omitidos, JSON_ARRAY()), '$', 'observaciones_pnp')
      WHERE cie10 = ? AND (nucleo_omitidos IS NULL OR NOT JSON_CONTAINS(nucleo_omitidos, '\"observaciones_pnp\"'))"
);
foreach (['O95', 'P96', 'A33', 'P35.0', 'Z21', 'A50', 'REG-FEB'] as $cie10) {
    $omitir->execute([$cie10]);
    echo "nucleo_omitidos {$cie10}: " . ($omitir->rowCount() ? 'observaciones_pnp agregada' : 'ya la tenía') . ".\n";
}

// Notificación colectiva (consolidado semanal de EDA, cólera, IRA, neumonía,
// SOB y asma), 2026-10-21: el área registra cada caso (registro de otras
// enfermedades; el cólera, en la ficha A00) y el consolidado los cuenta.
// cie10_vigilancia.consolidado dice en qué grupo cuenta cada código. Los
// grupos (qué códigos son "diarrea aguda", "IRA no neumonía"...) se pueden
// ajustar acá sin tocar el código del reporte.
$columnas = array_column($pdo->query('SHOW COLUMNS FROM cie10_vigilancia')->fetchAll(), 'Type', 'Field');
if (!str_contains($columnas['notificacion'], 'COLECTIVA')) {
    $pdo->exec("ALTER TABLE cie10_vigilancia MODIFY notificacion enum('INMEDIATA','SEMANAL','COLECTIVA') COLLATE utf8mb4_unicode_ci NOT NULL");
}
if (!isset($columnas['consolidado'])) {
    $pdo->exec('ALTER TABLE cie10_vigilancia ADD COLUMN consolidado varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER enfermedad_cie10');
    echo "cie10_vigilancia: columna consolidado agregada.\n";
}
$pdo->exec("UPDATE cie10_vigilancia SET consolidado = 'COLERA' WHERE codigo = 'A00'");
$colectivas = [
    ['A09', 'A01,A02,A04,A05,A07,A08,A09', 'Diarrea aguda (enfermedad diarreica aguda)', 'EDA'],
    ['A03', 'A03,A060', 'Disentería', 'DISENTERIA'],
    ['J00-J06', 'J00,J01,J02,J03,J04,J05,J06,H65,H66', 'Infección respiratoria aguda (no neumonía)', 'IRA'],
    ['J12-J18', 'J12,J13,J14,J15,J16,J17,J18', 'Neumonía', 'NEUMONIA'],
    ['J21', 'J21', 'Síndrome obstructivo bronquial (SOB)', 'SOBA'],
    ['J45-J46', 'J45,J46', 'Asma', 'ASMA'],
];
$orden = (int) $pdo->query('SELECT MAX(orden) FROM cie10_vigilancia')->fetchColumn();
$insertarColectiva = $pdo->prepare(
    "INSERT INTO cie10_vigilancia (codigo, prefijos, descripcion, notificacion, enfermedad_cie10, consolidado, orden) VALUES (?, ?, ?, 'COLECTIVA', NULL, ?, ?)
     ON DUPLICATE KEY UPDATE prefijos = VALUES(prefijos), descripcion = VALUES(descripcion), notificacion = 'COLECTIVA', consolidado = VALUES(consolidado)"
);
foreach ($colectivas as $i => [$codigo, $prefijos, $descripcion, $grupo]) {
    $insertarColectiva->execute([$codigo, $prefijos, $descripcion, $grupo, $orden + $i + 1]);
}
echo 'cie10_vigilancia: ' . count($colectivas) . " grupos de notificación colectiva.\n";

// Fichas y registros que se parecen (2026-10-21, pedido del usuario: "¿cuál
// elijo para reportar el caso?"). La ventana "Cambiar enfermedad" los muestra
// juntos, cada uno con su criterio de uso, y el registro avisa con el
// criterio de la ficha:
//   - enfermedad.criterio: cuándo se usa la ficha MINSA.
//   - cie10_vigilancia.par_cie10 / indicacion: la ficha con la que se puede
//     confundir el código y cuándo se usa el registro.
// Borrador: los criterios de cólera y EDA grave los valida el usuario con
// las definiciones de caso del MINSA.
if (!array_key_exists('criterio', array_column($pdo->query('SHOW COLUMNS FROM enfermedad')->fetchAll(), 'Type', 'Field'))) {
    $pdo->exec('ALTER TABLE enfermedad ADD COLUMN criterio varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER palabras_clave');
    echo "enfermedad: columna criterio agregada.\n";
}
$columnasVig = array_column($pdo->query('SHOW COLUMNS FROM cie10_vigilancia')->fetchAll(), 'Type', 'Field');
if (!isset($columnasVig['par_cie10'])) {
    $pdo->exec('ALTER TABLE cie10_vigilancia ADD COLUMN par_cie10 varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER consolidado,
                ADD COLUMN indicacion varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER par_cie10');
    echo "cie10_vigilancia: columnas par_cie10 e indicacion agregadas.\n";
}
$criterios = [
    'A00'   => 'Sospecha de cólera o diarrea con deshidratación grave',
    'B01'   => 'Varicela con complicaciones (neumonía, encefalitis, meningitis u otras)',
    'B26'   => 'Parotiditis con complicaciones (orquitis, meningitis, encefalitis, pancreatitis u otras)',
    'A50'   => 'Sífilis en gestante o sífilis congénita',
    'Y59.0' => 'Evento supuestamente atribuido a la vacunación o inmunización',
];
$ponerCriterio = $pdo->prepare('UPDATE enfermedad SET criterio = ? WHERE cie10 = ?');
foreach ($criterios as $cie10 => $criterio) {
    $ponerCriterio->execute([$criterio, $cie10]);
}
// El nombre corto de A50 era "Sífilis", igual que el registro de la sífilis del adulto (A51).
$pdo->exec("UPDATE enfermedad SET nombre_corto = 'Sífilis materna y congénita' WHERE cie10 = 'A50' AND nombre_corto = 'Sífilis'");

$indicaciones = [
    'A09'     => ['A00', 'Diarrea sin sospecha de cólera ni deshidratación grave; se cuenta en el consolidado semanal'],
    'A03'     => ['A00', 'Diarrea con sangre; se cuenta en el consolidado semanal'],
    'B01.9'   => ['B01', 'Varicela sin complicaciones'],
    'B26.9'   => ['B26', 'Parotiditis sin complicaciones'],
    'A51'     => ['A50', 'Sífilis en adulto o adolescente que no es gestante'],
    'GVI'     => ['Y59.0', 'Gestante vacunada sin saber que estaba embarazada, tenga o no algún evento'],
    'J00-J06' => [null, 'Resfrío, faringitis, amigdalitis, otitis, sinusitis o laringitis; se cuenta en el consolidado semanal'],
    'J12-J18' => [null, 'Se cuenta en el consolidado semanal (menores de 5 años: indicar si es grave)'],
    'J21'     => [null, 'Bronquiolitis o síndrome obstructivo bronquial; se cuenta en el consolidado semanal'],
    'J45-J46' => [null, 'Se cuenta en el consolidado semanal'],
];
$ponerIndicacion = $pdo->prepare('UPDATE cie10_vigilancia SET par_cie10 = ?, indicacion = ? WHERE codigo = ?');
foreach ($indicaciones as $codigo => [$par, $indicacion]) {
    $ponerIndicacion->execute([$par, $indicacion, $codigo]);
}
$pdo->exec("UPDATE cie10_vigilancia SET descripcion = 'Sífilis (no materna ni congénita)' WHERE codigo = 'A51'");
echo 'Criterios de uso: ' . count($criterios) . ' fichas y ' . count($indicaciones) . " códigos del registro.\n";
