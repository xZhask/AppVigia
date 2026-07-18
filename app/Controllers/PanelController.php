<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Estadistica;
use App\Models\Caso;
use DateTime;

class PanelController extends Controller
{
    public function index(): void
    {
        $totalFichas = Caso::contarTodos();

        if ($totalFichas === 0) {
            $this->vista('panel/index', [
                'tituloVista' => 'Panel de vigilancia',
                'rutaActual'  => '',
                'totalFichas' => 0,
            ]);
            return;
        }

        $hoy = new DateTime();
        $actual = semanaEpidemiologica($hoy->format('Y-m-d'));
        $anterior = semanaEpidemiologica((clone $hoy)->modify('-7 days')->format('Y-m-d'));

        $rango = ($_GET['rango'] ?? '14') === 'anio' ? 'anio' : '14';
        if ($rango === 'anio') {
            $anioDesde = $actual['anio'];
            $seDesde = 1;
        } else {
            $inicio = semanaEpidemiologica((clone $hoy)->modify('-13 weeks')->format('Y-m-d'));
            $anioDesde = $inicio['anio'];
            $seDesde = $inicio['semana'];
        }

        $config = require __DIR__ . '/../../config/config.php';
        $umbralAnios = $config['reportes']['anios_minimos_corredor'] ?? 2;

        $curva = $this->calcularCurva(null, $anioDesde, $seDesde, $actual['anio'], $actual['semana'], $umbralAnios);

        // "Por enfermedad": ventana corta fija (últimas 4 SE), como el mockup.
        $inicioBrk = semanaEpidemiologica((clone $hoy)->modify('-3 weeks')->format('Y-m-d'));

        $this->vista('panel/index', [
            'tituloVista' => 'Panel de vigilancia',
            'rutaActual'  => '',
            'totalFichas' => $totalFichas,
            'rango'       => $rango,
            'semanaActual' => $actual,
            'metricas'    => Caso::metricasPanel($actual, $anterior),
            'curva'       => $curva,
            'distribucionEnfermedad' => Caso::distribucionPorEnfermedad(
                $inicioBrk['anio'], $inicioBrk['semana'], $actual['anio'], $actual['semana']
            ),
            'distribucionClasificacion' => Caso::distribucionPorClasificacion(),
        ]);
    }

    /**
     * Calcula la serie semanal (rellenando semanas sin casos con cero) y la
     * banda de referencia: canal histórico por percentiles si hay al menos
     * $umbralAnios de historia previa, o un respaldo de media/desviación de
     * la propia serie mostrada en caso contrario. Nunca se llama "corredor
     * endémico" al respaldo.
     */
    private function calcularCurva(
        ?int $enfermedadId,
        int $anioDesde,
        int $seDesde,
        int $anioHasta,
        int $seHasta,
        int $umbralAnios
    ): array {
        $semanas = semanasEnRango($anioDesde, $seDesde, $anioHasta, $seHasta);

        $serieCruda = Caso::serieSemanal($enfermedadId, $anioDesde, $seDesde, $anioHasta, $seHasta);
        $totalesPorClave = [];
        foreach ($serieCruda as $fila) {
            $totalesPorClave[$fila['anio_epi'] . '-' . $fila['semana_epi']] = (int) $fila['total'];
        }

        $valores = [];
        foreach ($semanas as $semana) {
            $valores[] = $totalesPorClave[$semana['anio'] . '-' . $semana['semana']] ?? 0;
        }

        $aniosConDatos = Caso::aniosConDatos($enfermedadId, $anioHasta);
        $usaHistorico = $aniosConDatos >= $umbralAnios;

        $bandaBaja = [];
        $bandaAlta = [];

        if ($usaHistorico) {
            $historico = Caso::serieHistoricaPorSemana($enfermedadId, $anioHasta);
            foreach ($semanas as $i => $semana) {
                $valoresHistoricos = $historico[$semana['semana']] ?? [];
                if (empty($valoresHistoricos)) {
                    $bandaBaja[] = $valores[$i];
                    $bandaAlta[] = $valores[$i];
                    continue;
                }
                $bandaBaja[] = Estadistica::percentil($valoresHistoricos, 25);
                $bandaAlta[] = Estadistica::percentil($valoresHistoricos, 75);
            }
            $etiquetaBanda = 'Corredor endémico';
            $notaBanda = null;
        } else {
            $stats = Estadistica::promedioDesviacion($valores);
            $bajo = max(0, $stats['promedio'] - $stats['desviacion']);
            $alto = $stats['promedio'] + $stats['desviacion'];
            foreach ($semanas as $i) {
                $bandaBaja[] = $bajo;
                $bandaAlta[] = $alto;
            }
            $etiquetaBanda = 'Referencia provisional (sin historia suficiente)';
            $notaBanda = "Se requieren al menos {$umbralAnios} años de datos para construir el corredor endémico.";
        }

        $sobreUmbral = [];
        foreach ($valores as $i => $valor) {
            $sobreUmbral[] = $valor > $bandaAlta[$i];
        }

        return [
            'semanas'      => $semanas,
            'valores'      => $valores,
            'bandaBaja'    => $bandaBaja,
            'bandaAlta'    => $bandaAlta,
            'sobreUmbral'  => $sobreUmbral,
            'etiquetaBanda' => $etiquetaBanda,
            'notaBanda'    => $notaBanda,
        ];
    }
}
