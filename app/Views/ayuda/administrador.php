<?php
// Guía del administrador (2026-11-04). Sin datos reales.
$guiaTitulo = 'Guía del administrador';
$guiaDescripcion = 'Cómo gestionar usuarios, supervisar la notificación y validar fichas';
$guiaFecha = '04/11/2026';
$indice = [
    'alcance'      => ['Qué puede hacer', ['roles' => 'Roles del sistema']],
    'usuarios'     => ['Usuarios', ['usuario-nuevo' => 'Crear un usuario', 'clave-usuario' => 'Contraseña olvidada', 'cambios-usuario' => 'Cambiar o dar de baja']],
    'seguimiento'  => ['Seguimiento semanal', ['reabrir' => 'Reabrir una semana', 'recordatorios' => 'Recordatorios']],
    'validar'      => ['Validar y anular fichas', ['fichas-privadas' => 'Fichas privadas']],
    'panel-reportes' => ['Panel y reportes', []],
    'catalogos'    => ['Catálogos', []],
    'errores'      => ['Errores del sistema', []],
    'problemas-admin' => ['Problemas frecuentes', []],
];
require __DIR__ . '/_inicio.php';
?>
      <section class="guia-seccion" id="alcance" data-bloque>
        <h2>Qué puede hacer</h2>
        <p>Como administrador, usted trabaja con alcance nacional: ve las fichas y los reportes de todas las IPRESS, gestiona los usuarios y los catálogos, y supervisa la notificación semanal.</p>
        <p>También puede registrar fichas, siguiendo los pasos de la <a href="/ayuda">Guía del registrador</a>. La diferencia es que debe elegir el <b>Establecimiento</b> al que pertenece la ficha o la importación.</p>
        <div class="guia-nota"><?= $icoInfo ?><div>El módulo IAAS y sus <b>Parámetros IAAS</b> se explican en la <a href="/ayuda/iaas">Guía de IAAS</a>.</div></div>
        <div class="guia-sub" id="roles" data-bloque>
          <h3>Roles del sistema</h3>
          <div class="guia-tabla-marco"><table class="guia-tabla">
            <thead><tr><th>Rol</th><th>Alcance</th><th>Puede</th><th>No puede</th></tr></thead>
            <tbody>
              <tr><td><b>Registrador/a</b></td><td>Su establecimiento</td><td>Registrar y editar fichas abiertas, enviarlas a validación, cerrar la semana e importar</td><td>Ver otros establecimientos ni las fichas privadas que registraron otros</td></tr>
              <tr><td><b>Jefe/a de red</b></td><td>Su red</td><td>Ver Panel, el consolidado de Reportes y el seguimiento semanal, y enviar recordatorios a sus IPRESS</td><td>Abrir fichas, usar los formatos con datos individuales, registrar o reabrir semanas</td></tr>
              <tr><td><b>Administrador</b></td><td>Nacional</td><td>Todo lo anterior, además de validar, cerrar y anular fichas, reabrir semanas y gestionar catálogos y usuarios</td><td>Desactivar su propia cuenta</td></tr>
            </tbody>
          </table></div>
        </div>
      </section>

      <section class="guia-seccion" id="usuarios" data-bloque>
        <h2>Usuarios <?= $ir('/catalogos/usuarios', 'Abrir Usuarios') ?></h2>
        <p>Cada persona debe tener su propia cuenta: así la bitácora de cada ficha muestra quién hizo qué. En el menú, abra <b>Usuarios</b>; ahí puede buscar por nombre, documento o correo, y filtrar por rol, red o estado.</p>
        <?php $captura('admin-usuarios.jpg', 'Listado de Usuarios: avisos arriba, filtros y una fila con el usuario, su rol, su establecimiento y su último acceso', 'Los avisos de arriba filtran la lista con un clic.'); ?>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Aviso</th><th>Qué significa</th></tr></thead>
          <tbody>
            <tr><td><b>Nunca ingresaron</b></td><td>La cuenta está activa, pero la persona aún no ha entrado</td></tr>
            <tr><td><b>Sin acceso &gt; 90 días</b></td><td>Revise si la persona sigue en el puesto</td></tr>
            <tr><td><b>En establecimiento inactivo</b></td><td>Transfiera a esa persona o desactive su cuenta</td></tr>
          </tbody>
        </table></div>

        <div class="guia-sub" id="usuario-nuevo" data-bloque>
          <h3>Crear un usuario <?= $ir('/catalogos/usuarios/nuevo', 'Abrir') ?></h3>
          <ol class="guia-pasos">
            <li>Pulse <b>Nuevo usuario</b>. También puede usar <b>Asignar registrador</b> en Establecimientos, que deja elegido el establecimiento.</li>
            <li>Elija el <b>Tipo de documento</b> y escriba el <b>N.º de documento</b>. Ese número es con el que la persona ingresará.</li>
            <li>Elija el <b>Rol</b>. Para <b>Registrador/a</b>, elija su <b>Establecimiento</b>. Para <b>Jefe/a de red</b>, elija su <b>Red</b>.</li>
            <li>Escriba una <b>Contraseña</b> de al menos 8 caracteres. El <b>Correo electrónico</b> es opcional.</li>
            <li>Pulse <b>Registrar usuario</b>. Si el documento no aparece en RENIEC ni en el padrón, el sistema le pedirá también los apellidos y nombres.</li>
            <li>Entregue la contraseña de forma privada y pida a la persona que la cambie al primer ingreso, con el ícono de la llave.</li>
          </ol>
          <?php $captura('admin-usuario-nuevo.jpg', 'Formulario Nuevo usuario con el rol Jefe/a de red elegido y el campo Red', 'Con Jefe/a de red, el formulario pide la red en lugar del establecimiento.'); ?>
        </div>

        <div class="guia-sub" id="clave-usuario" data-bloque>
          <h3>Contraseña olvidada</h3>
          <p>Por ahora el sistema no envía el enlace de recuperación por correo, así que las contraseñas olvidadas pasan por usted.</p>
          <ol class="guia-pasos">
            <li>En <b>Usuarios</b>, abra el menú de la fila y pulse <b>Editar datos, rol o contraseña</b>.</li>
            <li>Escriba la contraseña nueva y pulse <b>Guardar cambios</b>. Si deja el campo en blanco, no cambia.</li>
            <li>Entréguela de forma privada y pida a la persona que la cambie al ingresar.</li>
          </ol>
          <p>Si alguien bloqueó su ingreso por 5 intentos fallidos, no hace falta cambiar nada: basta con esperar unos 15 minutos.</p>
        </div>

        <div class="guia-sub" id="cambios-usuario" data-bloque>
          <h3>Cambiar o dar de baja</h3>
          <div class="guia-tabla-marco"><table class="guia-tabla">
            <thead><tr><th>Situación</th><th>Qué usar</th><th>Qué ocurre</th></tr></thead>
            <tbody>
              <tr><td>Un registrador cambia de IPRESS</td><td><b>Transferir a otro establecimiento…</b></td><td>Conserva su cuenta. Las fichas que ya registró siguen en el establecimiento donde se notificaron</td></tr>
              <tr><td>Alguien deja el puesto</td><td><b>Desactivar…</b>, con el motivo</td><td>Ya no puede ingresar. Sus fichas se conservan y puede reactivarse después</td></tr>
              <tr><td>Cambia de rol o de red</td><td><b>Editar datos, rol o contraseña</b></td><td>El cambio rige de inmediato</td></tr>
            </tbody>
          </table></div>
          <div class="guia-alerta"><?= $icoAviso ?><div>Cambiar la contraseña, el rol, el establecimiento o la red de una cuenta, o desactivarla, cierra de inmediato las sesiones abiertas de esa persona. En su propia cuenta, su sesión actual continúa, salvo que cambie su rol: entonces deberá volver a ingresar.</div></div>
          <p>Antes de transferir o desactivar al único registrador de una IPRESS, el sistema le avisa que esa IPRESS quedará sin registrador activo.</p>
        </div>
      </section>

      <section class="guia-seccion" id="seguimiento" data-bloque>
        <h2>Seguimiento semanal <?= $ir('/notificacion-semanal', 'Abrir') ?></h2>
        <p>En <b>Notificación semanal</b> se ve qué IPRESS cerraron cada semana epidemiológica (SE). El número junto al menú indica cuántas faltan. Use las flechas de arriba para cambiar de semana, y los recuadros, el buscador y el filtro de red para acotar la lista.</p>
        <?php $captura('admin-notificacion.jpg', 'Notificación semanal del administrador: totales por estado y lista de establecimientos con su estado, fichas, registradores y botón Recordar', 'Lo que requiere atención aparece primero.'); ?>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Estado</th><th>Qué significa</th></tr></thead>
          <tbody>
            <tr><td><b>A tiempo</b></td><td>Cerró dentro del plazo</td></tr>
            <tr><td><b>Fuera de plazo</b></td><td>Cerró después del plazo, con un motivo</td></tr>
            <tr><td><b>Pendiente</b></td><td>No ha cerrado y el plazo sigue vigente</td></tr>
            <tr><td><b>No notificó</b></td><td>No ha cerrado y el plazo ya venció</td></tr>
          </tbody>
        </table></div>
        <p>La columna <b>Fichas</b> marca también las tardías, es decir, las registradas después del cierre.</p>

        <div class="guia-sub" id="reabrir" data-bloque>
          <h3>Reabrir una semana</h3>
          <p>Cuando un registrador pide reabrir, su fila muestra <b>Pide reabrir</b> con el motivo que escribió, y aparece arriba de la lista.</p>
          <ol class="guia-pasos">
            <li>Busque la IPRESS en la semana que corresponde.</li>
            <li>Pulse <b>Reabrir</b>, escriba el motivo y confirme.</li>
            <li>Avise al registrador: debe corregir lo necesario y volver a cerrar esa semana.</li>
          </ol>
        </div>

        <div class="guia-sub" id="recordatorios" data-bloque>
          <h3>Recordatorios</h3>
          <p><b>Recordar a…</b> avisa a todas las IPRESS que faltan, y <b>Recordar</b>, en una fila, solo a esa.</p>
          <div class="guia-alerta"><?= $icoAviso ?><div>Por ahora los recordatorios no se envían, porque falta configurar la cuenta de correo del sistema: la pantalla mostrará ese aviso. Mientras tanto, contacte a las IPRESS por otra vía. Cuando funcione, también hará falta que cada registrador tenga su correo registrado.</div></div>
        </div>
      </section>

      <section class="guia-seccion" id="validar" data-bloque>
        <h2>Validar y anular fichas <?= $ir('/casos?pestana=VALIDACION', 'Abrir En validación') ?></h2>
        <p>En <b>Fichas</b>, la pestaña <b>En validación</b> reúne lo que las IPRESS enviaron a revisar. El Panel muestra cuántas hay en <b>Pend. de validación</b>. Abra cada ficha y use sus botones.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Botón</th><th>Cuándo usarlo</th><th>Qué ocurre</th></tr></thead>
          <tbody>
            <tr><td><b>Cerrar ficha</b></td><td>La ficha está correcta</td><td>Pasa a Cerrada</td></tr>
            <tr><td><b>Devolver a abierta</b></td><td>Hay algo que corregir</td><td>El registrador puede volver a editarla. Avísele qué debe corregir, porque el sistema no se lo notifica</td></tr>
            <tr><td><b>Editar ficha</b></td><td>Prefiere corregirla usted</td><td>Puede editar fichas abiertas, en validación o cerradas</td></tr>
            <tr><td><b>Anular ficha</b></td><td>Se registró por error</td><td>Pide un motivo y la ficha deja de contar en los reportes</td></tr>
          </tbody>
        </table></div>
        <div class="guia-alerta"><?= $icoAviso ?><div><b>Anular no se puede deshacer</b> desde el sistema, y una ficha anulada ya no se puede editar. Una ficha <b>Cerrada</b> tampoco tiene botón para volver a abierta, aunque usted puede editarla.</div></div>
        <div class="guia-sub" id="fichas-privadas" data-bloque>
          <h3>Fichas privadas y datos sensibles</h3>
          <p>Las fichas de Z21, A50, B24 y violencia familiar solo las ve, entre los registradores, quien las registró. Usted puede abrirlas todas, y cada consulta queda anotada en la <b>Bitácora</b> de la ficha.</p>
          <p>La etnia del paciente solo se muestra al administrador; abrir una ficha que tenga datos sensibles también queda anotado. Abra estas fichas solo cuando su trabajo lo requiera.</p>
        </div>
      </section>

      <section class="guia-seccion" id="panel-reportes" data-bloque>
        <h2>Panel y reportes <?= $ir('/', 'Abrir Panel') ?></h2>
        <?php $captura('admin-panel.jpg', 'Panel de vigilancia: periodo, indicadores de fichas, enfermedades en vigilancia y curva epidemiológica', 'El periodo elegido arriba se aplica a todo el Panel.'); ?>
        <ul>
          <li><b>Panel:</b> muestra los indicadores nacionales, las enfermedades y su curva epidemiológica. Pulse una enfermedad para ver su curva.</li>
          <li><b>Corredor endémico:</b> aparece cuando hay al menos 2 años anteriores con datos. Mientras tanto, la enfermedad figura como <b>Sin historia</b>.</li>
          <li><b>Cumplimiento de notificación:</b> es un mapa por establecimiento y semana, que puede filtrar por red. Pulse una celda para abrir esa semana en Notificación semanal.</li>
          <li><b>Reportes:</b> funciona como se explica en la <a href="/ayuda#reportes">Guía del registrador</a>, con datos de todo el país. En los <b>Formatos</b> puede elegir el establecimiento.</li>
        </ul>
      </section>

      <section class="guia-seccion" id="catalogos" data-bloque>
        <h2>Catálogos</h2>
        <p>Los catálogos alimentan las listas de todo el sistema. Encontrará Redes de salud como pestaña de Establecimientos, y Grados PNP como pestaña de Usuarios.</p>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Catálogo</th><th>Para qué sirve</th><th>Tenga en cuenta</th></tr></thead>
          <tbody>
            <tr><td><a href="/catalogos/establecimientos"><b>Establecimientos</b></a></td><td>Las IPRESS, con su red, código RENIPRESS y categoría. Sus avisos señalan las que no tienen registrador, las de cumplimiento menor a 60 % y aquellas cuyo registrador nunca ingresó</td><td>Al desactivar uno, con motivo, deja de contar en la notificación semanal y en el cumplimiento. Sus fichas se conservan</td></tr>
            <tr><td><a href="/catalogos/redes"><b>Redes de salud</b></a></td><td>Agrupan los establecimientos y definen qué ve cada jefe de red</td><td>No se puede eliminar una red que tenga establecimientos. Cambiar la red de un establecimiento cambia lo que ven los jefes de ambas redes</td></tr>
            <tr><td><a href="/catalogos/enfermedades"><b>Enfermedades</b></a></td><td>Las enfermedades bajo vigilancia: nombre, CIE-10, tipo de notificación y grupo</td><td>Aquí no se cambian las preguntas de cada ficha: pídalo a soporte. Desactivar una enfermedad la quita de las opciones para registrar</td></tr>
            <tr><td><a href="/catalogos/grados"><b>Grados PNP</b></a></td><td>Los grados que se eligen en la condición Efectivo PNP</td><td>No se puede eliminar un grado que tenga personas registradas</td></tr>
          </tbody>
        </table></div>
      </section>

      <section class="guia-seccion" id="errores" data-bloque>
        <h2>Errores del sistema <?= $ir('/sistema/errores', 'Abrir Errores') ?></h2>
        <p>Cuando algo falla, el usuario ve un código, por ejemplo E-00042, y el sistema lo registra aquí. El número junto al menú <b>Errores</b> indica cuántos faltan resolver.</p>
        <ol class="guia-pasos">
          <li>Si una IPRESS le dicta un código, escríbalo en el buscador y pulse <b>Abrir</b>.</li>
          <li>Revise quién, dónde y cuándo ocurrió, y cuántas veces se repitió. Envíe el código a soporte.</li>
          <li>Cuando esté corregido, pulse <b>Marcar como resuelto</b>. Si vuelve a ocurrir, aparecerá otra vez como sin resolver.</li>
        </ol>
        <p>El registro no guarda lo que el usuario había escrito en el formulario, así que hay que preguntarle qué estaba haciendo.</p>
      </section>

      <section class="guia-seccion" id="problemas-admin" data-bloque>
        <h2>Problemas frecuentes</h2>
        <div class="guia-tabla-marco"><table class="guia-tabla">
          <thead><tr><th>Qué pasa</th><th>Qué hacer</th></tr></thead>
          <tbody>
            <tr><td>Un usuario no puede ingresar</td><td>Compruebe que su cuenta esté activa y que use el número de documento. Si no recuerda la contraseña, asígnele una nueva</td></tr>
            <tr><td>Un registrador no ve una ficha de su IPRESS</td><td>Si es Z21, A50, B24 o violencia familiar, solo la ve quien la registró</td></tr>
            <tr><td>«Ya existe un usuario con este número de documento»</td><td>Búsquelo en <b>Usuarios</b>, incluidos los <b>Inactivos</b>, y reactívelo si corresponde</td></tr>
            <tr><td>Una IPRESS aparece sin registrador</td><td>Use <b>Asignar registrador</b> en Establecimientos, o transfiera a alguien</td></tr>
            <tr><td>Una IPRESS cerrada ya no debe contar</td><td>Desactívela en Establecimientos, indicando el motivo</td></tr>
            <tr><td>Un jefe de red no ve datos</td><td>Revise en <b>Usuarios</b> que tenga su red asignada, y en Establecimientos que sus IPRESS pertenezcan a esa red</td></tr>
            <tr><td>Un niño quedó sin enlazar a su madre</td><td>Consúltelo con soporte, con los códigos de ambas fichas que le envíe la IPRESS</td></tr>
          </tbody>
        </table></div>
      </section>

<?php require __DIR__ . '/_fin.php'; ?>
