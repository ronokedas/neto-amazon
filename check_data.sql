-- Verificar dados inseridos nas tabelas principais
SELECT 'embarcacoes' as tabela, COUNT(*) as total FROM embarcacoes
UNION ALL
SELECT 'clientes', COUNT(*) FROM clientes
UNION ALL
SELECT 'pessoas', COUNT(*) FROM pessoas
UNION ALL
SELECT 'usuarios', COUNT(*) FROM usuarios
UNION ALL
SELECT 'vistorias', COUNT(*) FROM vistorias
UNION ALL
SELECT 'agendamentos', COUNT(*) FROM agendamentos
UNION ALL
SELECT 'certificados_csn', COUNT(*) FROM certificados_csn
UNION ALL
SELECT 'certificados_cnbl', COUNT(*) FROM certificados_cnbl
UNION ALL
SELECT 'certificados_cnarq', COUNT(*) FROM certificados_cnarq
UNION ALL
SELECT 'certificados_lp', COUNT(*) FROM certificados_lp
UNION ALL
SELECT 'certificados_lc', COUNT(*) FROM certificados_lc
UNION ALL
SELECT 'certificados_cht', COUNT(*) FROM certificados_cht
UNION ALL
SELECT 'financeiro_lancamentos', COUNT(*) FROM financeiro_lancamentos
UNION ALL
SELECT 'logs_atividade', COUNT(*) FROM logs_atividade;