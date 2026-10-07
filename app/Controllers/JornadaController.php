<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Fecha;
use Base;
use InvalidArgumentException;
use Throwable;

final class JornadaController extends Controlador
{
    public function index(Base $f3): void
    {
        $this->mostrarVista($f3, 'jornadas.html', 'Jornadas', 'jornadas');
    }

    public function listar(Base $f3): void
    {
        $filas = $this->db($f3)->exec(
            'SELECT JOR_Jornada,
                    JOR_Numero,
                    JOR_Fecha_Juego
               FROM TOR_JORNADA
              ORDER BY JOR_Numero ASC'
        );

        foreach ($filas as &$fila) {
            $fila['JOR_Fecha_Juego_Vista'] = Fecha::aVista((string) $fila['JOR_Fecha_Juego']);
        }

        $this->json(['jornadas' => $filas]);
    }

    public function crear(Base $f3): void
    {
        try {
            $cuerpo = $this->cuerpo($f3);
            $numero = $this->entero($cuerpo['JOR_Numero'] ?? null, 'número de jornada');
            $fecha = Fecha::aIso((string) ($cuerpo['JOR_Fecha_Juego'] ?? ''));

            if ($numero > 99) {
                throw new InvalidArgumentException('El número de jornada debe estar entre 1 y 99.');
            }

            $this->db($f3)->exec(
                'INSERT INTO TOR_JORNADA (JOR_Numero, JOR_Fecha_Juego)
                      VALUES (?, ?)',
                [$numero, $fecha]
            );

            $this->json(['mensaje' => 'Jornada registrada.'], 201);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error), 409);
        }
    }

    public function actualizar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'jornada');
            $cuerpo = $this->cuerpo($f3);
            $numero = $this->entero($cuerpo['JOR_Numero'] ?? null, 'número de jornada');
            $fecha = Fecha::aIso((string) ($cuerpo['JOR_Fecha_Juego'] ?? ''));

            if ($numero > 99) {
                throw new InvalidArgumentException('El número de jornada debe estar entre 1 y 99.');
            }

            $actualizados = $this->db($f3)->exec(
                'UPDATE TOR_JORNADA
                    SET JOR_Numero = ?,
                        JOR_Fecha_Juego = ?
                  WHERE JOR_Jornada = ?',
                [$numero, $fecha, $id]
            );

            if ($actualizados === 0) {
                $this->error('La jornada no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Jornada actualizada.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        } catch (Throwable $error) {
            $this->error($this->mensajeDuplicado($error), 409);
        }
    }

    public function eliminar(Base $f3): void
    {
        try {
            $id = $this->entero($f3->get('PARAMS.id'), 'jornada');
            $db = $this->db($f3);

            $goles = $db->exec(
                'SELECT COUNT(GOL_Gol) AS total
                   FROM TOR_GOL
                  WHERE JOR_Jornada = ?
                  ORDER BY total ASC',
                [$id]
            );

            if ((int) ($goles[0]['total'] ?? 0) > 0) {
                $this->error('No se puede eliminar una jornada que ya tiene goles.');
                return;
            }

            $borrados = $db->exec(
                'DELETE FROM TOR_JORNADA
                      WHERE JOR_Jornada = ?',
                [$id]
            );

            if ($borrados === 0) {
                $this->error('La jornada no existe.', 404);
                return;
            }

            $this->json(['mensaje' => 'Jornada eliminada.']);
        } catch (InvalidArgumentException $error) {
            $this->error($error->getMessage());
        }
    }

    private function mensajeDuplicado(Throwable $error): string
    {
        $texto = $error->getMessage();

        if (stripos($texto, 'unique') !== false || stripos($texto, 'duplicate') !== false) {
            if (stripos($texto, 'FECHA') !== false || stripos($texto, 'Fecha') !== false) {
                return 'Ya existe una jornada con esa fecha.';
            }

            return 'Ya existe una jornada con ese número.';
        }

        return 'No se pudo guardar la jornada.';
    }
}
