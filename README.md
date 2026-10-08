# Torneo infantil de fútbol

Aplicación con Fat-Free Framework 3.9, PHP y JavaScript puro.

## Ejecutar

Desde esta carpeta:

```powershell
C:\xampp\php\php.exe composer.phar start
```

Abrir `http://localhost:8000`.

También puede ejecutarse con Apache de XAMPP desde:

`http://localhost/fatfree-app/public/`

## Base de datos

La aplicación usa MySQL/MariaDB de XAMPP. Credenciales por defecto: usuario `root` sin contraseña.

1. Arrancar Apache y MySQL en el Panel de Control de XAMPP.
2. Ejecutar `sql/01-crear-base-mysql.sql` (phpMyAdmin o cliente MySQL).
3. Confirmar que `config/database.ini` tenga `driver=mysql` y la base `tor_futbol_infantil`.

Para validar: abrir `http://localhost/phpmyadmin`, elegir `tor_futbol_infantil` y revisar las 6 tablas. También se puede usar `C:\xampp\mysql\bin\mysql.exe`.

## Instalar dependencias nuevamente

```powershell
C:\xampp\php\php.exe -d extension=zip composer.phar install
```
