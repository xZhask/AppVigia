<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Módulo IAAS (2026-10-26): vigilancia de infecciones asociadas a la atención
// de salud según la NTS N° 163-MINSA/2020/CDC, anexos 4 a 11. Ver
// HOJA_DE_RUTA.md §3 punto 6. Se puede correr más de una vez: crea lo que
// falte y carga los catálogos solo si están vacíos.
//
//   iaas_establecimiento  qué vigila cada IPRESS (o "sin hospitalización")
//   iaas_sala             servicios (UPSS del Anexo 2) y sus salas
//   iaas_internacion      paciente vigilado en una sala (Anexo 4)
//   iaas_dispositivo      cada dispositivo de ese paciente, con instalación y retiro
//   iaas_marca            la marca diaria: E = expuesto (✓), T = IAAS en tratamiento (×)
//   iaas_procedimiento    partos y cirugías del día (Anexos 6 y 7)
//   iaas_caso             ficha de investigación (Anexo 5)
//   iaas_cultivo          hasta dos cultivos por caso
//   iaas_antibiograma     perfil de sensibilidad de cada cultivo
//   iaas_exposicion       exposición laboral a sangre (Anexo 8)
//   iaas_personal         personal de salud por año (Anexo 9)
//   iaas_cierre_mes       cierre mensual por sala
//   iaas_microorganismo   Anexo 11
//   iaas_antimicrobiano   antimicrobianos del punto 4.5 del Anexo 5, literal
//   iaas_horas_t          percentil 75 de duración por cirugía (índice de riesgo de ISQ)

$motor = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
$upss = "enum('UCI_ADULTOS','UCI_PEDIATRICA','UCI_NEONATAL','NEONATOLOGIA','GINECO_OBSTETRICIA','CIRUGIA','TRAUMATOLOGIA','MEDICINA')";
$dispositivo = "enum('CVC','CVP','NPT','HD','CUP','VM')";
$procedimiento = "enum('PARTO_VAGINAL','CESAREA','COLE_LAPAROTOMIA','COLE_LAPAROSCOPIA','HERNIOPLASTIA','PROTESIS_CADERA')";

$tablas = [
    'iaas_establecimiento' => "CREATE TABLE iaas_establecimiento (
        establecimiento_id int NOT NULL,
        sin_hospitalizacion tinyint(1) NOT NULL DEFAULT '0',
        actualizado_por int NOT NULL,
        actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (establecimiento_id),
        KEY fk_iaas_est_usuario (actualizado_por),
        CONSTRAINT fk_iaas_est_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
        CONSTRAINT fk_iaas_est_usuario FOREIGN KEY (actualizado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_sala' => "CREATE TABLE iaas_sala (
        id int NOT NULL AUTO_INCREMENT,
        establecimiento_id int NOT NULL,
        upss $upss NOT NULL,
        nombre varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
        activo tinyint(1) NOT NULL DEFAULT '1',
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_iaas_sala (establecimiento_id, upss, nombre),
        CONSTRAINT fk_iaas_sala_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id)
    ) $motor",

    'iaas_internacion' => "CREATE TABLE iaas_internacion (
        id int NOT NULL AUTO_INCREMENT,
        sala_id int NOT NULL,
        persona_id int NOT NULL,
        n_cama varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        historia_clinica varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fecha_ingreso date NOT NULL,
        peso_nacer_g smallint unsigned DEFAULT NULL,
        diagnostico_ingreso varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        diagnostico_vigilado varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fecha_egreso date DEFAULT NULL,
        condicion_egreso enum('V','F','T') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        observaciones varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        creado_por int NOT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY ix_iaas_int_sala (sala_id, fecha_ingreso),
        KEY fk_iaas_int_persona (persona_id),
        KEY fk_iaas_int_usuario (creado_por),
        CONSTRAINT fk_iaas_int_sala FOREIGN KEY (sala_id) REFERENCES iaas_sala (id),
        CONSTRAINT fk_iaas_int_persona FOREIGN KEY (persona_id) REFERENCES persona (id),
        CONSTRAINT fk_iaas_int_usuario FOREIGN KEY (creado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_dispositivo' => "CREATE TABLE iaas_dispositivo (
        id int NOT NULL AUTO_INCREMENT,
        internacion_id int NOT NULL,
        tipo $dispositivo NOT NULL,
        fecha_instalacion date NOT NULL,
        fecha_retiro date DEFAULT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY fk_iaas_disp_int (internacion_id),
        CONSTRAINT fk_iaas_disp_int FOREIGN KEY (internacion_id) REFERENCES iaas_internacion (id) ON DELETE CASCADE
    ) $motor",

    'iaas_marca' => "CREATE TABLE iaas_marca (
        dispositivo_id int NOT NULL,
        fecha date NOT NULL,
        estado enum('E','T') COLLATE utf8mb4_unicode_ci NOT NULL,
        usuario_id int NOT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (dispositivo_id, fecha),
        KEY ix_iaas_marca_fecha (fecha),
        KEY fk_iaas_marca_usuario (usuario_id),
        CONSTRAINT fk_iaas_marca_disp FOREIGN KEY (dispositivo_id) REFERENCES iaas_dispositivo (id) ON DELETE CASCADE,
        CONSTRAINT fk_iaas_marca_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id)
    ) $motor",

    'iaas_procedimiento' => "CREATE TABLE iaas_procedimiento (
        sala_id int NOT NULL,
        fecha date NOT NULL,
        tipo $procedimiento NOT NULL,
        cantidad smallint unsigned NOT NULL DEFAULT '0',
        usuario_id int NOT NULL,
        actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (sala_id, fecha, tipo),
        KEY fk_iaas_proc_usuario (usuario_id),
        CONSTRAINT fk_iaas_proc_sala FOREIGN KEY (sala_id) REFERENCES iaas_sala (id),
        CONSTRAINT fk_iaas_proc_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id)
    ) $motor",

    'iaas_microorganismo' => "CREATE TABLE iaas_microorganismo (
        id int NOT NULL AUTO_INCREMENT,
        familia tinyint NOT NULL,
        nombre varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
        abreviatura varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
        orden smallint NOT NULL DEFAULT '0',
        activo tinyint(1) NOT NULL DEFAULT '1',
        PRIMARY KEY (id)
    ) $motor",

    'iaas_antimicrobiano' => "CREATE TABLE iaas_antimicrobiano (
        id int NOT NULL AUTO_INCREMENT,
        nombre varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
        orden smallint NOT NULL DEFAULT '0',
        activo tinyint(1) NOT NULL DEFAULT '1',
        PRIMARY KEY (id)
    ) $motor",

    'iaas_horas_t' => "CREATE TABLE iaas_horas_t (
        procedimiento $procedimiento NOT NULL,
        horas decimal(4,2) NOT NULL,
        actualizado_por int DEFAULT NULL,
        actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (procedimiento),
        KEY fk_iaas_horas_usuario (actualizado_por),
        CONSTRAINT fk_iaas_horas_usuario FOREIGN KEY (actualizado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_caso' => "CREATE TABLE iaas_caso (
        id int NOT NULL AUTO_INCREMENT,
        establecimiento_id int NOT NULL,
        sala_id int NOT NULL,
        internacion_id int DEFAULT NULL,
        dispositivo_id int DEFAULT NULL,
        persona_id int NOT NULL,
        estado enum('POR_COMPLETAR','COMPLETO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POR_COMPLETAR',
        historia_clinica varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        peso_nacer_g smallint unsigned DEFAULT NULL,
        fecha_ingreso_ipress date DEFAULT NULL,
        fecha_ingreso_servicio date DEFAULT NULL,
        fecha_egreso_servicio date DEFAULT NULL,
        condicion_egreso enum('V','F','T') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        dx1_cie10 varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        dx2_cie10 varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        dx3_cie10 varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        tipo_iaas enum('ITS','ITU','NEU','ISQ_SUP','ISQ_PROF','END') COLLATE utf8mb4_unicode_ci NOT NULL,
        fecha_iaas date NOT NULL,
        criterio varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        its_secundaria tinyint(1) DEFAULT NULL,
        dispositivo_tipo $dispositivo DEFAULT NULL,
        fecha_instalacion date DEFAULT NULL,
        fecha_retiro date DEFAULT NULL,
        procedimiento_tipo enum('PARTO_VAGINAL','CESAREA','COLE_LAPAROTOMIA','COLE_LAPAROSCOPIA','HERNIOPLASTIA','PROTESIS_CADERA','OTRA') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        procedimiento_nombre varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fecha_procedimiento date DEFAULT NULL,
        duracion_min smallint unsigned DEFAULT NULL,
        clase_herida enum('L','LC','C','I') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        asa tinyint DEFAULT NULL,
        implante tinyint(1) DEFAULT NULL,
        fecha_fin_tratamiento date DEFAULT NULL,
        observaciones varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        creado_por int NOT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        actualizado_en timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        anulado_por int DEFAULT NULL,
        anulado_en timestamp NULL DEFAULT NULL,
        motivo_anulacion varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id),
        KEY ix_iaas_caso_est (establecimiento_id, fecha_iaas),
        KEY fk_iaas_caso_sala (sala_id),
        KEY fk_iaas_caso_int (internacion_id),
        KEY fk_iaas_caso_disp (dispositivo_id),
        KEY fk_iaas_caso_persona (persona_id),
        KEY fk_iaas_caso_creado (creado_por),
        KEY fk_iaas_caso_anulado (anulado_por),
        CONSTRAINT fk_iaas_caso_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
        CONSTRAINT fk_iaas_caso_sala FOREIGN KEY (sala_id) REFERENCES iaas_sala (id),
        CONSTRAINT fk_iaas_caso_int FOREIGN KEY (internacion_id) REFERENCES iaas_internacion (id) ON DELETE SET NULL,
        CONSTRAINT fk_iaas_caso_disp FOREIGN KEY (dispositivo_id) REFERENCES iaas_dispositivo (id) ON DELETE SET NULL,
        CONSTRAINT fk_iaas_caso_persona FOREIGN KEY (persona_id) REFERENCES persona (id),
        CONSTRAINT fk_iaas_caso_creado FOREIGN KEY (creado_por) REFERENCES usuario (id),
        CONSTRAINT fk_iaas_caso_anulado FOREIGN KEY (anulado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_cultivo' => "CREATE TABLE iaas_cultivo (
        id int NOT NULL AUTO_INCREMENT,
        caso_id int NOT NULL,
        orden tinyint NOT NULL,
        tipo_cultivo enum('HEMOCULTIVO','UROCULTIVO','SECRECION_BRONQUIAL','SECRECION_HERIDA','OTRO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        tipo_cultivo_otro varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fecha_extraccion date DEFAULT NULL,
        microorganismo_id int DEFAULT NULL,
        microorganismo_otro varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        blee enum('SI','NO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        otro_mecanismo varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_iaas_cultivo (caso_id, orden),
        KEY fk_iaas_cultivo_micro (microorganismo_id),
        CONSTRAINT fk_iaas_cultivo_caso FOREIGN KEY (caso_id) REFERENCES iaas_caso (id) ON DELETE CASCADE,
        CONSTRAINT fk_iaas_cultivo_micro FOREIGN KEY (microorganismo_id) REFERENCES iaas_microorganismo (id)
    ) $motor",

    'iaas_antibiograma' => "CREATE TABLE iaas_antibiograma (
        cultivo_id int NOT NULL,
        antimicrobiano_id int NOT NULL,
        resultado enum('S','I','R') COLLATE utf8mb4_unicode_ci NOT NULL,
        PRIMARY KEY (cultivo_id, antimicrobiano_id),
        KEY fk_iaas_atb_antimicrobiano (antimicrobiano_id),
        CONSTRAINT fk_iaas_atb_cultivo FOREIGN KEY (cultivo_id) REFERENCES iaas_cultivo (id) ON DELETE CASCADE,
        CONSTRAINT fk_iaas_atb_antimicrobiano FOREIGN KEY (antimicrobiano_id) REFERENCES iaas_antimicrobiano (id)
    ) $motor",

    'iaas_exposicion' => "CREATE TABLE iaas_exposicion (
        id int NOT NULL AUTO_INCREMENT,
        establecimiento_id int NOT NULL,
        identificador varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        edad_anios tinyint unsigned DEFAULT NULL,
        sexo enum('M','F') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        profesion enum('MEDICINA','ENFERMERIA','TEC_ENFERMERIA','TEC_LABORATORIO','LIMPIEZA','OTRO') COLLATE utf8mb4_unicode_ci NOT NULL,
        profesion_otra varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        condicion enum('ESTUDIANTE','INTERNO','RESIDENTE','NOMBRADO','CONTRATADO','OTRO') COLLATE utf8mb4_unicode_ci NOT NULL,
        servicio enum('EMERGENCIA','OBSTETRICIA','CENTRO_QUIRURGICO','UCI','CONSULTORIOS','BANCO_SANGRE','LABORATORIO','OTRO') COLLATE utf8mb4_unicode_ci NOT NULL,
        servicio_otro varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        factor enum('PUNZOCORTANTE','SALPICADURA') COLLATE utf8mb4_unicode_ci NOT NULL,
        fecha date NOT NULL,
        hora time DEFAULT NULL,
        objeto varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        profundidad enum('SUPERFICIAL','MODERADA','GRAVE') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fluido enum('SANGRE','ORINA','VOMITO','ESPUTO','OTRO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        fluido_otro varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        epp set('GUANTES','LENTES','MANDIL','MASCARILLA','RESPIRADOR') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        localizacion enum('MANO','OTRA') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        paciente_fuente tinyint(1) DEFAULT NULL,
        procedimiento enum('DESCONOCIDO','INYECCION_IM_SC','OTRA_INYECCION','VIA_IV','SUTURA','TOMA_MUESTRA','OTRO') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        procedimiento_otro varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        profilaxis tinyint(1) DEFAULT NULL,
        profilaxis_medicamento varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        observaciones varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        creado_por int NOT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        anulado_por int DEFAULT NULL,
        anulado_en timestamp NULL DEFAULT NULL,
        PRIMARY KEY (id),
        KEY ix_iaas_expo_est (establecimiento_id, fecha),
        KEY fk_iaas_expo_creado (creado_por),
        KEY fk_iaas_expo_anulado (anulado_por),
        CONSTRAINT fk_iaas_expo_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
        CONSTRAINT fk_iaas_expo_creado FOREIGN KEY (creado_por) REFERENCES usuario (id),
        CONSTRAINT fk_iaas_expo_anulado FOREIGN KEY (anulado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_personal' => "CREATE TABLE iaas_personal (
        establecimiento_id int NOT NULL,
        anio smallint NOT NULL,
        estudiante smallint unsigned NOT NULL DEFAULT '0',
        interno smallint unsigned NOT NULL DEFAULT '0',
        residente smallint unsigned NOT NULL DEFAULT '0',
        nombrado smallint unsigned NOT NULL DEFAULT '0',
        contratado smallint unsigned NOT NULL DEFAULT '0',
        otro smallint unsigned NOT NULL DEFAULT '0',
        actualizado_por int NOT NULL,
        actualizado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (establecimiento_id, anio),
        KEY fk_iaas_personal_usuario (actualizado_por),
        CONSTRAINT fk_iaas_personal_est FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id),
        CONSTRAINT fk_iaas_personal_usuario FOREIGN KEY (actualizado_por) REFERENCES usuario (id)
    ) $motor",

    'iaas_cierre_mes' => "CREATE TABLE iaas_cierre_mes (
        id int NOT NULL AUTO_INCREMENT,
        sala_id int NOT NULL,
        anio smallint NOT NULL,
        mes tinyint NOT NULL,
        usuario_id int NOT NULL,
        creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reabierto_por int DEFAULT NULL,
        reabierto_en timestamp NULL DEFAULT NULL,
        motivo_reapertura varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY uq_iaas_cierre (sala_id, anio, mes),
        KEY fk_iaas_cierre_usuario (usuario_id),
        KEY fk_iaas_cierre_reabierto (reabierto_por),
        CONSTRAINT fk_iaas_cierre_sala FOREIGN KEY (sala_id) REFERENCES iaas_sala (id),
        CONSTRAINT fk_iaas_cierre_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id),
        CONSTRAINT fk_iaas_cierre_reabierto FOREIGN KEY (reabierto_por) REFERENCES usuario (id)
    ) $motor",
];

foreach ($tablas as $nombre => $sql) {
    if ($pdo->query("SHOW TABLES LIKE '$nombre'")->fetchColumn()) {
        echo "Tabla $nombre ya existe.\n";
        continue;
    }
    $pdo->exec($sql);
    echo "Tabla $nombre creada.\n";
}

// Anexo 11, tal cual la norma (incluidas sus erratas: "aeroginosa", "Gran",
// "Aspergillus niger spp"). Familias: 1 cocos gram positivos, 2 bacilos gram
// positivos, 3 enterobacterias, 4 bacilos gram negativos no fermentadores,
// 5 levaduras, 6 hongos filamentosos, 7 otros.
$microorganismos = [
    [1, 'Staphylococcus aureus', 'SAU'], [1, 'Staphylococcus epidermidis', 'SEP'],
    [1, 'Staphylococcus saprophyticus', 'SAP'], [1, 'Staphylococcus coagulasa-negativo', 'SCN'],
    [1, 'Staphylococcus spp', 'STA'], [1, 'Enterococcus faecalis', 'EFA'],
    [1, 'Enterococcus faecium', 'EFM'], [1, 'Enterococcus spp', 'ENT'],
    [1, 'Otros cocos gram positivos', 'Otros cocos gram positivos'],
    [2, 'Listeria monocytogenes', 'LMO'],
    [3, 'Escherichia coli', 'ECO'], [3, 'Enterobacter cloacae', 'ECL'],
    [3, 'Enterobacter aerogenes', 'EAE'], [3, 'Enterobacter spp', 'EN-'],
    [3, 'klebsiella pneumoniae', 'KPN'], [3, 'klebsiella spp.', 'KL-'],
    [3, 'klebsiella oxytoca', 'KOX'], [3, 'Morganella morganii', 'MMO'],
    [3, 'Proteus mirabilis', 'PMI'], [3, 'Proteus spp.', 'PR-'],
    [3, 'Otras enterobacterias', 'Otras enterobacterias'],
    [4, 'Pseudomonas aeroginosa', 'PAE'], [4, 'Pseudomonas spp', 'PS-'],
    [4, 'Acinetobacter baumannii', 'ABA'], [4, 'Acinetobacter calcoaceticus', 'ACA'],
    [4, 'Acinetobacter spp', 'AC-'], [4, 'Stenotrophomonas maltophilia', 'PMA'],
    [4, 'Otras pseudomonas no fermentadores', 'Otros no fermentadores'],
    [5, 'Candida albicans', 'CAL'], [5, 'Candida no albicans', 'CXA'],
    [5, 'Cryptococcus neoformans', 'CNE'], [5, 'Otras levaduras', 'otras levaduras'],
    [6, 'Aspergillus fumigatus', 'AFU'], [6, 'Aspergillus flavus', 'AFL'],
    [6, 'Aspergillus niger', 'ANI'], [6, 'Aspergillus niger spp', 'ASP'],
    [6, 'Otros hongos filamentosos', 'Otros hongos filamentosos'],
    [7, 'Otros microorganismos no incluidos en el listado', 'Otro'],
];
if (!(int) $pdo->query('SELECT COUNT(*) FROM iaas_microorganismo')->fetchColumn()) {
    $insertar = $pdo->prepare('INSERT INTO iaas_microorganismo (familia, nombre, abreviatura, orden) VALUES (?, ?, ?, ?)');
    foreach ($microorganismos as $i => [$familia, $nombre, $abreviatura]) {
        $insertar->execute([$familia, $nombre, $abreviatura, $i + 1]);
    }
    echo 'Microorganismos cargados: ' . count($microorganismos) . ".\n";
}

// Punto 4.5 del Anexo 5, literal: Cefepime aparece dos veces y
// "Sulfaperazona/Sulfametoxazol" queda como en el papel (consulta pendiente
// con el área, HOJA_DE_RUTA.md §3.6).
$antimicrobianos = [
    'Ceftazidime', 'Cefepime', 'Ceftriaxona', 'Ciprofloxacina', 'Meropenem',
    'Ampicilina/Sulbactam', 'Cefotaxima', 'Aztreonam', 'Cefuroxima',
    'Ticarcilina/Acido Clavulánico', 'Imipenem', 'Piperacilina/Tazobactam',
    'Cefepime', 'Gentamicina', 'Amikacina', 'Sulfaperazona/Sulfametoxazol',
    'Acido Nalixidico', 'Norfloxacino', 'Oxacilina', 'Vancomicina',
    'Eritromicina', 'Linezolid',
];
if (!(int) $pdo->query('SELECT COUNT(*) FROM iaas_antimicrobiano')->fetchColumn()) {
    $insertar = $pdo->prepare('INSERT INTO iaas_antimicrobiano (nombre, orden) VALUES (?, ?)');
    foreach ($antimicrobianos as $i => $nombre) {
        $insertar->execute([$nombre, $i + 1]);
    }
    echo 'Antimicrobianos cargados: ' . count($antimicrobianos) . ".\n";
}

// Horas t: valores de referencia NNIS/NHSN (aprobados por el usuario el
// 26/10; se ajustan antes del lanzamiento si el área pide otros).
$horas = ['CESAREA' => 1, 'COLE_LAPAROTOMIA' => 2, 'COLE_LAPAROSCOPIA' => 2, 'HERNIOPLASTIA' => 2, 'PROTESIS_CADERA' => 3];
if (!(int) $pdo->query('SELECT COUNT(*) FROM iaas_horas_t')->fetchColumn()) {
    $insertar = $pdo->prepare('INSERT INTO iaas_horas_t (procedimiento, horas) VALUES (?, ?)');
    foreach ($horas as $procedimientoT => $h) {
        $insertar->execute([$procedimientoT, $h]);
    }
    echo "Horas t cargadas.\n";
}
