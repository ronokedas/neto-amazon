-- Migration 034: Ajustar lógica de Armador
-- Armador passa a ser uma seleção na Vistoria, não um vínculo fixo da Embarcação.

-- Adicionar armador_id na tabela vistorias
ALTER TABLE vistorias ADD COLUMN armador_id char(36) NULL AFTER pessoa_id;
ALTER TABLE vistorias ADD CONSTRAINT fk_vistorias_armador FOREIGN KEY (armador_id) REFERENCES clientes(id);

-- O campo proprietario em embarcacoes já existe e é varchar. 
-- Manteremos como está, mas ele passará a representar o Proprietário/Dono fixo.

-- O sistema já tem uma tabela clientes com perfil 'armador'. Usaremos ela.
