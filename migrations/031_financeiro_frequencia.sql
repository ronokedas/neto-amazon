-- migrations/031_financeiro_frequencia.sql
ALTER TABLE financeiro_lancamentos 
ADD COLUMN frequencia ENUM('unica', 'mensal', 'trimestral', 'anual') DEFAULT 'unica' NOT NULL AFTER status;
