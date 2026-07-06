-- migrations/032_financeiro_data_null.sql
ALTER TABLE financeiro_lancamentos MODIFY COLUMN data DATE NULL;
