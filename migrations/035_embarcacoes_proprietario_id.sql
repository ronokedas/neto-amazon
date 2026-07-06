ALTER TABLE embarcacoes ADD COLUMN proprietario_id char(36) NULL AFTER id;
ALTER TABLE embarcacoes ADD CONSTRAINT fk_embarcacoes_proprietario FOREIGN KEY (proprietario_id) REFERENCES clientes(id);
