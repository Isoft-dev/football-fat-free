<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class FotoJugador
{
    private const TAMANO_MAXIMO = 2097152;

    private const TIPOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public static function guardar(array $archivo): string
    {
        $error = (int) ($archivo['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('La fotografía es obligatoria.');
        }

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('No se pudo recibir la fotografía.');
        }

        if ((int) ($archivo['size'] ?? 0) > self::TAMANO_MAXIMO) {
            throw new RuntimeException('La fotografía no puede superar 2 MB.');
        }

        $temporal = (string) ($archivo['tmp_name'] ?? '');

        if ($temporal === '' || !is_file($temporal)) {
            throw new RuntimeException('La fotografía no es válida.');
        }

        // El tipo se lee del archivo real, no de la extensión que envía el navegador.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($temporal);

        if (!isset(self::TIPOS[$mime])) {
            throw new RuntimeException('Solo se aceptan imágenes JPG, PNG o WEBP.');
        }

        $nombre = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        $directorio = dirname(__DIR__, 2) . '/public/uploads/jugadores';

        if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
            throw new RuntimeException('No se pudo crear el directorio de fotografías.');
        }

        $destino = $directorio . '/' . $nombre;

        if (!move_uploaded_file($temporal, $destino)) {
            throw new RuntimeException('No se pudo guardar la fotografía.');
        }

        return 'uploads/jugadores/' . $nombre;
    }

    public static function eliminarSiEsPropia(string $ruta): void
    {
        if (!str_starts_with($ruta, 'uploads/jugadores/')) {
            return;
        }

        $archivo = dirname(__DIR__, 2) . '/public/' . $ruta;

        if (is_file($archivo)) {
            unlink($archivo);
        }
    }
}
