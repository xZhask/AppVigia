<?php
require __DIR__ . '/../app/Core/Autoload.php';
require __DIR__ . '/../app/Core/ayudantes.php';

use App\Controllers\AuthController;
use App\Controllers\CasosController;
use App\Controllers\CuestionariosController;
use App\Controllers\EnfermedadesController;
use App\Controllers\ErroresController;
use App\Controllers\EstablecimientosController;
use App\Controllers\GradosController;
use App\Controllers\IaasCasosController;
use App\Controllers\IaasExposicionController;
use App\Controllers\IaasIndicadoresController;
use App\Controllers\IaasParametrosController;
use App\Controllers\IaasRondaController;
use App\Controllers\ImportacionController;
use App\Controllers\NotificacionSemanalController;
use App\Controllers\PanelController;
use App\Controllers\RedesController;
use App\Controllers\ReniecController;
use App\Controllers\ReportesController;
use App\Controllers\UbigeoController;
use App\Controllers\UnidadesController;
use App\Controllers\UsuariosController;
use App\Core\Auth;
use App\Core\RegistroErrores;
use App\Core\Router;
use App\Core\Session;

$config = require __DIR__ . '/../config/config.php';

date_default_timezone_set($config['app']['timezone'] ?? 'America/Lima');

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');

// Cabecera por defecto para respuestas HTML; los controladores JSON/CSV
// la sobreescriben explícitamente (header() reemplaza por nombre).
header('Content-Type: text/html; charset=UTF-8');

// Excepciones, avisos y caídas graves quedan en error_registro con un código
// (E-00042) que se muestra al usuario; ver App\Core\RegistroErrores.
RegistroErrores::instalar();

Session::iniciar();

$rutaSolicitada = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$rutasPublicas = ['login', 'login/olvide', 'login/restablecer'];

if (!in_array($rutaSolicitada, $rutasPublicas, true) && !Auth::estaAutenticado()) {
    // Se guarda la URL que el usuario quería abrir para volver ahí después de
    // iniciar sesión. Solo en GET: no tiene sentido "repetir" un POST, y el
    // valor sale siempre de la ruta ya interpretada por este mismo router
    // (nunca de un parámetro externo), así que es una ruta interna por
    // construcción.
    $esAsset = preg_match('/\.(ico|png|jpg|jpeg|gif|css|js|map|woff|woff2|ttf|svg)$/i', $rutaSolicitada);
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $rutaSolicitada !== '' && !$esAsset) {
        $query = $_SERVER['QUERY_STRING'] ?? '';
        $_SESSION['_url_deseada'] = '/' . $rutaSolicitada . ($query !== '' ? '?' . $query : '');
    }
    header('Location: /login');
    exit;
}

if (Auth::estaAutenticado()) {
    $usuario = Auth::usuario();
    $esRutaPermitidaIncompleto = in_array($rutaSolicitada, ['perfil/completar', 'logout']) || str_starts_with($rutaSolicitada, 'api/reniec/');
    if ($usuario['perfil_incompleto'] && !$esRutaPermitidaIncompleto) {
        header('Location: /perfil/completar');
        exit;
    }
}

$router = new Router();

// ---------- Autenticación y Perfil ----------
$router->get('/login', function () {
    (new AuthController())->mostrarLogin();
});
$router->post('/login', function () {
    (new AuthController())->login();
});
$router->post('/logout', function () {
    (new AuthController())->logout();
});
$router->get('/login/olvide', function () {
    (new AuthController())->mostrarOlvide();
});
$router->post('/login/olvide', function () {
    (new AuthController())->procesarOlvide();
});
$router->get('/login/restablecer', function () {
    (new AuthController())->mostrarRestablecer();
});
$router->post('/login/restablecer', function () {
    (new AuthController())->procesarRestablecer();
});

$router->get('/perfil/completar', function () {
    (new \App\Controllers\PerfilController())->mostrarCompletar();
});
$router->post('/perfil/completar', function () {
    (new \App\Controllers\PerfilController())->procesarCompletar();
});

// ---------- Operación ----------
$router->get('/', function () {
    (new PanelController())->index();
});

// Notificación semanal (2026-10-17): cierre de cada SE por establecimiento.
$router->get('/notificacion-semanal', function () {
    (new NotificacionSemanalController())->index();
});
$router->post('/notificacion-semanal/cerrar', function () {
    (new NotificacionSemanalController())->cerrar();
});
$router->post('/notificacion-semanal/solicitar-reapertura', function () {
    (new NotificacionSemanalController())->solicitarReapertura();
});
$router->post('/notificacion-semanal/reabrir', function () {
    (new NotificacionSemanalController())->reabrir();
});
$router->post('/notificacion-semanal/recordar', function () {
    (new NotificacionSemanalController())->recordar();
});

$router->get('/casos', function () {
    (new CasosController())->index();
});

$router->get('/casos/exportar', function () {
    (new CasosController())->exportar();
});

$router->get('/casos/nuevo', function () {
    (new CasosController())->nuevo();
});

$router->post('/casos/nuevo', function () {
    (new CasosController())->crear();
});

$router->get('/casos/nuevo/paciente', function () {
    (new CasosController())->buscarPaciente();
});

$router->get('/casos/nuevo/titular', function () {
    (new CasosController())->buscarTitular();
});

$router->get('/casos/nuevo/vinculo', function () {
    (new CasosController())->buscarVinculo();
});

$router->get('/cie10/buscar', function () {
    (new CasosController())->buscarCie10();
});

$router->get('/casos/importar', function () {
    (new ImportacionController())->formulario();
});

$router->get('/casos/importar/plantilla', function () {
    (new ImportacionController())->plantilla();
});

$router->post('/casos/importar', function () {
    (new ImportacionController())->procesar();
});

$router->get('/casos/importar/lotes', function () {
    (new ImportacionController())->lotes();
});

$router->get('/casos/{id}/editar', function ($id) {
    (new CasosController())->editar($id);
});

$router->post('/casos/{id}/estado', function ($id) {
    (new CasosController())->cambiarEstado($id);
});

$router->post('/casos/{id}/anular', function ($id) {
    (new CasosController())->anular($id);
});

// Cuestionarios asociados a un caso (2026-10-11: Anexo 6.2 de ESAVI).
$router->get('/casos/{id}/cuestionario/{clave}', function ($id, $clave) {
    (new CuestionariosController())->mostrar($id, $clave);
});

$router->post('/casos/{id}/cuestionario/{clave}', function ($id, $clave) {
    (new CuestionariosController())->guardar($id, $clave);
});

$router->get('/casos/{id}', function ($id) {
    (new CasosController())->ver($id);
});

$router->post('/casos/{id}', function ($id) {
    (new CasosController())->actualizar($id);
});

$router->get('/reportes', function () {
    (new ReportesController())->index();
});

$router->get('/reportes/exportar', function () {
    (new ReportesController())->exportarExcel();
});

// Registro de búsqueda activa de gestantes con VIH y niños expuestos (pág. 15 del PDF, Z21)
$router->get('/reportes/busqueda-activa-vih', function () {
    (new ReportesController())->busquedaActivaVih();
});
$router->get('/reportes/busqueda-activa-vih/exportar', function () {
    (new ReportesController())->exportarBusquedaActivaVih();
});

// Ficha de notificación de muerte fetal y neonatal (pág. 28 del PDF, P96): listado semanal y semana sin casos
// Formatos del Área de Epidemiología PNP (2026-10-21).
$router->get('/reportes/registro-individual', function () {
    (new ReportesController())->registroIndividual(false);
});

$router->get('/reportes/registro-individual/exportar', function () {
    (new ReportesController())->exportarRegistroIndividual(false);
});

$router->get('/reportes/registro-individual-vih', function () {
    (new ReportesController())->registroIndividual(true);
});

$router->get('/reportes/registro-individual-vih/exportar', function () {
    (new ReportesController())->exportarRegistroIndividual(true);
});

// Menú "Registro de febriles": la hoja del día con el botón para registrar.
$router->get('/febriles', function () {
    (new ReportesController())->febriles(true);
});

$router->get('/reportes/consolidado-pnp', function () {
    (new ReportesController())->consolidadoPnp();
});

$router->get('/reportes/consolidado-pnp/exportar', function () {
    (new ReportesController())->exportarConsolidadoPnp();
});

$router->get('/reportes/febriles', function () {
    (new ReportesController())->febriles();
});

$router->get('/reportes/febriles/exportar', function () {
    (new ReportesController())->exportarFebriles();
});

$router->get('/reportes/muerte-fetal-neonatal', function () {
    (new ReportesController())->muerteFetalNeonatal();
});
$router->get('/reportes/muerte-fetal-neonatal/exportar', function () {
    (new ReportesController())->exportarMuerteFetalNeonatal();
});
$router->post('/reportes/muerte-fetal-neonatal/sin-casos', function () {
    (new ReportesController())->notificarSinCasosMuerteFetalNeonatal();
});
$router->post('/reportes/muerte-fetal-neonatal/sin-casos/anular', function () {
    (new ReportesController())->anularSinCasosMuerteFetalNeonatal();
});

// ---------- IAAS (2026-10-26): NTS N° 163-MINSA/2020/CDC, anexos 4 a 11 ----------
$router->get('/iaas', function () {
    (new IaasRondaController())->inicio();
});
$router->get('/iaas/servicios', function () {
    (new IaasRondaController())->servicios();
});
$router->post('/iaas/servicios', function () {
    (new IaasRondaController())->guardarServicios();
});
$router->get('/iaas/ronda', function () {
    (new IaasRondaController())->ronda();
});
$router->get('/iaas/paciente', function () {
    (new IaasRondaController())->buscarPersona();
});
foreach ([
    'marcar' => 'marcar', 'confirmar' => 'confirmar', 'completar' => 'completar', 'ingresar' => 'ingresar',
    'dispositivo' => 'agregarDispositivo', 'retirar' => 'retirar', 'egresar' => 'egresar', 'internacion' => 'editarInternacion',
    'iniciar-iaas' => 'iniciarIaas', 'fin-tratamiento' => 'finTratamiento', 'procedimientos' => 'procedimientos',
] as $accionRonda => $metodoRonda) {
    $router->post('/iaas/ronda/' . $accionRonda, function () use ($metodoRonda) {
        (new IaasRondaController())->$metodoRonda();
    });
}
$router->get('/iaas/casos', function () {
    (new IaasCasosController())->index();
});
$router->get('/iaas/casos/nuevo', function () {
    (new IaasCasosController())->nuevo();
});
$router->post('/iaas/casos', function () {
    (new IaasCasosController())->crear();
});
$router->post('/iaas/casos/{id}/anular', function ($id) {
    (new IaasCasosController())->anular($id);
});
$router->get('/iaas/casos/{id}', function ($id) {
    (new IaasCasosController())->ver($id);
});
$router->post('/iaas/casos/{id}', function ($id) {
    (new IaasCasosController())->actualizar($id);
});
$router->get('/iaas/exposicion', function () {
    (new IaasExposicionController())->index();
});
$router->get('/iaas/exposicion/nueva', function () {
    (new IaasExposicionController())->nueva();
});
$router->post('/iaas/exposicion', function () {
    (new IaasExposicionController())->crear();
});
$router->post('/iaas/exposicion/{id}/anular', function ($id) {
    (new IaasExposicionController())->anular($id);
});
$router->get('/iaas/exposicion/{id}', function ($id) {
    (new IaasExposicionController())->ver($id);
});
$router->post('/iaas/exposicion/{id}', function ($id) {
    (new IaasExposicionController())->actualizar($id);
});
$router->get('/iaas/indicadores', function () {
    (new IaasIndicadoresController())->index();
});
$router->post('/iaas/indicadores/cerrar', function () {
    (new IaasIndicadoresController())->cerrar();
});
$router->post('/iaas/indicadores/reabrir', function () {
    (new IaasIndicadoresController())->reabrir();
});
$router->post('/iaas/indicadores/personal', function () {
    (new IaasIndicadoresController())->guardarPersonal();
});

// ---------- Catálogo: parámetros IAAS ----------
$router->get('/catalogos/iaas', function () {
    (new IaasParametrosController())->index();
});
$router->post('/catalogos/iaas/horas', function () {
    (new IaasParametrosController())->guardarHoras();
});
$router->post('/catalogos/iaas/antimicrobiano', function () {
    (new IaasParametrosController())->guardarAntimicrobiano();
});
$router->post('/catalogos/iaas/microorganismo', function () {
    (new IaasParametrosController())->guardarMicroorganismo();
});

// ---------- Errores del sistema (2026-10-27) ----------
$router->get('/sistema/errores', function () {
    (new ErroresController())->index();
});
$router->get('/sistema/errores/{id}', function ($id) {
    (new ErroresController())->ver($id);
});
$router->post('/sistema/errores/{id}/resolver', function ($id) {
    (new ErroresController())->resolver($id);
});
$router->post('/errores/js', function () {
    (new ErroresController())->registrarJs();
});

// ---------- Catálogo: enfermedades ----------
$router->get('/catalogos/enfermedades', function () {
    (new EnfermedadesController())->index();
});
$router->get('/catalogos/enfermedades/nuevo', function () {
    (new EnfermedadesController())->nuevo();
});
$router->post('/catalogos/enfermedades', function () {
    (new EnfermedadesController())->crear();
});
$router->get('/catalogos/enfermedades/{id}/editar', function ($id) {
    (new EnfermedadesController())->editar($id);
});
$router->post('/catalogos/enfermedades/{id}', function ($id) {
    (new EnfermedadesController())->actualizar($id);
});
$router->post('/catalogos/enfermedades/{id}/alternar', function ($id) {
    (new EnfermedadesController())->alternarActivo($id);
});

// ---------- Catálogo: establecimientos ----------
$router->get('/catalogos/establecimientos', function () {
    (new EstablecimientosController())->index();
});
$router->get('/catalogos/establecimientos/nuevo', function () {
    (new EstablecimientosController())->nuevo();
});
$router->post('/catalogos/establecimientos', function () {
    (new EstablecimientosController())->crear();
});
$router->get('/catalogos/establecimientos/{id}/editar', function ($id) {
    (new EstablecimientosController())->editar($id);
});
$router->post('/catalogos/establecimientos/{id}', function ($id) {
    (new EstablecimientosController())->actualizar($id);
});
$router->post('/catalogos/establecimientos/{id}/alternar', function ($id) {
    (new EstablecimientosController())->alternarActivo($id);
});

// ---------- Catálogo: redes de salud ----------
$router->get('/catalogos/redes', function () {
    (new RedesController())->index();
});
$router->get('/catalogos/redes/nuevo', function () {
    (new RedesController())->nuevo();
});
$router->post('/catalogos/redes', function () {
    (new RedesController())->crear();
});
$router->get('/catalogos/redes/{id}/editar', function ($id) {
    (new RedesController())->editar($id);
});
$router->post('/catalogos/redes/{id}', function ($id) {
    (new RedesController())->actualizar($id);
});
$router->post('/catalogos/redes/{id}/eliminar', function ($id) {
    (new RedesController())->eliminar($id);
});

// ---------- Catálogo: usuarios ----------
$router->get('/catalogos/usuarios', function () {
    (new UsuariosController())->index();
});
$router->get('/catalogos/usuarios/nuevo', function () {
    (new UsuariosController())->nuevo();
});
$router->post('/catalogos/usuarios', function () {
    (new UsuariosController())->crear();
});
$router->get('/catalogos/usuarios/{id}/editar', function ($id) {
    (new UsuariosController())->editar($id);
});
$router->post('/catalogos/usuarios/{id}', function ($id) {
    (new UsuariosController())->actualizar($id);
});
$router->post('/catalogos/usuarios/{id}/alternar', function ($id) {
    (new UsuariosController())->alternarActivo($id);
});
$router->post('/catalogos/usuarios/{id}/transferir', function ($id) {
    (new UsuariosController())->transferir($id);
});

// ---------- Catálogo: grados PNP ----------
$router->get('/catalogos/grados', function () {
    (new GradosController())->index();
});
$router->get('/catalogos/grados/nuevo', function () {
    (new GradosController())->nuevo();
});
$router->post('/catalogos/grados', function () {
    (new GradosController())->crear();
});
$router->get('/catalogos/grados/{id}/editar', function ($id) {
    (new GradosController())->editar($id);
});
$router->post('/catalogos/grados/{id}', function ($id) {
    (new GradosController())->actualizar($id);
});
$router->post('/catalogos/grados/{id}/eliminar', function ($id) {
    (new GradosController())->eliminar($id);
});

// ---------- Catálogo: unidades PNP ----------
$router->get('/catalogos/unidades', function () {
    (new UnidadesController())->index();
});
$router->get('/catalogos/unidades/nuevo', function () {
    (new UnidadesController())->nuevo();
});
$router->post('/catalogos/unidades', function () {
    (new UnidadesController())->crear();
});
$router->get('/catalogos/unidades/{id}/editar', function ($id) {
    (new UnidadesController())->editar($id);
});
$router->post('/catalogos/unidades/{id}', function ($id) {
    (new UnidadesController())->actualizar($id);
});
$router->post('/catalogos/unidades/{id}/alternar', function ($id) {
    (new UnidadesController())->alternarActivo($id);
});

// ---------- API UBIGEO encadenada ----------
$router->get('/api/provincias', function () {
    (new UbigeoController())->provincias();
});
$router->get('/api/distritos', function () {
    (new UbigeoController())->distritos();
});

// ---------- API RENIEC (proxy del servidor; nunca directo desde el navegador) ----------
$router->get('/api/reniec/{dni}', function ($dni) {
    (new ReniecController())->buscar($dni);
});

$router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
