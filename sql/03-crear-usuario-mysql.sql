-- Catálogo de quien puede entrar a la aplicación.
-- La contraseña no se guarda en claro: la app siembra admin / Torneo07 al arrancar.

USE tor_futbol_infantil;

CREATE TABLE IF NOT EXISTS TOR_USUARIO (
    USU_Usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
    USU_Nombre_Usuario VARCHAR(60) NOT NULL,
    USU_Hash_Contrasena VARCHAR(255) NOT NULL,
    CONSTRAINT PK_USUARIO
        PRIMARY KEY (USU_Usuario),
    CONSTRAINT UQ_USUARIO_NOMBRE
        UNIQUE (USU_Nombre_Usuario)
);
