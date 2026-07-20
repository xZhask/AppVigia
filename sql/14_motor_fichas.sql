-- 14_motor_fichas.sql
-- Ampliación del motor de fichas para palabras clave, nuevas dependencias y tipos de campos complejos.

-- 1. Agregar palabras_clave a enfermedad
ALTER TABLE enfermedad
ADD COLUMN palabras_clave VARCHAR(255) NULL AFTER nombre_corto;

-- Poblar palabras clave
UPDATE enfermedad SET palabras_clave = 'dengue, chikungunya, zika, arbovirosis, arbovirus' WHERE nombre_corto LIKE 'Dengue y arbovirosis%';
UPDATE enfermedad SET palabras_clave = 'sarampion, rubeola, febriles eruptivas, exantematicas' WHERE nombre_corto LIKE 'Sarampión / rubéola%';
UPDATE enfermedad SET palabras_clave = 'colera, diarrea, eda, vibrio' WHERE nombre_corto LIKE 'EDA grave / cólera%';
UPDATE enfermedad SET palabras_clave = 'bartonelosis, verruga peruana, carrion' WHERE nombre_corto LIKE 'Enfermedad de Carrión%';
UPDATE enfermedad SET palabras_clave = 'mpox, monkeypox, viruela simica' WHERE nombre_corto LIKE 'Viruela del mono%';
UPDATE enfermedad SET palabras_clave = 'vih, gestante, transmision vertical' WHERE nombre_corto LIKE 'Gestante con VIH%';
UPDATE enfermedad SET palabras_clave = 'pfa, polio, poliomielitis' WHERE nombre_corto LIKE 'Parálisis flácida aguda%';

-- 2. Agregar flags a enfermedad
ALTER TABLE enfermedad
ADD COLUMN usa_contactos TINYINT(1) NOT NULL DEFAULT 0 AFTER familia,
ADD COLUMN usa_muestras TINYINT(1) NOT NULL DEFAULT 0 AFTER usa_contactos,
ADD COLUMN usa_viajes TINYINT(1) NOT NULL DEFAULT 0 AFTER usa_muestras,
ADD COLUMN usa_vacunas TINYINT(1) NOT NULL DEFAULT 0 AFTER usa_viajes;

-- 3. Extender ENUM de campo_def.tipo y agregar config
ALTER TABLE campo_def
MODIFY COLUMN tipo ENUM('TEXTO','NUMERO','FECHA','BOOLEANO','SELECT','MULTISELECT','TEXTAREA','GRUPO_SI_NO','SI_NO_FECHA','MATRIZ','CRONOLOGIA') NOT NULL;

ALTER TABLE campo_def
ADD COLUMN config JSON NULL AFTER catalogo_id;
