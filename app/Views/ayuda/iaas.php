<?php
// Guía del módulo IAAS (2026-11-04). Sin datos reales.
$guiaTitulo = 'Guía de IAAS';
$guiaDescripcion = 'Cómo llevar la vigilancia de infecciones asociadas a la atención de salud';
$guiaFecha = '04/11/2026';
$esAdminGuia = \App\Core\Auth::tieneRol('ADMIN');
$indice = [
    'iaas-inicio' => ['Qué es y cómo se organiza', []],
    'servicios'   => ['Indicar los servicios', []],
    'ronda'       => ['Ronda diaria', ['paciente-iaas' => 'Agregar un paciente', 'marcar' => 'Marcar el día', 'cambios-ronda' => 'Retiro, egreso e IAAS', 'partos' => 'Partos y cirugías', 'mes-iaas' => 'Vista del mes']],
    'casos-iaas'  => ['Casos IAAS', []],
    'exposicion'  => ['Exposición laboral', []],
    'indicadores' => ['Indicadores y cierre del mes', ['personal' => 'Personal de salud']],
];
if ($esAdminGuia) {
    $indice['admin-iaas'] = ['Para el administrador', []];
}
$indice['problemas-iaas'] = ['Problemas frecuentes', []];
require __DIR__ . '/_inicio.php';
?>
      <section class="guia-seccion" id="iaas-inicio" data-bloque>
        <h2>Qué es y cómo se organiza</h2>
        <p>El módulo IAAS lleva la vigilancia de las infecciones asociadas a la atención de salud, según la NTS N.° 163-MINSA/2020/CDC. Es independiente de las fichas de notificación: no aparece en <b>Fichas</b>, en la notificación semanal ni en <b>Reportes</b>.</p>
        <p>Cada IPRESS trabaja solo con sus datos. Los números del mes se transcriben al aplicativo del CDC u otros sistemas: VIGÍA no los reemplaza.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Pantalla</th><th>Formato de la norma</th><th>Cuándo se usa</th></tr></thead>
          <tbody>
            <tr><td><a href="#servicios"><b>Servicios vigilados</b></a></td><td>Anexo 2</td><td>Al empezar, y cuando cambien los servicios o las salas</td></tr>
            <tr><td><a href="#ronda"><b>Ronda diaria</b></a></td><td>Anexos 4, 6 y 7</td><td>Todos los días, por sala</td></tr>
            <tr><td><a href="#casos-iaas"><b>Casos IAAS</b></a></td><td>Anexo 5</td><td>Por cada infección</td></tr>
            <tr><td><a href="#exposicion"><b>Exposición laboral</b></a></td><td>Anexo 8</td><td>Por cada exposición del personal</td></tr>
            <tr><td><a href="#indicadores"><b>Indicadores</b></a></td><td>Anexos 9 y 10</td><td>Cada mes, para revisar, cerrar y reportar</td></tr>
          </tbody>
        </table></div>
        <div class="guia-nota"><?= $icoInfo ?><div>Si su IPRESS no tiene hospitalización, solo usará <a href="#exposicion">Exposición laboral</a> e <a href="#indicadores">Indicadores</a>.</div></div>
      </section>

      <section class="guia-seccion" id="servicios" data-bloque>
        <h2>Indicar los servicios <?= $ir('/iaas/servicios', 'Abrir') ?></h2>
        <p>La primera vez que entre al módulo, el sistema le pedirá qué servicios tiene su IPRESS. Con esa respuesta, la ronda muestra solo lo que la norma vigila en cada sala.</p>
        <ol class="guia-pasos">
          <li>Elija <b>Tiene servicios con hospitalización</b> o <b>No tiene hospitalización</b>.</li>
          <li>Si tiene, marque cada servicio y escriba sus <b>Salas</b>, una por línea.</li>
          <li>Pulse <b>Guardar servicios</b>. Puede cambiarlos cuando quiera, salvo quitar una sala que tenga pacientes sin egreso.</li>
        </ol>
        <?php $captura('iaas-servicios.jpg', 'Pantalla Servicios vigilados, con la pregunta sobre hospitalización y las tarjetas de servicios y salas', 'Cada tarjeta indica lo que se vigila en ese servicio.'); ?>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Servicio</th><th>Qué se vigila</th></tr></thead>
          <tbody>
            <tr><td><b>UCI adultos</b></td><td>CVC, NPT, catéter de hemodiálisis, CUP y ventilador</td></tr>
            <tr><td><b>UCI pediátrica</b></td><td>CVC, NPT, CUP y ventilador</td></tr>
            <tr><td><b>UCI neonatal</b></td><td>CVC, CVP, NPT y ventilador, por peso al nacer</td></tr>
            <tr><td><b>Neonatología</b></td><td>CVC y CVP, por peso al nacer</td></tr>
            <tr><td><b>Gineco-obstetricia</b></td><td>Partos vaginales y cesáreas</td></tr>
            <tr><td><b>Cirugía</b></td><td>Colecistectomías, hernioplastías y CUP</td></tr>
            <tr><td><b>Traumatología</b></td><td>Prótesis de cadera</td></tr>
            <tr><td><b>Medicina</b></td><td>CUP</td></tr>
          </tbody>
        </table></div>
        <p class="guia-tenue">Siglas: CVC, catéter venoso central · CVP, catéter venoso periférico · NPT, nutrición parenteral total · CUP, catéter urinario permanente.</p>
      </section>

      <section class="guia-seccion" id="ronda" data-bloque>
        <h2>Ronda diaria <?= $ir('/iaas/ronda', 'Abrir') ?></h2>
        <p>La ronda registra, día por día y sala por sala, qué pacientes tienen un dispositivo. Elija la sala en las pestañas de arriba; cada una indica cuántos dispositivos faltan marcar. Las flechas cambian el día.</p>
        <?php $captura('iaas-ronda.jpg', 'Ronda diaria de una sala: paciente ficticio con su cama y un dispositivo, y los botones para marcar, retirar e iniciar una IAAS', 'Ronda de un día, con datos ficticios.'); ?>

        <div class="guia-sub" id="paciente-iaas" data-bloque>
          <h3>Agregar un paciente</h3>
          <p>Agréguelo cuando le instalen un dispositivo vigilado.</p>
          <ol class="guia-pasos">
            <li>Pulse <b>Agregar paciente</b>.</li>
            <li>Elija el <b>Documento</b>, escriba el <b>Número</b> y pulse <b>Buscar</b>. Complete los datos que falten.</li>
            <li>Indique el <b>N.º de cama</b> y la fecha de <b>Ingreso al servicio</b>. En servicios neonatales, también el <b>Peso al nacer</b>, que es obligatorio.</li>
            <li>En <b>Dispositivos instalados</b>, marque los que tiene y desde cuándo.</li>
            <li>Pulse <b>Agregar a la ronda</b>.</li>
          </ol>
          <p>Un paciente está en una sola sala a la vez. Para pasarlo a otra, registre su egreso como <b>Transferido</b> y agréguelo en la nueva.</p>
        </div>

        <div class="guia-sub" id="marcar" data-bloque>
          <h3>Marcar el día</h3>
          <ul>
            <li><b>Si nada cambió en la sala:</b> pulse <b>Confirmar sala: todo sigue igual</b>.</li>
            <li><b>Dispositivo por dispositivo:</b> pulse <b>Sigue</b> en cada uno. Se marca al instante; púlselo otra vez para quitar la marca.</li>
            <li><b>Si quedaron días sin marcar:</b> aparece un aviso con la opción <b>Completar como «sigue igual»</b>. Úsela solo si realmente nada cambió; si no, pulse <b>Revisar en el mes</b>.</li>
          </ul>
          <div class="guia-tabla-marco"><table class="guia-tabla">
            <thead><tr><th>Marca</th><th>Qué significa</th></tr></thead>
            <tbody>
              <tr><td><b>✓</b></td><td>Expuesto al dispositivo: suma al total de días</td></tr>
              <tr><td><b>×</b></td><td>Expuesto con la IAAS en tratamiento: no suma</td></tr>
              <tr><td><b>→</b></td><td>Sin el dispositivo, pero sigue hospitalizado</td></tr>
              <tr><td><b>*</b></td><td>Continúa del mes anterior</td></tr>
              <tr><td><b>1, 2, 3</b></td><td>Instalación, retiro e inicio de la IAAS</td></tr>
            </tbody>
          </table></div>
          <p>El día de la instalación cuenta como exposición; el del retiro, no.</p>
        </div>

        <div class="guia-sub" id="cambios-ronda" data-bloque>
          <h3>Retiro, egreso e IAAS</h3>
          <div class="guia-tabla-marco"><table class="guia-tabla">
            <thead><tr><th>Qué ocurrió</th><th>Qué pulsar</th><th>Qué hace</th></tr></thead>
            <tbody>
              <tr><td>Le pusieron otro dispositivo</td><td><b>Dispositivo</b></td><td>Lo agrega desde su fecha de instalación</td></tr>
              <tr><td>Le retiraron un dispositivo</td><td><b>Retiro</b></td><td>Deja de sumar. Se puede deshacer con <b>Deshacer retiro</b></td></tr>
              <tr><td>Salió de la sala</td><td><b>Egreso</b>, con Vivo, Fallecido o Transferido</td><td>Retira los dispositivos que seguían instalados. Se puede deshacer con <b>Deshacer egreso</b></td></tr>
              <tr><td>Cambió la cama, la historia clínica o el diagnóstico</td><td><b>Editar</b></td><td>Actualiza los datos del paciente en la sala</td></tr>
              <tr><td>Presenta una infección</td><td><b>Inicia IAAS</b></td><td>Crea la ficha del Anexo 5, con el paciente y el dispositivo cargados. Desde esa fecha, sus días se marcan × y no suman</td></tr>
              <tr><td>Terminó el tratamiento</td><td><b>Fin del tratamiento</b></td><td>Desde el día siguiente, si el dispositivo sigue instalado, sus días vuelven a sumar</td></tr>
            </tbody>
          </table></div>
        </div>

        <div class="guia-sub" id="partos" data-bloque>
          <h3>Partos y cirugías</h3>
          <p>En gineco-obstetricia, cirugía y traumatología, la sala pide además cuántos procedimientos hubo.</p>
          <ol class="guia-pasos">
            <li>Escriba la cantidad de cada procedimiento del día.</li>
            <li>Si no hubo, deje 0 y guarde: así queda registrado que no hubo.</li>
            <li>Pulse <b>Guardar registro del día</b>.</li>
          </ol>
          <p>Las endometritis e infecciones de sitio quirúrgico (ISQ) no se escriben aquí: se cuentan desde las fichas IAAS, en el mes del procedimiento.</p>
        </div>

        <div class="guia-sub" id="mes-iaas" data-bloque>
          <h3>Vista del mes</h3>
          <p>Pulse <b>Mes</b>, arriba, para ver la grilla del Anexo 4 y, según la sala, la planilla de los Anexos 6 y 7. Puede corregir un día con un clic en su celda, y sacar el formato en papel con <b>Imprimir</b>.</p>
          <?php $captura('iaas-mes.jpg', 'Vista del mes de la ronda: grilla por días con las marcas de un paciente ficticio y la leyenda de símbolos', 'Grilla del Anexo 4 con datos ficticios.'); ?>
        </div>
      </section>

      <section class="guia-seccion" id="casos-iaas" data-bloque>
        <h2>Casos IAAS <?= $ir('/iaas/casos', 'Abrir') ?></h2>
        <p>Cada infección lleva una ficha, el Anexo 5. Los tipos de infección son: del torrente sanguíneo (ITS), del tracto urinario (ITU), neumonía (NEU), de sitio quirúrgico superficial o profunda (ISQ) y endometritis puerperal.</p>
        <ul>
          <li><b>Si la infección está asociada a un dispositivo de la ronda:</b> empiece con <b>Inicia IAAS</b> en la ronda, para que la ficha y la ronda queden unidas. Puede elegir <b>Registrar; completo la ficha después</b>.</li>
          <li><b>Si no:</b> en <b>Casos IAAS</b>, pulse <b>Nueva ficha IAAS</b>.</li>
        </ul>
        <ol class="guia-pasos">
          <li>Complete <b>I · Paciente</b> y <b>II · Infección</b>. Al elegir el tipo de IAAS aparecen sus criterios de caso.</li>
          <li>En <b>III · Factor de riesgo</b> aparece lo que corresponde al tipo: el dispositivo, la cirugía o el parto.</li>
          <li>En <b>IV · Agente aislado</b> registre hasta 2 cultivos, con su microorganismo y su perfil de sensibilidad.</li>
          <li>En <b>V · Seguimiento</b> indique el <b>Último día de tratamiento</b> cuando termine. Mientras esté vacío, los días del dispositivo siguen marcados × y no suman.</li>
          <li>Pulse <b>Guardar ficha</b>. Puede guardarla incompleta: el recuadro <b>Para completarla falta</b> dice qué pide la norma.</li>
        </ol>
        <p>La pestaña <b>Por completar</b> reúne las fichas pendientes, y el menú muestra cuántas hay. <b>Imprimir</b> saca la ficha en papel.</p>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Anular ficha</b> la quita de los indicadores y, si estaba unida a la ronda, sus días vuelven a sumar. Queda registrado quién la anuló.</div></div>
      </section>

      <section class="guia-seccion" id="exposicion" data-bloque>
        <h2>Exposición laboral <?= $ir('/iaas/exposicion', 'Abrir') ?></h2>
        <p>Registre aquí cada exposición del personal a sangre u otros fluidos, por ejemplo un accidente punzocortante. Se aplica a todos los servicios, también en las IPRESS sin hospitalización.</p>
        <ol class="guia-pasos">
          <li>Pulse <b>Registrar exposición</b>.</li>
          <li>En <b>La exposición</b>, elija el factor de riesgo, la fecha y los demás datos que pide.</li>
          <li>En <b>Persona expuesta</b>, escriba su DNI, historia clínica o un código autogenerado, su edad, sexo, profesión y condición. El formato no pide el nombre.</li>
          <li>En <b>Protección y seguimiento</b>, marque el equipo de protección que usaba y si recibió profilaxis, con el medicamento.</li>
          <li>Pulse <b>Guardar</b>.</li>
        </ol>
        <?php $captura('iaas-exposicion.jpg', 'Formulario de exposición laboral, con el factor de riesgo y los datos de la exposición', 'El formulario de la exposición laboral.'); ?>
        <p>Para corregir un registro, ábralo desde la lista. <b>Anular registro</b> lo retira, y <b>Ver anuladas</b> muestra los anulados.</p>
      </section>

      <section class="guia-seccion" id="indicadores" data-bloque>
        <h2>Indicadores y cierre del mes <?= $ir('/iaas/indicadores', 'Abrir') ?></h2>
        <p>Los indicadores del Anexo 10 se calculan solos con la ronda y las fichas. Por defecto se abre el mes anterior, que es el que toca revisar. Pulse un número para ver de dónde sale. <b>Imprimir</b> saca el reporte para transcribirlo.</p>
        <?php $captura('iaas-indicadores.jpg', 'Pantalla Indicadores: estado del mes y tabla de eventos vigilados con numerador, denominador y tasa', 'Un «—» en la tasa significa que ese mes no hubo denominador.'); ?>
        <h4>Cerrar el mes</h4>
        <ol class="guia-pasos">
          <li>Desde el día 1 del mes siguiente, abra <b>Revisión y cierre por sala</b>.</li>
          <li>Revise lo que falta: días sin marcar, días sin registrar partos o cirugías y fichas por completar. Cada pendiente enlaza a donde se corrige.</li>
          <li>Pulse <b>Cerrar</b> y el nombre del mes en cada sala. Si la sala aún tiene pendientes, el sistema le advierte antes de cerrar.</li>
          <li>Cuando todas las salas estén cerradas, aparece <b>Listo para reportar</b>: transcriba los números.</li>
        </ol>
        <div class="guia-alerta"><?= $icoAviso ?><div>Un mes cerrado ya no deja editar la ronda de esa sala. Solo el administrador puede reabrirlo, con un motivo.</div></div>
        <div class="guia-sub" id="personal" data-bloque>
          <h3>Personal de salud</h3>
          <p>El total del personal de la IPRESS, por condición, es el denominador de la exposición laboral (Anexo 9). Regístrelo una vez al año en <b>Personal de salud</b>, antes del plazo que indica la pantalla. Si cambia, puede actualizarlo; el indicador de todos los meses del año usa el último valor guardado.</p>
        </div>
      </section>

<?php if ($esAdminGuia): ?>
      <section class="guia-seccion" id="admin-iaas" data-bloque>
        <h2>Para el administrador</h2>
        <ul>
          <li><b>Elegir la IPRESS:</b> como cada IPRESS lleva su propia vigilancia, elija una en el selector de arriba. Todo lo que registre o consulte será de esa IPRESS.</li>
          <li><b>Reabrir un mes:</b> en <b>Revisión y cierre por sala</b>, pulse <b>Reabrir</b> e indique el motivo.</li>
          <li><b>Parámetros IAAS:</b> en <a href="/catalogos/iaas">Catálogos</a> están las horas t del índice de riesgo de ISQ, los antimicrobianos del antibiograma y los microorganismos. Se cargaron tal como están en la norma: ajústelos solo si el área lo indica. Cambiar una hora t recalcula el índice de todas las fichas al mostrarlas.</li>
        </ul>
      </section>
<?php endif; ?>

      <section class="guia-seccion" id="problemas-iaas" data-bloque>
        <h2>Problemas frecuentes</h2>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Qué pasa</th><th>Qué hacer</th></tr></thead>
          <tbody>
            <tr><td>El menú solo muestra Exposición laboral e Indicadores</td><td>Su IPRESS figura sin hospitalización. Si es un error, corríjalo en <a href="/iaas/servicios">Servicios vigilados</a></td></tr>
            <tr><td>No puedo quitar una sala</td><td>Tiene pacientes sin egreso. Registre primero su egreso</td></tr>
            <tr><td>No puedo cambiar la ronda de un día</td><td>Ese mes está cerrado. Pida al administrador que lo reabra</td></tr>
            <tr><td>Aparece «Faltan marcar» días</td><td>Revíselos en la vista del mes, o use <b>Completar como «sigue igual»</b> si nada cambió</td></tr>
            <tr><td>Una tasa aparece como «—»</td><td>Ese mes no hubo denominador, por ejemplo días con ese dispositivo</td></tr>
            <tr><td>Un paciente aparece en otra sala</td><td>Registre allí su egreso como Transferido y agréguelo en la nueva sala</td></tr>
          </tbody>
        </table></div>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Si aparece un error del sistema</b>, anote el <b>código</b> que muestra, por ejemplo E-00042, y envíelo al Área de Epidemiología junto con lo que estaba haciendo.</div></div>
      </section>

<?php require __DIR__ . '/_fin.php'; ?>
