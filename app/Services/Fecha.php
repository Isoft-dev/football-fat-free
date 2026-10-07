<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class Fecha
{
    public static function aIso(string $valor): string
    {
        $valor = trim($valor);

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $valor, $partes) === 1) {
            $dia = (int) $partes[1];
            $mes = (int) $partes[2];
            $anio = (int) $partes[3];
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $valor, $partes) === 1) {
            $anio = (int) $partes[1];
            $mes = (int) $partes[2];
            $dia = (int) $partes[3];
        } else {
            throw new InvalidArgumentException('La fecha debe tener el formato DD/MM/YYYY.');
        }

        if (!checkdate($mes, $dia, $anio)) {
            throw new InvalidArgumentException('La fecha no es válida.');
        }

        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }

    public static function noFutura(string $iso, string $campo = 'fecha'): string
    {
        if ($iso > date('Y-m-d')) {
            throw new InvalidArgumentException("La {$campo} no puede ser posterior a hoy.");
        }

        return $iso;
    }

    public static function aVista(?string $iso): string
    {
        if ($iso === null || $iso === '') {
            return '';
        }

        $fecha = substr($iso, 0, 10);
        $partes = explode('-', $fecha);

        if (count($partes) !== 3) {
            return $iso;
        }

        return $partes[2] . '/' . $partes[1] . '/' . $partes[0];
    }
}
