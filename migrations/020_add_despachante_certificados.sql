ALTER TABLE certificados_cnarq ADD COLUMN despachante_id CHAR(36) NULL AFTER vistoria_id;
ALTER TABLE certificados_cnbl ADD COLUMN despachante_id CHAR(36) NULL AFTER vistoria_id;
ALTER TABLE certificados_lp ADD COLUMN despachante_id CHAR(36) NULL AFTER vistoria_id;
ALTER TABLE certificados_lc ADD COLUMN despachante_id CHAR(36) NULL AFTER vistoria_id;
ALTER TABLE certificados_cht ADD COLUMN despachante_id CHAR(36) NULL AFTER vistoria_id;
