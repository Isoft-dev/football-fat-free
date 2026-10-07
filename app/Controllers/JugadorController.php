<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Fecha;
use App\Services\FotoJugador;
use App\Services\Suspension;
use Base;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

final class JugadorController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'jugadores.html', 'Jugadores', 'jugadores');
    }

    public function ficha(Base $f3): void
    {
        $id = (string) $f3->get('PARAMS.id');
        $this->mostrarVista($f3, 'jugador-ficha.html', 'Ficha del jugador', 'ficha', [
            'jugadorId' => $id,
        ]);
    }

    public function listar(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT JUG.JUG_Jugador,
                    JUG.EQU_Equipo,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    JUG.JUG_Fecha_Nacimiento,
                    JUG.JUG_Fotografia,
                    EQU.EQU_Nombre
               FROM TOR_JUGADOR JUG
         INNER JOIN TOR_EQUIPO EQU
                 ON EQU.EQU_Equipo = JUG.EQU_Equipo
              ORDER BY EQU.EQU_Nombre ASC,
                    JUG.JUG_Primer_Apellido ASC,
                    JUG.JUG_Primer_Nombre ASC'
        );

        foreach ($filas as &$fila) {
            $fila['JUG_Fecha_Nacimiento_Vista'] = Fecha::aVista((string) $fila['JUG_Fecha_Nacimiento']);
            $fila['JUG_Nombre_Completo'] = $this->nombreCompleto($fila);
        }

        $this->json(['jugadores' => $filas]);
    }

    public function ver(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'jugador');
            $jugador = $this->obtenerJugador($f3, $id);

            if ($jugador === null) {
                $this->error('El jugador no existe.', 404);
                return;
            }

            $goles = $this->db($f3)->exec(
                'SELECT GOL.GOL_Gol,
                        GOL.GOL_Cantidad,
                        JOR.JOR_Numero,
                        JOR.JOR_Fecha_Juego
                   FROM TOR_GOL GOL
             INNER JOIN TOR_JORNADA JOR
                     ON JOR.JOR_Jornada = GOL.JOR_Jornada
                  WHERE GOL.JUG_Jugador = ?
                  ORDER BY JOR.JOR_Numero ASC',
                [$id]
            );

            foreach ($goles as &$gol) {
                $gol['JOR_Fecha_Juego_Vista'] = Fecha::aVista((string) $gol['JOR_Fecha_Juego']);
            }

            $incidencias = $this->db($f3)->exec(
                'SELECT INC.INC_Incidencia,
                        INC.INC_Descripcion,
                        TTA.TTA_Codigo AS INC_Tipo_Tarjeta,
                        TTA.TTA_Nombre AS INC_Tipo_Tarjeta_Nombre,
                        INC.INC_Fecha_Incidencia,
                        INC.INC_Fecha_Suspension
                   FROM TOR_INCIDENCIA INC
             INNER JOIN TOR_TIPO_TARJETA TTA
                     ON TTA.TTA_Tipo_Tarjeta = INC.TTA_Tipo_Tarjeta
                  WHERE INC.JUG_Jugador = ?
                  ORDER BY INC.INC_Fecha_Incidencia ASC,
                        INC.INC_Incidencia ASC',
                [$id]
            );

            foreach ($incidencias as &$incidencia) {
                $incidencia['INC_Fecha_Incidencia_Vista'] = Fecha::aVista((string) $incidencia['INC_Fecha_Incidencia']);
                $incidencia['INC_Fecha_Suspension_Vista'] = Fecha::aVista(
                    $incidencia['INC_Fecha_Suspension'] !== null
                        ? (string) $incidencia['INC_Fecha_Suspension']
                        : null
                );
            }

            $jornadaId = filter_var($f3->get('GET.jornada'), FILTER_VALIDATE_INT);
            $suspendido = null;

            if ($jornadaId !== false && $jornadaId > 0) {
                $jornada = $this->db($f3)->exec(
                    'SELECT JOR_Fecha_Juego
                       FROM TOR_JORNADA
                      WHERE JOR_Jornada = ?
                      ORDER BY JOR_Jornada ASC',
                    [$jornadaId]
                );

                if ($jornada !== []) {
                    $suspendido = Suspension::estaSuspendido(
                        (string) $jornada[0]['JOR_Fecha_Juego'],
                        $incidencias
                    );
                }
            }

            $this->json([
                'jugador' => $jugador,
                'goles' => $goles,
                'incidencias' => $incidencias,
                'suspendido' => $suspendido,
            ]);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    public function crear(Base $f3): void
    {
        try {
            $datos = $this->datosJugador($f3, true);
            $this->evitarDuplicado($f3, $datos, null);

            $this->db($f3)->exec(
                'INSERT INTO TOR_JUGADOR (
                        EQU_Equipo,
                        JUG_Primer_Nombre,
                        JUG_Segundo_Nombre,
                        JUG_Primer_Apellido,
                        JUG_Segundo_Apellido,
                        JUG_Fecha_Nacimiento,
                        JUG_Fotografia
                    ) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [
                    $datos['EQU_Equipo'],
                    $datos['JUG_Primer_Nombre'],
                    $datos['JUG_Segundo_Nombre'],
                    $datos['JUG_Primer_Apellido'],
                    $datos['JUG_Segundo_Apellido'],
                    $datos['JUG_Fecha_Nacimiento'],
                    $datos['JUG_Fotografia'],
                ]
            );

            $this->json(['mensaje' => 'Jugador registrado.'], 201);
        } catch (InvalidArgumentException | RuntimeException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error), 409);
        }
    }

    public function actualizar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'jugador');
            $actual = $this->obtenerJugador($f3, $id);

            if ($actual === null) {
                $this->error('El jugador no existe.', 404);
                return;
            }

            $datos = $this->datosJugador($f3, false);
            $this->evitarDuplicado($f3, $datos, $id);

            if ($datos['JUG_Fotografia'] !== '') {
                FotoJugador::eliminarSiEsPropia((string) $actual['JUG_Fotografia']);
            } else {
                $datos['JUG_Fotografia'] = (string) $actual['JUG_Fotografia'];
            }

            $this->db($f3)->exec(
                'UPDATE TOR_JUGADOR
                    SET EQU_Equipo = ?,
                        JUG_Primer_Nombre = ?,
                        JUG_Segundo_Nombre = ?,
                        JUG_Primer_Apellido = ?,
                        JUG_Segundo_Apellido = ?,
                        JUG_Fecha_Nacimiento = ?,
                        JUG_Fotografia = ?
                  WHERE JUG_Jugador = ?',
                [
                    $datos['EQU_Equipo'],
                    $datos['JUG_Primer_Nombre'],
                    $datos['JUG_Segundo_Nombre'],
                    $datos['JUG_Primer_Apellido'],
                    $datos['JUG_Segundo_Apellido'],
                    $datos['JUG_Fecha_Nacimiento'],
                    $datos['JUG_Fotografia'],
                    $id,
                ]
            );

            $this->json(['mensaje' => 'Jugador actualizado.']);
        } catch (InvalidArgumentException | RuntimeException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error), 409);
        }
    }

    public function eliminar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'jugador');
            $db = $this->db($f3);

            $goles = $db->exec(
                'SELECT COUNT(GOL_Gol) AS total
                   FROM TOR_GOL
                  WHERE JUG_Jugador = ?
                  ORDER BY total ASC',
                [$id]
            );
            $incidencias = $db->exec(
                'SELECT COUNT(INC_Incidencia) AS total
                   FROM TOR_INCIDENCIA
                  WHERE JUG_Jugador = ?
                  ORDER BY total ASC',
                [$id]
            );

            if ((int) ($goles[0]['total'] ?? 0) > 0 || (int) ($incidencias[0]['total'] ?? 0) > 0) {
                $this->error('No se puede eliminar un jugador que ya tiene goles o incidencias.');
                return;
            }

            $jugador = $this->obtenerJugador($f3, $id);

            if ($jugador === null) {
                $this->error('El jugador no existe.', 404);
                return;
            }

            $db->exec(
                'DELETE FROM TOR_JUGADOR
                      WHERE JUG_Jugador = ?',
                [$id]
            );
            FotoJugador::eliminarSiEsPropia((string) $jugador['JUG_Fotografia']);

            $this->json(['mensaje' => 'Jugador eliminado.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    private function datosJugador(Base $f3, bool $fotoObligatoria): array
    {
        $equipo = $this->entero($f3->get('POST.EQU_Equipo'), 'equipo');

        $equipos = $this->db($f3)->exec(
            'SELECT EQU_Equipo
               FROM TOR_EQUIPO
              WHERE EQU_Equipo = ?
              ORDER BY EQU_Equipo ASC',
            [$equipo]
        );

        if ($equipos === []) {
            throw new InvalidArgumentException('El equipo no existe.');
        }

        $foto = '';
        $archivo = $f3->get('FILES.fotografia');

        $hayFoto = is_array($archivo)
            && (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && (int) ($archivo['size'] ?? 0) > 0;

        if ($hayFoto) {
            $foto = FotoJugador::guardar($archivo);
        } elseif ($fotoObligatoria) {
            throw new RuntimeException('La fotografía es obligatoria.');
        }

        return [
            'EQU_Equipo' => $equipo,
            'JUG_Primer_Nombre' => $this->texto($f3->get('POST.JUG_Primer_Nombre'), 'primer nombre', 60),
            'JUG_Segundo_Nombre' => $this->texto($f3->get('POST.JUG_Segundo_Nombre'), 'segundo nombre', 60, false) ?: null,
            'JUG_Primer_Apellido' => $this->texto($f3->get('POST.JUG_Primer_Apellido'), 'primer apellido', 60),
            'JUG_Segundo_Apellido' => $this->texto($f3->get('POST.JUG_Segundo_Apellido'), 'segundo apellido', 60, false) ?: null,
            'JUG_Fecha_Nacimiento' => Fecha::noFutura(
                Fecha::aIso((string) $f3->get('POST.JUG_Fecha_Nacimiento')),
                'fecha de nacimiento'
            ),
            'JUG_Fotografia' => $foto,
        ];
    }

    private function evitarDuplicado(Base $f3, array $datos, ?int $excepto): void
    {
        $sql = 'SELECT JUG_Jugador
                  FROM TOR_JUGADOR
                 WHERE EQU_Equipo = ?
                   AND JUG_Primer_Nombre = ?
                   AND IFNULL(JUG_Segundo_Nombre, ?) = ?
                   AND JUG_Primer_Apellido = ?
                   AND IFNULL(JUG_Segundo_Apellido, ?) = ?
                   AND JUG_Fecha_Nacimiento = ?';
        $params = [
            $datos['EQU_Equipo'],
            $datos['JUG_Primer_Nombre'],
            '',
            $datos['JUG_Segundo_Nombre'] ?? '',
            $datos['JUG_Primer_Apellido'],
            '',
            $datos['JUG_Segundo_Apellido'] ?? '',
            $datos['JUG_Fecha_Nacimiento'],
        ];

        if ($this->db($f3)->driver() === 'sqlite') {
            $sql = 'SELECT JUG_Jugador
                      FROM TOR_JUGADOR
                     WHERE EQU_Equipo = ?
                       AND JUG_Primer_Nombre = ?
                       AND IFNULL(JUG_Segundo_Nombre, ?) = ?
                       AND JUG_Primer_Apellido = ?
                       AND IFNULL(JUG_Segundo_Apellido, ?) = ?
                       AND JUG_Fecha_Nacimiento = ?';
        }

        if ($excepto !== null) {
            $sql .= ' AND JUG_Jugador <> ?';
            $params[] = $excepto;
        }

        $sql .= ' ORDER BY JUG_Jugador ASC';

        $existe = $this->db($f3)->exec($sql, $params);

        if ($existe !== []) {
            throw new InvalidArgumentException('Ya existe un jugador con los mismos datos en ese equipo.');
        }
    }

    private function obtenerJugador(Base $f3, int $id): ?array
    {
        $filas = $this->db($f3)->exec(
            'SELECT JUG.JUG_Jugador,
                    JUG.EQU_Equipo,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    JUG.JUG_Fecha_Nacimiento,
                    JUG.JUG_Fotografia,
                    EQU.EQU_Nombre
               FROM TOR_JUGADOR JUG
         INNER JOIN TOR_EQUIPO EQU
                 ON EQU.EQU_Equipo = JUG.EQU_Equipo
              WHERE JUG.JUG_Jugador = ?
              ORDER BY JUG.JUG_Jugador ASC',
            [$id]
        );

        if ($filas === []) {
            return null;
        }

        $jugador = $filas[0];
        $jugador['JUG_Fecha_Nacimiento_Vista'] = Fecha::aVista((string) $jugador['JUG_Fecha_Nacimiento']);
        $jugador['JUG_Nombre_Completo'] = $this->nombreCompleto($jugador);

        return $jugador;
    }

    private function mensajeDuplicado(Throwable $error): string
    {
        $texto = $error->getMessage();

        if (stripos($texto, 'unique') !== false || stripos($texto, 'duplicate') !== false) {
            return 'Ya existe un jugador con los mismos datos en ese equipo.';
        }

        return 'No se pudo guardar el jugador.';
    }
}
