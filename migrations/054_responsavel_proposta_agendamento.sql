-- Leva o nome livre do responsavel da proposta ate o agendamento/vistoria.
ALTER TABLE propostas
  ADD COLUMN operador_nome varchar(255) NULL AFTER armador_id;

ALTER TABLE agendamentos
  ADD COLUMN operador_nome varchar(255) NULL AFTER armador_id;
