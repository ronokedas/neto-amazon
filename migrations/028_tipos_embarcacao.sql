-- Migration 028: Criar tabela tipos_embarcacao e alterar embarcacoes

DROP TABLE IF EXISTS `tipos_embarcacao`;

CREATE TABLE `tipos_embarcacao` (
    `id` CHAR(36) NOT NULL PRIMARY KEY,
    `nome` VARCHAR(100) NOT NULL,
    `ativo` TINYINT(1) DEFAULT 1,
    `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tipos_embarcacao` (`id`, `nome`) VALUES
(UUID(), 'Balsa'),
(UUID(), 'Empurrador'),
(UUID(), 'Lancha'),
(UUID(), 'Rebocador'),
(UUID(), 'Flutuante'),
(UUID(), 'Draga'),
(UUID(), 'Pontão'),
(UUID(), 'Bote'),
(UUID(), 'Navio'),
(UUID(), 'Iate'),
(UUID(), 'Chata'),
(UUID(), 'Ferry Boat');

ALTER TABLE `embarcacoes`
ADD CONSTRAINT `fk_embarcacoes_tipo` FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao`(`id`) ON DELETE SET NULL;
