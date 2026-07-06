-- Migration 042: campos oficiais do CNBL no cadastro da embarcação
-- Separa dados gerais da embarcação dos campos usados no modelo oficial CNBL.

ALTER TABLE `embarcacoes`
  ADD COLUMN `cnbl_tipo_embarcacao` varchar(1) DEFAULT NULL AFTER `tipo_embarcacao`,
  ADD COLUMN `cnbl_area_navegacao` varchar(20) DEFAULT NULL AFTER `area_navegacao`;
