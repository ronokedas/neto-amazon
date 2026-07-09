-- Garante que a proposta possa representar a assinatura/aceite concluido.
ALTER TABLE propostas
  MODIFY COLUMN status enum('rascunho','enviada','aprovada','recusada','cancelada','assinada') NOT NULL DEFAULT 'rascunho';
