-- Renomear/Adicionar coluna assinatura_url em propostas
ALTER TABLE `propostas` ADD COLUMN `assinatura_url` VARCHAR(500) NULL AFTER `assinatura_imagem`;
