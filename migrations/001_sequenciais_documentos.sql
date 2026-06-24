-- Migration 001: Criar tabela sequenciais_documentos (Fase 1 - passo 1)
-- Data: 2026-06-23

CREATE TABLE IF NOT EXISTS `sequenciais_documentos` (
  `tipo_documento` varchar(10) NOT NULL,
  `ano` int(11) NOT NULL,
  `ultimo_numero` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`tipo_documento`, `ano`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`) VALUES
('CSN',   2026, 0),
('CNBL',  2026, 0),
('CNARQ', 2026, 0),
('ORC',   2026, 0),
('REL-V', 2026, 0),
('REL-AP',2026, 0),
('OS',    2026, 0);