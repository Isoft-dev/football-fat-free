-- Datos de ejemplo del torneo: equipos, jornadas, jugadores, goles e incidencias.
-- BORRA lo que haya en esas tablas y reinicia los IDs. No toca usuarios ni tipos de tarjeta.
-- Se puede correr más de una vez: siempre deja la base en el mismo estado.
--
-- Escenario (hoy = 17/10/2026):
--   * Jornadas 1 a 6 ya jugadas (con goles y tarjetas); 7 y 8 por jugar.
--   * Reporte del árbitro, jornada 7: Sebastián Cruz (Leones) y Rodrigo Morales (Tigres) suspendidos.
--     Jornada 8: solo Rodrigo Morales. Andrés Navarro (Águilas) ya cumplió su suspensión.
--   * Goleadores: Leonardo Martínez lidera con 9; empate en 6 entre Bruno Domínguez y Joaquín Medina;
--     varios jugadores en 0 (porteros y defensas).
--   * Los suspendidos no tienen goles en las jornadas que no jugaron.
-- Fotos: avatares en public/assets/img/jugadores (fuera de uploads, la app no los borra).

USE tor_futbol_infantil;

DELETE FROM TOR_INCIDENCIA;
DELETE FROM TOR_GOL;
DELETE FROM TOR_JUGADOR;
DELETE FROM TOR_JORNADA;
DELETE FROM TOR_EQUIPO;

ALTER TABLE TOR_INCIDENCIA AUTO_INCREMENT = 1;
ALTER TABLE TOR_GOL AUTO_INCREMENT = 1;
ALTER TABLE TOR_JUGADOR AUTO_INCREMENT = 1;
ALTER TABLE TOR_JORNADA AUTO_INCREMENT = 1;
ALTER TABLE TOR_EQUIPO AUTO_INCREMENT = 1;

INSERT INTO TOR_EQUIPO (EQU_Equipo, EQU_Nombre) VALUES
    (1, 'Leones'),
    (2, 'Tigres'),
    (3, 'Águilas'),
    (4, 'Halcones');

-- Un domingo por jornada.
INSERT INTO TOR_JORNADA (JOR_Jornada, JOR_Numero, JOR_Fecha_Juego) VALUES
    (1, 1, '2026-09-06'),
    (2, 2, '2026-09-13'),
    (3, 3, '2026-09-20'),
    (4, 4, '2026-09-27'),
    (5, 5, '2026-10-04'),
    (6, 6, '2026-10-11'),
    (7, 7, '2026-10-18'),
    (8, 8, '2026-10-25');

-- 6 jugadores por equipo, nacidos entre 2014 y 2016. Algunos sin segundo nombre o apellido.
INSERT INTO TOR_JUGADOR (
    JUG_Jugador,
    EQU_Equipo,
    JUG_Primer_Nombre,
    JUG_Segundo_Nombre,
    JUG_Primer_Apellido,
    JUG_Segundo_Apellido,
    JUG_Fecha_Nacimiento,
    JUG_Fotografia
) VALUES
    -- Leones
    (1, 1, 'Luis', 'Ángel', 'García', 'Morales', '2015-05-01', 'assets/img/jugadores/01-luis-garcia.svg'),
    (2, 1, 'Mateo', NULL, 'Hernández', 'López', '2015-08-14', 'assets/img/jugadores/02-mateo-hernandez.svg'),
    (3, 1, 'Santiago', 'José', 'Ramírez', NULL, '2014-11-30', 'assets/img/jugadores/03-santiago-ramirez.svg'),
    (4, 1, 'Diego', NULL, 'Torres', 'Castillo', '2016-02-09', 'assets/img/jugadores/04-diego-torres.svg'),
    (5, 1, 'Emiliano', NULL, 'Flores', 'Rivera', '2015-12-21', 'assets/img/jugadores/05-emiliano-flores.svg'),
    (6, 1, 'Sebastián', 'Alejandro', 'Cruz', 'Mendoza', '2014-07-03', 'assets/img/jugadores/06-sebastian-cruz.svg'),
    -- Tigres
    (7, 2, 'Leonardo', NULL, 'Martínez', 'Ruiz', '2015-03-17', 'assets/img/jugadores/07-leonardo-martinez.svg'),
    (8, 2, 'Daniel', NULL, 'Gómez', 'Ortiz', '2014-09-25', 'assets/img/jugadores/08-daniel-gomez.svg'),
    (9, 2, 'Gael', 'Antonio', 'Vargas', 'Reyes', '2016-01-12', 'assets/img/jugadores/09-gael-vargas.svg'),
    (10, 2, 'Iker', NULL, 'Sánchez', 'Jiménez', '2015-06-08', 'assets/img/jugadores/10-iker-sanchez.svg'),
    (11, 2, 'Rodrigo', NULL, 'Morales', 'Aguilar', '2014-12-02', 'assets/img/jugadores/11-rodrigo-morales.svg'),
    (12, 2, 'Adrián', 'Felipe', 'Romero', NULL, '2015-10-19', 'assets/img/jugadores/12-adrian-romero.svg'),
    -- Águilas
    (13, 3, 'Valentina', NULL, 'Pérez', 'Salazar', '2015-04-22', 'assets/img/jugadores/13-valentina-perez.svg'),
    (14, 3, 'Andrés', NULL, 'Navarro', 'Delgado', '2014-08-11', 'assets/img/jugadores/14-andres-navarro.svg'),
    (15, 3, 'Joaquín', 'Elías', 'Medina', 'Vega', '2016-03-05', 'assets/img/jugadores/15-joaquin-medina.svg'),
    (16, 3, 'Tomás', NULL, 'Castro', 'Ríos', '2015-07-27', 'assets/img/jugadores/16-tomas-castro.svg'),
    (17, 3, 'Nicolás', NULL, 'Herrera', 'Fuentes', '2014-10-14', 'assets/img/jugadores/17-nicolas-herrera.svg'),
    (18, 3, 'Ximena', 'Sofía', 'Guzmán', 'Campos', '2015-11-03', 'assets/img/jugadores/18-ximena-guzman.svg'),
    -- Halcones
    (19, 4, 'Bruno', NULL, 'Domínguez', 'Rojas', '2015-01-29', 'assets/img/jugadores/19-bruno-dominguez.svg'),
    (20, 4, 'Julián', NULL, 'Silva', 'Paredes', '2014-06-16', 'assets/img/jugadores/20-julian-silva.svg'),
    (21, 4, 'Maximiliano', NULL, 'Ortega', 'Luna', '2016-04-18', 'assets/img/jugadores/21-maximiliano-ortega.svg'),
    (22, 4, 'Samuel', 'David', 'Cabrera', 'Molina', '2015-09-09', 'assets/img/jugadores/22-samuel-cabrera.svg'),
    (23, 4, 'Alonso', NULL, 'Mejía', 'Soto', '2014-05-24', 'assets/img/jugadores/23-alonso-mejia.svg'),
    (24, 4, 'Renata', NULL, 'Ibarra', 'Núñez', '2015-02-14', 'assets/img/jugadores/24-renata-ibarra.svg');

-- Goles por jugador y jornada (solo jornadas 1 a 6). Total en el comentario de cada jugador.
INSERT INTO TOR_GOL (JUG_Jugador, JOR_Jornada, GOL_Cantidad) VALUES
    -- Leonardo Martínez: 9
    (7, 1, 2), (7, 2, 1), (7, 3, 2), (7, 5, 3), (7, 6, 1),
    -- Luis García: 7
    (1, 1, 1), (1, 2, 2), (1, 4, 1), (1, 5, 1), (1, 6, 2),
    -- Bruno Domínguez: 6
    (19, 1, 2), (19, 3, 1), (19, 4, 2), (19, 6, 1),
    -- Joaquín Medina: 6
    (15, 2, 1), (15, 3, 2), (15, 4, 1), (15, 5, 2),
    -- Valentina Pérez: 4
    (13, 1, 1), (13, 4, 2), (13, 6, 1),
    -- Gael Vargas: 4
    (9, 2, 2), (9, 4, 1), (9, 6, 1),
    -- Santiago Ramírez: 3
    (3, 3, 1), (3, 5, 2),
    -- Samuel Cabrera: 3
    (22, 2, 1), (22, 5, 1), (22, 6, 1),
    -- Iker Sánchez: 2
    (10, 1, 1), (10, 3, 1),
    -- Andrés Navarro: 2 (no juega la 3, suspendido)
    (14, 1, 1), (14, 4, 1),
    -- Rodrigo Morales: 2 (no juega la 6, suspendido)
    (11, 2, 1), (11, 4, 1),
    -- Un gol cada uno
    (5, 6, 1),
    (21, 5, 1),
    (18, 3, 1),
    (24, 2, 1);

-- Tarjetas. La fecha de incidencia es la de la jornada en que ocurrió.
-- Roja: suspendido desde la incidencia hasta la fecha de suspensión (inclusive).
INSERT INTO TOR_INCIDENCIA (
    JUG_Jugador,
    TTA_Tipo_Tarjeta,
    INC_Descripcion,
    INC_Fecha_Incidencia,
    INC_Fecha_Suspension
)
SELECT semilla.JUG_Jugador,
       TTA.TTA_Tipo_Tarjeta,
       semilla.INC_Descripcion,
       semilla.INC_Fecha_Incidencia,
       semilla.INC_Fecha_Suspension
  FROM (
        -- Sebastián Cruz (Leones): dos amarillas y roja en la 6 -> no juega la 7.
        SELECT 6 AS JUG_Jugador, 'amarilla' AS TTA_Codigo, 'Falta por detrás' AS INC_Descripcion,
               DATE('2026-09-06') AS INC_Fecha_Incidencia, CAST(NULL AS DATE) AS INC_Fecha_Suspension
        UNION ALL SELECT 6, 'amarilla', 'Protestar al árbitro', '2026-09-27', NULL
        UNION ALL SELECT 6, 'roja', 'Juego brusco grave', '2026-10-11', '2026-10-18'
        -- Mateo Hernández (Leones)
        UNION ALL SELECT 2, 'amarilla', 'Simular una falta', '2026-10-11', NULL
        -- Rodrigo Morales (Tigres): roja en la 5 -> no juega la 6, 7 ni 8.
        UNION ALL SELECT 11, 'roja', 'Agresión a un rival', '2026-10-04', '2026-10-25'
        -- Daniel Gómez (Tigres)
        UNION ALL SELECT 8, 'amarilla', 'Retrasar el saque', '2026-09-20', NULL
        -- Andrés Navarro (Águilas): roja en la 2 -> no jugó la 3; ya cumplió.
        UNION ALL SELECT 14, 'roja', 'Mano intencional que evita un gol', '2026-09-13', '2026-09-20'
        -- Nicolás Herrera (Águilas)
        UNION ALL SELECT 17, 'amarilla', 'Salir del campo sin permiso', '2026-09-06', NULL
        -- Julián Silva (Halcones)
        UNION ALL SELECT 20, 'amarilla', 'Falta táctica', '2026-09-13', NULL
        UNION ALL SELECT 20, 'amarilla', 'Falta táctica', '2026-10-04', NULL
        -- Alonso Mejía (Halcones)
        UNION ALL SELECT 23, 'amarilla', 'Entrada fuerte', '2026-09-27', NULL
       ) semilla
 INNER JOIN TOR_TIPO_TARJETA TTA
         ON TTA.TTA_Codigo = semilla.TTA_Codigo;
