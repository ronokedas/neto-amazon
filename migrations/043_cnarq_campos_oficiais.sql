-- Migration 043: campos oficiais para o Certificado Nacional de Arqueação (CNARQ)
-- Guarda na embarcação os dados técnicos usados no Anexo 7-A e copia para o
-- certificado no momento da emissão, preservando o histórico do PDF emitido.

ALTER TABLE `embarcacoes`
  ADD COLUMN `cnarq_data_quilha` varchar(50) DEFAULT NULL AFTER `metodo_arqueacao`,
  ADD COLUMN `cnarq_calado_moldado_m` decimal(8,3) DEFAULT NULL AFTER `cnarq_data_quilha`,
  ADD COLUMN `cnarq_espacos_incluidos_ab` text DEFAULT NULL AFTER `cnarq_calado_moldado_m`,
  ADD COLUMN `cnarq_espacos_incluidos_al` text DEFAULT NULL AFTER `cnarq_espacos_incluidos_ab`,
  ADD COLUMN `cnarq_espacos_excluidos_m3` decimal(10,2) DEFAULT NULL AFTER `cnarq_espacos_incluidos_al`,
  ADD COLUMN `cnarq_data_local_arqueacao_original` varchar(200) DEFAULT NULL AFTER `cnarq_espacos_excluidos_m3`,
  ADD COLUMN `cnarq_data_local_ultima_rearqueacao` varchar(200) DEFAULT NULL AFTER `cnarq_data_local_arqueacao_original`;

ALTER TABLE `certificados_cnarq`
  ADD COLUMN `tipo` varchar(30) DEFAULT 'Condicional' AFTER `numero`,
  ADD COLUMN `data_quilha` varchar(50) DEFAULT NULL AFTER `local_construcao`,
  ADD COLUMN `calado_moldado_m` decimal(8,3) DEFAULT NULL AFTER `metodo_arqueacao`,
  ADD COLUMN `passageiros_camarotes` int DEFAULT 0 AFTER `calado_moldado_m`,
  ADD COLUMN `passageiros_outros` int DEFAULT 0 AFTER `passageiros_camarotes`,
  ADD COLUMN `espacos_incluidos_ab` text DEFAULT NULL AFTER `passageiros_outros`,
  ADD COLUMN `espacos_incluidos_al` text DEFAULT NULL AFTER `espacos_incluidos_ab`,
  ADD COLUMN `espacos_excluidos_m3` decimal(10,2) DEFAULT 0 AFTER `espacos_incluidos_al`,
  ADD COLUMN `data_local_arqueacao_original` varchar(200) DEFAULT NULL AFTER `espacos_excluidos_m3`,
  ADD COLUMN `data_local_ultima_rearqueacao` varchar(200) DEFAULT NULL AFTER `data_local_arqueacao_original`;
