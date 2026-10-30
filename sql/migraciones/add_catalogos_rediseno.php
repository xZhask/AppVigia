<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Rediseño de Establecimientos, Usuarios y Enfermedades (2026-10-19):
//   - usuario.ultimo_acceso: se escribe en cada inicio de sesión
//     (Auth::intentarLogin). Se rellena con el último login exitoso que haya
//     en login_intento.
//   - usuario.motivo_inactivo / establecimiento.motivo_inactivo: por qué se
//     desactivó (se pide al desactivar; se borra al reactivar).
//   - establecimiento.nombre_corto: cómo se muestra en listados (opcional;
//     sin él se usa el nombre).
$agregar = [
    'usuario' => [
        'ultimo_acceso'   => 'ADD COLUMN ultimo_acceso timestamp NULL DEFAULT NULL AFTER activo',
        'motivo_inactivo' => 'ADD COLUMN motivo_inactivo varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER ultimo_acceso',
    ],
    'establecimiento' => [
        'nombre_corto'    => 'ADD COLUMN nombre_corto varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER nombre',
        'motivo_inactivo' => 'ADD COLUMN motivo_inactivo varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL AFTER activo',
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

// login_por_documento.php (29/10) renombra login_intento.email a
// identificador. Si ya corrió, el relleno sobra: desde el 19/10
// Auth::intentarLogin() escribe ultimo_acceso en cada ingreso.
if (!$pdo->query("SHOW COLUMNS FROM login_intento LIKE 'email'")->fetch()) {
    echo "Último acceso: ya se escribe al ingresar (login_intento ya no tiene email); no hace falta rellenar.\n";
    return;
}

$n = $pdo->exec(
    'UPDATE usuario u
       JOIN (SELECT email, MAX(fecha) AS ultima FROM login_intento WHERE exitoso = 1 GROUP BY email) li ON li.email = u.email
        SET u.ultimo_acceso = li.ultima
      WHERE u.ultimo_acceso IS NULL'
);
echo "Último acceso rellenado desde login_intento: $n usuario(s).\n";
