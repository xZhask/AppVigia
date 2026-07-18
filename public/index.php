<?php
require __DIR__ . '/../app/Core/Autoload.php';
require __DIR__ . '/../app/Core/ayudantes.php';

use App\Controllers\AuthController;
use App\Controllers\CasosController;
use App\Controllers\EnfermedadesController;
use App\Controllers\EstablecimientosController;
use App\Controllers\GradosController;
use App\Controllers\ImportacionController;
use App\Controllers\PanelController;
use App\Controllers\RedesController;
use App\Controllers\ReportesController;
use App\Controllers\UbigeoController;
use App\Controllers\UnidadesController;
use App\Controllers\UsuariosController;
use App\Core\Auth;
use App\Core\Router;
use App\Core\Session;

$config = require __DIR__ . '/../config/config.php';

error_reporting(E_ALL);
ini_set('display_errors', $config['app']['debug'] ? '1' : '0');

set_exception_handler(function (\Throwable $e): void {
    error_log('Excepción no capturada: ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
    }
    require __DIR__ . '/../app/Views/500.php';
});

Session::iniciar();

$rutaSolicitada = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$rutasPublicas = ['login'];

if (!in_array($rutaSolicitada, $rutasPublicas, true) && !Auth::estaAutenticado()) {
    header('Location: /login');
    exit;
}

$router = new Router();

// ---------- Autenticación ----------
$router->get('/login', function () {
    (new AuthController())->mostrarLogin();
});
$router->post('/login', function () {
    (new AuthController())->login();
});
$router->post('/logout', function () {
    (new AuthController())->logout();
});

// ---------- Operación ----------
$router->get('/', function () {
    (new PanelController())->index();
});

$router->get('/casos', function () {
    (new CasosController())->index();
});

$router->get('/casos/nuevo', function () {
    (new CasosController())->nuevo();
});

$router->post('/casos/nuevo', function () {
    (new CasosController())->crear();
});

$router->get('/casos/nuevo/secciones-clinicas', function () {
    (new CasosController())->seccionesClinicas();
});

$router->get('/casos/nuevo/paciente', function () {
    (new CasosController())->buscarPaciente();
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

$router->despachar($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
