-- Migration 013: Adicionar soft delete na tabela financeiro_lancamentos
ALTER TABLE financeiro_lancamentos ADD COLUMN ativo TINYINT(1) NOT NULL DEFAULT 1 AFTER observacoes;