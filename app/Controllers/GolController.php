<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Fecha;
use Base;
use InvalidArgumentException;
use Throwable;

final class GolController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'goles.html', 'Goles', 'goles');
    }

    public function listar(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT GOL.GOL_Gol,
                    GOL.GOL_Cantidad,
                    GOL.JUG_Jugador,
                    GOL.JOR_Jornada,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    EQU.EQU_Nombre,
                    JOR.JOR_Numero,
                    JOR.JOR_Fecha_Juego
               FROM TOR_GOL GOL
         INNER JOIN TOR_JUGADOR JUG
                 ON JUG.JUG_Jugador = GOL.JUG_Jugador
         INNER JOIN TOR_EQUIPO EQU
                 ON EQU.EQU_Equipo = JUG.EQU_Equipo
         INNER JOIN TOR_JORNADA JOR
                 ON JOR.JOR_Jornada = GOL.JOR_Jornada
              ORDER BY JOR.JOR_Numero ASC,
                    EQU.EQU_Nombre ASC,
                    JUG.JUG_Primer_Apellido ASC'
        );

        foreach ($filas as &$fila) {
            $fila['JOR_Fecha_Juego_Vista'] = Fecha::aVista((string) $fila['JOR_Fecha_Juego']);
            $fila['JUG_Nombre_Completo'] = $this->nombreCompleto($fila);
        }

        $this->json(['goles' => $filas]);
    }

    public function guardar(Base $f3): void
    {
        try {
            $cuerpo = $this->cuerpo($f3);
            $jugador = $this->entero($cuerpo['JUG_Jugador'] ?? null, 'jugador');
            $jornada = $this->entero($cuerpo['JOR_Jornada'] ?? null, 'jornada');
            $cantidad = $this->entero($cuerpo['GOL_Cantidad'] ?? null, 'cantidad de goles');

            if ($cantidad > 99) {
                throw new InvalidArgumentException('La cantidad de goles no es válida.');
            }

            $db = $this->db($f3);

            $existeJugador = $db->exec(
                'SELECT JUG_Jugador
                   FROM TOR_JUGADOR
                  WHERE JUG_Jugador = ?
                  ORDER BY JUG_Jugador ASC',
                [$jugador]
            );

            if ($existeJugador === []) {
                throw new InvalidArgumentException('El jugador no existe.');
            }

            $existeJornada = $db->exec(
                'SELECT JOR_Jornada
                   FROM TOR_JORNADA
                  WHERE JOR_Jornada = ?
                  ORDER BY JOR_Jornada ASC',
                [$jornada]
            );

            if ($existeJornada === []) {
                throw new InvalidArgumentException('La jornada no existe. Habilite el calendario al inicio del torneo.');
            }

            // Un solo registro por jugador y jornada: si ya existe, se actualiza la cantidad.
            $previo = $db->exec(
                'SELECT GOL_Gol
                   FROM TOR_GOL
                  WHERE JUG_Jugador = ?
                    AND JOR_Jornada = ?
                  ORDER BY GOL_Gol ASC',
                [$jugador, $jornada]
            );

            if ($previo !== []) {
                $db->exec(
                    'UPDATE TOR_GOL
                        SET GOL_Cantidad = ?
                      WHERE GOL_Gol = ?',
                    [$cantidad, $previo[0]['GOL_Gol']]
                );
                $this->json(['mensaje' => 'Goles de esa jornada actualizados.']);
                return;
            }

            $db->exec(
                'INSERT INTO TOR_GOL (JUG_Jugador, JOR_Jornada, GOL_Cantidad)
                      VALUES (?, ?, ?)',
                [$jugador, $jornada, $cantidad]
            );

            $this->json(['mensaje' => 'Goles registrados.'], 201);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error('No se pudieron guardar los goles.');
        }
    }

    public function eliminar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'gol');
            $borrados = $this->db($f3)->exec(
                'DELETE FROM TOR_GOL
                      WHERE GOL_Gol = ?',
                [$id]
            );

            if ($borrados === 0) {
                $this->error('El registro de goles no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Registro de goles eliminado.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }
}
