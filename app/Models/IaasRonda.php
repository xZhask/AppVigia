<?php
namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;

/**
 * Ronda diaria (Anexo 4) y partos/cirugías del día (Anexos 6 y 7).
 *
 * Reglas de conteo (ver HOJA_DE_RUTA.md §3.6):
 *   - Un dispositivo está "instalado" el día d si fecha_instalacion <= d y
 *     (sin retiro o d < fecha_retiro): el día de la instalación cuenta, el del
 *     retiro no. El egreso retira los dispositivos que seguían puestos.
 *   - Cada día confirmado deja una marca: E (✓, expuesto, suma al total) o
 *     T (×, expuesto con la IAAS en tratamiento, no suma: instructivo del
 *     Anexo 4, ítem 17). Qué marca toca la decide la ficha IAAS asociada al
 *     dispositivo (fecha de la IAAS hasta el fin del tratamiento).
 *   - Los códigos 1, 2 y 3 del papel (instalación, retiro, inicio de IAAS) y
 *     los símbolos → y * se calculan; nadie los escribe.
 */
class IaasRonda
{
    /** Días hacia atrás que se revisan para avisar marcas faltantes. */
    public const DIAS_REVISION = 31;

    private static function pdo(): \PDO
    {
        return Database::conexion();
    }

    // ------------------------------------------------------------ consultas

    /** Pacientes de la sala ese día, con sus dispositivos y marcas. */
    public static function delDia(int $salaId, string $fecha): array
    {
        $consulta = self::pdo()->prepare(
            'SELECT i.*, p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac
               FROM iaas_internacion i
               JOIN persona p ON p.id = i.persona_id
              WHERE i.sala_id = :sala AND i.fecha_ingreso <= :f1 AND (i.fecha_egreso IS NULL OR i.fecha_egreso >= :f2)
           ORDER BY (i.n_cama IS NULL), LENGTH(i.n_cama), i.n_cama, p.apellido_paterno'
        );
        $consulta->execute(['sala' => $salaId, 'f1' => $fecha, 'f2' => $fecha]);
        $internaciones = $consulta->fetchAll();
        if (!$internaciones) {
            return [];
        }

        $ids = array_column($internaciones, 'id');
        $dispositivos = self::dispositivosDe($ids);
        $idsDisp = array_column($dispositivos, 'id');
        $marcas = self::marcasEn($idsDisp, $fecha, $fecha);
        $casos = self::casosDeDispositivos($idsDisp);

        $porInternacion = [];
        foreach ($dispositivos as $d) {
            $instalado = $d['fecha_instalacion'] <= $fecha && ($d['fecha_retiro'] === null || $fecha < $d['fecha_retiro']);
            $retiradoHoy = $d['fecha_retiro'] === $fecha;
            if (!$instalado && !$retiradoHoy) {
                continue;
            }
            $caso = self::casoActivo($casos[$d['id']] ?? [], $fecha);
            $d['instalado'] = $instalado;
            $d['retirado_hoy'] = $retiradoHoy;
            $d['marca'] = $marcas[$d['id'] . '|' . $fecha] ?? null;
            $d['dia'] = (new DateTimeImmutable($d['fecha_instalacion']))->diff(new DateTimeImmutable($fecha))->days + 1;
            $d['caso'] = $caso;
            $d['casos'] = $casos[$d['id']] ?? [];
            $porInternacion[$d['internacion_id']][] = $d;
        }

        foreach ($internaciones as &$i) {
            $i['dispositivos'] = $porInternacion[$i['id']] ?? [];
            $i['dia'] = (new DateTimeImmutable($i['fecha_ingreso']))->diff(new DateTimeImmutable($fecha))->days + 1;
            $i['egreso_hoy'] = $i['fecha_egreso'] === $fecha;
        }
        return $internaciones;
    }

    public static function dispositivosDe(array $internacionIds): array
    {
        if (!$internacionIds) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($internacionIds), '?'));
        $consulta = self::pdo()->prepare("SELECT * FROM iaas_dispositivo WHERE internacion_id IN ($marcadores) ORDER BY FIELD(tipo, 'CVC','CVP','NPT','HD','CUP','VM'), fecha_instalacion");
        $consulta->execute(array_values($internacionIds));
        return $consulta->fetchAll();
    }

    /** @return array<string, string> "dispositivo|fecha" => E/T */
    public static function marcasEn(array $dispositivoIds, string $desde, string $hasta): array
    {
        if (!$dispositivoIds) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($dispositivoIds), '?'));
        $consulta = self::pdo()->prepare("SELECT dispositivo_id, fecha, estado FROM iaas_marca WHERE dispositivo_id IN ($marcadores) AND fecha BETWEEN ? AND ?");
        $consulta->execute([...array_values($dispositivoIds), $desde, $hasta]);
        $marcas = [];
        foreach ($consulta->fetchAll() as $m) {
            $marcas[$m['dispositivo_id'] . '|' . $m['fecha']] = $m['estado'];
        }
        return $marcas;
    }

    /** Fichas IAAS (no anuladas) asociadas a cada dispositivo. */
    public static function casosDeDispositivos(array $dispositivoIds): array
    {
        if (!$dispositivoIds) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($dispositivoIds), '?'));
        $consulta = self::pdo()->prepare(
            "SELECT id, dispositivo_id, tipo_iaas, fecha_iaas, fecha_fin_tratamiento, estado
               FROM iaas_caso WHERE dispositivo_id IN ($marcadores) AND anulado_en IS NULL ORDER BY fecha_iaas"
        );
        $consulta->execute(array_values($dispositivoIds));
        $casos = [];
        foreach ($consulta->fetchAll() as $c) {
            $casos[$c['dispositivo_id']][] = $c;
        }
        return $casos;
    }

    /** La ficha cuya IAAS está en tratamiento ese día, si hay. */
    private static function casoActivo(array $casos, string $fecha): ?array
    {
        foreach ($casos as $c) {
            if ($c['fecha_iaas'] <= $fecha && ($c['fecha_fin_tratamiento'] === null || $fecha <= $c['fecha_fin_tratamiento'])) {
                return $c;
            }
        }
        return null;
    }

    /** Estado que corresponde a una marca de ese dispositivo ese día. */
    public static function estadoPara(int $dispositivoId, string $fecha): string
    {
        $casos = self::casosDeDispositivos([$dispositivoId])[$dispositivoId] ?? [];
        return self::casoActivo($casos, $fecha) ? 'T' : 'E';
    }

    /**
     * Días anteriores a $fecha (hasta DIAS_REVISION atrás) en que algún
     * dispositivo de la sala estaba instalado y nadie lo marcó.
     *
     * @return array<string, int> fecha => dispositivos sin marca
     */
    public static function faltantes(int $salaId, string $fecha): array
    {
        $desde = (new DateTimeImmutable($fecha))->modify('-' . self::DIAS_REVISION . ' days')->format('Y-m-d');
        $ayer = (new DateTimeImmutable($fecha))->modify('-1 day')->format('Y-m-d');
        $consulta = self::pdo()->prepare(
            'SELECT d.id, d.fecha_instalacion, d.fecha_retiro
               FROM iaas_dispositivo d
               JOIN iaas_internacion i ON i.id = d.internacion_id
              WHERE i.sala_id = :sala AND d.fecha_instalacion <= :ayer
                AND (d.fecha_retiro IS NULL OR d.fecha_retiro > :desde)'
        );
        $consulta->execute(['sala' => $salaId, 'ayer' => $ayer, 'desde' => $desde]);
        $dispositivos = $consulta->fetchAll();
        if (!$dispositivos) {
            return [];
        }
        $marcas = self::marcasEn(array_column($dispositivos, 'id'), $desde, $ayer);
        $faltan = [];
        foreach ($dispositivos as $d) {
            $dia = max($d['fecha_instalacion'], $desde);
            while ($dia <= $ayer && ($d['fecha_retiro'] === null || $dia < $d['fecha_retiro'])) {
                if (!isset($marcas[$d['id'] . '|' . $dia]) && !self::mesCerrado($salaId, $dia)) {
                    $faltan[$dia] = ($faltan[$dia] ?? 0) + 1;
                }
                $dia = (new DateTimeImmutable($dia))->modify('+1 day')->format('Y-m-d');
            }
        }
        ksort($faltan);
        return $faltan;
    }

    /** Salas del establecimiento que hoy tienen algo por marcar (insignia del menú). */
    public static function salasPendientesHoy(int $establecimientoId): int
    {
        $hoy = date('Y-m-d');
        $consulta = self::pdo()->prepare(
            'SELECT COUNT(DISTINCT i.sala_id)
               FROM iaas_dispositivo d
               JOIN iaas_internacion i ON i.id = d.internacion_id
               JOIN iaas_sala s ON s.id = i.sala_id AND s.activo = 1
          LEFT JOIN iaas_marca m ON m.dispositivo_id = d.id AND m.fecha = :hoy
              WHERE s.establecimiento_id = :est AND d.fecha_instalacion <= :hoy2
                AND (d.fecha_retiro IS NULL OR d.fecha_retiro > :hoy3) AND m.dispositivo_id IS NULL'
        );
        $consulta->execute(['hoy' => $hoy, 'est' => $establecimientoId, 'hoy2' => $hoy, 'hoy3' => $hoy]);
        return (int) $consulta->fetchColumn();
    }

    /** Pacientes activos y dispositivos sin marcar hoy, por sala (pestañas de la ronda). */
    public static function resumenSalas(int $establecimientoId, string $fecha): array
    {
        $consulta = self::pdo()->prepare(
            'SELECT s.id,
                    COUNT(DISTINCT CASE WHEN i.fecha_ingreso <= :f1 AND (i.fecha_egreso IS NULL OR i.fecha_egreso > :f2) THEN i.id END) AS pacientes,
                    COUNT(DISTINCT CASE WHEN d.fecha_instalacion <= :f3 AND (d.fecha_retiro IS NULL OR d.fecha_retiro > :f4) AND m.dispositivo_id IS NULL THEN d.id END) AS sin_marcar
               FROM iaas_sala s
          LEFT JOIN iaas_internacion i ON i.sala_id = s.id
          LEFT JOIN iaas_dispositivo d ON d.internacion_id = i.id
          LEFT JOIN iaas_marca m ON m.dispositivo_id = d.id AND m.fecha = :f5
              WHERE s.establecimiento_id = :est AND s.activo = 1
           GROUP BY s.id'
        );
        $consulta->execute(['f1' => $fecha, 'f2' => $fecha, 'f3' => $fecha, 'f4' => $fecha, 'f5' => $fecha, 'est' => $establecimientoId]);
        $resumen = [];
        foreach ($consulta->fetchAll() as $fila) {
            $resumen[(int) $fila['id']] = ['pacientes' => (int) $fila['pacientes'], 'sin_marcar' => (int) $fila['sin_marcar']];
        }
        return $resumen;
    }

    public static function internacion(int $id, int $establecimientoId): ?array
    {
        $consulta = self::pdo()->prepare(
            'SELECT i.*, s.upss, s.nombre AS sala_nombre, s.establecimiento_id,
                    p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac, p.n_historia_clinica
               FROM iaas_internacion i
               JOIN iaas_sala s ON s.id = i.sala_id
               JOIN persona p ON p.id = i.persona_id
              WHERE i.id = :id AND s.establecimiento_id = :est'
        );
        $consulta->execute(['id' => $id, 'est' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    public static function dispositivo(int $id, int $establecimientoId): ?array
    {
        $consulta = self::pdo()->prepare(
            'SELECT d.*, i.sala_id, i.fecha_ingreso, i.fecha_egreso, i.persona_id
               FROM iaas_dispositivo d
               JOIN iaas_internacion i ON i.id = d.internacion_id
               JOIN iaas_sala s ON s.id = i.sala_id
              WHERE d.id = :id AND s.establecimiento_id = :est'
        );
        $consulta->execute(['id' => $id, 'est' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    /** ¿El paciente ya tiene una internación abierta en otra sala de este establecimiento? */
    public static function internacionAbierta(int $personaId, int $establecimientoId): ?array
    {
        $consulta = self::pdo()->prepare(
            'SELECT i.*, s.upss, s.nombre AS sala_nombre
               FROM iaas_internacion i JOIN iaas_sala s ON s.id = i.sala_id
              WHERE i.persona_id = :p AND s.establecimiento_id = :est AND i.fecha_egreso IS NULL
              LIMIT 1'
        );
        $consulta->execute(['p' => $personaId, 'est' => $establecimientoId]);
        return $consulta->fetch() ?: null;
    }

    // ------------------------------------------------------------ escritura

    /**
     * @param array<string, string> $dispositivos tipo => fecha de instalación
     */
    public static function ingresar(int $salaId, int $personaId, array $datos, array $dispositivos, int $usuarioId): int
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare(
                'INSERT INTO iaas_internacion (sala_id, persona_id, n_cama, historia_clinica, fecha_ingreso, peso_nacer_g, diagnostico_ingreso, diagnostico_vigilado, creado_por)
                 VALUES (:sala, :persona, :cama, :hc, :ingreso, :peso, :dx, :dxv, :usr)'
            )->execute([
                'sala' => $salaId, 'persona' => $personaId, 'cama' => $datos['n_cama'], 'hc' => $datos['historia_clinica'],
                'ingreso' => $datos['fecha_ingreso'], 'peso' => $datos['peso_nacer_g'], 'dx' => $datos['diagnostico_ingreso'],
                'dxv' => $datos['diagnostico_vigilado'], 'usr' => $usuarioId,
            ]);
            $internacionId = (int) $pdo->lastInsertId();
            foreach ($dispositivos as $tipo => $instalacion) {
                self::instalar($internacionId, $tipo, $instalacion);
            }
            $pdo->commit();
            return $internacionId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function instalar(int $internacionId, string $tipo, string $fecha): int
    {
        self::pdo()->prepare('INSERT INTO iaas_dispositivo (internacion_id, tipo, fecha_instalacion) VALUES (?, ?, ?)')
            ->execute([$internacionId, $tipo, $fecha]);
        return (int) self::pdo()->lastInsertId();
    }

    /** Retira el dispositivo: el día del retiro y los siguientes ya no cuentan. */
    public static function retirar(int $dispositivoId, string $fecha): void
    {
        self::pdo()->prepare('UPDATE iaas_dispositivo SET fecha_retiro = ? WHERE id = ?')->execute([$fecha, $dispositivoId]);
        self::pdo()->prepare('DELETE FROM iaas_marca WHERE dispositivo_id = ? AND fecha >= ?')->execute([$dispositivoId, $fecha]);
    }

    /** Deshace un retiro registrado por error. */
    public static function deshacerRetiro(int $dispositivoId): void
    {
        self::pdo()->prepare('UPDATE iaas_dispositivo SET fecha_retiro = NULL WHERE id = ?')->execute([$dispositivoId]);
    }

    public static function egresar(int $internacionId, string $fecha, string $condicion): void
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE iaas_internacion SET fecha_egreso = ?, condicion_egreso = ? WHERE id = ?')->execute([$fecha, $condicion, $internacionId]);
            $abiertos = $pdo->prepare('SELECT id FROM iaas_dispositivo WHERE internacion_id = ? AND (fecha_retiro IS NULL OR fecha_retiro > ?)');
            $abiertos->execute([$internacionId, $fecha]);
            foreach ($abiertos->fetchAll(\PDO::FETCH_COLUMN) as $id) {
                self::retirar((int) $id, $fecha);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function deshacerEgreso(int $internacionId): void
    {
        self::pdo()->prepare('UPDATE iaas_internacion SET fecha_egreso = NULL, condicion_egreso = NULL WHERE id = ?')->execute([$internacionId]);
    }

    public static function actualizarInternacion(int $internacionId, array $datos): void
    {
        self::pdo()->prepare(
            'UPDATE iaas_internacion SET n_cama = :cama, historia_clinica = :hc, peso_nacer_g = :peso,
                    diagnostico_ingreso = :dx, diagnostico_vigilado = :dxv, observaciones = :obs WHERE id = :id'
        )->execute([
            'cama' => $datos['n_cama'], 'hc' => $datos['historia_clinica'], 'peso' => $datos['peso_nacer_g'],
            'dx' => $datos['diagnostico_ingreso'], 'dxv' => $datos['diagnostico_vigilado'], 'obs' => $datos['observaciones'],
            'id' => $internacionId,
        ]);
    }

    /** Marca (o desmarca) un día. Devuelve el estado guardado, o null si se desmarcó. */
    public static function marcar(int $dispositivoId, string $fecha, bool $marcado, int $usuarioId): ?string
    {
        if (!$marcado) {
            self::pdo()->prepare('DELETE FROM iaas_marca WHERE dispositivo_id = ? AND fecha = ?')->execute([$dispositivoId, $fecha]);
            return null;
        }
        $estado = self::estadoPara($dispositivoId, $fecha);
        self::pdo()->prepare(
            'INSERT INTO iaas_marca (dispositivo_id, fecha, estado, usuario_id) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE estado = VALUES(estado), usuario_id = VALUES(usuario_id)'
        )->execute([$dispositivoId, $fecha, $estado, $usuarioId]);
        return $estado;
    }

    /** "Todo sigue igual": marca los dispositivos instalados de la sala ese día que no tenían marca. */
    public static function confirmarSala(int $salaId, string $fecha, int $usuarioId): int
    {
        $n = 0;
        foreach (self::delDia($salaId, $fecha) as $internacion) {
            foreach ($internacion['dispositivos'] as $d) {
                if ($d['instalado'] && $d['marca'] === null) {
                    self::marcar((int) $d['id'], $fecha, true, $usuarioId);
                    $n++;
                }
            }
        }
        return $n;
    }

    /** Completa los días faltantes como "siguió igual" (aviso de días sin marcar). */
    public static function completarFaltantes(int $salaId, string $fecha, int $usuarioId): int
    {
        $n = 0;
        foreach (array_keys(self::faltantes($salaId, $fecha)) as $dia) {
            $n += self::confirmarSala($salaId, $dia, $usuarioId);
        }
        return $n;
    }

    /** Vuelve a calcular E/T de todas las marcas del dispositivo (al cambiar su ficha IAAS). */
    public static function recalcularEstados(int $dispositivoId): void
    {
        $casos = self::casosDeDispositivos([$dispositivoId])[$dispositivoId] ?? [];
        $marcas = self::pdo()->prepare('SELECT fecha, estado FROM iaas_marca WHERE dispositivo_id = ?');
        $marcas->execute([$dispositivoId]);
        $actualizar = self::pdo()->prepare('UPDATE iaas_marca SET estado = ? WHERE dispositivo_id = ? AND fecha = ?');
        foreach ($marcas->fetchAll() as $m) {
            $estado = self::casoActivo($casos, $m['fecha']) ? 'T' : 'E';
            if ($estado !== $m['estado']) {
                $actualizar->execute([$estado, $dispositivoId, $m['fecha']]);
            }
        }
    }

    // ------------------------------------------------- partos y cirugías del día

    /** @return array<string, int> tipo => cantidad registrada ese día (sin fila = no registrado) */
    public static function procedimientosDelDia(int $salaId, string $fecha): array
    {
        $consulta = self::pdo()->prepare('SELECT tipo, cantidad FROM iaas_procedimiento WHERE sala_id = ? AND fecha = ?');
        $consulta->execute([$salaId, $fecha]);
        return array_map('intval', array_column($consulta->fetchAll(), 'cantidad', 'tipo'));
    }

    /** @param array<string, int> $cantidades */
    public static function guardarProcedimientos(int $salaId, string $fecha, array $cantidades, int $usuarioId): void
    {
        $guardar = self::pdo()->prepare(
            'INSERT INTO iaas_procedimiento (sala_id, fecha, tipo, cantidad, usuario_id) VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad), usuario_id = VALUES(usuario_id)'
        );
        foreach ($cantidades as $tipo => $cantidad) {
            $guardar->execute([$salaId, $fecha, $tipo, $cantidad, $usuarioId]);
        }
    }

    /** @return array<string, array<string, int>> fecha => tipo => cantidad */
    public static function procedimientosDelMes(int $salaId, string $desde, string $hasta): array
    {
        $consulta = self::pdo()->prepare('SELECT fecha, tipo, cantidad FROM iaas_procedimiento WHERE sala_id = ? AND fecha BETWEEN ? AND ?');
        $consulta->execute([$salaId, $desde, $hasta]);
        $porDia = [];
        foreach ($consulta->fetchAll() as $f) {
            $porDia[$f['fecha']][$f['tipo']] = (int) $f['cantidad'];
        }
        return $porDia;
    }

    /** Fichas de ISQ y endometritis por fecha del procedimiento (columnas de infecciones de los Anexos 6 y 7). */
    public static function infeccionesPorProcedimiento(int $salaId, string $desde, string $hasta): array
    {
        $consulta = self::pdo()->prepare(
            "SELECT fecha_procedimiento AS fecha, procedimiento_tipo AS tipo, tipo_iaas, COUNT(*) AS n
               FROM iaas_caso
              WHERE sala_id = ? AND anulado_en IS NULL AND fecha_procedimiento BETWEEN ? AND ?
                AND tipo_iaas IN ('ISQ_SUP','ISQ_PROF','END')
           GROUP BY fecha_procedimiento, procedimiento_tipo, tipo_iaas"
        );
        $consulta->execute([$salaId, $desde, $hasta]);
        $porDia = [];
        foreach ($consulta->fetchAll() as $f) {
            $clave = ($f['tipo_iaas'] === 'END' ? 'END' : 'ISQ') . '|' . $f['tipo'];
            $porDia[$f['fecha']][$clave] = ($porDia[$f['fecha']][$clave] ?? 0) + (int) $f['n'];
        }
        return $porDia;
    }

    // ------------------------------------------------------------ vista del mes

    /**
     * Grilla del Anexo 4 de una sala y mes: una tabla por dispositivo, una
     * fila por episodio (paciente + dispositivo), una celda por día.
     * Cada celda: ['cod' => '1'|'2'|'3'|'', 'sim' => 'E'|'T'|'H'|'F'|'', 'cont' => bool]
     *   E ✓ expuesto · T × IAAS en tratamiento · H → hospitalizado sin el
     *   dispositivo · F falta marcar · cont = viene del mes anterior (*).
     */
    public static function mes(int $salaId, int $anio, int $mes): array
    {
        $inicio = sprintf('%04d-%02d-01', $anio, $mes);
        $fin = (new DateTimeImmutable($inicio))->modify('last day of this month')->format('Y-m-d');
        $hoy = date('Y-m-d');

        $consulta = self::pdo()->prepare(
            'SELECT i.*, p.tipo_doc, p.num_doc, p.apellido_paterno, p.apellido_materno, p.nombres, p.sexo, p.fecha_nac
               FROM iaas_internacion i JOIN persona p ON p.id = i.persona_id
              WHERE i.sala_id = :sala AND i.fecha_ingreso <= :fin AND (i.fecha_egreso IS NULL OR i.fecha_egreso >= :inicio)
           ORDER BY i.fecha_ingreso, p.apellido_paterno'
        );
        $consulta->execute(['sala' => $salaId, 'fin' => $fin, 'inicio' => $inicio]);
        $internaciones = [];
        foreach ($consulta->fetchAll() as $i) {
            $internaciones[$i['id']] = $i;
        }
        $dispositivos = array_values(array_filter(
            self::dispositivosDe(array_keys($internaciones)),
            fn($d) => $d['fecha_instalacion'] <= $fin && ($d['fecha_retiro'] === null || $d['fecha_retiro'] >= $inicio)
        ));
        $ids = array_column($dispositivos, 'id');
        $marcas = self::marcasEn($ids, $inicio, $fin);
        $casos = self::casosDeDispositivos($ids);
        $dias = (int) substr($fin, 8, 2);

        $tablas = [];
        foreach ($dispositivos as $d) {
            $i = $internaciones[$d['internacion_id']];
            $celdas = [];
            $total = 0;
            $inicioIaas = array_column($casos[$d['id']] ?? [], 'fecha_iaas');
            for ($n = 1; $n <= $dias; $n++) {
                $f = sprintf('%04d-%02d-%02d', $anio, $mes, $n);
                $celda = ['cod' => '', 'sim' => '', 'cont' => false, 'fecha' => $f];
                $hospitalizado = $i['fecha_ingreso'] <= $f && ($i['fecha_egreso'] === null || $f <= $i['fecha_egreso']);
                $instalado = $d['fecha_instalacion'] <= $f && ($d['fecha_retiro'] === null || $f < $d['fecha_retiro']);
                if ($f === $d['fecha_instalacion']) {
                    $celda['cod'] = '1';
                } elseif ($f === $d['fecha_retiro']) {
                    $celda['cod'] = '2';
                }
                if (in_array($f, $inicioIaas, true)) {
                    $celda['cod'] .= '3';
                }
                if ($f > $hoy) {
                    $celda['sim'] = '';
                } elseif (isset($marcas[$d['id'] . '|' . $f])) {
                    $celda['sim'] = $marcas[$d['id'] . '|' . $f];
                    $total += $celda['sim'] === 'E' ? 1 : 0;
                } elseif ($instalado) {
                    $celda['sim'] = 'F';
                } elseif ($hospitalizado) {
                    $celda['sim'] = 'H';
                }
                $celda['editable'] = $instalado && $f <= $hoy;
                $celda['cont'] = $n === 1 && $instalado && $d['fecha_instalacion'] < $inicio;
                $celdas[] = $celda;
            }
            $tablas[$d['tipo']][] = ['internacion' => $i, 'dispositivo' => $d, 'celdas' => $celdas, 'total' => $total];
        }
        $orden = array_flip(array_keys(IaasNorma::DISPOSITIVOS));
        uksort($tablas, fn($a, $b) => $orden[$a] <=> $orden[$b]);

        return ['inicio' => $inicio, 'fin' => $fin, 'dias' => $dias, 'tablas' => $tablas, 'pacientes' => count($internaciones)];
    }

    // ------------------------------------------------------------ cierre del mes

    public static function cierre(int $salaId, int $anio, int $mes): ?array
    {
        $consulta = self::pdo()->prepare(
            'SELECT c.*, u.nombre AS usuario_nombre, r.nombre AS reabierto_nombre
               FROM iaas_cierre_mes c JOIN usuario u ON u.id = c.usuario_id LEFT JOIN usuario r ON r.id = c.reabierto_por
              WHERE c.sala_id = ? AND c.anio = ? AND c.mes = ?'
        );
        $consulta->execute([$salaId, $anio, $mes]);
        return $consulta->fetch() ?: null;
    }

    public static function mesCerrado(int $salaId, string $fecha): bool
    {
        static $cache = [];
        $clave = $salaId . '|' . substr($fecha, 0, 7);
        if (!array_key_exists($clave, $cache)) {
            $cierre = self::cierre($salaId, (int) substr($fecha, 0, 4), (int) substr($fecha, 5, 2));
            $cache[$clave] = $cierre !== null && $cierre['reabierto_en'] === null;
        }
        return $cache[$clave];
    }

    public static function cerrarMes(int $salaId, int $anio, int $mes, int $usuarioId): void
    {
        self::pdo()->prepare(
            'INSERT INTO iaas_cierre_mes (sala_id, anio, mes, usuario_id) VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE usuario_id = VALUES(usuario_id), creado_en = CURRENT_TIMESTAMP,
                                     reabierto_por = NULL, reabierto_en = NULL, motivo_reapertura = NULL'
        )->execute([$salaId, $anio, $mes, $usuarioId]);
    }

    public static function reabrirMes(int $salaId, int $anio, int $mes, int $usuarioId, string $motivo): void
    {
        self::pdo()->prepare(
            'UPDATE iaas_cierre_mes SET reabierto_por = ?, reabierto_en = CURRENT_TIMESTAMP, motivo_reapertura = ?
              WHERE sala_id = ? AND anio = ? AND mes = ?'
        )->execute([$usuarioId, $motivo, $salaId, $anio, $mes]);
    }
}
