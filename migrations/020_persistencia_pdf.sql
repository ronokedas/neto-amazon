-- Migration 020: Colunas de persistência do PDF para certificados
-- Fase 1 - Persistência do PDF no momento da assinatura

ALTER TABLE `certificados_csn` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `assinado`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;

ALTER TABLE `certificados_cnbl` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `assinado`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;

ALTER TABLE `certificados_cnarq` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `assinado`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;

ALTER TABLE `certificados_lp` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `assinado`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;

ALTER TABLE `certificados_lc` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `assinado`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;

ALTER TABLE `certificados_cht` 
ADD COLUMN `caminho_arquivo_pdf` VARCHAR(255) NULL AFTER `status`,
ADD COLUMN `hash_arquivo_pdf` CHAR(64) NULL AFTER `caminho_arquivo_pdf`;
