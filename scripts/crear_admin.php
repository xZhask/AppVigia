<?php
/**
 * Crea el primer usuario ADMIN en una base recién instalada desde
 * sql/01_esquema_actual.sql (que no trae usuarios a propósito).
 *
 *   php scripts/crear_admin.php correo@dominio [Nombre visible]
 *
 * La contraseña se genera al azar y se imprime una sola vez. El usuario queda
 * con perfil_incompleto = 1: al entrar, la app le pide completar sus datos de
 * persona (mismo flujo que scripts/migrar_usuarios.php).
 */
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;

$email  = trim($argv[1] ?? '');
$nombre = trim($argv[2] ?? 'Administrador');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php scripts/crear_admin.php correo@dominio [Nombre visible]\n");
    exit(1);
}

$db = Database::conexion();

$existe = $db->prepare('SELECT id FROM usuario WHERE email = ?');
$existe->execute([$email]);
if ($existe->fetch()) {
    fwrite(STDERR, "Ya existe un usuario con el correo $email. No se cambió nada.\n");
    exit(1);
}

$clave = bin2hex(random_bytes(6));

$db->prepare("
    INSERT INTO usuario (nombre, email, password_hash, rol, perfil_incompleto, activo)
    VALUES (?, ?, ?, 'ADMIN', 1, 1)
")->execute([$nombre, $email, password_hash($clave, PASSWORD_BCRYPT)]);

echo "Usuario ADMIN creado (id {$db->lastInsertId()}).\n";
echo "  correo:     $email\n";
echo "  contraseña: $clave\n";
echo "Cámbiala después de entrar.\n";
