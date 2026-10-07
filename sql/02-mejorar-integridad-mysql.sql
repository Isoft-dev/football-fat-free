-- Semilla del catálogo de tarjetas.
-- La aplicación completa la migración al arrancar (uniques, FK y CHECK).
-- Este script es opcional si se abre cualquier página de la app.

USE tor_futbol_infantil;

CREATE TABLE IF NOT EXISTS TOR_TIPO_TARJETA (
    TTA_Tipo_Tarjeta INT UNSIGNED NOT NULL AUTO_INCREMENT,
    TTA_Codigo VARCHAR(10) NOT NULL,
    TTA_Nombre VARCHAR(30) NOT NULL,
    CONSTRAINT PK_TIPO_TARJETA
        PRIMARY KEY (TTA_Tipo_Tarjeta),
    CONSTRAINT UQ_TIPO_TARJETA_CODIGO
        UNIQUE (TTA_Codigo)
);

INSERT INTO TOR_TIPO_TARJETA (TTA_Codigo, TTA_Nombre)
SELECT semilla.TTA_Codigo, semilla.TTA_Nombre
  FROM (
        SELECT 'amarilla' AS TTA_Codigo, 'Tarjeta amarilla' AS TTA_Nombre
        UNION ALL
        SELECT 'roja', 'Tarjeta roja'
       ) semilla
  LEFT JOIN TOR_TIPO_TARJETA TTA
         ON TTA.TTA_Codigo = semilla.TTA_Codigo
 WHERE TTA.TTA_Tipo_Tarjeta IS NULL;
