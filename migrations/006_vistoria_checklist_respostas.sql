CREATE TABLE IF NOT EXISTS vistoria_checklist_respostas (
    id CHAR(36) COLLATE utf8mb4_general_ci PRIMARY KEY,
    vistoria_id CHAR(36) COLLATE utf8mb4_general_ci NOT NULL,
    catalogo_id CHAR(36) COLLATE utf8mb4_general_ci NOT NULL,
    status ENUM('CONFORME', 'NAO_CONFORME', 'NAO_SE_APLICA') COLLATE utf8mb4_general_ci NOT NULL,
    observacao TEXT COLLATE utf8mb4_general_ci,
    vencimento DATE,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vistoria_id) REFERENCES vistorias(id) ON DELETE CASCADE,
    FOREIGN KEY (catalogo_id) REFERENCES exigencias_catalogo(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
