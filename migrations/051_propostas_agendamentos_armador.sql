-- Adiciona armador responsavel ao fluxo comercial/agendamento

ALTER TABLE propostas
  ADD COLUMN armador_id CHAR(36) NULL AFTER cliente_id,
  ADD INDEX idx_propostas_armador_id (armador_id),
  ADD CONSTRAINT fk_propostas_armador
    FOREIGN KEY (armador_id) REFERENCES clientes(id)
    ON DELETE SET NULL;

ALTER TABLE agendamentos
  ADD COLUMN armador_id CHAR(36) NULL AFTER cliente_id,
  ADD INDEX idx_agendamentos_armador_id (armador_id),
  ADD CONSTRAINT fk_agendamentos_armador
    FOREIGN KEY (armador_id) REFERENCES clientes(id)
    ON DELETE SET NULL;
