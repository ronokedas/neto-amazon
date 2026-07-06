-- Migration 047: Permitir o mesmo servico em embarcacoes diferentes na mesma proposta
-- A proposta pode conter o mesmo servico para varias embarcacoes do proprietario.

ALTER TABLE `propostas_servicos`
  DROP INDEX `proposta_servico`,
  ADD UNIQUE KEY `proposta_embarcacao_servico` (`proposta_id`, `embarcacao_id`, `servico_id`);
