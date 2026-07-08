-- Adiciona comprovantes/notas aos lancamentos financeiros.
CREATE TABLE IF NOT EXISTS financeiro_comprovantes (
  id char(36) COLLATE utf8mb4_general_ci NOT NULL DEFAULT (uuid()),
  lancamento_id char(36) COLLATE utf8mb4_general_ci NOT NULL,
  nome_original varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  nome_arquivo varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  caminho varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  mime_type varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  tamanho int unsigned NOT NULL DEFAULT 0,
  criado_por char(36) COLLATE utf8mb4_general_ci DEFAULT NULL,
  criado_em datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_financeiro_comprovantes_lancamento (lancamento_id),
  CONSTRAINT fk_financeiro_comprovantes_lancamento
    FOREIGN KEY (lancamento_id) REFERENCES financeiro_lancamentos(id)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
