<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Fecha;
use Base;
use InvalidArgumentException;
use Throwable;

final class IncidenciaController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'incidencias.html', 'Incidencias', 'incidencias');
    }

    public function listar(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT INC.INC_Incidencia,
                    INC.INC_Descripcion,
                    TTA.TTA_Codigo AS INC_Tipo_Tarjeta,
                    TTA.TTA_Nombre AS INC_Tipo_Tarjeta_Nombre,
                    INC.INC_Fecha_Incidencia,
                    INC.INC_Fecha_Suspension,
                    INC.JUG_Jugador,
                    JUG.JUG_Primer_Nombre,
                    JUG.JUG_Segundo_Nombre,
                    JUG.JUG_Primer_Apellido,
                    JUG.JUG_Segundo_Apellido,
                    EQU.EQU_Nombre,
                    EQU.EQU_Equipo
               FROM TOR_INCIDENCIA INC
         INNER JOIN TOR_TIPO_TARJETA TTA
                 ON TTA.TTA_Tipo_Tarjeta = INC.TTA_Tipo_Tarjeta
         INNER JOIN TOR_JUGADOR JUG
                 ON JUG.JUG_Jugador = INC.JUG_Jugador
         INNER JOIN TOR_EQUIPO EQU
                 ON EQU.EQU_Equipo = JUG.EQU_Equipo
              ORDER BY INC.INC_Fecha_Incidencia DESC,
                    INC.INC_Incidencia DESC'
        );

        foreach ($filas as &$fila) {
            $fila['INC_Fecha_Incidencia_Vista'] = Fecha::aVista((string) $fila['INC_Fecha_Incidencia']);
            $fila['INC_Fecha_Suspension_Vista'] = Fecha::aVista(
                $fila['INC_Fecha_Suspension'] !== null
                    ? (string) $fila['INC_Fecha_Suspension']
                    : null
            );
            $fila['JUG_Nombre_Completo'] = $this->nombreCompleto($fila);
        }

        $tipos = $this->db($f3)->exec(
            'SELECT TTA_Tipo_Tarjeta,
                    TTA_Codigo,
                    TTA_Nombre
               FROM TOR_TIPO_TARJETA
              ORDER BY TTA_Nombre ASC'
        );

        $this->json([
            'incidencias' => $filas,
            'tipos' => $tipos,
        ]);
    }

    public function crear(Base $f3): void
    {
        try {
            $cuerpo = $this->cuerpo($f3);
            $jugador = $this->entero($cuerpo['JUG_Jugador'] ?? null, 'jugador');
            $descripcion = $this->texto($cuerpo['INC_Descripcion'] ?? '', 'descripción', 255);
            $tipo = $this->tipoTarjeta($f3, (string) ($cuerpo['INC_Tipo_Tarjeta'] ?? ''));
            $fechaIncidencia = Fecha::aIso((string) ($cuerpo['INC_Fecha_Incidencia'] ?? ''));

            $fechaSuspension = null;

            // Amarilla no lleva suspensión; roja exige fecha y no puede ser anterior a la incidencia.
            if ($tipo['TTA_Codigo'] === 'roja') {
                $suspension = trim((string) ($cuerpo['INC_Fecha_Suspension'] ?? ''));

                if ($suspension === '') {
                    throw new InvalidArgumentException(
                        'La fecha de suspensión es obligatoria cuando la tarjeta es roja.'
                    );
                }

                $fechaSuspension = Fecha::aIso($suspension);

                if ($fechaSuspension < $fechaIncidencia) {
                    throw new InvalidArgumentException(
                        'La fecha de suspensión no puede ser anterior a la fecha de incidencia.'
                    );
                }
            }

            $existeJugador = $this->db($f3)->exec(
                'SELECT JUG_Jugador
                   FROM TOR_JUGADOR
                  WHERE JUG_Jugador = ?
                  ORDER BY JUG_Jugador ASC',
                [$jugador]
            );

            if ($existeJugador === []) {
                throw new InvalidArgumentException('El jugador no existe.');
            }

            $this->db($f3)->exec(
                'INSERT INTO TOR_INCIDENCIA (
                        JUG_Jugador,
                        TTA_Tipo_Tarjeta,
                        INC_Descripcion,
                        INC_Fecha_Incidencia,
                        INC_Fecha_Suspension
                    ) VALUES (?, ?, ?, ?, ?)',
                [$jugador, $tipo['TTA_Tipo_Tarjeta'], $descripcion, $fechaIncidencia, $fechaSuspension]
            );

            $this->json(['mensaje' => 'Incidencia registrada.'], 201);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error('No se pudo guardar la incidencia.');
        }
    }

    public function eliminar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'incidencia');
            $borrados = $this->db($f3)->exec(
                'DELETE FROM TOR_INCIDENCIA
                      WHERE INC_Incidencia = ?',
                [$id]
            );

            if ($borrados === 0) {
                $this->error('La incidencia no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Incidencia eliminada.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    private function tipoTarjeta(Base $f3, string $codigo): array
    {
        $codigo = strtolower($this->texto($codigo, 'tipo de tarjeta', 10));
        $filas = $this->db($f3)->exec(
            'SELECT TTA_Tipo_Tarjeta,
                    TTA_Codigo
               FROM TOR_TIPO_TARJETA
              WHERE TTA_Codigo = ?
              ORDER BY TTA_Tipo_Tarjeta ASC',
            [$codigo]
        );

        if ($filas === []) {
            throw new InvalidArgumentException('El tipo de tarjeta no existe.');
        }

        return $filas[0];
    }
}
