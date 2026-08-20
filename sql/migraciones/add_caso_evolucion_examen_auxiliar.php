<?php
/**
 * Tabla hija nueva (A44 "Laboratorio y evolución", 2026-08-19): la ficha en
 * papel trae 2 tablas por FECHA (Evolución clínica y Exámenes auxiliares),
 * el formulario solo tenía campos de un único registro -- se reemplazan por
 * un componente repetible (mismo patrón que caso_viaje/caso_muestra).
 */
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

$antibioticos = ['penicilina', 'cloranfenicol', 'rifampicina', 'ciprofloxacina', 'eritromicina', 'cotrimoxazol', 'ceftriaxona', 'otros'];
$columnasAtb = [];
foreach ($antibioticos as $atb) {
    $columnasAtb[] = "atb_{$atb}_usado TINYINT(1) DEFAULT NULL";
    $columnasAtb[] = "atb_{$atb}_dosis VARCHAR(60) DEFAULT NULL";
}
$columnasAtbSql = implode(",\n  ", $columnasAtb);

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS caso_evolucion (
  id INT NOT NULL AUTO_INCREMENT,
  caso_id INT NOT NULL,
  fecha DATE DEFAULT NULL,
  temperatura VARCHAR(20) DEFAULT NULL,
  hemoglobina VARCHAR(20) DEFAULT NULL,
  hematocrito VARCHAR(20) DEFAULT NULL,
  transfusiones VARCHAR(20) DEFAULT NULL,
  frotis VARCHAR(60) DEFAULT NULL,
  hemocultivo_muestra_tomada TINYINT(1) DEFAULT NULL,
  hemocultivo_fecha_toma DATE DEFAULT NULL,
  hemocultivo_resultado VARCHAR(20) DEFAULT NULL,
  hemocultivo_fecha_resultado DATE DEFAULT NULL,
  {$columnasAtbSql},
  atb_otros_especificar VARCHAR(80) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY ix_evol_caso (caso_id),
  CONSTRAINT fk_evol_caso FOREIGN KEY (caso_id) REFERENCES caso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabla caso_evolucion lista.\n";
} catch (\PDOException $e) {
    echo "Error al crear caso_evolucion: " . $e->getMessage() . "\n";
}

$camposExamen = ['grupo_sanguineo', 'plaquetas', 'hematies', 'tgo', 'tgp', 'fosfatasa_alcalina', 'bilirrubina_directa', 'bilirrubina_indirecta', 'bilirrubina_total', 'urea', 'glucosa', 'creatinina', 'leucocitos_totales', 'segmentados', 'abastonados', 'linfocitos', 'monocitos', 'eosinofilos', 'basofilos', 'blastos', 'aglutinacion_tifico_o', 'aglutinacion_tifico_h', 'paratifico_a', 'paratifico_b', 'brucellas'];
$columnasExamenSql = implode(",\n  ", array_map(fn($c) => "{$c} VARCHAR(20) DEFAULT NULL", $camposExamen));

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS caso_examen_auxiliar (
  id INT NOT NULL AUTO_INCREMENT,
  caso_id INT NOT NULL,
  fecha DATE DEFAULT NULL,
  {$columnasExamenSql},
  PRIMARY KEY (id),
  KEY ix_exau_caso (caso_id),
  CONSTRAINT fk_exau_caso FOREIGN KEY (caso_id) REFERENCES caso(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "Tabla caso_examen_auxiliar lista.\n";
} catch (\PDOException $e) {
    echo "Error al crear caso_examen_auxiliar: " . $e->getMessage() . "\n";
}
