-- Migration 048: manter pre-agendamentos automaticos sem data ate alguem agendar
-- Corrige registros criados automaticamente antes do ajuste, sem tocar em datas editadas manualmente.

ALTER TABLE agendamentos
  MODIFY data_vistoria DATE NULL;

UPDATE agendamentos
SET data_vistoria = NULL
WHERE status = 'pendente'
  AND vistoriador_id IS NULL
  AND hora_vistoria IS NULL
  AND data_vistoria = DATE(created_at)
  AND observacoes = 'Agendamento gerado automaticamente a partir da proposta assinada. Favor definir data e vistoriador.';
