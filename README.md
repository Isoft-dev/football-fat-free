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

Para validar: abrir `http://localhost/phpmyadmin`, elegir `tor_futbol_infantil` y revisar las 7 tablas. También se puede usar `C:\xampp\mysql\bin\mysql.exe`.

Si la base ya existía, ejecutar también `sql/03-crear-usuario-mysql.sql` o abrir cualquier página: la app crea `TOR_USUARIO` sola.

### Datos de ejemplo

`sql/05-sembrar-datos-mysql.sql` **borra** equipos, jornadas, jugadores, goles e incidencias y carga un torneo completo: 4 equipos, 8 jornadas (6 jugadas, 2 por jugar), 24 jugadores con foto, goles y tarjetas. No toca usuarios.

```powershell
cmd /c "C:\xampp\mysql\bin\mysql.exe -uroot --default-character-set=utf8mb4 < sql\05-sembrar-datos-mysql.sql"
```

(o importarlo desde phpMyAdmin).

Con estos datos el reporte del árbitro de la jornada 7 muestra dos suspendidos, la de la jornada 8 uno, y la tabla de goleadores tiene líder, empates y jugadores en cero.

## Entrar

La contraseña se guarda hasheada en `TOR_USUARIO`.

## Instalar dependencias nuevamente

```powershell
C:\xampp\php\php.exe -d extension=zip composer.phar install
```
