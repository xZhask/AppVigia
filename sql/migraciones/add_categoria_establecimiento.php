<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// establecimiento.categoria: categoría del establecimiento en RENIPRESS
// (NTS N.° 021-MINSA/DGSP). Hasta ahora el padrón solo guardaba nombre, red,
// institución y distrito, así que las fichas que piden el tipo de EE.SS. lo
// capturaban a mano aunque es un dato del establecimiento, igual que
// DISA/DIRESA e institución. Primer uso: Z21 ("Tipo de EESS": Hospital /
// Centro de Salud / Puesto de Salud), que deja de pedirlo en el formulario
// -- el tipo sale de esta columna (tipoEstablecimientoPorCategoria() en
// app/Core/ayudantes.php). También lo piden A50 ("Nivel del establecimiento")
// y O95 ("Categoría del EE.SS."), pendientes en PENDIENTES.md.
$hasCol = $pdo->query("SHOW COLUMNS FROM establecimiento LIKE 'categoria'")->fetchColumn();
if (!$hasCol) {
    $pdo->query("ALTER TABLE establecimiento ADD COLUMN categoria ENUM('I-1','I-2','I-3','I-4','II-1','II-2','II-E','III-1','III-2','III-E') COLLATE utf8mb4_unicode_ci NULL AFTER institucion");
    echo "Columna categoria agregada a la tabla establecimiento.\n";
} else {
    echo "Columna categoria ya existe en establecimiento.\n";
}

// Categoría de las 82 IPRESS PNP del padrón, tomada del listado RENIPRESS
// "Listado de IPRESS.xlsx" (código único + categoría) y cruzada por código
// RENIPRESS: 82 de 82 coinciden. Solo se llena donde la categoría sigue vacía,
// para no pisar lo que un administrador ya haya corregido desde
// Catálogos > Establecimientos.
$categorias = [
    '00010680' => 'I-1', // POSTA DE SALUD POLICIAL HUANCABAMBA
    '00025659' => 'I-1', // PUESTO DE SALUD POLICIAL CHEPEN
    '00038693' => 'I-2', // CENTRO DE SALUD MENTAL COMUNITARIO DE LA POLICIA NACIONAL DEL PERU
    '00036660' => 'I-2', // MEDICA POLICIAL PICHARI
    '00011747' => 'I-2', // POLICIAL PUCUTO
    '00011227' => 'I-2', // POSTA  DE SALUD POLICIAL CHACLACAYO
    '00009476' => 'I-2', // POSTA  DE SALUD POLICIAL- HUANCAVELICA
    '00010688' => 'I-2', // POSTA  MEDICA  POLICIAL  SULLANA
    '00010451' => 'I-2', // POSTA DE SALUD POLICIAL  VIPOL
    '00035044' => 'I-2', // POSTA DE SALUD POLICIAL ANDAHUAYLAS
    '00012923' => 'I-2', // POSTA DE SALUD POLICIAL EESTP PNP PUENTE PIEDRA
    '00025870' => 'I-2', // POSTA DE SALUD POLICIAL ILO
    '00012741' => 'I-2', // POSTA DE SALUD POLICIAL INDEPENDENCIA
    '00025272' => 'I-2', // POSTA DE SALUD POLICIAL SANTA LUCIA
    '00019390' => 'I-2', // POSTA MEDICA ETS. PNP. SAN BARTOLO
    '00011773' => 'I-2', // POSTA MEDICA PNP CERRO DE PASCO
    '00011437' => 'I-2', // POSTA MEDICA PNP HUANTA
    '00009320' => 'I-2', // POSTA MEDICA PNP MADRE DE DIOS
    '00011664' => 'I-2', // POSTA MEDICA POLICIAL "SAN MARTIN DE PORRES"  AREQUIPA
    '00011661' => 'I-2', // POSTA MEDICA POLICIAL CAMANA
    '00010929' => 'I-2', // POSTA MEDICA POLICIAL CHOTA
    '00025776' => 'I-2', // POSTA MEDICA POLICIAL EESTP - HYO
    '00035261' => 'I-2', // POSTA MEDICA POLICIAL ESCUELA D EDUCACION SUPERIOR PROFESIONAL PNP TARAPOTO
    '00010834' => 'I-2', // POSTA MEDICA POLICIAL HUACHO
    '00017336' => 'I-2', // POSTA MEDICA POLICIAL ISLAY
    '00011874' => 'I-2', // POSTA MEDICA POLICIAL JAUJA
    '00011913' => 'I-2', // POSTA MEDICA POLICIAL LA MERCED
    '00012454' => 'I-2', // POSTA MEDICA POLICIAL LOS SINCHIS
    '00010153' => 'I-2', // POSTA MEDICA POLICIAL PAMPAS
    '00011400' => 'I-2', // POSTA MEDICA POLICIAL PISCO
    '00011485' => 'I-2', // POSTA MEDICA POLICIAL SATIPO
    '00010610' => 'I-2', // POSTA MEDICA POLICIAL SEDE DIRAVPOL
    '00015771' => 'I-2', // POSTA MEDICA POLICIAL TINGO MARIA
    '00010573' => 'I-2', // POSTA MEDICA POLICIAL VENTANILLA
    '00027415' => 'I-2', // POSTA MEDICO POLICIAL EESTP PNP AYACUCHO
    '00011894' => 'I-2', // POSTA MÉDICA SANIDAD CHINCHEROS
    '00027203' => 'I-2', // PUESTO DE SALUD PNP PALMAPAMPA
    '00024663' => 'I-2', // SANIDAD PNP  SICUANI
    '00011744' => 'I-2', // SANIDAD PNP LA CONVENCION - QUILLABAMBA
    '00012822' => 'I-3', // CENTRO ODONTOLOGICO PNP ANGAMOS
    '00011743' => 'I-3', // POLICIAL "SANTA ROSA"  CUSCO
    '00020827' => 'I-3', // POLICLINICO  PNP TARAPOTO
    '00010557' => 'I-3', // POLICLINICO  POLICIAL CALLAO
    '00017391' => 'I-3', // POLICLINICO COIP
    '00009296' => 'I-3', // POLICLINICO PNP CAJAMARCA
    '00017690' => 'I-3', // POLICLINICO PNP CHIMBOTE
    '00010205' => 'I-3', // POLICLINICO PNP HUARAZ
    '00011876' => 'I-3', // POLICLINICO PNP SAN MARTIN DE PORRES
    '00012885' => 'I-3', // POLICLINICO PNP WALTER ROSALES LEON
    '00012088' => 'I-3', // POLICLINICO POLICIAL "LA CRUZ" TUMBES
    '00008036' => 'I-3', // POLICLINICO POLICIAL ABANCAY
    '00010679' => 'I-3', // POLICLINICO POLICIAL ALMIRANTE MIGUEL GRAU PIURA
    '00018106' => 'I-3', // POLICLINICO POLICIAL BAGUA GRANDE
    '00017849' => 'I-3', // POLICLINICO POLICIAL CAÑETE
    '00035992' => 'I-3', // POLICLINICO POLICIAL CHACHAPOYAS
    '00011398' => 'I-3', // POLICLINICO POLICIAL CHINCHA
    '00020382' => 'I-3', // POLICLINICO POLICIAL CHORRILLOS
    '00011426' => 'I-3', // POLICLINICO POLICIAL DE AYACUCHO
    '00012664' => 'I-3', // POLICLINICO POLICIAL DEFENSORES DE LA DEMOCRACIA CARABAYLLO
    '00012275' => 'I-3', // POLICLINICO POLICIAL DINOES
    '00010710' => 'I-3', // POLICLINICO POLICIAL HUANCAYO
    '00011421' => 'I-3', // POLICLINICO POLICIAL HUÁNUCO
    '00010900' => 'I-3', // POLICLINICO POLICIAL ICA
    '00011369' => 'I-3', // POLICLINICO POLICIAL IQUITOS
    '00018950' => 'I-3', // POLICLINICO POLICIAL JAEN
    '00010236' => 'I-3', // POLICLINICO POLICIAL JULIACA
    '00012740' => 'I-3', // POLICLINICO POLICIAL MININTER
    '00015236' => 'I-3', // POLICLINICO POLICIAL MOQUEGUA
    '00018077' => 'I-3', // POLICLINICO POLICIAL MOYOBAMBA
    '00010148' => 'I-3', // POLICLINICO POLICIAL PUCALLPA
    '00010235' => 'I-3', // POLICLINICO POLICIAL PUNO
    '00012739' => 'I-3', // POLICLINICO POLICIAL SAN DIEGO
    '00011528' => 'I-3', // POLICLINICO POLICIAL TACNA
    '00011154' => 'I-3', // POLICLINICO POLICIAL TRUJILLO
    '00030839' => 'I-3', // POLICLINICO POLICIAL ZARATE
    '00011399' => 'I-3', // POSTA MEDICA PNP NAZCA
    '00035199' => 'I-3', // PUESTO SANITARIO PNP BAGUA CHICA
    '00016094' => 'II-1', // HOSPITAL PNP "AUGUSTO B. LEGUIA"
    '00011794' => 'II-1', // HOSPITAL REGIONAL PNP AREQUIPA
    '00014718' => 'II-E', // HOSPITAL POLICIAL GERIATRICO SAN JOSE
    '00011833' => 'II-E', // HOSPITAL REGIONAL POLICIAL CHICLAYO
    '00013591' => 'III-1', // HOSPITAL NACIONAL POLICIA NACIONAL DEL PERU GRAL PNP LUIS N. SAENZ.
];

$actualizar = $pdo->prepare('UPDATE establecimiento SET categoria = :categoria WHERE cod_renipress = :cod AND categoria IS NULL');
$llenados = 0;
foreach ($categorias as $cod => $categoria) {
    $actualizar->execute(['categoria' => $categoria, 'cod' => $cod]);
    $llenados += $actualizar->rowCount();
}
$sinCategoria = (int) $pdo->query('SELECT COUNT(*) FROM establecimiento WHERE categoria IS NULL')->fetchColumn();
echo "Categoría llenada en {$llenados} establecimiento(s); quedan {$sinCategoria} sin categoría.\n";
