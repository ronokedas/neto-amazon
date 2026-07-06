-- Migration 041: complementos do CNBL para emissão via assistente
-- Guarda o tipo escolhido (Definitivo/Provisório/Condicional) e o porto
-- de inscrição usado no PDF do Certificado Nacional de Borda Livre.

ALTER TABLE `certificados_cnbl`
  ADD COLUMN `tipo` varchar(30) NOT NULL DEFAULT 'Condicional' AFTER `numero`,
  ADD COLUMN `porto_inscricao` varchar(100) DEFAULT NULL AFTER `numero_inscricao`;
