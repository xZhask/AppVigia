<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Registro de errores (2026-10-27), para el piloto con las IPRESS: cada error
// del servidor (excepción, aviso, caída grave) y del navegador queda con
// quién, dónde y en qué pantalla. Su id es el código que ve el usuario
// (E-00042). Ver App\Core\RegistroErrores y /sistema/errores.
//
// "huella" agrupa las repeticiones del mismo error (origen, mensaje sin
// números, archivo y línea). No guarda lo escrito en los formularios (son
// datos de pacientes): solo los nombres de los campos enviados.
// Se puede correr más de una vez.
$existe = $pdo->query("SHOW TABLES LIKE 'error_registro'")->fetchColumn();
if ($existe) {
    echo "error_registro: ya existe.\n";
    return;
}

$pdo->exec("CREATE TABLE error_registro (
    id int NOT NULL AUTO_INCREMENT,
    huella char(40) COLLATE utf8mb4_unicode_ci NOT NULL,
    origen enum('PHP','JS') COLLATE utf8mb4_unicode_ci NOT NULL,
    nivel varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
    mensaje varchar(1000) COLLATE utf8mb4_unicode_ci NOT NULL,
    archivo varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    linea int DEFAULT NULL,
    traza text COLLATE utf8mb4_unicode_ci,
    metodo varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    ruta varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    campos_enviados varchar(2000) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    usuario_id int DEFAULT NULL,
    establecimiento_id int DEFAULT NULL,
    navegador varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    ip varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    creado_en timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    resuelto_en timestamp NULL DEFAULT NULL,
    resuelto_por int DEFAULT NULL,
    PRIMARY KEY (id),
    KEY ix_error_huella (huella, creado_en),
    KEY ix_error_creado (creado_en),
    KEY fk_error_usuario (usuario_id),
    KEY fk_error_establecimiento (establecimiento_id),
    KEY fk_error_resuelto_por (resuelto_por),
    CONSTRAINT fk_error_usuario FOREIGN KEY (usuario_id) REFERENCES usuario (id) ON DELETE SET NULL,
    CONSTRAINT fk_error_establecimiento FOREIGN KEY (establecimiento_id) REFERENCES establecimiento (id) ON DELETE SET NULL,
    CONSTRAINT fk_error_resuelto_por FOREIGN KEY (resuelto_por) REFERENCES usuario (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
echo "error_registro: creada.\n";
