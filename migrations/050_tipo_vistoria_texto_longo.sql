-- Migration 050: permitir lista longa de servicos no tipo de vistoria
-- A assinatura da proposta gera pre-agendamentos com todos os servicos escolhidos.

ALTER TABLE agendamentos
  MODIFY tipo_vistoria TEXT NOT NULL;

ALTER TABLE ordens_servico
  MODIFY tipo_vistoria TEXT NOT NULL;
