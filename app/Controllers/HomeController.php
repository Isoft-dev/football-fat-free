<?php

declare(strict_types=1);

namespace App\Controllers;

use Base;

final class HomeController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'inicio.html', 'Torneo infantil de fútbol', 'inicio');
    }

    public function resumen(Base $f3): void
    {
        $db = $this->db($f3);

        $equipos = $db->exec(
            'SELECT COUNT(EQU_Equipo) AS total
               FROM TOR_EQUIPO
              ORDER BY total ASC'
        );
        $jornadas = $db->exec(
            'SELECT COUNT(JOR_Jornada) AS total
               FROM TOR_JORNADA
              ORDER BY total ASC'
        );
        $jugadores = $db->exec(
            'SELECT COUNT(JUG_Jugador) AS total
               FROM TOR_JUGADOR
              ORDER BY total ASC'
        );
        $goles = $db->exec(
            'SELECT COALESCE(SUM(GOL_Cantidad), 0) AS total
               FROM TOR_GOL
              ORDER BY total ASC'
        );
        $incidencias = $db->exec(
            'SELECT COUNT(INC_Incidencia) AS total
               FROM TOR_INCIDENCIA
              ORDER BY total ASC'
        );

        $this->json([
            'equipos' => (int) ($equipos[0]['total'] ?? 0),
            'jornadas' => (int) ($jornadas[0]['total'] ?? 0),
            'jugadores' => (int) ($jugadores[0]['total'] ?? 0),
            'goles' => (int) ($goles[0]['total'] ?? 0),
            'incidencias' => (int) ($incidencias[0]['total'] ?? 0),
        ]);
    }
}
