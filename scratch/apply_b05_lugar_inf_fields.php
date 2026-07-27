<?php
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Section ID para 'Lugar probable de infección' en B05 es 2777

// 1. Actualizar etiqueta de ID 16065
$pdo->exec("UPDATE campo_def SET etiqueta = 'Entre 7 a 30 días antes de la erupción cutánea, el caso tuvo contacto con:' WHERE id = 16065");

// 2. Crear campo 'Si es otros, especificar' (depende_de 16065, valor_activador OTROS)
$stmtExist1 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2777 AND clave = 'especificar_otro_contacto'");
$stmtExist1->execute();
$idEspContacto = $stmtExist1->fetchColumn();

if (!$idEspContacto) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, depende_de, valor_activador, orden) VALUES (2777, 'especificar_otro_contacto', 'Si es otros, especificar', 'TEXTO', 0, 16065, 'OTROS', 2)");
    $ins->execute();
    $idEspContacto = $pdo->lastInsertId();
    echo "Creado campo 'Si es otros, especificar' (ID $idEspContacto).\n";
}

// 3. Crear campo 'Paciente viajó entre los 7 a 30 días antes del inicio de la erupción' (SELECT, catalogo_id 145)
$stmtExist2 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2777 AND clave = 'paciente_viajo_7_30_dias'");
$stmtExist2->execute();
$idPacienteViajo = $stmtExist2->fetchColumn();

if (!$idPacienteViajo) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, catalogo_id, orden) VALUES (2777, 'paciente_viajo_7_30_dias', 'Paciente viajó entre los 7 a 30 días antes del inicio de la erupción', 'SELECT', 0, 145, 3)");
    $ins->execute();
    $idPacienteViajo = $pdo->lastInsertId();
    echo "Creado campo 'Paciente viajó entre los 7 a 30 días antes del inicio de la erupción' (ID $idPacienteViajo).\n";
}

// 4. Actualizar ID 16066 a 'Latitud del domicilio'
$pdo->exec("UPDATE campo_def SET clave = 'latitud_domicilio', etiqueta = 'Latitud del domicilio' WHERE id = 16066");

// 5. Crear campo 'Longitud del domicilio'
$stmtExist3 = $pdo->prepare("SELECT id FROM campo_def WHERE seccion_id = 2777 AND clave = 'longitud_domicilio'");
$stmtExist3->execute();
$idLongitud = $stmtExist3->fetchColumn();

if (!$idLongitud) {
    $ins = $pdo->prepare("INSERT INTO campo_def (seccion_id, clave, etiqueta, tipo, obligatorio, orden) VALUES (2777, 'longitud_domicilio', 'Longitud del domicilio', 'TEXTO', 0, 5)");
    $ins->execute();
    $idLongitud = $pdo->lastInsertId();
    echo "Creado campo 'Longitud del domicilio' (ID $idLongitud).\n";
}

// 6. Reordenar campos en sección 2777
$ordenDeseado = [
    16065, // Entre 7 a 30 días antes de la erupción cutánea, el caso tuvo contacto con:
    (int) $idEspContacto, // Si es otros, especificar
    (int) $idPacienteViajo, // Paciente viajó entre los 7 a 30 días antes del inicio de la erupción
    16066, // Latitud del domicilio
    (int) $idLongitud, // Longitud del domicilio
];

$i = 1;
foreach ($ordenDeseado as $cId) {
    $pdo->prepare("UPDATE campo_def SET orden = :ord WHERE id = :id")->execute(['ord' => $i, 'id' => $cId]);
    $i++;
}

echo "Reordenados los campos de Lugar probable de infección (1 a " . ($i - 1) . ").\n";
