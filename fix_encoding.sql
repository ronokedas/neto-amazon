-- Script para corrigir dados corrompidos por encoding
-- Converte caracteres UTF-8 que foram interpretados como Latin1 de volta para UTF-8 correto

-- ============================================================
-- CORREÇÃO DE DADOS CORROMPIDOS
-- ============================================================

-- Atualizar tabela embarcacoes
UPDATE `embarcacoes` SET 
  `nome` = CONVERT(BINARY CONVERT(`nome` USING latin1) USING utf8mb4),
  `tipo` = CONVERT(BINARY CONVERT(`tipo` USING latin1) USING utf8mb4),
  `proprietario` = CONVERT(BINARY CONVERT(`proprietario` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4)
WHERE `nome` LIKE '%Ã%' OR `tipo` LIKE '%Ã%' OR `proprietario` LIKE '%Ã%';

-- Atualizar tabela clientes
UPDATE `clientes` SET 
  `nome` = CONVERT(BINARY CONVERT(`nome` USING latin1) USING utf8mb4),
  `endereco` = CONVERT(BINARY CONVERT(`endereco` USING latin1) USING utf8mb4)
WHERE `nome` LIKE '%Ã%' OR `endereco` LIKE '%Ã%';

-- Atualizar tabela pessoas
UPDATE `pessoas` SET 
  `nome_completo` = CONVERT(BINARY CONVERT(`nome_completo` USING latin1) USING utf8mb4),
  `endereco` = CONVERT(BINARY CONVERT(`endereco` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4)
WHERE `nome_completo` LIKE '%Ã%' OR `endereco` LIKE '%Ã%';

-- Atualizar tabela certificados_csn
UPDATE `certificados_csn` SET 
  `nome_embarcacao` = CONVERT(BINARY CONVERT(`nome_embarcacao` USING latin1) USING utf8mb4),
  `atividades_servicos` = CONVERT(BINARY CONVERT(`atividades_servicos` USING latin1) USING utf8mb4),
  `tipo_embarcacao` = CONVERT(BINARY CONVERT(`tipo_embarcacao` USING latin1) USING utf8mb4),
  `fabricante_motor` = CONVERT(BINARY CONVERT(`fabricante_motor` USING latin1) USING utf8mb4),
  `material_casco` = CONVERT(BINARY CONVERT(`material_casco` USING latin1) USING utf8mb4),
  `obs_passageiros` = CONVERT(BINARY CONVERT(`obs_passageiros` USING latin1) USING utf8mb4),
  `local_vistoria` = CONVERT(BINARY CONVERT(`local_vistoria` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `nome_embarcacao` LIKE '%Ã%' OR `atividades_servicos` LIKE '%Ã%';

-- Atualizar tabela certificados_cnbl
UPDATE `certificados_cnbl` SET 
  `nome_embarcacao` = CONVERT(BINARY CONVERT(`nome_embarcacao` USING latin1) USING utf8mb4),
  `atividades_servicos` = CONVERT(BINARY CONVERT(`atividades_servicos` USING latin1) USING utf8mb4),
  `tipo_embarcacao` = CONVERT(BINARY CONVERT(`tipo_embarcacao` USING latin1) USING utf8mb4),
  `material_casco` = CONVERT(BINARY CONVERT(`material_casco` USING latin1) USING utf8mb4),
  `tipo_navegacao` = CONVERT(BINARY CONVERT(`tipo_navegacao` USING latin1) USING utf8mb4),
  `area_navegacao` = CONVERT(BINARY CONVERT(`area_navegacao` USING latin1) USING utf8mb4),
  `local_vistoria` = CONVERT(BINARY CONVERT(`local_vistoria` USING latin1) USING utf8mb4),
  `local_emissao` = CONVERT(BINARY CONVERT(`local_emissao` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `nome_embarcacao` LIKE '%Ã%' OR `atividades_servicos` LIKE '%Ã%';

-- Atualizar tabela certificados_cnarq
UPDATE `certificados_cnarq` SET 
  `nome_embarcacao` = CONVERT(BINARY CONVERT(`nome_embarcacao` USING latin1) USING utf8mb4),
  `tipo_embarcacao` = CONVERT(BINARY CONVERT(`tipo_embarcacao` USING latin1) USING utf8mb4),
  `material_casco` = CONVERT(BINARY CONVERT(`material_casco` USING latin1) USING utf8mb4),
  `porto_inscricao` = CONVERT(BINARY CONVERT(`porto_inscricao` USING latin1) USING utf8mb4),
  `local_construcao` = CONVERT(BINARY CONVERT(`local_construcao` USING latin1) USING utf8mb4),
  `metodo_arqueacao` = CONVERT(BINARY CONVERT(`metodo_arqueacao` USING latin1) USING utf8mb4),
  `local_vistoria` = CONVERT(BINARY CONVERT(`local_vistoria` USING latin1) USING utf8mb4),
  `local_emissao` = CONVERT(BINARY CONVERT(`local_emissao` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `nome_embarcacao` LIKE '%Ã%' OR `tipo_embarcacao` LIKE '%Ã%';

-- Atualizar tabela certificados_lp
UPDATE `certificados_lp` SET 
  `nome_embarcacao` = CONVERT(BINARY CONVERT(`nome_embarcacao` USING latin1) USING utf8mb4),
  `tipo_embarcacao` = CONVERT(BINARY CONVERT(`tipo_embarcacao` USING latin1) USING utf8mb4),
  `material_casco` = CONVERT(BINARY CONVERT(`material_casco` USING latin1) USING utf8mb4),
  `proprietario_nome` = CONVERT(BINARY CONVERT(`proprietario_nome` USING latin1) USING utf8mb4),
  `proprietario_endereco` = CONVERT(BINARY CONVERT(`proprietario_endereco` USING latin1) USING utf8mb4),
  `estaleiro_nome` = CONVERT(BINARY CONVERT(`estaleiro_nome` USING latin1) USING utf8mb4),
  `estaleiro_endereco` = CONVERT(BINARY CONVERT(`estaleiro_endereco` USING latin1) USING utf8mb4),
  `observacoes_exigencias` = CONVERT(BINARY CONVERT(`observacoes_exigencias` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `nome_embarcacao` LIKE '%Ã%' OR `proprietario_nome` LIKE '%Ã%';

-- Atualizar tabela certificados_lc
UPDATE `certificados_lc` SET 
  `nome_embarcacao` = CONVERT(BINARY CONVERT(`nome_embarcacao` USING latin1) USING utf8mb4),
  `tipo_embarcacao` = CONVERT(BINARY CONVERT(`tipo_embarcacao` USING latin1) USING utf8mb4),
  `material_casco` = CONVERT(BINARY CONVERT(`material_casco` USING latin1) USING utf8mb4),
  `sociedade_classificadora` = CONVERT(BINARY CONVERT(`sociedade_classificadora` USING latin1) USING utf8mb4),
  `tipo_navegacao` = CONVERT(BINARY CONVERT(`tipo_navegacao` USING latin1) USING utf8mb4),
  `area_navegacao` = CONVERT(BINARY CONVERT(`area_navegacao` USING latin1) USING utf8mb4),
  `atividade_servico` = CONVERT(BINARY CONVERT(`atividade_servico` USING latin1) USING utf8mb4),
  `propulsao` = CONVERT(BINARY CONVERT(`propulsao` USING latin1) USING utf8mb4),
  `proprietario_nome` = CONVERT(BINARY CONVERT(`proprietario_nome` USING latin1) USING utf8mb4),
  `proprietario_endereco` = CONVERT(BINARY CONVERT(`proprietario_endereco` USING latin1) USING utf8mb4),
  `estaleiro_nome` = CONVERT(BINARY CONVERT(`estaleiro_nome` USING latin1) USING utf8mb4),
  `estaleiro_endereco` = CONVERT(BINARY CONVERT(`estaleiro_endereco` USING latin1) USING utf8mb4),
  `local_emissao` = CONVERT(BINARY CONVERT(`local_emissao` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `nome_embarcacao` LIKE '%Ã%' OR `proprietario_nome` LIKE '%Ã%';

-- Atualizar tabela certificados_cht
UPDATE `certificados_cht` SET 
  `profissional_empresa` = CONVERT(BINARY CONVERT(`profissional_empresa` USING latin1) USING utf8mb4),
  `atividade_homologada` = CONVERT(BINARY CONVERT(`atividade_homologada` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4),
  `assinante_nome` = CONVERT(BINARY CONVERT(`assinante_nome` USING latin1) USING utf8mb4),
  `assinante_titulo` = CONVERT(BINARY CONVERT(`assinante_titulo` USING latin1) USING utf8mb4),
  `assinante_registro` = CONVERT(BINARY CONVERT(`assinante_registro` USING latin1) USING utf8mb4)
WHERE `profissional_empresa` LIKE '%Ã%' OR `atividade_homologada` LIKE '%Ã%';

-- Atualizar tabela agendamentos
UPDATE `agendamentos` SET 
  `local` = CONVERT(BINARY CONVERT(`local` USING latin1) USING utf8mb4),
  `contato_nome` = CONVERT(BINARY CONVERT(`contato_nome` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4)
WHERE `local` LIKE '%Ã%' OR `contato_nome` LIKE '%Ã%';

-- Atualizar tabela ordens_servico
UPDATE `ordens_servico` SET 
  `local` = CONVERT(BINARY CONVERT(`local` USING latin1) USING utf8mb4),
  `contato_nome` = CONVERT(BINARY CONVERT(`contato_nome` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4)
WHERE `local` LIKE '%Ã%' OR `contato_nome` LIKE '%Ã%';

-- Atualizar tabela vistorias
UPDATE `vistorias` SET 
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4),
  `resultado` = CONVERT(BINARY CONVERT(`resultado` USING latin1) USING utf8mb4)
WHERE `observacoes` LIKE '%Ã%' OR `resultado` LIKE '%Ã%';

-- Atualizar tabela financeiro_lancamentos
UPDATE `financeiro_lancamentos` SET 
  `descricao` = CONVERT(BINARY CONVERT(`descricao` USING latin1) USING utf8mb4),
  `categoria` = CONVERT(BINARY CONVERT(`categoria` USING latin1) USING utf8mb4),
  `observacoes` = CONVERT(BINARY CONVERT(`observacoes` USING latin1) USING utf8mb4)
WHERE `descricao` LIKE '%Ã%' OR `categoria` LIKE '%Ã%';

-- Atualizar tabela logs_atividade
UPDATE `logs_atividade` SET 
  `acao` = CONVERT(BINARY CONVERT(`acao` USING latin1) USING utf8mb4),
  `descricao` = CONVERT(BINARY CONVERT(`descricao` USING latin1) USING utf8mb4)
WHERE `acao` LIKE '%Ã%' OR `descricao` LIKE '%Ã%';

-- ============================================================
-- VERIFICAR RESULTADO
-- ============================================================

SELECT 'embarcacoes' as tabela, COUNT(*) as total FROM embarcacoes WHERE nome LIKE '%Ã%'
UNION ALL
SELECT 'clientes', COUNT(*) FROM clientes WHERE nome LIKE '%Ã%'
UNION ALL
SELECT 'pessoas', COUNT(*) FROM pessoas WHERE nome_completo LIKE '%Ã%'
UNION ALL
SELECT 'certificados_csn', COUNT(*) FROM certificados_csn WHERE nome_embarcacao LIKE '%Ã%'
UNION ALL
SELECT 'certificados_cnbl', COUNT(*) FROM certificados_cnbl WHERE nome_embarcacao LIKE '%Ã%'
UNION ALL
SELECT 'certificados_cnarq', COUNT(*) FROM certificados_cnarq WHERE nome_embarcacao LIKE '%Ã%'
UNION ALL
SELECT 'certificados_lp', COUNT(*) FROM certificados_lp WHERE nome_embarcacao LIKE '%Ã%'
UNION ALL
SELECT 'certificados_lc', COUNT(*) FROM certificados_lc WHERE nome_embarcacao LIKE '%Ã%'
UNION ALL
SELECT 'certificados_cht', COUNT(*) FROM certificados_cht WHERE profissional_empresa LIKE '%Ã%';