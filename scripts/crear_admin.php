<?php
/**
 * Crea el primer usuario ADMIN en una base recién instalada desde
 * sql/01_esquema_actual.sql (que no trae usuarios a propósito).
 *
 *   php scripts/crear_admin.php DNI "Apellido paterno" "Apellido materno" "Nombres" [correo]
 *
 * Desde el 29/10 se entra con DNI y contraseña: el usuario queda unido a la
 * persona de ese DNI (se crea si no está en el padrón; si ya está, se usan
 * sus datos y los nombres escritos aquí se ignoran). El correo es opcional.
 * La contraseña se genera al azar y se imprime una sola vez.
 */
require __DIR__ . '/../app/Core/Autoload.php';

use App\Core\Database;
use App\Models\Persona;
use App\Services\PersonaService;

$dni             = trim($argv[1] ?? '');
$apellidoPaterno = trim($argv[2] ?? '');
$apellidoMaterno = trim($argv[3] ?? '');
$nombres         = trim($argv[4] ?? '');
$email           = trim($argv[5] ?? '');

$uso = "Uso: php scripts/crear_admin.php DNI \"Apellido paterno\" \"Apellido materno\" \"Nombres\" [correo]\n";
if (!preg_match('/^\d{8}$/', $dni)) {
    fwrite(STDERR, "El DNI debe tener 8 dígitos.\n" . $uso);
    exit(1);
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "El correo no es válido.\n" . $uso);
    exit(1);
}

$db = Database::conexion();

$existe = $db->prepare('SELECT u.id FROM usuario u JOIN persona p ON p.id = u.persona_id WHERE p.num_doc = ?');
$existe->execute([$dni]);
if ($existe->fetch()) {
    fwrite(STDERR, "Ya existe un usuario con el DNI $dni. No se cambió nada.\n");
    exit(1);
}
if ($email !== '') {
    $existe = $db->prepare('SELECT id FROM usuario WHERE email = ?');
    $existe->execute([$email]);
    if ($existe->fetch()) {
        fwrite(STDERR, "Ya existe un usuario con el correo $email. No se cambió nada.\n");
        exit(1);
    }
}

$persona = Persona::buscarPorDocumento('DNI', $dni);
if (!$persona) {
    if ($apellidoPaterno === '' || $nombres === '') {
        fwrite(STDERR, "El DNI $dni no está en el padrón: escribe al menos el apellido paterno y los nombres.\n" . $uso);
        exit(1);
    }
    $persona = PersonaService::crearManual('DNI', $dni, [
        'apellido_paterno' => mb_strtoupper($apellidoPaterno),
        'apellido_materno' => mb_strtoupper($apellidoMaterno),
        'nombres'          => mb_strtoupper($nombres),
    ]);
}

$clave = bin2hex(random_bytes(6));

$db->prepare("
    INSERT INTO usuario (persona_id, nombre, email, password_hash, rol, perfil_incompleto, activo)
    VALUES (?, ?, ?, ?, 'ADMIN', 0, 1)
")->execute([
    (int) $persona['id'],
    Persona::nombreCompleto($persona),
    $email !== '' ? $email : null,
    password_hash($clave, PASSWORD_BCRYPT),
]);

echo "Usuario ADMIN creado (id {$db->lastInsertId()}): " . Persona::nombreCompleto($persona) . ".\n";
echo "  DNI:        $dni\n";
echo "  contraseña: $clave\n";
echo "Cámbiala después de entrar (Catálogos > Usuarios > Editar).\n";
