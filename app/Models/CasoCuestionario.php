<?php
namespace App\Models;

use App\Core\Database;

/**
 * Cuestionarios asociados a un caso (2026-10-11; hoy solo el Anexo 6.2 de
 * ESAVI, "Lista de chequeo del vacunatorio"). No son fichas de enfermedad: no
 * usan seccion_def/campo_def. La definición vive en
 * config/cuestionarios/<clave>.php y las respuestas en caso_cuestionario.datos
 * (JSON plano clave => valor). Todo lo que se guarda pasa por validar(): una
 * clave que la definición no declara se descarta.
 */
class CasoCuestionario
{
    private static array $cacheDefiniciones = [];

    /** Definición de un cuestionario, o null si no existe. */
    public static function definicion(string $clave): ?array
    {
        if (!preg_match('/^[a-z0-9_]+$/', $clave)) {
            return null;
        }
        if (!array_key_exists($clave, self::$cacheDefiniciones)) {
            $archivo = __DIR__ . '/../../config/cuestionarios/' . $clave . '.php';
            self::$cacheDefiniciones[$clave] = is_file($archivo) ? require $archivo : null;
        }
        return self::$cacheDefiniciones[$clave];
    }

    /** Cuestionarios que se ofrecen para una ficha (por su CIE-10). */
    public static function aplicables(string $cie10): array
    {
        $aplicables = [];
        foreach (glob(__DIR__ . '/../../config/cuestionarios/*.php') ?: [] as $archivo) {
            $definicion = self::definicion(basename($archivo, '.php'));
            if ($definicion && in_array($cie10, $definicion['cie10'] ?? [], true)) {
                $aplicables[] = $definicion;
            }
        }
        return $aplicables;
    }

    /**
     * Preguntas de una sección con su clave completa ("I.7", "I.7.a"): los
     * subítems se numeran a, b, c... en el orden del papel.
     */
    public static function preguntasConClave(array $seccion): array
    {
        $lista = [];
        foreach ($seccion['preguntas'] as $pregunta) {
            $pregunta['clave'] = $seccion['id'] . '.' . $pregunta['id'];
            $subitems = [];
            foreach ($pregunta['subitems'] ?? [] as $indice => $subitem) {
                $subitem['clave'] = $pregunta['clave'] . '.' . chr(ord('a') + $indice);
                $subitem['letra'] = chr(ord('a') + $indice);
                $subitems[] = $subitem;
            }
            $pregunta['subitems'] = $subitems;
            $lista[] = $pregunta;
        }
        return $lista;
    }

    /**
     * Todos los campos que se pueden guardar, aplanados: [clave => pregunta].
     * Incluye la cabecera ("cab.fecha"), los textos "extra" ("IV.8.extra"),
     * los "especificar" ("IX.2.especificar"), las celdas de las tablas
     * ("VII.1.vacuna.1") y los comentarios de sección ("comentario.I").
     */
    public static function campos(array $definicion): array
    {
        $campos = [];
        $agregar = function (array $pregunta) use (&$campos): void {
            $clave = $pregunta['clave'];
            if (($pregunta['tipo'] ?? '') === 'tabla') {
                foreach ($pregunta['filas'] as $fila => $etiquetaFila) {
                    for ($columna = 1; $columna <= (int) $pregunta['columnas']; $columna++) {
                        $campos["{$clave}.{$fila}.{$columna}"] = ['tipo' => 'texto', 'depende' => $pregunta['depende'] ?? null];
                    }
                }
            } elseif (($pregunta['tipo'] ?? '') !== 'titulo') {
                $campos[$clave] = $pregunta;
            }
            if (!empty($pregunta['extra'])) {
                $campos["{$clave}.extra"] = ['tipo' => 'texto', 'depende' => $pregunta['depende'] ?? null];
            }
            if (!empty($pregunta['especificar'])) {
                $campos["{$clave}.especificar"] = ['tipo' => 'texto', 'depende' => ['id' => $clave, 'valores' => [$pregunta['especificar']['valor']]]];
            }
        };

        foreach ($definicion['cabecera'] ?? [] as $grupo) {
            foreach ($grupo['campos'] as $campo) {
                $campo['clave'] = 'cab.' . $campo['id'];
                $agregar($campo);
            }
        }
        foreach ($definicion['secciones'] as $seccion) {
            foreach (self::preguntasConClave($seccion) as $pregunta) {
                $agregar($pregunta);
                foreach ($pregunta['subitems'] as $subitem) {
                    $agregar($subitem);
                }
            }
            if (!empty($seccion['comentario'])) {
                $campos['comentario.' . $seccion['id']] = ['tipo' => 'textarea'];
            }
        }
        return $campos;
    }

    /**
     * Respuestas validadas a partir de lo que llegó del formulario
     * ($entrada = $_POST['r']). Devuelve [datos, errores]. Descarta claves no
     * declaradas, opciones que no son de la pregunta y respuestas cuya
     * condición ("depende") no se cumple.
     */
    public static function validar(array $definicion, array $entrada): array
    {
        $campos = self::campos($definicion);
        $datos = [];
        $errores = [];

        foreach ($campos as $clave => $campo) {
            $crudo = $entrada[$clave] ?? null;
            $tipo = $campo['tipo'] ?? 'texto';
            $opciones = array_keys($campo['opciones'] ?? []);

            if ($tipo === 'multiple') {
                $valor = array_values(array_intersect($opciones, array_map('strval', is_array($crudo) ? $crudo : [])));
                if ($valor) {
                    $datos[$clave] = $valor;
                }
                continue;
            }
            if (is_array($crudo)) {
                continue;
            }
            $valor = trim((string) $crudo);
            if ($valor === '') {
                continue;
            }
            switch ($tipo) {
                case 'si_no':
                    if (in_array($valor, ['SI', 'NO'], true)) {
                        $datos[$clave] = $valor;
                    }
                    break;
                case 'opciones':
                    if (in_array($valor, $opciones, true)) {
                        $datos[$clave] = $valor;
                    }
                    break;
                case 'numero':
                    if (ctype_digit($valor) && strlen($valor) <= 6) {
                        $datos[$clave] = $valor;
                    } else {
                        $errores[$clave] = 'Ingresa un número entero (0 o más).';
                        $datos[$clave] = $valor;
                    }
                    break;
                case 'fecha':
                    $iso = fechaIsoValida($valor);
                    if ($iso) {
                        $datos[$clave] = $iso;
                    } else {
                        $errores[$clave] = 'Ingresa una fecha válida.';
                        $datos[$clave] = $valor;
                    }
                    break;
                default: // texto, textarea
                    $datos[$clave] = mb_substr($valor, 0, $tipo === 'textarea' ? 4000 : 255);
            }
        }

        // "depende": se resuelve al final y en cadena (una respuesta
        // descartada arrastra a las que dependen de ella).
        do {
            $quitadas = 0;
            foreach ($campos as $clave => $campo) {
                $depende = $campo['depende'] ?? null;
                if (!$depende || !array_key_exists($clave, $datos)) {
                    continue;
                }
                $valorPadre = $datos[$depende['id']] ?? null;
                $valoresPadre = is_array($valorPadre) ? $valorPadre : ($valorPadre === null ? [] : [$valorPadre]);
                if (!array_intersect($valoresPadre, $depende['valores'])) {
                    unset($datos[$clave], $errores[$clave]);
                    $quitadas++;
                }
            }
        } while ($quitadas > 0);

        return [$datos, $errores];
    }

    /** Fila guardada (con 'datos' decodificado), o null. */
    public static function porCaso(int $casoId, string $clave): ?array
    {
        $consulta = Database::conexion()->prepare(
            'SELECT cc.*, u.nombre AS usuario_nombre
               FROM caso_cuestionario cc
          LEFT JOIN usuario u ON u.id = cc.usuario_id
              WHERE cc.caso_id = :caso AND cc.cuestionario = :clave'
        );
        $consulta->execute(['caso' => $casoId, 'clave' => $clave]);
        $fila = $consulta->fetch();
        if (!$fila) {
            return null;
        }
        $fila['datos'] = json_decode((string) $fila['datos'], true) ?: [];
        return $fila;
    }

    /** Guarda (o reemplaza) las respuestas del cuestionario de un caso. */
    public static function guardar(int $casoId, string $clave, array $datos, int $usuarioId): void
    {
        Database::conexion()->prepare(
            'INSERT INTO caso_cuestionario (caso_id, cuestionario, datos, usuario_id)
                  VALUES (:caso, :clave, :datos, :usuario)
             ON DUPLICATE KEY UPDATE datos = VALUES(datos), usuario_id = VALUES(usuario_id)'
        )->execute([
            'caso'    => $casoId,
            'clave'   => $clave,
            'datos'   => json_encode($datos, JSON_UNESCAPED_UNICODE),
            'usuario' => $usuarioId,
        ]);
    }

    /** Cuántas preguntas respondidas (sin contar cabecera ni comentarios). */
    public static function respondidas(array $datos): int
    {
        return count(array_filter(array_keys($datos), fn(string $k) => !str_starts_with($k, 'cab.') && !str_starts_with($k, 'comentario.')));
    }
}
