<?php

declare(strict_types=1);

namespace App\Controllers;

use Base;
use InvalidArgumentException;
use Throwable;

final class EquipoController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'equipos.html', 'Equipos', 'equipos');
    }

    public function listar(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT EQU_Equipo,
                    EQU_Nombre
               FROM TOR_EQUIPO
              ORDER BY EQU_Nombre ASC'
        );

        $this->json(['equipos' => $filas]);
    }

    public function crear(Base $f3): void
    {
        try {
            $cuerpo = $this->cuerpo($f3);
            $nombre = $this->texto($cuerpo['EQU_Nombre'] ?? '', 'nombre del equipo', 80);

            $this->db($f3)->exec(
                'INSERT INTO TOR_EQUIPO (EQU_Nombre)
                      VALUES (?)',
                [$nombre]
            );

            $this->json(['mensaje' => 'Equipo registrado.'], 201);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error, 'Ya existe un equipo con ese nombre.'), 409);
        }
    }

    public function actualizar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'equipo');
            $cuerpo = $this->cuerpo($f3);
            $nombre = $this->texto($cuerpo['EQU_Nombre'] ?? '', 'nombre del equipo', 80);

            $actualizados = $this->db($f3)->exec(
                'UPDATE TOR_EQUIPO
                    SET EQU_Nombre = ?
                  WHERE EQU_Equipo = ?',
                [$nombre, $id]
            );

            if ($actualizados === 0) {
                $this->error('El equipo no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Equipo actualizado.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error, 'Ya existe un equipo con ese nombre.'), 409);
        }
    }

    public function eliminar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'equipo');
            $db = $this->db($f3);

            $jugadores = $db->exec(
                'SELECT COUNT(JUG_Jugador) AS total
                   FROM TOR_JUGADOR
                  WHERE EQU_Equipo = ?
                  ORDER BY total ASC',
                [$id]
            );

            if ((int) ($jugadores[0]['total'] ?? 0) > 0) {
                $this->error('No se puede eliminar un equipo que ya tiene jugadores.');
                return;
            }

            $borrados = $db->exec(
                'DELETE FROM TOR_EQUIPO
                      WHERE EQU_Equipo = ?',
                [$id]
            );

            if ($borrados === 0) {
                $this->error('El equipo no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Equipo eliminado.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    private function mensajeDuplicado(Throwable $error, string $alterno): string
    {
        $texto = $error->getMessage();

        if (stripos($texto, 'unique') !== false || stripos($texto, 'duplicate') !== false) {
            return $alterno;
        }

        return 'No se pudo guardar el equipo.';
    }
}
