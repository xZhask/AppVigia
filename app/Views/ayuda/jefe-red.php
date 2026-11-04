<?php
// Guía del jefe de red (2026-11-04). Sin datos reales.
$guiaTitulo = 'Guía del jefe de red';
$guiaDescripcion = 'Cómo supervisar la notificación y consultar las cifras de su red';
$guiaFecha = '04/11/2026';
$indice = [
    'alcance-red'     => ['Qué puede hacer', []],
    'ingreso-red'     => ['Ingresar al sistema', []],
    'seguimiento-red' => ['Seguimiento semanal', ['recordatorios-red' => 'Recordatorios']],
    'panel-red'       => ['Panel', []],
    'reportes-red'    => ['Reportes', []],
    'problemas-red'   => ['Problemas frecuentes', []],
];
require __DIR__ . '/_inicio.php';
?>
      <section class="guia-seccion" id="alcance-red" data-bloque>
        <h2>Qué puede hacer</h2>
        <p>Como jefe o jefa de red, usted supervisa las IPRESS de la red asignada a su cuenta: ve si cierran cada semana y consulta sus cifras de vigilancia. No registra ni corrige fichas.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Puede</th><th>No puede</th></tr></thead>
          <tbody>
            <tr>
              <td>
                <ul>
                  <li>Ver el Panel de su red</li>
                  <li>Armar y descargar el consolidado de Reportes</li>
                  <li>Ver qué IPRESS cerraron cada semana</li>
                  <li>Pedir a sus IPRESS que cierren la semana</li>
                </ul>
              </td>
              <td>
                <ul>
                  <li>Abrir, registrar ni editar fichas</li>
                  <li>Ver el nombre o el documento de los pacientes</li>
                  <li>Usar los formatos de Reportes con datos individuales</li>
                  <li>Reabrir semanas ni ver otras redes</li>
                  <li>Usar el módulo IAAS</li>
                </ul>
              </td>
            </tr>
          </tbody>
        </table></div>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Cuide las cifras bajas.</b> Las fichas privadas de VIH, sífilis y violencia familiar suman en los totales, sin datos de la persona. Aun así, en una red pequeña una cifra baja podría identificar a alguien: no la comparta fuera de su función.</div></div>
      </section>

      <section class="guia-seccion" id="ingreso-red" data-bloque>
        <h2>Ingresar al sistema</h2>
        <ol class="guia-pasos">
          <li>Abra la dirección del sistema que le indicó el Área de Epidemiología.</li>
          <li>Escriba su <b>DNI</b> y su <b>Contraseña</b>, y pulse <b>Ingresar</b>.</li>
          <li>La primera vez, cambie la contraseña con el ícono de la llave, junto a su nombre, al pie del menú lateral. Debe tener al menos 8 caracteres y no puede ser su DNI.</li>
        </ol>
        <p>Si la olvidó, pida al Área de Epidemiología que le asigne una nueva. Al terminar, cierre la sesión con el ícono de salida.</p>
      </section>

      <section class="guia-seccion" id="seguimiento-red" data-bloque>
        <h2>Seguimiento semanal <?= $ir('/notificacion-semanal', 'Abrir') ?></h2>
        <p>Cada IPRESS debe cerrar cada semana epidemiológica (SE), aunque no haya tenido casos. En <b>Notificación semanal</b> ve cuáles lo hicieron. El número junto al menú indica cuántas faltan.</p>
        <?php $captura('red-notificacion.jpg', 'Notificación semanal del jefe de red: totales por estado y lista de las IPRESS de la red con su estado, fichas y registradores', 'Solo aparecen las IPRESS de su red, y primero las que requieren atención.'); ?>
        <ol class="guia-pasos">
          <li>Use las flechas de arriba para elegir la semana.</li>
          <li>Pulse un recuadro para filtrar por estado, o busque una IPRESS por su nombre.</li>
          <li>Revise el estado de cada IPRESS, sus fichas y quién es su registrador.</li>
        </ol>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Estado</th><th>Qué significa</th></tr></thead>
          <tbody>
            <tr><td><b>A tiempo</b></td><td>Cerró dentro del plazo</td></tr>
            <tr><td><b>Fuera de plazo</b></td><td>Cerró después del plazo</td></tr>
            <tr><td><b>Pendiente</b></td><td>No ha cerrado y el plazo sigue vigente</td></tr>
            <tr><td><b>No notificó</b></td><td>No ha cerrado y el plazo ya venció</td></tr>
          </tbody>
        </table></div>
        <p>Si ve <b>Sin registrador activo</b>, avise al Área de Epidemiología: esa IPRESS no tiene quién cierre la semana. Si ve <b>Pide reabrir</b>, la solicitud la atiende el administrador.</p>
        <div class="guia-sub" id="recordatorios-red" data-bloque>
          <h3>Recordatorios</h3>
          <p><b>Recordar a…</b> avisa a todas las IPRESS de su red que faltan, y <b>Recordar</b>, en una fila, solo a esa.</p>
          <div class="guia-alerta"><?= $icoAviso ?><div>Por ahora los recordatorios no se envían, porque falta configurar la cuenta de correo del sistema: la pantalla mostrará ese aviso. Mientras tanto, contacte a sus IPRESS por otra vía.</div></div>
        </div>
      </section>

      <section class="guia-seccion" id="panel-red" data-bloque>
        <h2>Panel <?= $ir('/', 'Abrir Panel') ?></h2>
        <p>El Panel resume la vigilancia de su red. Elija arriba el <b>Periodo</b>: SE actual, últimas 4 o 12 SE, o el año. Se aplica a todas las tarjetas.</p>
        <?php $captura('red-panel.jpg', 'Panel de vigilancia de una red: indicadores, enfermedades en vigilancia y curva epidemiológica', 'La descripción y la parte superior derecha indican de qué red son los datos.'); ?>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Tarjeta</th><th>Qué muestra</th></tr></thead>
          <tbody>
            <tr><td><b>Indicadores</b></td><td>Fichas, casos en investigación, confirmados y fichas pendientes de validación</td></tr>
            <tr><td><b>Enfermedades en vigilancia</b></td><td>Casos por enfermedad. Pulse una fila para ver su curva</td></tr>
            <tr><td><b>Curva epidemiológica</b></td><td>Casos por semana. El corredor endémico aparece cuando hay al menos 2 años anteriores con datos; hasta entonces, la enfermedad figura como <b>Sin historia</b></td></tr>
            <tr><td><b>Notificaciones inmediatas recientes</b></td><td>La enfermedad, su clasificación, la IPRESS y la fecha, sin datos del paciente</td></tr>
            <tr><td><b>Cumplimiento de notificación</b></td><td>Un mapa por IPRESS y semana. Pulse una celda para abrir esa semana en Notificación semanal</td></tr>
          </tbody>
        </table></div>
      </section>

      <section class="guia-seccion" id="reportes-red" data-bloque>
        <h2>Reportes <?= $ir('/reportes', 'Abrir') ?></h2>
        <p>En <b>Reportes</b> puede armar cuadros con los casos de su red. Las fichas anuladas no cuentan.</p>
        <?php $captura('red-reportes.jpg', 'Reporte consolidado de una red, con sus filtros y el cuadro de casos por establecimiento y clasificación', 'El reporte indica la red a la que pertenecen los datos.'); ?>
        <ol class="guia-pasos">
          <li>Elija la <b>Enfermedad</b>, las <b>Filas</b>, las <b>Columnas</b> y el <b>Periodo</b> (4 SE, 12 SE, Año o Rango). El cuadro se actualiza solo.</li>
          <li>Pulse <b>Tabla</b> o <b>Gráfico</b> para cambiar la vista.</li>
          <li>Pulse <b>Excel</b> o <b>PDF</b> para descargar o imprimir. El archivo indica su red como ámbito.</li>
        </ol>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Gráfico</th><th>Cuándo aparece</th></tr></thead>
          <tbody>
            <tr><td><b>Líneas</b></td><td>Con filas por semana epidemiológica</td></tr>
            <tr><td><b>Dona</b></td><td>Con Columnas en «Ninguna» y de 2 a 12 categorías con casos</td></tr>
            <tr><td><b>Pirámide</b></td><td>Con filas por etapa de vida, sin columnas o con columnas por sexo</td></tr>
            <tr><td><b>Barras</b></td><td>En las demás agrupaciones</td></tr>
          </tbody>
        </table></div>
      </section>

      <section class="guia-seccion" id="problemas-red" data-bloque>
        <h2>Problemas frecuentes</h2>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Qué pasa</th><th>Qué hacer</th></tr></thead>
          <tbody>
            <tr><td>Aparece «No tienes acceso a esta sección»</td><td>Su rol no incluye esa pantalla, por ejemplo una ficha o un formato con datos individuales. Vuelva al Panel</td></tr>
            <tr><td>No veo datos o me falta una IPRESS</td><td>Pida al Área de Epidemiología que revise la red de su cuenta y la de esa IPRESS</td></tr>
            <tr><td>Necesito el detalle de un caso</td><td>Pídalo a la IPRESS o al Área de Epidemiología</td></tr>
            <tr><td>El sistema cerró mi sesión</td><td>Cambió su contraseña o los datos de su cuenta, o se equivocó 5 veces en la contraseña actual al querer cambiarla. Ingrese de nuevo; si no puede, avise al Área de Epidemiología</td></tr>
            <tr><td>«DNI o contraseña incorrectos»</td><td>Revise el número y si está activado Bloq Mayús. Tras 5 fallos, espere unos 15 minutos</td></tr>
          </tbody>
        </table></div>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Si aparece un error del sistema</b>, anote el <b>código</b> que muestra, por ejemplo E-00042, y envíelo al Área de Epidemiología junto con lo que estaba haciendo.</div></div>
      </section>

<?php require __DIR__ . '/_fin.php'; ?>
