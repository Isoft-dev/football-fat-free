<?php

declare(strict_types=1);

namespace App\Services;

use Base;
use DB\SQL;
use RuntimeException;
use Throwable;

final class Conexion
{
    public static function abrir(Base $f3): SQL
    {
        $ruta = dirname(__DIR__, 2) . '/config/database.ini';

        if (!is_file($ruta)) {
            throw new RuntimeException('Falta el archivo config/database.ini.');
        }

        $config = parse_ini_file($ruta);

        if (!is_array($config)) {
            throw new RuntimeException('No se pudo leer config/database.ini.');
        }

        $driver = (string) ($config['driver'] ?? 'sqlite');

        if ($driver === 'mysql') {
            $db = new SQL(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                    $config['host'] ?? '127.0.0.1',
                    $config['port'] ?? '3306',
                    $config['name'] ?? 'tor_futbol_infantil'
                ),
                (string) ($config['user'] ?? 'root'),
                (string) ($config['password'] ?? ''),
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } else {
            $archivo = dirname(__DIR__, 2) . '/' . ltrim((string) ($config['path'] ?? 'storage/torneo.sqlite'), '/');
            $directorio = dirname($archivo);

            if (!is_dir($directorio) && !mkdir($directorio, 0775, true) && !is_dir($directorio)) {
                throw new RuntimeException('No se pudo crear el directorio de almacenamiento.');
            }

            $db = new SQL(
                'sqlite:' . $archivo,
                null,
                null,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ]
            );
            $db->exec('PRAGMA foreign_keys = ON');
        }

        self::asegurarEsquema($db);
        $f3->set('DB', $db);

        return $db;
    }

    private static function asegurarEsquema(SQL $db): void
    {
        $driver = $db->driver();

        if ($driver === 'sqlite') {
            if (!self::tieneTabla($db, 'TOR_EQUIPO')) {
                self::crearEsquemaSqlite($db);
            }

            self::aplicarMejoras($db);
            return;
        }

        if (!self::tieneTabla($db, 'TOR_EQUIPO')) {
            throw new RuntimeException(
                'La base MySQL no tiene tablas. Ejecuta sql/01-crear-base-mysql.sql.'
            );
        }

        self::aplicarMejoras($db);
    }

    private static function crearEsquemaSqlite(SQL $db): void
    {
        $db->exec("
            CREATE TABLE TOR_EQUIPO (
                EQU_Equipo INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                EQU_Nombre TEXT NOT NULL,
                CONSTRAINT UQ_EQUIPO_NOMBRE UNIQUE (EQU_Nombre)
            )
        ");
        $db->exec("
            CREATE TABLE TOR_JORNADA (
                JOR_Jornada INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                JOR_Numero INTEGER NOT NULL,
                JOR_Fecha_Juego TEXT NOT NULL,
                CONSTRAINT UQ_JORNADA_NUMERO UNIQUE (JOR_Numero),
                CONSTRAINT UQ_JORNADA_FECHA UNIQUE (JOR_Fecha_Juego),
                CONSTRAINT CK_JORNADA_NUMERO CHECK (JOR_Numero >= 1)
            )
        ");
        $db->exec("
            CREATE TABLE TOR_TIPO_TARJETA (
                TTA_Tipo_Tarjeta INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                TTA_Codigo TEXT NOT NULL,
                TTA_Nombre TEXT NOT NULL,
                CONSTRAINT UQ_TIPO_TARJETA_CODIGO UNIQUE (TTA_Codigo)
            )
        ");
        $db->exec("
            CREATE TABLE TOR_JUGADOR (
                JUG_Jugador INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                EQU_Equipo INTEGER NOT NULL,
                JUG_Primer_Nombre TEXT NOT NULL,
                JUG_Segundo_Nombre TEXT NULL,
                JUG_Primer_Apellido TEXT NOT NULL,
                JUG_Segundo_Apellido TEXT NULL,
                JUG_Fecha_Nacimiento TEXT NOT NULL,
                JUG_Fotografia TEXT NOT NULL,
                JUG_Segundo_Nombre_Norm TEXT
                    GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Nombre, '')) STORED,
                JUG_Segundo_Apellido_Norm TEXT
                    GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Apellido, '')) STORED,
                CONSTRAINT FK_JUGADOR_EQUIPO
                    FOREIGN KEY (EQU_Equipo)
                    REFERENCES TOR_EQUIPO (EQU_Equipo)
            )
        ");
        $db->exec(
            'CREATE UNIQUE INDEX UQ_JUGADOR_IDENTIDAD
                        ON TOR_JUGADOR (
                            EQU_Equipo,
                            JUG_Primer_Nombre,
                            JUG_Segundo_Nombre_Norm,
                            JUG_Primer_Apellido,
                            JUG_Segundo_Apellido_Norm,
                            JUG_Fecha_Nacimiento
                        )'
        );
        $db->exec("
            CREATE TABLE TOR_GOL (
                GOL_Gol INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                JUG_Jugador INTEGER NOT NULL,
                JOR_Jornada INTEGER NOT NULL,
                GOL_Cantidad INTEGER NOT NULL,
                CONSTRAINT UQ_GOL_JUGADOR_JORNADA UNIQUE (JUG_Jugador, JOR_Jornada),
                CONSTRAINT FK_GOL_JUGADOR
                    FOREIGN KEY (JUG_Jugador)
                    REFERENCES TOR_JUGADOR (JUG_Jugador),
                CONSTRAINT FK_GOL_JORNADA
                    FOREIGN KEY (JOR_Jornada)
                    REFERENCES TOR_JORNADA (JOR_Jornada),
                CONSTRAINT CK_GOL_CANTIDAD CHECK (GOL_Cantidad > 0)
            )
        ");
        $db->exec("
            CREATE TABLE TOR_INCIDENCIA (
                INC_Incidencia INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                JUG_Jugador INTEGER NOT NULL,
                TTA_Tipo_Tarjeta INTEGER NOT NULL,
                INC_Descripcion TEXT NOT NULL,
                INC_Fecha_Incidencia TEXT NOT NULL,
                INC_Fecha_Suspension TEXT NULL,
                CONSTRAINT FK_INCIDENCIA_JUGADOR
                    FOREIGN KEY (JUG_Jugador)
                    REFERENCES TOR_JUGADOR (JUG_Jugador),
                CONSTRAINT FK_INCIDENCIA_TIPO_TARJETA
                    FOREIGN KEY (TTA_Tipo_Tarjeta)
                    REFERENCES TOR_TIPO_TARJETA (TTA_Tipo_Tarjeta),
                CONSTRAINT CK_INCIDENCIA_SUSPENSION CHECK (
                    INC_Fecha_Suspension IS NULL
                    OR INC_Fecha_Suspension >= INC_Fecha_Incidencia
                )
            )
        ");
    }

    private static function aplicarMejoras(SQL $db): void
    {
        self::asegurarTiposTarjeta($db);
        self::asegurarJornada($db);
        self::asegurarJugadorIdentidad($db);
        self::asegurarIncidenciaCatalogo($db);
    }

    private static function asegurarTiposTarjeta(SQL $db): void
    {
        if (!self::tieneTabla($db, 'TOR_TIPO_TARJETA')) {
            if ($db->driver() === 'sqlite') {
                $db->exec("
                    CREATE TABLE TOR_TIPO_TARJETA (
                        TTA_Tipo_Tarjeta INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                        TTA_Codigo TEXT NOT NULL,
                        TTA_Nombre TEXT NOT NULL,
                        CONSTRAINT UQ_TIPO_TARJETA_CODIGO UNIQUE (TTA_Codigo)
                    )
                ");
            } else {
                $db->exec("
                    CREATE TABLE TOR_TIPO_TARJETA (
                        TTA_Tipo_Tarjeta INT UNSIGNED NOT NULL AUTO_INCREMENT,
                        TTA_Codigo VARCHAR(10) NOT NULL,
                        TTA_Nombre VARCHAR(30) NOT NULL,
                        CONSTRAINT PK_TIPO_TARJETA PRIMARY KEY (TTA_Tipo_Tarjeta),
                        CONSTRAINT UQ_TIPO_TARJETA_CODIGO UNIQUE (TTA_Codigo)
                    )
                ");
            }
        }

        $existentes = $db->exec(
            'SELECT TTA_Codigo
               FROM TOR_TIPO_TARJETA
              ORDER BY TTA_Codigo ASC'
        );
        $codigos = array_map(
            static fn (array $fila): string => (string) $fila['TTA_Codigo'],
            $existentes
        );

        foreach (['amarilla' => 'Tarjeta amarilla', 'roja' => 'Tarjeta roja'] as $codigo => $nombre) {
            if (!in_array($codigo, $codigos, true)) {
                $db->exec(
                    'INSERT INTO TOR_TIPO_TARJETA (TTA_Codigo, TTA_Nombre)
                          VALUES (?, ?)',
                    [$codigo, $nombre]
                );
            }
        }
    }

    private static function asegurarJornada(SQL $db): void
    {
        if (self::tieneIndice($db, 'TOR_JORNADA', 'UQ_JORNADA_FECHA')) {
            return;
        }

        $db->exec(
            'CREATE UNIQUE INDEX UQ_JORNADA_FECHA
                        ON TOR_JORNADA (JOR_Fecha_Juego)'
        );
    }

    private static function asegurarJugadorIdentidad(SQL $db): void
    {
        if (!self::tieneColumna($db, 'TOR_JUGADOR', 'JUG_Segundo_Nombre_Norm')) {
            if ($db->driver() === 'sqlite') {
                $db->exec(
                    'ALTER TABLE TOR_JUGADOR
                            ADD COLUMN JUG_Segundo_Nombre_Norm TEXT
                            GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Nombre, \'\')) STORED'
                );
                $db->exec(
                    'ALTER TABLE TOR_JUGADOR
                            ADD COLUMN JUG_Segundo_Apellido_Norm TEXT
                            GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Apellido, \'\')) STORED'
                );
            } else {
                $db->exec(
                    'ALTER TABLE TOR_JUGADOR
                            ADD COLUMN JUG_Segundo_Nombre_Norm VARCHAR(60)
                            GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Nombre, \'\')) STORED'
                );
                $db->exec(
                    'ALTER TABLE TOR_JUGADOR
                            ADD COLUMN JUG_Segundo_Apellido_Norm VARCHAR(60)
                            GENERATED ALWAYS AS (IFNULL(JUG_Segundo_Apellido, \'\')) STORED'
                );
            }
        }

        if (self::tieneIndice($db, 'TOR_JUGADOR', 'UQ_JUGADOR_IDENTIDAD')) {
            return;
        }

        $db->exec(
            'CREATE UNIQUE INDEX UQ_JUGADOR_IDENTIDAD
                        ON TOR_JUGADOR (
                            EQU_Equipo,
                            JUG_Primer_Nombre,
                            JUG_Segundo_Nombre_Norm,
                            JUG_Primer_Apellido,
                            JUG_Segundo_Apellido_Norm,
                            JUG_Fecha_Nacimiento
                        )'
        );
    }

    private static function asegurarIncidenciaCatalogo(SQL $db): void
    {
        if (self::tieneColumna($db, 'TOR_INCIDENCIA', 'TTA_Tipo_Tarjeta')
            && !self::tieneColumna($db, 'TOR_INCIDENCIA', 'INC_Tipo_Tarjeta')
        ) {
            return;
        }

        if ($db->driver() === 'sqlite') {
            self::reconstruirIncidenciaSqlite($db);
            return;
        }

        self::reconstruirIncidenciaMysql($db);
    }

    private static function reconstruirIncidenciaSqlite(SQL $db): void
    {
        $db->exec("
            CREATE TABLE TOR_INCIDENCIA_NUEVA (
                INC_Incidencia INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
                JUG_Jugador INTEGER NOT NULL,
                TTA_Tipo_Tarjeta INTEGER NOT NULL,
                INC_Descripcion TEXT NOT NULL,
                INC_Fecha_Incidencia TEXT NOT NULL,
                INC_Fecha_Suspension TEXT NULL,
                CONSTRAINT FK_INCIDENCIA_JUGADOR
                    FOREIGN KEY (JUG_Jugador)
                    REFERENCES TOR_JUGADOR (JUG_Jugador),
                CONSTRAINT FK_INCIDENCIA_TIPO_TARJETA
                    FOREIGN KEY (TTA_Tipo_Tarjeta)
                    REFERENCES TOR_TIPO_TARJETA (TTA_Tipo_Tarjeta),
                CONSTRAINT CK_INCIDENCIA_SUSPENSION CHECK (
                    INC_Fecha_Suspension IS NULL
                    OR INC_Fecha_Suspension >= INC_Fecha_Incidencia
                )
            )
        ");
        $db->exec(
            'INSERT INTO TOR_INCIDENCIA_NUEVA (
                    INC_Incidencia,
                    JUG_Jugador,
                    TTA_Tipo_Tarjeta,
                    INC_Descripcion,
                    INC_Fecha_Incidencia,
                    INC_Fecha_Suspension
                )
                SELECT INC.INC_Incidencia,
                       INC.JUG_Jugador,
                       TTA.TTA_Tipo_Tarjeta,
                       INC.INC_Descripcion,
                       INC.INC_Fecha_Incidencia,
                       INC.INC_Fecha_Suspension
                  FROM TOR_INCIDENCIA INC
            INNER JOIN TOR_TIPO_TARJETA TTA
                    ON TTA.TTA_Codigo = INC.INC_Tipo_Tarjeta'
        );
        $db->exec('DROP TABLE TOR_INCIDENCIA');
        $db->exec('ALTER TABLE TOR_INCIDENCIA_NUEVA RENAME TO TOR_INCIDENCIA');
    }

    private static function reconstruirIncidenciaMysql(SQL $db): void
    {
        if (!self::tieneColumna($db, 'TOR_INCIDENCIA', 'TTA_Tipo_Tarjeta')) {
            $db->exec(
                'ALTER TABLE TOR_INCIDENCIA
                        ADD COLUMN TTA_Tipo_Tarjeta INT UNSIGNED NULL
                        AFTER JUG_Jugador'
            );
        }

        $db->exec(
            'UPDATE TOR_INCIDENCIA INC
          INNER JOIN TOR_TIPO_TARJETA TTA
                  ON TTA.TTA_Codigo = INC.INC_Tipo_Tarjeta
                 SET INC.TTA_Tipo_Tarjeta = TTA.TTA_Tipo_Tarjeta'
        );

        $db->exec(
            'ALTER TABLE TOR_INCIDENCIA
                    MODIFY TTA_Tipo_Tarjeta INT UNSIGNED NOT NULL'
        );

        if (!self::tieneIndice($db, 'TOR_INCIDENCIA', 'FK_INCIDENCIA_TIPO_TARJETA')) {
            $db->exec(
                'ALTER TABLE TOR_INCIDENCIA
                        ADD CONSTRAINT FK_INCIDENCIA_TIPO_TARJETA
                        FOREIGN KEY (TTA_Tipo_Tarjeta)
                        REFERENCES TOR_TIPO_TARJETA (TTA_Tipo_Tarjeta)'
            );
        }

        try {
            $db->exec('ALTER TABLE TOR_INCIDENCIA DROP CONSTRAINT CK_INCIDENCIA_TARJETA');
        } catch (Throwable $error) {
            $db->exec('ALTER TABLE TOR_INCIDENCIA DROP CHECK CK_INCIDENCIA_TARJETA');
        }

        $db->exec('ALTER TABLE TOR_INCIDENCIA DROP COLUMN INC_Tipo_Tarjeta');

        try {
            $db->exec(
                'ALTER TABLE TOR_INCIDENCIA
                        ADD CONSTRAINT CK_INCIDENCIA_SUSPENSION CHECK (
                            INC_Fecha_Suspension IS NULL
                            OR INC_Fecha_Suspension >= INC_Fecha_Incidencia
                        )'
            );
        } catch (Throwable $error) {
            // Ya existía o el motor no admite nombrarlo de nuevo.
        }
    }

    private static function tieneTabla(SQL $db, string $tabla): bool
    {
        if ($db->driver() === 'sqlite') {
            $filas = $db->exec(
                "SELECT name
                   FROM sqlite_master
                  WHERE type = ?
                    AND name = ?
                  ORDER BY name ASC",
                ['table', $tabla]
            );

            return $filas !== [];
        }

        $filas = $db->exec(
            "SELECT TABLE_NAME
               FROM information_schema.TABLES
              WHERE UPPER(TABLE_NAME) = ?
                AND TABLE_SCHEMA = DATABASE()
              ORDER BY TABLE_NAME ASC",
            [strtoupper($tabla)]
        );

        return $filas !== [];
    }

    private static function tieneColumna(SQL $db, string $tabla, string $columna): bool
    {
        if ($db->driver() === 'sqlite') {
            $filas = $db->exec('PRAGMA table_info(' . $tabla . ')');

            foreach ($filas as $fila) {
                if (strcasecmp((string) $fila['name'], $columna) === 0) {
                    return true;
                }
            }

            return false;
        }

        $filas = $db->exec(
            "SELECT COLUMN_NAME
               FROM information_schema.COLUMNS
              WHERE UPPER(TABLE_NAME) = ?
                AND UPPER(COLUMN_NAME) = ?
                AND TABLE_SCHEMA = DATABASE()
              ORDER BY COLUMN_NAME ASC",
            [strtoupper($tabla), strtoupper($columna)]
        );

        return $filas !== [];
    }

    private static function tieneIndice(SQL $db, string $tabla, string $indice): bool
    {
        if ($db->driver() === 'sqlite') {
            $filas = $db->exec('PRAGMA index_list(' . $tabla . ')');

            foreach ($filas as $fila) {
                if (strcasecmp((string) $fila['name'], $indice) === 0) {
                    return true;
                }
            }

            return false;
        }

        $filas = $db->exec(
            "SELECT INDEX_NAME
               FROM information_schema.STATISTICS
              WHERE UPPER(TABLE_NAME) = ?
                AND UPPER(INDEX_NAME) = ?
                AND TABLE_SCHEMA = DATABASE()
              ORDER BY INDEX_NAME ASC",
            [strtoupper($tabla), strtoupper($indice)]
        );

        return $filas !== [];
    }
}
