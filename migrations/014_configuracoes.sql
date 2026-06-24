-- Migration 014: Tabela de configurações do sistema (chave/valor)
CREATE TABLE IF NOT EXISTS configuracoes (
    chave VARCHAR(100) NOT NULL PRIMARY KEY,
    valor TEXT NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Valor padrão da meta mensal
INSERT INTO configuracoes (chave, valor, descricao) VALUES ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$');