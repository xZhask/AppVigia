<?php
namespace App\Core;

use Exception;

/**
 * Se lanza cuando un usuario intenta registrar o editar una ficha en la que
 * él mismo es la persona notificada (caso.persona_id = usuario.persona_id).
 * Se distingue de errores genéricos para poder mostrar un mensaje claro al
 * usuario y dejar constancia del intento en caso_bitacora, en vez de caer en
 * el manejo genérico de errores internos.
 */
class ConflictoInteresException extends Exception
{
}
