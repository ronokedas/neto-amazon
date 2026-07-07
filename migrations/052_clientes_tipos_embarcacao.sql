-- Vincula clientes/despachantes aos tipos de embarcacao que atendem

CREATE TABLE IF NOT EXISTS `clientes_tipos_embarcacao` (
  `cliente_id` char(36) NOT NULL,
  `tipo_embarcacao_id` char(36) NOT NULL,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cliente_id`, `tipo_embarcacao_id`),
  KEY `idx_cte_tipo_embarcacao` (`tipo_embarcacao_id`),
  CONSTRAINT `fk_cte_cliente`
    FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
    ON DELETE CASCADE,
  CONSTRAINT `fk_cte_tipo_embarcacao`
    FOREIGN KEY (`tipo_embarcacao_id`) REFERENCES `tipos_embarcacao` (`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
