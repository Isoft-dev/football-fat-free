<?php

declare(strict_types=1);

namespace App\Services;

final class RespuestaJson
{
    public static function enviar(array $datos, int $codigo = 200): void
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $datos,
            JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
        );
    }

    public static function error(string $mensaje, int $codigo = 400): void
    {
        self::enviar(['error' => $mensaje], $codigo);
    }
}
