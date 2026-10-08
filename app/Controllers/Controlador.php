<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\RespuestaJson;
use Base;
use DB\SQL;
use InvalidArgumentException;

abstract class Controlador
{
    protected function db(Base $f3): SQL
    {
        if (!$f3->exists('DB')) {
            throw new \RuntimeException(
                (string) ($f3->get('DB_ERROR') ?: 'No hay conexión a la base de datos.')
            );
        }

        return $f3->get('DB');
    }

    protected function json(array $datos, int $codigo = 200): void
    {
        RespuestaJson::enviar($datos, $codigo);
    }

    protected function error(string $mensaje, int $codigo = 400): void
    {
        RespuestaJson::error($mensaje, $codigo);
    }

    protected function cuerpo(Base $f3): array
    {
        $crudo = (string) $f3->get('BODY');
        $json = json_decode($crudo, true);

        if (is_array($json)) {
            return $json;
        }

        $post = $f3->get('POST');

        return is_array($post) ? $post : [];
    }

    protected function entero(mixed $valor, string $campo): int
    {
        $entero = filter_var($valor, FILTER_VALIDATE_INT);

        if ($entero === false || $entero < 1) {
            throw new InvalidArgumentException("El campo {$campo} no es válido.");
        }

        return $entero;
    }

    protected function texto(mixed $valor, string $campo, int $maximo, bool $obligatorio = true): string
    {
        $texto = trim((string) $valor);

        if ($texto === '') {
            if ($obligatorio) {
                throw new InvalidArgumentException("El campo {$campo} es obligatorio.");
            }

            return '';
        }

        if (mb_strlen($texto) > $maximo) {
            throw new InvalidArgumentException("El campo {$campo} es demasiado largo.");
        }

        return $texto;
    }

    protected function nombreCompleto(array $fila): string
    {
        return trim(implode(' ', array_filter([
            $fila['JUG_Primer_Nombre'] ?? '',
            $fila['JUG_Segundo_Nombre'] ?? '',
            $fila['JUG_Primer_Apellido'] ?? '',
            $fila['JUG_Segundo_Apellido'] ?? '',
        ], static fn ($parte) => $parte !== null && $parte !== '')));
    }

    // Color de cada pantalla (menú activo y borde del formulario).
    private const ACENTOS = [
        'inicio' => '#ffd200',
        'equipos' => '#1b6b43',
        'jornadas' => '#7dd3fc',
        'jugadores' => '#86efac',
        'ficha' => '#86efac',
        'goles' => '#ffb020',
        'incidencias' => '#fb7185',
        'reporte-arbitro' => '#a5b4fc',
        'reporte-incidencias' => '#fda4af',
        'reporte-goleadores' => '#facc15',
    ];

    protected function mostrarVista(Base $f3, string $vista, string $titulo, string $pagina, array $extra = []): void
    {
        $f3->set('title', $titulo);
        $f3->set('page', $pagina);
        $f3->set('acento', self::ACENTOS[$pagina] ?? '#ffd200');

        foreach ($extra as $clave => $valor) {
            $f3->set($clave, $valor);
        }

        echo \Template::instance()->render($vista);
    }
}
