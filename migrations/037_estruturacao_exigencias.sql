-- Migração 037: Estruturação de Categorias e Aplicabilidade para Exigências de Catálogo
CREATE TABLE IF NOT EXISTS `exigencias_categorias` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `nome` varchar(100) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_categoria_nome` (`nome`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Adicionar colunas extras na tabela exigencias_catalogo
ALTER TABLE `exigencias_catalogo`
  ADD COLUMN `categoria_id` char(36) DEFAULT NULL AFTER `codigo_interno`,
  ADD COLUMN `bloco_vistoria` enum('seco', 'flutuando', 'borda_livre', 'arqueacao') DEFAULT NULL AFTER `item_normam`,
  ADD COLUMN `aplicabilidade_a` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `aplicabilidade_b` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `aplicabilidade_c` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `aplicabilidade_d` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `aplicabilidade_e` tinyint(1) NOT NULL DEFAULT 1,
  ADD COLUMN `aplicabilidade_f` tinyint(1) NOT NULL DEFAULT 1,
  ADD CONSTRAINT `fk_catalogo_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `exigencias_categorias` (`id`) ON DELETE SET NULL;

-- Sincronizar o bloco_vistoria com o tipo_vistoria existente para os registros atuais
UPDATE `exigencias_catalogo` SET `bloco_vistoria` = `tipo_vistoria` WHERE `tipo_vistoria` IS NOT NULL;
