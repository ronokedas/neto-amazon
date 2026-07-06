-- migrations/030_certificados_vistoria_id.sql
-- Adiciona a coluna vistoria_id nas tabelas de certificados

ALTER TABLE certificados_csn ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_csn ADD CONSTRAINT fk_csn_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;

ALTER TABLE certificados_cnbl ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_cnbl ADD CONSTRAINT fk_cnbl_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;

ALTER TABLE certificados_cnarq ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_cnarq ADD CONSTRAINT fk_cnarq_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;

ALTER TABLE certificados_lp ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_lp ADD CONSTRAINT fk_lp_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;

ALTER TABLE certificados_lc ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_lc ADD CONSTRAINT fk_lc_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;

ALTER TABLE certificados_cht ADD COLUMN vistoria_id CHAR(36) NULL;
ALTER TABLE certificados_cht ADD CONSTRAINT fk_cht_vistoria FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE SET NULL;
