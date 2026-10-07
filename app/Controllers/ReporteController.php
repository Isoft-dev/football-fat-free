<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Fecha;
use App\Services\Suspension;
use Base;
use InvalidArgumentException;

final class ReporteController extends Controlador
{
    public function paginaArbitro(Base $f3): void
    {
        $this->mostrarVista($f3, 'reportes-arbitro.html', 'Lista para el árbitro', 'reporte-arbitro');
    }

    public function paginaIncidencias(Base $f3): void
    {
        $this->mostrarVista($f3, 'reportes-incidencias.html', 'Reporte de incidencias', 'reporte-incidencias');
    }

    public function paginaGoleadores(Base $f3): void
    {
        $this->mostrarVista($f3, 'reportes-goleadores.html', 'Tabla de goleadores', 'reporte-goleadores');
    }

    public function arbitro(Base $f3): void
    {
        try {
            $jornadaId = $this->entero($f3->get('GET.jornada'), 'jornada');
            $db = $this->db($f3);

            $jornada = $db->exec(
                'SELECT JOR_Jornada,
                        JOR_Numero,
                        JOR_Fecha_Juego
                   FROM TOR_JORNADA
                  WHERE JOR_Jornada = ?
                  ORDER BY JOR_Jornada ASC',
                [$jornadaId]
            );

            if ($jornada === []) {
                $this->error('La jornada no existe.', 404);
                return;
            }

            $jugadores = $db->exec(
                'SELECT JUG.JUG_Jugador,
                        JUG.JUG_Primer_Nombre,
                        JUG.JUG_Segundo_Nombre,
                        JUG.JUG_Primer_Apellido,
                        JUG.JUG_Segundo_Apellido,
                        JUG.JUG_Fotografia,
                        JUG.JUG_Fecha_Nacimiento,
                        EQU.EQU_Equipo,
                        EQU.EQU_Nombre
                   FROM TOR_JUGADOR JUG
             INNER JOIN TOR_EQUIPO EQU
                     ON EQU.EQU_Equipo = JUG.EQU_Equipo
                  ORDER BY EQU.EQU_Nombre ASC,
                        JUG.JUG_Primer_Apellido ASC,
                        JUG.JUG_Primer_Nombre ASC'
            );

            $incidencias = $db->exec(
                'SELECT INC.JUG_Jugador,
                        TTA.TTA_Codigo AS INC_Tipo_Tarjeta,
                        INC.INC_Fecha_Incidencia,
                        INC.INC_Fecha_Suspension
                   FROM TOR_INCIDENCIA INC
             INNER JOIN TOR_TIPO_TARJETA TTA
                     ON TTA.TTA_Tipo_Tarjeta = INC.TTA_Tipo_Tarjeta
                  ORDER BY INC.JUG_Jugador ASC,
                        INC.INC_Incidencia ASC'
            );

            $porJugador = [];

            foreach ($incidencias as $incidencia) {
                $porJugador[(int) $incidencia['JUG_Jugador']][] = $incidencia;
            }

            $fechaJuego = (string) $jornada[0]['JOR_Fecha_Juego'];
            $equipos = [];

            foreach ($jugadores as $jugador) {
                $equipoId = (int) $jugador['EQU_Equipo'];

                if (!isset($equipos[$equipoId])) {
                    $equipos[$equipoId] = [
                        'EQU_Equipo' => $equipoId,
                        'EQU_Nombre' => $jugador['EQU_Nombre'],
                        'jugadores' => [],
                    ];
                }

                $id = (int) $jugador['JUG_Jugador'];
                $equipos[$equipoId]['jugadores'][] = [
                    'JUG_Jugador' => $id,
                    'JUG_Nombre_Completo' => $this->nombreCompleto($jugador),
                    'JUG_Fotografia' => $jugador['JUG_Fotografia'],
                    'JUG_Fecha_Nacimiento_Vista' => Fecha::aVista((string) $jugador['JUG_Fecha_Nacimiento']),
                    'suspendido' => Suspension::estaSuspendido(
                        $fechaJuego,
                        $porJugador[$id] ?? []
                    ),
                ];
            }

            $this->json([
                'jornada' => [
                    'JOR_Jornada' => (int) $jornada[0]['JOR_Jornada'],
                    'JOR_Numero' => (int) $jornada[0]['JOR_Numero'],
                    'JOR_Fecha_Juego_Vista' => Fecha::aVista($fechaJuego),
                ],
                'equipos' => array_values($equipos),
            ]);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    public function incidencias(Base $f3): void
    {
        try {
            $equipoId = $this->entero($f3->get('GET.equipo'), 'equipo');
            $jugadorFiltro = filter_var($f3->get('GET.jugador'), FILTER_VALIDATE_INT);
            $db = $this->db($f3);

            $equipo = $db->exec(
                'SELECT EQU_Equipo,
                        EQU_Nombre
                   FROM TOR_EQUIPO
                  WHERE EQU_Equipo = ?
                  ORDER BY EQU_Equipo ASC',
                [$equipoId]
            );

            if ($equipo === []) {
                $this->error('El equipo no existe.', 404);
                return;
            }

            $sql = 'SELECT INC.INC_Incidencia,
                           INC.INC_Descripcion,
                           TTA.TTA_Codigo AS INC_Tipo_Tarjeta,
                           TTA.TTA_Nombre AS INC_Tipo_Tarjeta_Nombre,
                           INC.INC_Fecha_Incidencia,
                           INC.INC_Fecha_Suspension,
                           JUG.JUG_Jugador,
                           JUG.JUG_Primer_Nombre,
                           JUG.JUG_Segundo_Nombre,
                           JUG.JUG_Primer_Apellido,
                           JUG.JUG_Segundo_Apellido,
                           JUG.JUG_Fotografia
                      FROM TOR_INCIDENCIA INC
                INNER JOIN TOR_TIPO_TARJETA TTA
                        ON TTA.TTA_Tipo_Tarjeta = INC.TTA_Tipo_Tarjeta
                INNER JOIN TOR_JUGADOR JUG
                        ON JUG.JUG_Jugador = INC.JUG_Jugador
                     WHERE JUG.EQU_Equipo = ?';
            $params = [$equipoId];

            if ($jugadorFiltro !== false && $jugadorFiltro > 0) {
                $sql .= ' AND JUG.JUG_Jugador = ?';
                $params[] = $jugadorFiltro;
            }

            $sql .= ' ORDER BY JUG.JUG_Primer_Apellido ASC,
                               INC.INC_Fecha_Incidencia ASC';

            $filas = $db->exec($sql, $params);

            foreach ($filas as &$fila) {
                $fila['JUG_Nombre_Completo'] = $this->nombreCompleto($fila);
                $fila['INC_Fecha_Incidencia_Vista'] = Fecha::aVista((string) $fila['INC_Fecha_Incidencia']);
                $fila['INC_Fecha_Suspension_Vista'] = Fecha::aVista(
                    $fila['INC_Fecha_Suspension'] !== null
                        ? (string) $fila['INC_Fecha_Suspension']
                        : null
                );
            }

            $this->json([
                'equipo' => $equipo[0],
                'incidencias' => $filas,
            ]);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    public function goleadores(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT JUG.JUG_Jugador,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    JUG.JUG_Fotografia,
                    EQU.EQU_Nombre,
                    COALESCE(SUM(GOL.GOL_Cantidad), 0) AS total_goles
               FROM TOR_JUGADOR JUG
         INNER JOIN TOR_EQUIPO EQU
                 ON EQU.EQU_Equipo = JUG.EQU_Equipo
          LEFT JOIN TOR_GOL GOL
                 ON GOL.JUG_Jugador = JUG.JUG_Jugador
              GROUP BY JUG.JUG_Jugador,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    JUG.JUG_Fotografia,
                    EQU.EQU_Nombre
              ORDER BY total_goles DESC,
                    JUG.JUG_Primer_Apellido ASC,
                    JUG.JUG_Primer_Nombre ASC'
        );

        foreach ($filas as &$fila) {
            $fila['JUG_Nombre_Completo'] = $this->nombreCompleto($fila);
            $fila['total_goles'] = (int) $fila['total_goles'];
        }

        $this->json(['goleadores' => $filas]);
    }
}
