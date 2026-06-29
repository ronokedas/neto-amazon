-- Migration 019: Adicionar cargo VENDEDOR e coluna vendedor_id em agendamentos
-- Data: 2026-06-27

-- 1. Alterar ENUM da coluna cargo para incluir VENDEDOR
ALTER TABLE usuarios 
MODIFY COLUMN cargo ENUM('ADMIN','VENDEDOR','VISTORIADOR') NOT NULL DEFAULT 'VISTORIADOR';

-- 2. Adicionar coluna vendedor_id em agendamentos (para rastrear quem criou)
ALTER TABLE agendamentos 
ADD COLUMN IF NOT EXISTS vendedor_id char(36) DEFAULT NULL AFTER vistoriador_id,
ADD KEY IF NOT EXISTS vendedor_id (vendedor_id),
ADD CONSTRAINT IF NOT EXISTS fk_agendamento_vendedor 
  FOREIGN KEY (vendedor_id) REFERENCES usuarios(id) ON DELETE SET NULL;
