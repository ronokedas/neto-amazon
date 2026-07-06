ALTER TABLE financeiro_lancamentos ADD COLUMN cliente_id char(36) NULL AFTER id;
ALTER TABLE financeiro_lancamentos ADD CONSTRAINT fk_financeiro_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id);
