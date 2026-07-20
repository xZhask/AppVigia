<?php
namespace App\Controllers;

use App\Core\Auth;
use App\Core\ReniecService;
use App\Models\ReniecConsulta;

/**
 * Proxy del servidor hacia RENIEC: la única puerta de salida hacia el
 * proveedor externo. El navegador nunca ve la URL ni el token (viven en
 * config.php); solo llega a este endpoint y recibe JSON.
 */
class ReniecController
{
    public function buscar(string $dni): void
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!preg_match('/^\d{8}$/', $dni)) {
            http_response_code(422);
            echo json_encode(['encontrado' => false, 'error' => 'DNI inválido']);
            return;
        }

        $resultado = ReniecService::buscarPorDni($dni);

        $usuario = Auth::usuario();
        if ($usuario) {
            ReniecConsulta::registrar((int) $usuario['id'], $dni, $resultado !== null);
        }

        if ($resultado === null) {
            echo json_encode(['encontrado' => false]);
            return;
        }

        echo json_encode(array_merge(['encontrado' => true], $resultado));
    }
}
