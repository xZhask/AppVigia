-- 29_condicion_paciente.sql
-- CAMBIOS_CONDICION_PACIENTE.md: unifica es_pnp + tipo_beneficiario en una
-- sola condición de 3 valores (EFECTIVO/DERECHOHABIENTE/PARTICULAR) y agrega
-- el vínculo del derechohabiente con su titular. La unidad/dependencia se
-- retira de la interfaz pero la tabla unidad_pnp y persona.unidad_id se
-- conservan intactas para cuando se disponga del padrón.

ALTER TABLE persona
  ADD COLUMN condicion ENUM('EFECTIVO','DERECHOHABIENTE','PARTICULAR')
    NOT NULL DEFAULT 'PARTICULAR' AFTER distrito_id;

UPDATE persona SET condicion = CASE
  WHEN es_pnp = 1 AND tipo_beneficiario = 'DERECHOHABIENTE' THEN 'DERECHOHABIENTE'
  WHEN es_pnp = 1                                           THEN 'EFECTIVO'
  ELSE 'PARTICULAR' END;

ALTER TABLE persona
  ADD COLUMN titular_id INT NULL AFTER condicion,
  ADD COLUMN vinculo_titular ENUM('CONYUGE','CONVIVIENTE','HIJO','PADRE','MADRE','OTRO')
    NULL AFTER titular_id,
  ADD CONSTRAINT fk_persona_titular FOREIGN KEY (titular_id) REFERENCES persona(id);

CREATE INDEX ix_persona_condicion ON persona (condicion);
CREATE INDEX ix_persona_titular   ON persona (titular_id);

-- La FK a unidad_pnp y la columna unidad_id NO se tocan: se dejan vacías,
-- sin uso desde la interfaz, hasta que se disponga del padrón de dependencias.
ALTER TABLE persona
  DROP COLUMN es_pnp,
  DROP COLUMN tipo_beneficiario;
