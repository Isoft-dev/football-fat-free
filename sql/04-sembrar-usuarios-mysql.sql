-- Usuarios extra del equipo. Misma clave que admin: Torneo07
-- (el hash no es la clave en claro; password_verify la comprueba).
-- Se puede correr más de una vez: no duplica nombres que ya existan.

USE tor_futbol_infantil;

INSERT INTO TOR_USUARIO (USU_Nombre_Usuario, USU_Hash_Contrasena)
SELECT 'isa', '$2y$10$OfwrO/1UqngCIMpLH2NFme4OI/IBwJ5qIBlpOy0o07af8dBS6E8Qi'
  FROM DUAL
 WHERE NOT EXISTS (
           SELECT USU_Usuario
             FROM TOR_USUARIO
            WHERE USU_Nombre_Usuario = 'isa'
       );

INSERT INTO TOR_USUARIO (USU_Nombre_Usuario, USU_Hash_Contrasena)
SELECT 'luis', '$2y$10$9IlDPc6xP.KjNYGH2uMnwOHSLLED2T7uccUhAEr/k51y/E1yWAt9.'
  FROM DUAL
 WHERE NOT EXISTS (
           SELECT USU_Usuario
             FROM TOR_USUARIO
            WHERE USU_Nombre_Usuario = 'luis'
       );

INSERT INTO TOR_USUARIO (USU_Nombre_Usuario, USU_Hash_Contrasena)
SELECT 'folgar', '$2y$10$flZ879DGX/8pb32xmbQivuqagcFjOrGww6nuIDhnDyX0YGdAMWvta'
  FROM DUAL
 WHERE NOT EXISTS (
           SELECT USU_Usuario
             FROM TOR_USUARIO
            WHERE USU_Nombre_Usuario = 'folgar'
       );

INSERT INTO TOR_USUARIO (USU_Nombre_Usuario, USU_Hash_Contrasena)
SELECT 'pablo', '$2y$10$Ckp1Bkc9/.gwGglJ.UMcnurxWCY/HygnyORtV6dk9wTBG2zR2X/MG'
  FROM DUAL
 WHERE NOT EXISTS (
           SELECT USU_Usuario
             FROM TOR_USUARIO
            WHERE USU_Nombre_Usuario = 'pablo'
       );

INSERT INTO TOR_USUARIO (USU_Nombre_Usuario, USU_Hash_Contrasena)
SELECT 'abraham', '$2y$10$Qk421nqLo7.k9CyGJ1Kth.MegRyWFIgvNPBQdcbpP4FSaP30Mr8oC'
  FROM DUAL
 WHERE NOT EXISTS (
           SELECT USU_Usuario
             FROM TOR_USUARIO
            WHERE USU_Nombre_Usuario = 'abraham'
       );
