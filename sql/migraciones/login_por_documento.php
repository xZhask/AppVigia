<?php
require __DIR__ . '/../../app/Core/Autoload.php';

use App\Core\Database;

$pdo = Database::conexion();

// Ingreso con DNI y contraseña (2026-10-29), en vez de correo y contraseña:
// el correo daba problemas (cuentas que no se revisan, que cambian o que no
// existen). El documento sale de la persona del usuario (persona.num_doc).
//   - usuario.email pasa a ser opcional: solo para avisos y para "Olvidé mi
//     contraseña" cuando el envío de correo esté configurado. uq_user_email
//     se mantiene (MySQL admite varios NULL en un índice único).
//   - login_intento.email pasa a llamarse identificador: guarda lo que se
//     escribió para entrar (el documento; el correo solo en cuentas sin
//     documento todavía, ver Auth::intentarLogin()).
// Se puede correr más de una vez.
$email = $pdo->query("SHOW COLUMNS FROM usuario LIKE 'email'")->fetch();
if ($email && $email['Null'] === 'NO') {
    $pdo->exec('ALTER TABLE usuario MODIFY email varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL');
    $vacios = $pdo->exec("UPDATE usuario SET email = NULL WHERE email = ''");
    echo "usuario.email: ahora opcional ($vacios vacío(s) pasados a NULL).\n";
} else {
    echo "usuario.email: ya era opcional.\n";
}

if ($pdo->query("SHOW COLUMNS FROM login_intento LIKE 'email'")->fetch()) {
    $pdo->exec('ALTER TABLE login_intento RENAME COLUMN email TO identificador,
        RENAME INDEX ix_login_email_fecha TO ix_login_identificador_fecha');
    echo "login_intento: email renombrada a identificador.\n";
} else {
    echo "login_intento.identificador ya existe.\n";
}

// Usuarios activos que todavía no pueden entrar con documento: entran una
// vez con su correo, completan el perfil y desde ahí usan el documento.
$sinDocumento = $pdo->query("SELECT COUNT(*) FROM usuario u
    LEFT JOIN persona p ON p.id = u.persona_id
    WHERE u.activo = 1 AND (p.num_doc IS NULL OR p.num_doc = '')")->fetchColumn();
echo "Usuarios activos sin documento (entran con su correo hasta completar el perfil): $sinDocumento.\n";
