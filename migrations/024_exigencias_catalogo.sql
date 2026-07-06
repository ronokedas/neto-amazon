CREATE TABLE IF NOT EXISTS `exigencias_catalogo` (
  `id` char(36) NOT NULL DEFAULT (UUID()),
  `codigo_interno` varchar(50) DEFAULT NULL,
  `descricao` text NOT NULL,
  `item_normam` varchar(200) DEFAULT NULL,
  `tipo_vistoria` enum('seco', 'flutuando', 'borda_livre', 'arqueacao') DEFAULT NULL,
  `prazo_padrao_dias` int(11) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime DEFAULT current_timestamp(),
  `atualizado_em` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
