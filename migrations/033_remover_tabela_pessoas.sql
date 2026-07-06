-- Migration 033: Remover tabela pessoas (substituída por clientes)
-- 1. Migrar dados de pessoas para clientes (registros que ainda não existem em clientes)
INSERT IGNORE INTO clientes (id, nome, tipo_pessoa, cpf_cnpj, perfil, telefone, email, endereco, status, criado_por, criado_em, atualizado_em)
SELECT 
    p.id,
    p.nome_completo AS nome,
    p.tipo_pessoa,
    COALESCE(p.cpf, p.cnpj) AS cpf_cnpj,
    'proprietario' AS perfil,
    p.telefone,
    p.email,
    p.endereco,
    CASE WHEN p.ativo = 1 THEN 'ATIVO' ELSE 'INATIVO' END AS status,
    p.criado_por,
    p.criado_em,
    p.atualizado_em
FROM pessoas p;

-- 2. Reapontar FK de contratos para tabela clientes (se houver constraint)
-- Primeiro verifica se a constraint existe e remove
SET @constraint_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_NAME = 'contratos_ibfk_1' 
    AND TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'contratos');

-- Remove FK antiga (only if exists)
SET @drop_fk = IF(@constraint_exists > 0, 
    'ALTER TABLE contratos DROP FOREIGN KEY contratos_ibfk_1',
    'SELECT 1');
PREPARE stmt FROM @drop_fk;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Adiciona nova FK apontando para clientes
ALTER TABLE contratos 
    ADD CONSTRAINT contratos_cliente_fk 
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) 
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- 3. Remover tabela pessoas
DROP TABLE IF EXISTS pessoas;

-- 4. Remover tabela clientes_embarcacoes se existir e for resquício (já existe embarcacoes.clientes)
-- Manter tabela original se estiver em uso

-- 5. (Opcional) Registrar migration se tabela existir
-- INSERT INTO migrations (versao, nome, aplicada_em) VALUES ('033', 'remover_tabela_pessoas', NOW());
