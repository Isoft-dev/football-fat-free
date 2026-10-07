<?php

declare(strict_types=1);

namespace App\Services;

final class Suspension
{
    // Roja: el jugador no juega si la fecha de la jornada cae entre la incidencia y la suspensión (inclusive).
    public static function estaSuspendido(
        string $fechaJuegoIso,
        array $incidencias
    ): bool {
        foreach ($incidencias as $incidencia) {
            $tipo = (string) ($incidencia['INC_Tipo_Tarjeta'] ?? '');
            $suspension = $incidencia['INC_Fecha_Suspension'] ?? null;
            $incidente = (string) ($incidencia['INC_Fecha_Incidencia'] ?? '');

            if ($tipo !== 'roja' || $suspension === null || $suspension === '') {
                continue;
            }

            $desde = substr($incidente, 0, 10);
            $hasta = substr((string) $suspension, 0, 10);
            $juego = substr($fechaJuegoIso, 0, 10);

            if ($juego >= $desde && $juego <= $hasta) {
                return true;
            }
        }

        return false;
    }
}
