<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$f3 = Base::instance();

$f3->set('DEBUG', 0);
$f3->set('UI', dirname(__DIR__) . '/app/views/');
$f3->set('DB_ERROR', '');

$script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
if (str_ends_with(strtolower($script), '/index.php')) {
    $base = rtrim(substr($script, 0, -10), '/');
    $f3->set('BASE', $base);
}

try {
    App\Services\Conexion::abrir($f3);
} catch (Throwable $error) {
    $f3->set('DB_ERROR', $error->getMessage());
}

// Páginas HTML que pinta Fat-Free.
$f3->route('GET /', 'App\Controllers\HomeController->index');
$f3->route('GET /equipos', 'App\Controllers\EquipoController->index');
$f3->route('GET /jornadas', 'App\Controllers\JornadaController->index');
$f3->route('GET /jugadores', 'App\Controllers\JugadorController->index');
$f3->route('GET /jugadores/@id', 'App\Controllers\JugadorController->ficha');
$f3->route('GET /goles', 'App\Controllers\GolController->index');
$f3->route('GET /incidencias', 'App\Controllers\IncidenciaController->index');
$f3->route('GET /reportes/arbitro', 'App\Controllers\ReporteController->paginaArbitro');
$f3->route('GET /reportes/incidencias', 'App\Controllers\ReporteController->paginaIncidencias');
$f3->route('GET /reportes/goleadores', 'App\Controllers\ReporteController->paginaGoleadores');

// API JSON que consume el JavaScript puro.
$f3->route('GET /api/resumen', 'App\Controllers\HomeController->resumen');
$f3->route('GET /api/equipos', 'App\Controllers\EquipoController->listar');
$f3->route('POST /api/equipos', 'App\Controllers\EquipoController->crear');
$f3->route('POST /api/equipos/@id', 'App\Controllers\EquipoController->actualizar');
$f3->route('POST /api/equipos/@id/eliminar', 'App\Controllers\EquipoController->eliminar');

$f3->route('GET /api/jornadas', 'App\Controllers\JornadaController->listar');
$f3->route('POST /api/jornadas', 'App\Controllers\JornadaController->crear');
$f3->route('POST /api/jornadas/@id', 'App\Controllers\JornadaController->actualizar');
$f3->route('POST /api/jornadas/@id/eliminar', 'App\Controllers\JornadaController->eliminar');

$f3->route('GET /api/jugadores', 'App\Controllers\JugadorController->listar');
$f3->route('GET /api/jugadores/@id', 'App\Controllers\JugadorController->ver');
$f3->route('POST /api/jugadores', 'App\Controllers\JugadorController->crear');
$f3->route('POST /api/jugadores/@id', 'App\Controllers\JugadorController->actualizar');
$f3->route('POST /api/jugadores/@id/eliminar', 'App\Controllers\JugadorController->eliminar');

$f3->route('GET /api/goles', 'App\Controllers\GolController->listar');
$f3->route('POST /api/goles', 'App\Controllers\GolController->guardar');
$f3->route('POST /api/goles/@id/eliminar', 'App\Controllers\GolController->eliminar');

$f3->route('GET /api/incidencias', 'App\Controllers\IncidenciaController->listar');
$f3->route('POST /api/incidencias', 'App\Controllers\IncidenciaController->crear');
$f3->route('POST /api/incidencias/@id/eliminar', 'App\Controllers\IncidenciaController->eliminar');

$f3->route('GET /api/reportes/arbitro', 'App\Controllers\ReporteController->arbitro');
$f3->route('GET /api/reportes/incidencias', 'App\Controllers\ReporteController->incidencias');
$f3->route('GET /api/reportes/goleadores', 'App\Controllers\ReporteController->goleadores');

$f3->run();
