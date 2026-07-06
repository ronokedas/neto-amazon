-- Migration 021: Módulo de Contratos
CREATE TABLE `contratos` (
    `id` CHAR(36) PRIMARY KEY,
    `proposta_id` CHAR(36) NULL,
    `cliente_id` CHAR(36) NOT NULL,
    `numero` VARCHAR(50) NULL,
    `status` ENUM('MINUTA', 'AGUARDANDO_ASSINATURA', 'ASSINADO', 'CANCELADO') NOT NULL DEFAULT 'MINUTA',
    `data_emissao` DATE NULL,
    `data_vencimento` DATE NULL,
    `valor_total` DECIMAL(10, 2) NULL,
    `conteudo` LONGTEXT NULL,
    `assinado_por` VARCHAR(255) NULL,
    `assinado_ip` VARCHAR(45) NULL,
    `assinado_em` DATETIME NULL,
    `caminho_arquivo_pdf` VARCHAR(255) NULL,
    `hash_arquivo_pdf` CHAR(64) NULL,
    `ativo` TINYINT(1) NOT NULL DEFAULT 1,
    `criado_por` CHAR(36),
    `criado_em` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `atualizado_em` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`cliente_id`) REFERENCES `pessoas`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`proposta_id`) REFERENCES `propostas`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`criado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;