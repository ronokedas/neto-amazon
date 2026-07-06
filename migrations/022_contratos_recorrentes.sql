-- Migration 022: Contratos Recorrentes
ALTER TABLE `contratos`
ADD COLUMN `frequencia` ENUM('ÚNICA', 'MENSAL', 'TRIMESTRAL', 'SEMESTRAL', 'ANUAL') NOT NULL DEFAULT 'ÚNICA' AFTER `status`,
ADD COLUMN `dia_vencimento` TINYINT(2) NULL AFTER `frequencia`,
ADD COLUMN `proximo_faturamento` DATE NULL AFTER `dia_vencimento`,
ADD COLUMN `renovacao_automatica` TINYINT(1) NOT NULL DEFAULT 1 AFTER `proximo_faturamento`;