-- Migration 004: Seed dos tipos de documento na tabela sequenciais_documentos
-- Fase 1 - correção

INSERT INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES
('CSN', YEAR(CURDATE()), 0),
('CNBL', YEAR(CURDATE()), 0),
('CNARQ', YEAR(CURDATE()), 0),
('ORC', YEAR(CURDATE()), 0),
('REL-V', YEAR(CURDATE()), 0),
('REL-AP', YEAR(CURDATE()), 0),
('OS', YEAR(CURDATE()), 0)
ON DUPLICATE KEY UPDATE `ultimo_numero` = `ultimo_numero`;