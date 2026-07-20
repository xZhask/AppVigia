<?php
require __DIR__ . '/../app/Core/Autoload.php';
$config = require __DIR__ . '/../config/config.php';

use App\Core\Database;

try {
    $db = Database::conexion();
    $db->beginTransaction();

    $stmt = $db->query("SELECT id, nombre FROM usuario WHERE persona_id IS NULL");
    $usuarios = $stmt->fetchAll();

    $insertPersona = $db->prepare("
        INSERT INTO persona (tipo_doc, num_doc, codigo_interno, apellido_paterno, apellido_materno, nombres, es_pnp)
        VALUES ('SIN_DOCUMENTO', NULL, :codigo, :nombre, '', '', 0)
    ");
    $updateUsuario = $db->prepare("
        UPDATE usuario 
           SET persona_id = :persona_id, perfil_incompleto = 1 
         WHERE id = :id
    ");

    $count = 0;
    foreach ($usuarios as $u) {
        $codigo = 'P-' . date('Y') . '-' . str_pad($u['id'], 5, '0', STR_PAD_LEFT);
        
        $insertPersona->execute([
            'codigo' => $codigo,
            'nombre' => $u['nombre']
        ]);
        
        $personaId = $db->lastInsertId();
        
        $updateUsuario->execute([
            'persona_id' => $personaId,
            'id'         => $u['id']
        ]);
        $count++;
    }

    $db->commit();
    echo "Migrados $count usuarios exitosamente.\n";
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
