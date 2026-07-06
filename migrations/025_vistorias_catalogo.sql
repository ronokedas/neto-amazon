-- Migration 025: Evoluir Vistorias para Catalogo de Exigencias
-- Data: 2026-07-02

ALTER TABLE `vistoria_exigencias`
  ADD COLUMN `catalogo_id` CHAR(36) NULL AFTER `vistoria_id`,
  ADD COLUMN `bloco_vistoria` ENUM('seco', 'flutuando', 'borda_livre', 'arqueacao') NULL AFTER `catalogo_id`,
  ADD COLUMN `vencimento` DATE NULL AFTER `observacao`,
  ADD COLUMN `status_item` ENUM('pendente', 'cumprida', 'nao_cumprida_transcrita', 'cumprida_parcial_reescrita', 'inserida') NOT NULL DEFAULT 'inserida' AFTER `vencimento`,
  ADD COLUMN `exigencia_origem_id` CHAR(36) NULL AFTER `status_item`,
  ADD CONSTRAINT `fk_vistoria_exig_catalogo` FOREIGN KEY (`catalogo_id`) REFERENCES `exigencias_catalogo` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_vistoria_exig_origem` FOREIGN KEY (`exigencia_origem_id`) REFERENCES `vistoria_exigencias` (`id`) ON DELETE SET NULL;

ALTER TABLE `vistorias`
  ADD COLUMN `relatorio_anterior_id` CHAR(36) NULL AFTER `agendamento_id`,
  ADD COLUMN `texto_observacoes_geradas` TEXT NULL AFTER `observacoes_tecnicas`,
  ADD COLUMN `data_emissao` DATE NULL AFTER `data_vistoria`,
  ADD CONSTRAINT `fk_vistoria_anterior` FOREIGN KEY (`relatorio_anterior_id`) REFERENCES `vistorias` (`id`) ON DELETE SET NULL;
