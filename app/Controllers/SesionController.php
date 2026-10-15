<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\Sesion;
use Base;

final class SesionController extends Controlador
{
    /** El login es público: solo arranca la cookie de sesión, no exige estar logueado. */
    public function beforeroute(Base $f3): void
    {
        Sesion::configurar($f3);
    }

    /** Muestra el formulario de entrar. Si ya hay sesión, manda al inicio. */
    public function formulario(Base $f3): void
    {
        if (Sesion::hay($f3)) {
            $f3->reroute('/');
        }

        $this->mostrarVista($f3, 'login.html', 'Entrar', 'login', [
            'error_login' => '',
        ]);
    }

    /** Recibe usuario y contraseña, abre sesión o vuelve al formulario con error. */
    public function entrar(Base $f3): void
    {
        if (Sesion::hay($f3)) {
            $f3->reroute('/');
        }

        if (!Sesion::csrfValido($f3, Sesion::tokenPeticion($f3))) {
            $this->mostrarVista($f3, 'login.html', 'Entrar', 'login', [
                'error_login' => 'La sesión expiró. Intente de nuevo.',
            ]);

            return;
        }

        try {
            $db = $this->db($f3);
        } catch (\Throwable $error) {
            $this->mostrarVista($f3, 'login.html', 'Entrar', 'login', [
                'error_login' => 'No hay conexión a la base de datos.',
            ]);

            return;
        }

        $cuerpo = $this->cuerpo($f3);
        $usuario = (string) ($cuerpo['usuario'] ?? '');
        $clave = (string) ($cuerpo['contrasena'] ?? '');
        $error = Sesion::entrar($f3, $db, $usuario, $clave);

        if ($error !== '') {
            $this->mostrarVista($f3, 'login.html', 'Entrar', 'login', [
                'error_login' => $error,
            ]);

            return;
        }

        $f3->reroute('/');
    }

    /** Cierra la sesión y vuelve al login. */
    public function salir(Base $f3): void
    {
        if (!Sesion::csrfValido($f3, Sesion::tokenPeticion($f3))) {
            $f3->reroute('/');
        }

        Sesion::salir($f3);
        $f3->reroute('/login');
    }
}
