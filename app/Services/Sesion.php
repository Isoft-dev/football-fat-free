<?php

declare(strict_types=1);

namespace App\Services;

use Base;
use DB\SQL;

final class Sesion
{
    private const FALLOS_MAX = 5;
    private const BLOQUEO_SEGUNDOS = 300;

    // Hash válido solo para gastar el mismo tiempo si el usuario no existe.
    private const HASH_FALSO = '$2y$10$dKX6PcFR53iV8ZPZnqNHIuIt5c2QSs6TwyNy7rkBdhwqkekCPURL.';

    /** Arranca la cookie de sesión (HttpOnly, SameSite). */
    public static function configurar(Base $f3): void
    {
        $base = (string) $f3->get('BASE');
        $ruta = $base === '' ? '/' : $base . '/';

        $f3->set('JAR.httponly', true);
        $f3->set('JAR.samesite', 'Lax');
        $f3->set('JAR.path', $ruta);

        $https = ($_SERVER['HTTPS'] ?? '') !== '' && ($_SERVER['HTTPS'] ?? '') !== 'off';

        if ($https) {
            $f3->set('JAR.secure', true);
        }

        $f3->sync('SESSION');
    }

    /** Crea o reutiliza el token CSRF de esta sesión. */
    public static function tokenCsrf(Base $f3): string
    {
        $token = (string) $f3->get('SESSION.csrf');

        if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
            $token = bin2hex(random_bytes(32));
            $f3->set('SESSION.csrf', $token);
        }

        return $token;
    }

    /** Compara el token recibido con el de la sesión (sin fugas de tiempo). */
    public static function csrfValido(Base $f3, string $token): bool
    {
        $esperado = (string) $f3->get('SESSION.csrf');

        return $esperado !== '' && hash_equals($esperado, $token);
    }

    /** Lee el CSRF del encabezado X-CSRF-Token o del POST. */
    public static function tokenPeticion(Base $f3): string
    {
        $cabecera = (string) (
            $f3->get('HEADERS.X-Csrf-Token')
            ?: $f3->get('HEADERS.X-CSRF-Token')
            ?: ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')
        );

        if ($cabecera !== '') {
            return $cabecera;
        }

        return (string) $f3->get('POST.csrf');
    }

    /** ¿Hay un usuario logueado en esta cookie? */
    public static function hay(Base $f3): bool
    {
        return (int) $f3->get('SESSION.USU_Usuario') > 0;
    }

    /** Si no hay sesión: 401 en API o redirección a /login. */
    public static function exigir(Base $f3): void
    {
        if (self::hay($f3)) {
            return;
        }

        $ruta = (string) $f3->get('PATH');

        if (str_starts_with($ruta, '/api/')) {
            RespuestaJson::error('Debe iniciar sesión.', 401);
            exit;
        }

        $f3->reroute('/login');
    }

    /** En POST, exige un CSRF válido. */
    public static function exigirCsrf(Base $f3): void
    {
        if (strtoupper((string) $f3->get('VERB')) !== 'POST') {
            return;
        }

        if (self::csrfValido($f3, self::tokenPeticion($f3))) {
            return;
        }

        $ruta = (string) $f3->get('PATH');

        if (str_starts_with($ruta, '/api/')) {
            RespuestaJson::error('La sesión expiró. Recargue la página.', 403);
            exit;
        }

        $f3->reroute('/login');
    }

    /** Valida usuario/clave, abre sesión y regenera el id. Devuelve '' o el error. */
    public static function entrar(Base $f3, SQL $db, string $usuario, string $clave): string
    {
        $ahora = time();
        $bloqueo = (int) $f3->get('SESSION.login_bloqueo_hasta');

        if ($bloqueo > $ahora) {
            $minutos = (int) ceil(($bloqueo - $ahora) / 60);

            return "Demasiados intentos. Espere {$minutos} minuto(s).";
        }

        $usuario = mb_strtolower(trim($usuario));

        if ($usuario === '' || $clave === '' || mb_strlen($usuario) > 60) {
            return 'Usuario o contraseña no válidos.';
        }

        $filas = $db->exec(
            'SELECT USU_Usuario,
                    USU_Nombre_Usuario,
                    USU_Hash_Contrasena
               FROM TOR_USUARIO
              WHERE USU_Nombre_Usuario = ?
              ORDER BY USU_Usuario ASC',
            [$usuario]
        );

        $hash = (string) ($filas[0]['USU_Hash_Contrasena'] ?? self::HASH_FALSO);
        $ok = password_verify($clave, $hash);

        if (!$ok || $filas === []) {
            $fallos = (int) $f3->get('SESSION.login_fallos') + 1;
            $f3->set('SESSION.login_fallos', $fallos);

            if ($fallos >= self::FALLOS_MAX) {
                $f3->set('SESSION.login_bloqueo_hasta', $ahora + self::BLOQUEO_SEGUNDOS);
                $f3->set('SESSION.login_fallos', 0);

                return 'Demasiados intentos. Espere 5 minutos.';
            }

            return 'Usuario o contraseña no válidos.';
        }

        session_regenerate_id(true);
        $f3->clear('SESSION.login_fallos');
        $f3->clear('SESSION.login_bloqueo_hasta');
        $f3->set('SESSION.USU_Usuario', (int) $filas[0]['USU_Usuario']);
        $f3->set('SESSION.USU_Nombre_Usuario', (string) $filas[0]['USU_Nombre_Usuario']);
        $f3->set('SESSION.csrf', bin2hex(random_bytes(32)));

        return '';
    }

    /** Borra la sesión y destruye la cookie. */
    public static function salir(Base $f3): void
    {
        $f3->clear('SESSION');

        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            session_regenerate_id(true);
            session_destroy();
        }
    }
}
