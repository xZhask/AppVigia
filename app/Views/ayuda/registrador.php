<?php
// Guía del registrador (2026-11-04). Sin datos reales.
$guiaTitulo = 'Guía del registrador';
$guiaDescripcion = 'Cómo registrar y notificar casos en VIGÍA SPNP';
$guiaFecha = '04/11/2026';
$indice = [
    'empezar'        => ['Antes de empezar', []],
    'ingresar'       => ['Ingresar al sistema', ['contrasena' => 'Contraseña']],
    'registrar'      => ['Registrar una ficha', ['condicion' => 'Condición del paciente', 'dengue' => 'Dengue y arbovirosis']],
    'buscar'         => ['Buscar y editar fichas', []],
    'especiales'     => ['Casos especiales', [
        'z21' => 'Gestante con VIH y niño (Z21)',
        'a50' => 'Sífilis materna y congénita (A50)',
        'o95' => 'Muerte materna (O95)',
        'y59' => 'ESAVI severo (Y59.0)',
        'p96' => 'Muerte fetal y neonatal (P96)',
        'y07-v99' => 'Violencia y accidentes (Y07, V99)',
        'b24' => 'VIH/SIDA (B24)',
    ]],
    'registros-pnp'  => ['Registros PNP', []],
    'notificacion'   => ['Notificación semanal', []],
    'importar'       => ['Importar desde Excel', []],
    'reportes'       => ['Reportes y descargas', []],
    'problemas'      => ['Problemas frecuentes', []],
];
require __DIR__ . '/_inicio.php';
?>

      <section class="guia-seccion" id="empezar" data-bloque>
        <h2>Antes de empezar</h2>
        <p>Esta guía explica cómo registrar y notificar casos en VIGÍA SPNP, el sistema de vigilancia epidemiológica. Está escrita para el personal de las IPRESS que tiene un usuario con rol de registrador.</p>
        <p>Como registrador, usted registra las fichas de su establecimiento y solo ve la información de ese establecimiento.</p>
        <p>Para empezar necesita:</p>
        <ul>
          <li>Conexión a internet y un navegador actualizado.</li>
          <li>Su usuario: su número de DNI y la contraseña que le entregue el Área de Epidemiología.</li>
        </ul>
        <div class="guia-nota">
          <?= $icoInfo ?>
          <div>Si la ficha que va a registrar es de gestante con VIH, sífilis, muerte materna, muerte fetal o neonatal, ESAVI, violencia familiar, accidente de tránsito o VIH/SIDA, lea también <a href="#especiales">Casos especiales</a>: esas fichas tienen un orden de registro propio.</div>
        </div>
        <p class="guia-tenue">El módulo IAAS (Ronda diaria, Casos IAAS, Exposición laboral e Indicadores) tiene su propia <a href="/ayuda/iaas">Guía de IAAS</a>.</p>
      </section>

      <section class="guia-seccion" id="ingresar" data-bloque>
        <h2>Ingresar al sistema</h2>
        <p>Se ingresa con el número de DNI y la contraseña, no con el correo.</p>
        <ol class="guia-pasos">
          <li>Abra la dirección del sistema que le indicó el Área de Epidemiología.</li>
          <li>Escriba su <b>DNI</b> y su <b>Contraseña</b>.</li>
          <li>Pulse <b>Ingresar</b>.</li>
        </ol>
        <?php $captura('ingreso.jpg', 'Pantalla de inicio de sesión de VIGÍA SPNP, con los campos DNI y Contraseña', 'Pantalla de ingreso.', 'guia-figura-media'); ?>
        <p>Al terminar, cierre la sesión con el ícono de salida, junto a su nombre, abajo en el menú lateral. Hágalo siempre en una computadora compartida.</p>
        <p><b>Si olvidó su contraseña:</b> pida al Área de Epidemiología que le asigne una nueva. Por ahora el sistema no envía el enlace por correo, aunque la pantalla <b>¿Olvidaste tu contraseña?</b> lo mencione.</p>
        <p><b>Si falla varias veces:</b> tras 5 intentos fallidos, el ingreso se bloquea por unos 15 minutos. Espere y vuelva a intentarlo.</p>
        <div class="guia-sub" id="contrasena" data-bloque>
          <h3>Cambiar su contraseña <?= $ir('/perfil/contrasena', 'Abrir') ?></h3>
          <p>Si la contraseña se la entregó el área, cámbiela la primera vez que ingrese.</p>
          <ol class="guia-pasos">
            <li>Pulse el ícono de la llave, junto a su nombre, al pie del menú lateral.</li>
            <li>Escriba la contraseña actual y dos veces la nueva. Debe tener al menos 8 caracteres y no puede ser su DNI.</li>
            <li>Pulse <b>Guardar contraseña</b>. Cualquier sesión suya abierta en otro equipo se cierra.</li>
          </ol>
          <?php $captura('contrasena.jpg', 'Pantalla Cambiar contraseña, con los tres campos; al pie del menú lateral, el ícono de la llave junto al nombre del usuario', 'El ícono de la llave está abajo a la izquierda, junto a su nombre.', 'guia-figura-media'); ?>
        </div>
      </section>

      <section class="guia-seccion" id="registrar" data-bloque>
        <h2>Registrar una ficha <?= $ir('/casos/nuevo', 'Abrir Nueva ficha') ?></h2>
        <p>Casi todas las fichas se registran igual, y el formulario sigue la ficha modelo MINSA de cada enfermedad.</p>
        <?php $captura('nueva-ficha.jpg', 'Formulario de Nueva ficha: arriba, la enfermedad y el botón Cambiar enfermedad; a la derecha, el Avance de la ficha y el botón Registrar ficha', 'Nueva ficha: arriba, la enfermedad; a la derecha, el avance y el botón para registrar.'); ?>
        <ol class="guia-pasos">
          <li>En el menú, pulse <b>Nueva ficha</b>.</li>
          <li>Si la enfermedad no es la correcta, pulse <b>Cambiar enfermedad</b> y búsquela por nombre o CIE-10. Hacerlo recarga el formulario: elija la enfermedad antes de escribir.</li>
          <li>Revise la <b>Fecha de notificación</b>. De ella sale la semana epidemiológica (SE) de la ficha.</li>
          <li>Escriba el <b>documento de identidad</b> del paciente. Con DNI, los apellidos y nombres se completan del padrón o de RENIEC; si la persona ya está registrada, se cargan sus datos.</li>
          <li>Elija la <a href="#condicion">condición del paciente</a> y complete sus datos.</li>
          <li>Llene las demás secciones. Los campos con asterisco (*) son obligatorios. El recuadro <b>Avance de la ficha</b>, a la derecha, muestra qué secciones faltan.</li>
          <li>Pulse <b>Registrar ficha</b> una sola vez. El botón cambia a «Guardando…» mientras se guarda: con la red lenta, espere.</li>
          <li>Revise el aviso con el código de la ficha, por ejemplo «Ficha registrada: F-00123». Luego el sistema abre un formulario en blanco de la misma enfermedad, para registrar otro caso.</li>
        </ol>
        <p><b>Si aparece «Posible duplicado»:</b> ya hay una ficha de la misma enfermedad con ese documento, de unos 30 días alrededor de la fecha. Revísela antes de continuar. El aviso no impide guardar.</p>
        <p><b>Si no se guarda:</b> el formulario vuelve a abrirse y marca en rojo lo que falta o está mal. Corrija esos campos y pulse <b>Registrar ficha</b> otra vez.</p>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Guardar borrador</b> aún no está disponible: una ficha sin terminar no se conserva.</div></div>

        <div class="guia-sub" id="condicion" data-bloque>
          <h3>Condición del paciente</h3>
          <div class="guia-tabla-marco"><table class="guia-tabla">
            <thead><tr><th>Condición</th><th>Cuándo usarla</th><th>Datos que pide</th></tr></thead>
            <tbody>
              <tr><td><b>Efectivo PNP</b></td><td>Titular de la Sanidad PNP</td><td>Grado, categoría, situación y CIP</td></tr>
              <tr><td><b>Personal en formación</b></td><td>Cadete o alumno</td><td>Tipo (cadete o alumno) y CIP</td></tr>
              <tr><td><b>Derechohabiente</b></td><td>Familiar de un efectivo</td><td>Vínculo y documento del titular (opcional)</td></tr>
              <tr><td><b>Particular</b></td><td>Sin vínculo con la PNP</td><td>Ninguno</td></tr>
            </tbody>
          </table></div>
          <?php $captura('condicion.png', 'Las cuatro tarjetas de condición del paciente, con Efectivo PNP elegida y sus datos: grado, categoría, situación y CIP', 'Al elegir una tarjeta aparecen los datos que pide.'); ?>
        </div>

        <div class="guia-sub" id="dengue" data-bloque>
          <h3>Dengue y arbovirosis</h3>
          <p>Elija primero <b>Enfermedad / evento a notificar</b>, en la franja de color de la sección Notificación. Es obligatorio y de él depende la clasificación de la ficha.</p>
          <?php $captura('dengue.png', 'Sección Notificación de la ficha de dengue, con el campo Enfermedad / evento a notificar resaltado en una franja de color', 'La franja de color desaparece al elegir la enfermedad.'); ?>
        </div>
      </section>

      <section class="guia-seccion" id="buscar" data-bloque>
        <h2>Buscar y editar fichas <?= $ir('/casos', 'Abrir Fichas') ?></h2>
        <p>Una ficha solo se puede editar mientras está <b>Abierta</b>, y solo desde su establecimiento.</p>
        <p>En el menú, <b>Fichas</b> lista lo registrado en su establecimiento. Busque por texto, filtre por enfermedad y periodo, y pulse una fila para abrir la ficha.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Pestaña</th><th>Qué significa</th><th>Qué puede hacer el registrador</th></tr></thead>
          <tbody>
            <tr><td><b>Abiertas</b></td><td>Recién registradas o en corrección</td><td>Ver, editar y enviar a validación</td></tr>
            <tr><td><b>En validación</b></td><td>Enviadas para revisión</td><td>Ver</td></tr>
            <tr><td><b>Cerradas</b></td><td>Revisadas y cerradas</td><td>Ver</td></tr>
            <tr><td><b>Anuladas</b></td><td>Dejaron de contar</td><td>Ver</td></tr>
            <tr><td><b>Todas</b></td><td>Todas las anteriores</td><td>Según el estado de cada ficha</td></tr>
          </tbody>
        </table></div>
        <ul>
          <li><b>Corregir una ficha:</b> ábrala, pulse <b>Editar ficha</b>, haga el cambio y guarde.</li>
          <li><b>Enviar a validación:</b> hágalo cuando la ficha esté completa, porque ya no podrá editarla. Si hay algo que corregir, el administrador puede devolverla a abierta.</li>
          <li><b>Anular una ficha:</b> solo lo hace el administrador. Si se registró por error, comuníquelo al Área de Epidemiología.</li>
          <li><b>Descargar:</b> <b>Exportar</b> baja en Excel las fichas de la lista, con los filtros aplicados.</li>
        </ul>
      </section>

      <section class="guia-seccion" id="especiales" data-bloque>
        <h2>Casos especiales</h2>
        <p>Estas fichas se registran distinto: primero compruebe a qué tipo pertenece la suya.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Ficha</th><th>Cómo se registra</th></tr></thead>
          <tbody>
            <tr><td><a href="#z21">Gestante con VIH y niño expuesto (Z21)</a></td><td>Dos fichas enlazadas: primero la madre, luego cada niño</td></tr>
            <tr><td><a href="#a50">Sífilis materna y congénita (A50)</a></td><td>Dos fichas enlazadas: primero la madre, luego cada producto de la gestación</td></tr>
            <tr><td><a href="#o95">Muerte materna (O95)</a></td><td>Una ficha en dos etapas: notificación y luego investigación</td></tr>
            <tr><td><a href="#y59">ESAVI severo (Y59.0)</a></td><td>Una ficha en dos etapas, más el cuestionario del Anexo 6.2</td></tr>
            <tr><td><a href="#p96">Muerte fetal y neonatal (P96)</a></td><td>Una ficha por cada defunción y un listado semanal</td></tr>
            <tr><td><a href="#y07-v99">Violencia familiar (Y07)</a></td><td>Una ficha con dos personas: agredida y agresora</td></tr>
            <tr><td><a href="#y07-v99">Accidentes de tránsito (V99)</a></td><td>Una ficha con dos personas: lesionado y conductor</td></tr>
            <tr><td><a href="#b24">VIH/SIDA (B24)</a></td><td>Una ficha por notificación; el formulario cambia según el motivo</td></tr>
          </tbody>
        </table></div>
        <div class="guia-nota"><?= $icoInfo ?><div><b>Fichas privadas.</b> Las de Z21, A50, B24 y violencia familiar solo las ve y edita, entre los registradores, quien las registró. Por eso, quien registró a la madre es quien debe registrar a sus niños.</div></div>

        <div class="guia-sub" id="z21" data-bloque>
          <h3>Gestante con VIH y niño expuesto (Z21)</h3>
          <p>Registre siempre primero a la madre: la ficha del niño se crea desde la de ella y queda enlazada.</p>
          <figure class="guia-figura guia-diagrama">
            <div class="guia-diagrama-marco">
              <svg viewBox="0 0 760 356" role="img" aria-label="Orden de registro: ficha de la madre, botón Registrar niño nacido expuesto, ficha del niño. Si faltan niños, vaya a Fichas, abra la ficha de la madre y repita." font-size="13">
                <defs><marker id="guia-flecha" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse"><path d="M0 0L10 5L0 10z" class="d-punta"/></marker></defs>
                <text x="24" y="28" font-size="15" font-weight="600" class="d-txt">La ficha de cada niño se abre desde la ficha de la madre</text>
                <g class="d-linea" fill="none" stroke-width="1.25">
                  <path d="M240 84H272" marker-end="url(#guia-flecha)"/>
                  <path d="M488 84H520" marker-end="url(#guia-flecha)"/>
                  <path d="M628 120V200" marker-end="url(#guia-flecha)"/>
                  <path d="M552 236H488" marker-end="url(#guia-flecha)"/>
                  <path d="M380 208V120" marker-end="url(#guia-flecha)"/>
                  <path d="M628 272V296" marker-end="url(#guia-flecha)"/>
                </g>
                <rect x="24" y="48" width="216" height="72" rx="8" class="d-principal"/>
                <text x="132" y="72" text-anchor="middle" font-weight="600" class="d-txt">1. Ficha de la madre</text>
                <text x="132" y="92" text-anchor="middle" font-size="11.5" class="d-txt">Tipo: Gestante con VIH</text>
                <text x="132" y="108" text-anchor="middle" font-size="11.5" class="d-txt">Incluya el N.º de nacidos vivos</text>
                <rect x="272" y="48" width="216" height="72" rx="8" class="d-caja"/>
                <text x="380" y="72" text-anchor="middle" font-weight="600" class="d-txt">2. Desde la ficha de la madre</text>
                <text x="380" y="92" text-anchor="middle" font-size="11.5" class="d-txt">Pulse Registrar niño</text>
                <text x="380" y="108" text-anchor="middle" font-size="11.5" class="d-txt">nacido expuesto</text>
                <rect x="520" y="48" width="216" height="72" rx="8" class="d-caja"/>
                <text x="628" y="72" text-anchor="middle" font-weight="600" class="d-txt">3. Ficha del niño</text>
                <text x="628" y="92" text-anchor="middle" font-size="11.5" class="d-txt">Llene los datos y pulse</text>
                <text x="628" y="108" text-anchor="middle" font-size="11.5" class="d-txt">Registrar ficha</text>
                <polygon points="552,236 628,200 704,236 628,272" class="d-caja"/>
                <text x="628" y="240" text-anchor="middle" class="d-txt">¿Faltan niños?</text>
                <rect x="272" y="208" width="216" height="56" rx="8" class="d-caja"/>
                <text x="380" y="232" text-anchor="middle" font-weight="600" class="d-txt">Vaya al menú Fichas</text>
                <text x="380" y="251" text-anchor="middle" font-size="11.5" class="d-txt">Abra la ficha de la madre</text>
                <rect x="520" y="296" width="216" height="44" rx="8" class="d-caja"/>
                <text x="628" y="322" text-anchor="middle" class="d-txt">Registro terminado</text>
                <text x="520" y="228" text-anchor="middle" font-size="11.5" class="d-tenue">sí</text>
                <text x="638" y="288" font-size="11.5" class="d-tenue">no</text>
              </svg>
            </div>
            <figcaption>Orden de registro en Z21: la ficha de la madre y la de cada niño.</figcaption>
          </figure>
          <h4>Primero, la ficha de la madre</h4>
          <ol class="guia-pasos">
            <li>En <b>Nueva ficha</b>, elija la enfermedad <b>Gestante VIH/Niño</b>.</li>
            <li>En <b>Tipo de registro</b>, elija <b>Gestante con VIH</b>.</li>
            <li>Llene sus datos, incluido el <b>N.º de nacidos vivos</b>.</li>
            <li>Pulse <b>Registrar ficha</b>. Se abre la ficha de la madre con el aviso de cuántos niños faltan, por ejemplo «falta registrar 2 de 2».</li>
          </ol>
          <?php $captura('fichas-vinculadas.png', 'Recuadro Fichas vinculadas de la ficha de la madre: niños por registrar, 2 de 2, y el botón Registrar niño nacido expuesto', 'En la ficha de la madre, el recuadro Fichas vinculadas muestra cuántos niños faltan.', 'guia-figura-media'); ?>
          <h4>Después, la ficha de cada niño</h4>
          <ol class="guia-pasos">
            <li>En la ficha de la madre, pulse <b>Registrar niño nacido expuesto</b>. El formulario se abre con ese tipo ya elegido y enlazado a la madre.</li>
            <li>Llene los datos del niño y pulse <b>Registrar ficha</b>.</li>
            <li>El sistema abre un formulario en blanco, sin vínculo con la madre: <b>no lo use para el siguiente niño</b>.</li>
            <li>Para otro niño, vaya a <b>Fichas</b>, abra la ficha de la madre y repita desde el paso 1.</li>
          </ol>
          <p>La ficha de la madre lista a los niños vinculados y cuántos faltan.</p>
          <p>Si la madre no tiene ficha en el sistema, registre al niño con <b>Sin vincular</b>.</p>
        </div>

        <div class="guia-sub" id="a50" data-bloque>
          <h3>Sífilis materna y congénita (A50)</h3>
          <p>El orden es el mismo, con una diferencia: al guardar a la madre, el sistema abre un formulario en blanco, no su ficha.</p>
          <ol class="guia-pasos">
            <li>En <b>Nueva ficha</b>, elija <b>Sífilis materna y congénita</b>.</li>
            <li>En <b>Investigación de</b>, elija <b>Sífilis materna</b>, llene los datos y pulse <b>Registrar ficha</b>.</li>
            <li>Vaya a <b>Fichas</b> y abra la ficha de la madre.</li>
            <li>Pulse <b>Registrar producto de la gestación</b>. El formulario se abre con <b>Sífilis congénita</b> ya elegida; los nombres y el DNI de la madre se toman de su ficha.</li>
            <li>Llene los datos del niño, mortinato o aborto y pulse <b>Registrar ficha</b>.</li>
            <li>Si hay otro producto, repita desde el paso 3.</li>
          </ol>
        </div>

        <div class="guia-sub" id="o95" data-bloque>
          <h3>Muerte materna (O95)</h3>
          <p>Es una sola ficha que se completa en dos momentos. No se registra una ficha nueva para la investigación.</p>
          <ol class="guia-pasos">
            <li><b>Notificación:</b> en <b>Etapa de la ficha</b>, deje <b>Anexo 1: Notificación inmediata</b>. Llene los datos y pulse <b>Registrar ficha</b>.</li>
            <li><b>Investigación:</b> cuando la tenga, abra la misma ficha en <b>Fichas</b> y pulse <b>Editar ficha</b>.</li>
            <li>Cambie la etapa a <b>Anexo 2: Investigación epidemiológica</b>. Aparecen las secciones de la investigación; llénelas y guarde.</li>
          </ol>
          <div class="guia-alerta"><?= $icoAviso ?><div>No envíe la ficha a validación antes de terminar la investigación: ya no podría editarla.</div></div>
        </div>

        <div class="guia-sub" id="y59" data-bloque>
          <h3>ESAVI severo (Y59.0)</h3>
          <p>Funciona igual que muerte materna, en una sola ficha.</p>
          <ol class="guia-pasos">
            <li>En <b>Etapa de la ficha</b>, elija <b>Notificación inmediata (Anexo 01)</b>, llene los datos y registre.</li>
            <li>Luego abra la misma ficha, pulse <b>Editar ficha</b>, cambie a <b>Investigación completa</b> y llene las nuevas secciones.</li>
            <li>Con la ficha guardada, complete el <b>Anexo 6.2</b> desde la sección <b>Cuestionarios</b> de la ficha.</li>
          </ol>
        </div>

        <div class="guia-sub" id="p96" data-bloque>
          <h3>Muerte fetal y neonatal (P96)</h3>
          <p>Cada defunción es una ficha; el listado semanal se arma solo con ellas.</p>
          <ol class="guia-pasos">
            <li>En <b>Nueva ficha</b>, elija <b>Muerte Fetal/Neonatal</b> y llene los datos del fallecido. Si no tiene documento, marque <b>Sin documento de identidad</b>.</li>
            <li>Si hay más defunciones, pulse <b>Registrar y agregar otra</b>: conserva el establecimiento y la fecha de notificación. En la última, pulse <b>Registrar ficha</b>.</li>
            <li>Para ver el listado, vaya a <b>Reportes</b>, pestaña <b>Formatos</b>, y abra el de <b>P96</b>.</li>
            <li>Si esa semana no hubo casos, pulse ahí <b>Notificar semana sin casos</b>.</li>
          </ol>
          <p>Esta ficha no se enlaza con la de muerte materna. Si fallecen la madre y el feto, registre ambas por separado.</p>
        </div>

        <div class="guia-sub" id="y07-v99" data-bloque>
          <h3>Violencia familiar (Y07) y accidentes de tránsito (V99)</h3>
          <p>Las dos personas se registran en la misma ficha, no en dos.</p>
          <ul>
            <li><b>Violencia familiar:</b> complete <b>Datos de la persona agredida</b> y <b>Datos de la persona agresora</b>.</li>
            <li><b>Accidentes de tránsito:</b> complete <b>Datos del lesionado</b> y <b>Datos del conductor</b>.</li>
          </ul>
        </div>

        <div class="guia-sub" id="b24" data-bloque>
          <h3>VIH/SIDA (B24)</h3>
          <p>Elija primero el <b>Motivo de notificación</b>, porque de él dependen las secciones que aparecen: <b>Infección por VIH</b>, <b>Niño nacido expuesto al VIH</b> o <b>Niño nacido expuesto, no infectado por VIH</b>.</p>
        </div>
      </section>

      <section class="guia-seccion" id="registros-pnp" data-bloque>
        <h2>Registros PNP <?= $ir('/febriles', 'Abrir Registro de febriles') ?></h2>
        <p>Los registros PNP son formatos del Área de Epidemiología PNP, no fichas MINSA: <b>Febriles</b> y <b>Otras enfermedades</b>.</p>
        <h4>Registro de febriles</h4>
        <ol class="guia-pasos">
          <li>En el menú, pulse <b>Registro de febriles</b>. Verá los febriles del día.</li>
          <li>Pulse <b>Registrar febril</b>, llene el paciente, el servicio, la temperatura y el diagnóstico CIE-10.</li>
          <li>Pulse <b>Registrar ficha</b>. Vuelve a la hoja del día, donde aparece el registro.</li>
        </ol>
        <p><b>Día sin febriles.</b> Si un día no hubo febriles, abra ese día en la hoja y pulse <b>Hoy no hubo febriles</b> (o <b>Notificar día sin febriles</b>, si es un día anterior). Los días de la semana muestran un punto: verde con febriles, gris sin febriles notificado y naranja pendiente.</p>
        <ul>
          <li>Si luego registra un febril en ese día, la notificación sin febriles se anula sola.</li>
          <li>Para anularla a mano, pulse <b>Anular</b> en el mismo recuadro. Solo puede hacerlo quien la notificó o un administrador.</li>
          <li>Al cerrar la semana, <b>Notificación semanal</b> avisa qué días quedaron pendientes. Es un aviso: puede cerrar igual.</li>
        </ul>
        <p><b>Otras enfermedades:</b> en <b>Nueva ficha</b>, pulse <b>Cambiar enfermedad</b> y elija <b>Otras enfermedades</b> en el grupo «Registro PNP».</p>
        <p><b>Pasar a ficha MINSA.</b> Si el diagnóstico tiene ficha MINSA (por ejemplo, un febril con dengue), al guardar se abre el registro con un aviso. Pulse <b>Pasar a ficha MINSA</b> y llene la ficha; ambos quedan relacionados.</p>
      </section>

      <section class="guia-seccion" id="notificacion" data-bloque>
        <h2>Notificación semanal <?= $ir('/notificacion-semanal', 'Abrir') ?></h2>
        <p>Cada establecimiento cierra cada semana epidemiológica (SE), aunque no haya tenido casos. La SE va de domingo a sábado, y el plazo que muestra la pantalla suele vencer el lunes siguiente a las 12:00.</p>
        <ol class="guia-pasos">
          <li>En el menú, pulse <b>Notificación semanal</b>. El indicador <b>Pendiente</b> avisa que falta cerrar.</li>
          <li>Revise el resumen por enfermedad y el número de fichas. Si falta un caso, regístrelo con una fecha de notificación de esa SE.</li>
          <li>En <b>Cerrar la semana</b>, marque <b>Confirmo que no hay más casos por notificar</b>.</li>
          <li>Pulse el botón <b>Cerrar SE</b> con el número de la semana.</li>
        </ol>
        <?php $captura('notificacion.jpg', 'Notificación semanal: aviso del plazo, resumen por enfermedad y, a la derecha, el recuadro Cerrar la semana con su confirmación', 'A la derecha, el recuadro Cerrar la semana.'); ?>
        <p>Si cierra antes del sábado, lo que registre hasta ese día contará como tardío.</p>
        <p>Al cerrar, las enfermedades sin fichas quedan notificadas como <b>sin casos</b>.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Situación</th><th>Qué pasa</th><th>Qué hacer</th></tr></thead>
          <tbody>
            <tr><td>Venció el plazo sin cerrar</td><td>Su establecimiento figura como <b>no notificó</b> esa semana</td><td>Ciérrela igual, indicando el motivo del cierre tardío</td></tr>
            <tr><td>Registra una ficha de una SE ya cerrada</td><td>La ficha se marca como <b>notificación tardía</b></td><td>Nada más: queda registrada</td></tr>
            <tr><td>Hay que reabrir una SE cerrada</td><td>Solo el administrador puede reabrirla</td><td>Pulse <b>Solicitar reapertura</b> y escriba el motivo</td></tr>
          </tbody>
        </table></div>
        <p>Mientras no se atienda la solicitud, la SE sigue cerrada.</p>
      </section>

      <section class="guia-seccion" id="importar" data-bloque>
        <h2>Importar fichas desde Excel <?= $ir('/casos/importar', 'Abrir') ?></h2>
        <p>Importar sirve para cargar varios casos de una misma enfermedad desde una planilla, sin digitarlos uno por uno.</p>
        <ol class="guia-pasos">
          <li>En <b>Fichas</b>, pulse <b>Importar Excel</b> y elija la enfermedad.</li>
          <li>Pulse <b>Descargar plantilla (.xlsx)</b>. La pantalla lista cada columna, su formato y si es obligatoria.</li>
          <li>Llene sus datos <b>desde la fila 3</b>. La fila 1 son los encabezados y la fila 2 es un ejemplo que siempre se ignora.</li>
          <li>Vuelva a la pantalla, seleccione el archivo (.xlsx o .csv) y pulse <b>Importar</b>.</li>
          <li>Revise el <b>Resultado de la importación</b>: las fichas importadas y las filas con error, con su motivo.</li>
        </ol>
        <p>Corrija las filas con error en su archivo y súbalas de nuevo. Quite las que ya se importaron: una fila con el mismo documento y enfermedad, a menos de 30 días, se rechaza como posible duplicado.</p>
        <div class="guia-nota"><?= $icoInfo ?><div>La plantilla no incluye las tablas ni los grupos de preguntas Sí/No de la ficha: complételos después, editando cada ficha importada.</div></div>
      </section>

      <section class="guia-seccion" id="reportes" data-bloque>
        <h2>Reportes y descargas <?= $ir('/reportes', 'Abrir') ?></h2>
        <p><b>Reportes</b> tiene dos pestañas: <b>Consolidado</b>, para armar sus propios cuadros, y <b>Formatos</b>, con los documentos ya diseñados. Como registrador, verá solo los datos de su establecimiento.</p>
        <h4>Consolidado</h4>
        <ol class="guia-pasos">
          <li>Elija la <b>Enfermedad</b>, las <b>Filas</b>, las <b>Columnas</b> y el <b>Periodo</b> (4 SE, 12 SE, Año o Rango). El cuadro se actualiza solo.</li>
          <li>Pulse <b>Tabla</b> o <b>Gráfico</b> para cambiar la vista.</li>
          <li>Pulse <b>Excel</b> o <b>PDF</b> para descargar o imprimir.</li>
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
        <p>Si no ve la opción <b>Dona</b>, normalmente es porque todos los casos caen en una sola categoría.</p>
        <h4>Formatos</h4>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Formato</th><th>Qué contiene</th></tr></thead>
          <tbody>
            <tr><td><b>Z21 · Planilla</b></td><td>Fichas de gestante con VIH y niño expuesto del periodo</td></tr>
            <tr><td><b>P96 · Listado semanal</b></td><td>Muerte fetal y neonatal, y la notificación de semana sin casos</td></tr>
            <tr><td><b>PNP · Registro semanal</b></td><td>Fichas MINSA y registros de otras enfermedades de la semana</td></tr>
            <tr><td><b>PNP · VIH/SIDA</b></td><td>El mismo registro para VIH/SIDA y Z21, con iniciales del paciente</td></tr>
            <tr><td><b>PNP · Consolidado semanal</b></td><td>EDA, cólera, IRA, neumonía, SOB y asma, contados de los casos de la semana</td></tr>
            <tr><td><b>PNP · Registro diario</b></td><td>Pacientes febriles, por día o por semana</td></tr>
          </tbody>
        </table></div>
        <p>Al pulsar <b>Excel</b>, el botón muestra «Generando…» hasta que llega el archivo.</p>
      </section>

      <section class="guia-seccion" id="problemas" data-bloque>
        <h2>Problemas frecuentes</h2>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Qué pasa</th><th>Qué hacer</th></tr></thead>
          <tbody>
            <tr><td>El sistema tarda en responder</td><td>Espere a que termine la barra verde de arriba. No pulse otra vez ni recargue</td></tr>
            <tr><td>«DNI o contraseña incorrectos»</td><td>Revise el número y si está activado Bloq Mayús. Tras 5 fallos, espere unos 15 minutos</td></tr>
            <tr><td>El sistema cerró mi sesión</td><td>Cambió su contraseña o los datos de su cuenta, o se equivocó 5 veces en la contraseña actual al querer cambiarla. Ingrese de nuevo; si no puede, avise al Área de Epidemiología</td></tr>
            <tr><td>Mi ficha no se guarda</td><td>Busque los campos marcados en rojo, corríjalos y registre otra vez</td></tr>
            <tr><td>No puedo editar una ficha</td><td>Revise su estado: solo se edita si está Abierta. Si está en validación, pida que se la devuelvan</td></tr>
            <tr><td>No encuentro una ficha</td><td>Abra la pestaña <b>Todas</b> y revise el periodo del filtro</td></tr>
            <tr><td>Registré al niño sin enlazarlo a la madre</td><td>Comuníquelo al Área de Epidemiología con los códigos de ambas fichas</td></tr>
            <tr><td>Veo una versión antigua de la pantalla</td><td>Recargue la página con Ctrl+F5</td></tr>
          </tbody>
        </table></div>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Si aparece un error del sistema</b>, anote el <b>código</b> que muestra, por ejemplo E-00042, y envíelo al Área de Epidemiología junto con lo que estaba haciendo. No envíe datos de pacientes por correo ni por mensajes: con el código de la ficha basta.</div></div>
      </section>

<?php require __DIR__ . '/_fin.php'; ?>
