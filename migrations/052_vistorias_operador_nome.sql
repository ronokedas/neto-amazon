-- Permite informar operador/responsavel livre na vistoria quando nao for o armador cadastrado.
ALTER TABLE vistorias
  ADD COLUMN operador_nome varchar(255) NULL AFTER armador_id;
