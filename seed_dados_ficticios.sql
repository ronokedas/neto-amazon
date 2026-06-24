-- ============================================================
-- SCRIPT DE POPULAÇÃO DE DADOS FICTÍCIOS PARA TESTES
-- Sistema ERP - Documentação e Certificados
-- ============================================================
-- Este script insere dados em todas as tabelas principais
-- para permitir testes completos do sistema
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- ============================================================
-- 1. USUÁRIOS (Já existem 2, vamos adicionar mais vistoriadores)
-- ============================================================

INSERT IGNORE INTO `usuarios` (`id`, `nome`, `email`, `senha_hash`, `cargo`, `ativo`, `criado_em`, `atualizado_em`) VALUES
('11111111-1111-1111-1111-111111111111', 'Carlos Mendes', 'carlos@sistema.com', '$2y$10$WkA/uXzt0FgkNZSgpV5IXuwgOYrjultBGwQMszFKOofoXqqTvG1aO', 'VISTORIADOR', 1, NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', 'Ana Paula Silva', 'ana@sistema.com', '$2y$10$WkA/uXzt0FgkNZSgpV5IXuwgOYrjultBGwQMszFKOofoXqqTvG1aO', 'VISTORIADOR', 1, NOW(), NOW()),
('33333333-3333-3333-3333-333333333333', 'Roberto Lima', 'roberto@sistema.com', '$2y$10$WkA/uXzt0FgkNZSgpV5IXuwgOYrjultBGwQMszFKOofoXqqTvG1aO', 'VISTORIADOR', 1, NOW(), NOW());

-- ============================================================
-- 2. PESSOAS (Clientes PF e PJ)
-- ============================================================

INSERT IGNORE INTO `pessoas` (`id`, `tipo_pessoa`, `nome_completo`, `cpf`, `cnpj`, `rg`, `telefone`, `email`, `sexo`, `endereco`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('44444444-4444-4444-4444-444444444444', 'PF', 'João Pedro Almeida', '12345678901', NULL, '1234567', '(91) 99999-1111', 'joao.almeida@email.com', 'M', 'Rua das Flores, 123 - Belém, PA', 'Cliente antigo', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('55555555-5555-5555-5555-555555555555', 'PF', 'Maria Fernanda Costa', '98765432100', NULL, '7654321', '(91) 99999-2222', 'maria.costa@email.com', 'F', 'Av. Nazaré, 456 - Belém, PA', 'Proprietária de múltiplas embarcações', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('66666666-6666-6666-6666-666666666666', 'PF', 'Pedro Henrique Santos', '45678912300', NULL, '4567890', '(91) 99999-3333', 'pedro.santos@email.com', 'M', 'Travessa Quintino Bocaiúva, 789 - Belém, PA', 'Armador profissional', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('77777777-7777-7777-7777-777777777777', 'PJ', 'Transportes Marítimos Ltda', NULL, '12345678000190', NULL, '(91) 3233-4444', 'contato@transportesmaritimos.com.br', NULL, 'Rodovia BR-316, Km 5 - Ananindeua, PA', 'Empresa de transporte', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('88888888-8888-8888-8888-888888888888', 'PJ', 'Navegação Amazônica S/A', NULL, '98765432000155', NULL, '(91) 3244-5555', 'contato@navegacaoamazonica.com.br', NULL, 'Av. Perimetral, 1000 - Belém, PA', 'Grande operador de frota', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('99999999-9999-9999-9999-999999999999', 'PF', 'José Maria Oliveira', '32165498700', NULL, '3216540', '(91) 99999-4444', 'jose.oliveira@email.com', 'M', 'Rua do Carmo, 321 - Belém, PA', 'Despachante', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 3. CLIENTES
-- ============================================================

INSERT IGNORE INTO `clientes` (`id`, `nome`, `tipo_pessoa`, `cpf_cnpj`, `perfil`, `telefone`, `email`, `endereco`, `status`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 'João Pedro Almeida', 'PF', '12345678901', 'proprietario', '(91) 99999-1111', 'joao.almeida@email.com', 'Rua das Flores, 123 - Belém, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 'Maria Fernanda Costa', 'PF', '98765432100', 'proprietario', '(91) 99999-2222', 'maria.costa@email.com', 'Av. Nazaré, 456 - Belém, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('cccccccc-cccc-cccc-cccc-cccccccccccc', 'Pedro Henrique Santos', 'PF', '45678912300', 'armador', '(91) 99999-3333', 'pedro.santos@email.com', 'Travessa Quintino Bocaiúva, 789 - Belém, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('dddddddd-dddd-dddd-dddd-dddddddddddd', 'Transportes Marítimos Ltda', 'PJ', '12345678000190', 'armador', '(91) 3233-4444', 'contato@transportesmaritimos.com.br', 'Rodovia BR-316, Km 5 - Ananindeua, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', 'Navegação Amazônica S/A', 'PJ', '98765432000155', 'proprietario', '(91) 3244-5555', 'contato@navegacaoamazonica.com.br', 'Av. Perimetral, 1000 - Belém, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('ffffffff-ffff-ffff-ffff-ffffffffffff', 'José Maria Oliveira', 'PF', '32165498700', 'despachante', '(91) 99999-4444', 'jose.oliveira@email.com', 'Rua do Carmo, 321 - Belém, PA', 'ATIVO', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 4. EMBARCAÇÕES (Já existem 3, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `embarcacoes` (`id`, `nome`, `tipo`, `registro`, `proprietario`, `ano`, `cliente_id`, `comprimento_total`, `comprimento_casco`, `comprimento_lpp`, `pontal_moldado`, `boca_moldada`, `boca_maxima`, `material_casco`, `tipo_servico`, `tipo_navegacao`, `area_navegacao`, `arqueacao_bruta`, `numero_inscricao`, `porto_inscricao`, `indicativo_chamada`, `numero_tripulantes`, `numero_passageiros_n1`, `numero_passageiros_n2`, `observacoes`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('11111111-1111-1111-1111-111111111111', 'Estrela do Mar', 'ferry boat', 'REG-2024-001', 'Maria Fernanda Costa', 2023, 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', 45.50, 42.00, 40.00, 4.20, 8.50, 9.00, 'aço', 'transporte passageiros', 'MAR ABERTO', 'Costa Brasileira', '450', 'PA-2024-001', 'Belém', 'PPW1234', 12, 200, 0, 'Embarcação de passageiros', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', 'Rio Amazonas', 'rebocador', 'REG-2024-002', 'Pedro Henrique Santos', 2022, 'cccccccc-cccc-cccc-cccc-cccccccccccc', 28.00, 25.50, 23.00, 3.80, 6.20, 6.50, 'aço', 'rebocador', 'MAR ABERTO', 'Área 1', '280', 'PA-2024-002', 'Belém', 'PWR5678', 8, 0, 0, 'Rebocador de alto mar', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('33333333-3333-3333-3333-333333333333', 'Boa Esperança', 'pesqueiro', 'REG-2024-003', 'João Pedro Almeida', 2021, 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', 18.50, 16.00, 14.50, 2.50, 5.00, 5.30, 'alumínio', 'pesca artesanal', 'COSTEIRA', 'Área 2', '85', 'PA-2024-003', 'Belém', 'PPE9012', 5, 12, 0, 'Embarcação de pesca artesanal', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('44444444-4444-4444-4444-444444444444', 'Atlântico Sul', 'carga geral', 'REG-2024-004', 'Transportes Marítimos Ltda', 2020, 'dddddddd-dddd-dddd-dddd-dddddddddddd', 65.00, 60.00, 58.00, 5.50, 11.00, 11.50, 'aço', 'transporte carga', 'MAR ABERTO', 'Costa Brasileira', '1200', 'PA-2024-004', 'Belém', 'PPC3456', 15, 0, 0, 'Navio de carga geral', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('55555555-5555-5555-5555-555555555555', 'Vitória Régia', 'passageiros', 'REG-2024-005', 'Navegação Amazônica S/A', 2024, 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', 32.00, 29.50, 27.00, 3.20, 7.00, 7.40, 'aço', 'transporte passageiros', 'INTERIOR', 'Área 3', '320', 'PA-2024-005', 'Belém', 'PPV7890', 10, 150, 0, 'Embarcação de passageiros para rios', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 5. CLIENTES_EMBARCAÇÕES (Relação N:N)
-- ============================================================

INSERT IGNORE INTO `clientes_embarcacoes` (`id`, `cliente_id`, `embarcacao_id`, `criado_em`) VALUES
(UUID(), 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', '11111111-1111-1111-1111-111111111111', NOW()),
(UUID(), 'cccccccc-cccc-cccc-cccc-cccccccccccc', '22222222-2222-2222-2222-222222222222', NOW()),
(UUID(), 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', '33333333-3333-3333-3333-333333333333', NOW()),
(UUID(), 'dddddddd-dddd-dddd-dddd-dddddddddddd', '44444444-4444-4444-4444-444444444444', NOW()),
(UUID(), 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', '55555555-5555-5555-5555-555555555555', NOW());

-- ============================================================
-- 6. SEQUENCIAIS DE DOCUMENTOS
-- ============================================================

INSERT IGNORE INTO `sequenciais_documentos` (`tipo_documento`, `ano`, `ultimo_numero`, `criado_em`, `atualizado_em`) VALUES
('CSN', 2026, 10, NOW(), NOW()),
('CNBL', 2026, 5, NOW(), NOW()),
('CNARQ', 2026, 3, NOW(), NOW()),
('LP', 2026, 8, NOW(), NOW()),
('LC', 2026, 6, NOW(), NOW()),
('EC', 2026, 4, NOW(), NOW()),
('REL-HT', 2026, 7, NOW(), NOW());

-- ============================================================
-- 7. VISTORIAS (Já existe 1, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `vistorias` (`id`, `numero`, `embarcacao_id`, `pessoa_id`, `agendamento_id`, `data_vistoria`, `status`, `observacoes`, `resultado`, `observacoes_tecnicas`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
('11111111-1111-1111-1111-111111111111', 'VIST-2026-001', '11111111-1111-1111-1111-111111111111', '55555555-5555-5555-5555-555555555555', NULL, '2026-06-15', 'APROVADA', 'Vistoria de rotina', 'Aprovado sem ressalvas', 'Embarcação em perfeitas condições', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', 'VIST-2026-002', '22222222-2222-2222-2222-222222222222', '66666666-6666-6666-6666-666666666666', NULL, '2026-06-18', 'APROVADA', 'Vistoria para renovação', 'Aprovado com observações menores', 'Necessária pequena reparo no casco', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('33333333-3333-3333-3333-333333333333', 'VIST-2026-003', '33333333-3333-3333-3333-333333333333', '44444444-4444-4444-4444-444444444444', NULL, '2026-06-20', 'PENDENTE', 'Primeira vistoria', NULL, NULL, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('44444444-4444-4444-4444-444444444444', 'VIST-2026-004', '44444444-4444-4444-4444-444444444444', '77777777-7777-7777-7777-777777777777', NULL, '2026-06-22', 'REPROVADA', 'Vistoria de segurança', 'Reprovado - falhas estruturais', 'Necessária reforma completa do casco', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('55555555-5555-5555-5555-555555555555', 'VIST-2026-005', '55555555-5555-5555-5555-555555555555', '88888888-8888-8888-8888-888888888888', NULL, '2026-06-25', 'PENDENTE', 'Vistoria para certificação', NULL, NULL, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 8. VISTORIA_EXIGÊNCIAS
-- ============================================================

INSERT IGNORE INTO `vistoria_exigencias` (`id`, `vistoria_id`, `ordem`, `item`, `descricao`, `conforme`, `observacao`) VALUES
(UUID(), '11111111-1111-1111-1111-111111111111', 1, 'Estrutura do casco', 'Verificação de integridade estrutural', 'sim', 'Sem problemas'),
(UUID(), '11111111-1111-1111-1111-111111111111', 2, 'Sistema de propulsão', 'Verificação do motor e hélices', 'sim', 'Funcionando perfeitamente'),
(UUID(), '11111111-1111-1111-1111-111111111111', 3, 'Equipamentos de segurança', 'Verificação de balsas, extintores e sinalização', 'sim', 'Todos os equipamentos em dia'),
(UUID(), '11111111-1111-1111-1111-111111111111', 4, 'Sistema de navegação', 'Verificação de GPS, radar e comunicação', 'sim', 'Equipamentos calibrados'),
(UUID(), '22222222-2222-2222-2222-222222222222', 1, 'Estrutura do casco', 'Verificação de integridade estrutural', 'sim', 'Pequena corrosão na proa'),
(UUID(), '22222222-2222-2222-2222-222222222222', 2, 'Sistema de propulsão', 'Verificação do motor e hélices', 'sim', 'Revisão em dia'),
(UUID(), '22222222-2222-2222-2222-222222222222', 3, 'Equipamentos de segurança', 'Verificação de balsas, extintores e sinalização', 'sim', 'Tudo OK'),
(UUID(), '44444444-4444-4444-4444-444444444444', 1, 'Estrutura do casco', 'Verificação de integridade estrutural', 'nao', 'Trincas estruturais detectadas'),
(UUID(), '44444444-4444-4444-4444-444444444444', 2, 'Sistema de propulsão', 'Verificação do motor e hélices', 'nao', 'Motor com problemas'),
(UUID(), '44444444-4444-4444-4444-444444444444', 3, 'Equipamentos de segurança', 'Verificação de balsas, extintores e sinalização', 'nao', 'Balsa salva-vidas vencida');

-- ============================================================
-- 9. AGENDAMENTOS
-- ============================================================

INSERT IGNORE INTO `agendamentos` (`id`, `proposta_id`, `embarcacao_id`, `cliente_id`, `vistoriador_id`, `tipo_vistoria`, `data_vistoria`, `hora_vistoria`, `local`, `contato_nome`, `contato_telefone`, `status`, `observacoes`, `criado_por`, `created_at`, `updated_at`) VALUES
('11111111-1111-1111-1111-111111111111', NULL, '11111111-1111-1111-1111-111111111111', 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', '11111111-1111-1111-1111-111111111111', 'Vistoria Inicial', '2026-06-28', '09:00:00', 'Porto de Belém - Atracadouro 3', 'Maria Fernanda Costa', '(91) 99999-2222', 'confirmado', 'Vistoria para emissão de certificado CSN', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', NULL, '22222222-2222-2222-2222-222222222222', 'cccccccc-cccc-cccc-cccc-cccccccccccc', '22222222-2222-2222-2222-222222222222', 'Vistoria Anual', '2026-06-30', '14:00:00', 'Estaleiro Naval de Belém', 'Pedro Henrique Santos', '(91) 99999-3333', 'confirmado', 'Renovação de certificado', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('33333333-3333-3333-3333-333333333333', NULL, '33333333-3333-3333-3333-333333333333', 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa', '33333333-3333-3333-3333-333333333333', 'Vistoria Inicial', '2026-07-02', '10:30:00', 'Porto de Belém - Atracadouro 1', 'João Pedro Almeida', '(91) 99999-1111', 'pendente', 'Primeira vistoria da embarcação', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('44444444-4444-4444-4444-444444444444', NULL, '44444444-4444-4444-4444-444444444444', 'dddddddd-dddd-dddd-dddd-dddddddddddd', '11111111-1111-1111-1111-111111111111', 'Vistoria Especial', '2026-07-05', '08:00:00', 'Estaleiro Naval de Belém', 'Carlos Mendes', '(91) 3233-4444', 'pendente', 'Vistoria após reforma', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('55555555-5555-5555-5555-555555555555', NULL, '55555555-5555-5555-5555-555555555555', 'eeeeeeee-eeee-eeee-eeee-eeeeeeeeeeee', '22222222-2222-2222-2222-222222222222', 'Vistoria Inicial', '2026-07-08', '11:00:00', 'Porto de Belém - Atracadouro 2', 'Ana Paula Silva', '(91) 3244-5555', 'pendente', 'Emissão de certificado CNBL', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 10. ORDENS DE SERVIÇO
-- ============================================================

INSERT IGNORE INTO `ordens_servico` (`id`, `numero`, `agendamento_id`, `proposta_id`, `embarcacao_id`, `cliente_id`, `vistoriador_id`, `tipo_vistoria`, `data_vistoria`, `hora_vistoria`, `local`, `contato_nome`, `contato_telefone`, `status`, `observacoes`, `criado_por`, `created_at`, `updated_at`) VALUES
('11111111-1111-1111-1111-111111111111', 'OS-2026-001', '11111111-1111-1111-1111-111111111111', NULL, '11111111-1111-1111-1111-111111111111', 'bbbbbbbb-bbbb-bbbb-bbbb-bbbbbbbbbbbb', '11111111-1111-1111-1111-111111111111', 'Vistoria Inicial', '2026-06-28', '09:00:00', 'Porto de Belém - Atracadouro 3', 'Maria Fernanda Costa', '(91) 99999-2222', 'em_andamento', 'Vistoria em andamento', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
('22222222-2222-2222-2222-222222222222', 'OS-2026-002', '22222222-2222-2222-2222-222222222222', NULL, '22222222-2222-2222-2222-222222222222', 'cccccccc-cccc-cccc-cccc-cccccccccccc', '22222222-2222-2222-2222-222222222222', 'Vistoria Anual', '2026-06-30', '14:00:00', 'Estaleiro Naval de Belém', 'Pedro Henrique Santos', '(91) 99999-3333', 'executado', 'Vistoria concluída com sucesso', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 11. CERTIFICADOS CSN (Já existem 5, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `certificados_csn` (`id`, `numero`, `token_assinatura`, `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `atividades_servicos`, `tipo_embarcacao`, `ano_construcao`, `comprimento_m`, `arqueacao_bruta`, `tipo_navegacao`, `area_navegacao`, `fabricante_motor`, `potencia_kw`, `material_casco`, `autorizado_carga`, `qtd_passageiros`, `obs_passageiros`, `relatorio_numero`, `data_vistoria_seco`, `data_vistoria_flutuando`, `local_vistoria`, `acessibilidade_sim`, `acessibilidade_nao`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-CSN-7/26', SHA2('cert7csn2026', 256), 'Estrela do Mar', 'PA-2024-001', 'PPW1234', 'transporte passageiros', 'ferry boat', '2023', 45.50, '450', 'MAR ABERTO', 'Costa Brasileira', 'MAN Diesel', '1500', 'aço', 0, 200, 'passageiros', 'REL-2026-007', '2026-06-15', '2026-06-15', 'Porto de Belém', 1, 0, '2026-06-16', '2027-06-16', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-CSN-8/26', SHA2('cert8csn2026', 256), 'Rio Amazonas', 'PA-2024-002', 'PWR5678', 'rebocador', 'rebocador', '2022', 28.00, '280', 'MAR ABERTO', 'Área 1', 'Caterpillar', '800', 'aço', 1, 0, '', 'REL-2026-008', '2026-06-18', '2026-06-18', 'Estaleiro Naval', 0, 1, '2026-06-19', '2027-06-19', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-CSN-9/26', SHA2('cert9csn2026', 256), 'Boa Esperança', 'PA-2024-003', 'PPE9012', 'pesca artesanal', 'pesqueiro', '2021', 18.50, '85', 'COSTEIRA', 'Área 2', 'Yamaha', '150', 'alumínio', 0, 12, 'tripulantes', 'REL-2026-009', '2026-06-20', '2026-06-20', 'Porto de Belém', 0, 1, '2026-06-21', '2027-06-21', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 12. CERTIFICADOS CNBL (Certificado de Navegação Borda Livre)
-- ============================================================

INSERT IGNORE INTO `certificados_cnbl` (`id`, `numero`, `token_assinatura`, `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `atividades_servicos`, `tipo_embarcacao`, `ano_construcao`, `comprimento_total`, `comprimento_casco`, `boca_moldada`, `borda_livre_mm`, `borda_livre_tipo`, `calado_maximo_m`, `pontal_moldado`, `arqueacao_bruta`, `tipo_navegacao`, `area_navegacao`, `material_casco`, `relatorio_numero`, `data_vistoria`, `local_vistoria`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-CNBL-1/26', SHA2('cnbl12026', 256), 'Atlântico Sul', 'PA-2024-004', 'PPC3456', 'transporte carga', 'carga geral', '2020', 65.00, 60.00, 11.00, 850, 'verão', 4.50, 5.50, '1200', 'MAR ABERTO', 'Costa Brasileira', 'aço', 'REL-CNBL-001', '2026-06-10', 'Estaleiro Naval', '2026-06-11', '2027-06-11', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-CNBL-2/26', SHA2('cnbl22026', 256), 'Vitória Régia', 'PA-2024-005', 'PPV7890', 'transporte passageiros', 'passageiros', '2024', 32.00, 29.50, 7.00, 420, 'tropical', 2.80, 3.20, '320', 'INTERIOR', 'Área 3', 'aço', 'REL-CNBL-002', '2026-06-12', 'Porto de Belém', '2026-06-13', '2027-06-13', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 13. CERTIFICADOS CNARQ (Certificado Nacional de Arqueação)
-- ============================================================

INSERT IGNORE INTO `certificados_cnarq` (`id`, `numero`, `token_assinatura`, `nome_embarcacao`, `numero_inscricao`, `indicativo_chamada`, `tipo_embarcacao`, `ano_construcao`, `material_casco`, `porto_inscricao`, `local_construcao`, `comprimento_total`, `comprimento_casco`, `comprimento_lpp`, `boca_moldada`, `boca_maxima`, `pontal_moldado`, `arqueacao_bruta`, `arqueacao_liquida`, `metodo_arqueacao`, `relatorio_numero`, `data_vistoria`, `local_vistoria`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-CNARQ-1/26', SHA2('cnarq12026', 256), 'Atlântico Sul', 'PA-2024-004', 'PPC3456', 'carga geral', '2020', 'aço', 'Belém', 'Estaleiro Naval de Belém', 65.00, 60.00, 58.00, 11.00, 11.50, 5.50, 1250.00, 950.00, 'NORMAM-01', 'REL-ARQ-001', '2026-06-08', 'Estaleiro Naval', '2026-06-09', '2027-06-09', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-CNARQ-2/26', SHA2('cnarq22026', 256), 'Vitória Régia', 'PA-2024-005', 'PPV7890', 'passageiros', '2024', 'aço', 'Belém', 'Estaleiro Naval de Belém', 32.00, 29.50, 27.00, 7.00, 7.40, 3.20, 340.00, 250.00, 'NORMAM-01', 'REL-ARQ-002', '2026-06-11', 'Porto de Belém', '2026-06-12', '2027-06-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 14. CERTIFICADOS LP (Licença Provisória)
-- ============================================================

INSERT IGNORE INTO `certificados_lp` (`id`, `numero_lp`, `embarcacao_id`, `token_assinatura`, `tipo_licenca`, `nome_embarcacao`, `tipo_embarcacao`, `numero_casco`, `material_casco`, `comprimento_total`, `boca_moldada`, `pontal_moldado`, `proprietario_nome`, `proprietario_cpf_cnpj`, `proprietario_endereco`, `estaleiro_nome`, `estaleiro_cpf_cnpj`, `estaleiro_endereco`, `observacoes_exigencias`, `data_emissao`, `validade_dias`, `validade_data`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `dados_json`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-LP-1/26', '11111111-1111-1111-1111-111111111111', SHA2('lp12026', 256), 'construção', 'Estrela do Mar', 'ferry boat', 'HULL-2024-001', 'aço', 45.50, 8.50, 4.20, 'Maria Fernanda Costa', '98765432100', 'Av. Nazaré, 456 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', 'Atender todas as normas de segurança', '2026-06-01', 180, '2026-11-28', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-LP-2/26', '22222222-2222-2222-2222-222222222222', SHA2('lp22026', 256), 'alteração', 'Rio Amazonas', 'rebocador', 'HULL-2024-002', 'aço', 28.00, 6.20, 3.80, 'Pedro Henrique Santos', '45678912300', 'Travessa Quintino Bocaiúva, 789 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', 'Alteração do sistema de propulsão', '2026-06-05', 150, '2026-11-03', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-LP-3/26', '33333333-3333-3333-3333-333333333333', SHA2('lp32026', 256), 'construção', 'Boa Esperança', 'pesqueiro', 'HULL-2024-003', 'alumínio', 18.50, 5.00, 2.50, 'João Pedro Almeida', '12345678901', 'Rua das Flores, 123 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', 'Construção de embarcação de pesca artesanal', '2026-06-10', 180, '2026-12-07', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 15. CERTIFICADOS LC (Licença de Construção/Alteração/Reclassificação/LCEC)
-- ============================================================

INSERT IGNORE INTO `certificados_lc` (`id`, `numero_lc`, `embarcacao_id`, `token_assinatura`, `tipo_licenca`, `data_termino_construcao`, `nome_embarcacao`, `tipo_embarcacao`, `numero_casco`, `material_casco`, `sociedade_classificadora`, `comprimento_total`, `comprimento_pp`, `boca_moldada`, `pontal_moldado`, `calado_maximo`, `porte_bruto`, `numero_tripulantes`, `numero_passageiros`, `tipo_navegacao`, `area_navegacao`, `atividade_servico`, `propulsao`, `proprietario_nome`, `proprietario_cpf_cnpj`, `proprietario_endereco`, `estaleiro_nome`, `estaleiro_cpf_cnpj`, `estaleiro_endereco`, `data_emissao`, `data_validade`, `local_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `dados_json`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-LC-1/26', '11111111-1111-1111-1111-111111111111', SHA2('lc12026', 256), 'LC', '2024-12-15', 'Estrela do Mar', 'ferry boat', 'HULL-2024-001', 'aço', 'Bureau Veritas', 45.50, 43.00, 8.50, 4.20, 3.50, 450.00, 12, 200, 'MAR ABERTO', 'Costa Brasileira', 'transporte passageiros', 'motor diesel', 'Maria Fernanda Costa', '98765432100', 'Av. Nazaré, 456 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', '2026-06-02', '2027-06-02', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-LC-2/26', '22222222-2222-2222-2222-222222222222', SHA2('lc22026', 256), 'LA', NULL, 'Rio Amazonas', 'rebocador', 'HULL-2024-002', 'aço', 'DNV', 28.00, 25.50, 6.20, 3.80, 2.80, 280.00, 8, 0, 'MAR ABERTO', 'Área 1', 'rebocador', 'motor diesel', 'Pedro Henrique Santos', '45678912300', 'Travessa Quintino Bocaiúva, 789 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', '2026-06-08', '2027-06-08', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-EC-1/26', '55555555-5555-5555-5555-555555555555', SHA2('ec12026', 256), 'LCEC', '2024-11-30', 'Vitória Régia', 'passageiros', 'HULL-2024-005', 'aço', 'Bureau Veritas', 32.00, 30.00, 7.00, 3.20, 2.50, 320.00, 10, 150, 'INTERIOR', 'Área 3', 'transporte passageiros', 'motor diesel', 'Navegação Amazônica S/A', '98765432000155', 'Av. Perimetral, 1000 - Belém, PA', 'Estaleiro Naval de Belém', '12345678000199', 'Rodovia BR-316, Km 5 - Ananindeua, PA', '2026-06-12', '2027-06-12', 'Belém-PA', 'Rosano Souza Capitao', 'Engenheiro', 'CREA: 42.555', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 16. CERTIFICADOS CHT (Certificado de Homologação Técnica)
-- ============================================================

INSERT IGNORE INTO `certificados_cht` (`id`, `numero_relatorio_ht`, `token_assinatura`, `profissional_empresa`, `cpf_cnpj`, `atividade_homologada`, `observacoes`, `data_emissao`, `assinante_nome`, `assinante_titulo`, `assinante_registro`, `assinatura_imagem`, `assinatura_ip`, `assinatura_em`, `assinado`, `dados_json`, `status`, `ativo`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'AM-REL-HT-1/26', SHA2('cht12026', 256), 'Carlos Engenharia Naval Ltda', '12345678000188', 'Projeto e construção de embarcações de até 50m', 'Homologação válida para projetos de embarcações de passageiros e carga', '2026-06-01', 'Rosano Souza Capitao', 'Diretor', 'Portaria nº 123/2026', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-REL-HT-2/26', SHA2('cht22026', 256), 'Ana Paula Silva - Consultoria', NULL, 'Vistoria e inspeção de embarcações', 'Homologação para vistorias técnicas', '2026-06-05', 'Rosano Souza Capitao', 'Diretor', 'Portaria nº 124/2026', NULL, NULL, NULL, 0, NULL, 'emitido', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'AM-REL-HT-3/26', SHA2('cht32026', 256), 'Roberto Lima Serviços Marítimos', NULL, 'Manutenção e reparo de sistemas de propulsão', 'Especialização em motores diesel marítimos', '2026-06-10', 'Rosano Souza Capitao', 'Diretor', 'Portaria nº 125/2026', NULL, NULL, NULL, 0, NULL, 'rascunho', 1, '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 17. CONVALIDAÇÕES (Para certificados CNBL e CNARQ)
-- ============================================================

INSERT IGNORE INTO `cert_convalidacoes` (`id`, `tipo_certificado`, `certificado_id`, `numero_vistoria`, `data_inicio`, `data_fim`, `local_data`, `vistoriador`) VALUES
(UUID(), 'CNBL', (SELECT id FROM certificados_cnbl WHERE numero = 'AM-CNBL-1/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-11', '2027-06-11', 'Estaleiro Naval', 'Rosano Souza Capitao'),
(UUID(), 'CNBL', (SELECT id FROM certificados_cnbl WHERE numero = 'AM-CNBL-2/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-13', '2027-06-13', 'Porto de Belém', 'Rosano Souza Capitao'),
(UUID(), 'CNARQ', (SELECT id FROM certificados_cnarq WHERE numero = 'AM-CNARQ-1/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-09', '2027-06-09', 'Estaleiro Naval', 'Rosano Souza Capitao'),
(UUID(), 'CNARQ', (SELECT id FROM certificados_cnarq WHERE numero = 'AM-CNARQ-2/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-12', '2027-06-12', 'Porto de Belém', 'Rosano Souza Capitao');

-- ============================================================
-- 18. CONVALIDAÇÕES CSN (Já existem, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `csn_convalidacoes` (`id`, `certificado_id`, `numero_vistoria`, `data_inicio`, `data_fim`, `local_data`, `vistoriador`) VALUES
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-7/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-16', '2027-06-16', 'Porto de Belém', 'Rosano Souza Capitao'),
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-8/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-19', '2027-06-19', 'Estaleiro Naval', 'Rosano Souza Capitao'),
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-9/26' LIMIT 1), '1ª VIST. ANUAL', '2026-06-21', '2027-06-21', 'Porto de Belém', 'Rosano Souza Capitao');

-- ============================================================
-- 19. DISTRIBUIÇÃO DE PASSAGEIROS (CSN)
-- ============================================================

INSERT IGNORE INTO `csn_distribuicao_passageiros` (`id`, `certificado_id`, `local_nome`, `quantidade`) VALUES
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-7/26' LIMIT 1), 'convés superior', 100),
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-7/26' LIMIT 1), 'convés inferior', 100),
(UUID(), (SELECT id FROM certificados_csn WHERE numero = 'AM-CSN-9/26' LIMIT 1), 'área interna', 12);

-- ============================================================
-- 20. FINANCEIRO (Já existem 2, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `financeiro_lancamentos` (`id`, `tipo`, `descricao`, `valor`, `data`, `categoria`, `observacoes`, `criado_por`, `criado_em`, `atualizado_em`) VALUES
(UUID(), 'RECEITA', 'Taxa de vistoria - Estrela do Mar', 850.00, '2026-06-16', 'Serviços', 'Vistoria inicial', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'RECEITA', 'Taxa de vistoria - Rio Amazonas', 750.00, '2026-06-19', 'Serviços', 'Vistoria anual', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'RECEITA', 'Taxa de certificação CNBL', 1200.00, '2026-06-11', 'Certificação', 'Certificado CNBL - Atlântico Sul', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'RECEITA', 'Taxa de certificação CNARQ', 1500.00, '2026-06-12', 'Certificação', 'Certificado CNARQ - Vitória Régia', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'DESPESA', 'Material de escritório', 350.00, '2026-06-15', 'Administrativo', 'Compra de papel e impressora', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'DESPESA', 'Manutenção de equipamentos', 1200.00, '2026-06-18', 'Manutenção', 'Manutenção de equipamentos de medição', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'RECEITA', 'Taxa de licença LP', 600.00, '2026-06-05', 'Licenciamento', 'Licença provisória - Rio Amazonas', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW()),
(UUID(), 'RECEITA', 'Taxa de licença LC', 900.00, '2026-06-08', 'Licenciamento', 'Licença de construção - Estrela do Mar', '95eb5557-65e8-11f1-85ef-047c16b568a3', NOW(), NOW());

-- ============================================================
-- 21. LOGS DE ATIVIDADE (Já existem 8, vamos adicionar mais)
-- ============================================================

INSERT IGNORE INTO `logs_atividade` (`id`, `usuario_id`, `acao`, `descricao`, `ip`, `criado_em`) VALUES
(9, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'cliente_criado', 'Cliente criado: João Pedro Almeida', '::1', NOW()),
(10, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'cliente_criado', 'Cliente criado: Maria Fernanda Costa', '::1', NOW()),
(11, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'cliente_criado', 'Cliente criado: Pedro Henrique Santos', '::1', NOW()),
(12, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'embarcacao_criada', 'Embarcação criada: Estrela do Mar', '::1', NOW()),
(13, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'embarcacao_criada', 'Embarcação criada: Rio Amazonas', '::1', NOW()),
(14, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'embarcacao_criada', 'Embarcação criada: Boa Esperança', '::1', NOW()),
(15, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'vistoria_criada', 'Vistoria criada: VIST-2026-001', '::1', NOW()),
(16, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'vistoria_criada', 'Vistoria criada: VIST-2026-002', '::1', NOW()),
(17, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'agendamento_criado', 'Agendamento criado: OS-2026-001', '::1', NOW()),
(18, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'agendamento_criado', 'Agendamento criado: OS-2026-002', '::1', NOW()),
(19, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_criado', 'Certificado AM-CSN-7/26 criado', '::1', NOW()),
(20, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_csn_criado', 'Certificado AM-CSN-8/26 criado', '::1', NOW()),
(21, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_cnbl_criado', 'Certificado AM-CNBL-1/26 criado', '::1', NOW()),
(22, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_cnarq_criado', 'Certificado AM-CNARQ-1/26 criado', '::1', NOW()),
(23, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_lp_criado', 'Certificado AM-LP-1/26 criado', '::1', NOW()),
(24, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_lc_criado', 'Certificado AM-LC-1/26 criado', '::1', NOW()),
(25, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'certificado_cht_criado', 'Certificado AM-REL-HT-1/26 criado', '::1', NOW()),
(26, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'financeiro_lancamento', 'Lançamento financeiro: Taxa de vistoria', '::1', NOW()),
(27, '95eb5557-65e8-11f1-85ef-047c16b568a3', 'financeiro_lancamento', 'Lançamento financeiro: Taxa de certificação', '::1', NOW());

-- ============================================================
-- FINALIZAÇÃO
-- ============================================================

COMMIT;

-- ============================================================
-- RESUMO DOS DADOS INSERIDOS
-- ============================================================
-- Usuários: +3 vistoriadores (total 5)
-- Pessoas: +5 (total 6)
-- Clientes: +6 (total 6)
-- Embarcações: +5 (total 8)
-- Clientes_Embarcações: +5 relações
-- Sequenciais: +7 tipos de documento
-- Vistorias: +4 (total 5)
-- Vistoria Exigências: +10 itens
-- Agendamentos: +5 (total 5)
-- Ordens de Serviço: +2 (total 2)
-- Certificados CSN: +3 (total 8)
-- Certificados CNBL: +2 (total 2)
-- Certificados CNARQ: +2 (total 2)
-- Certificados LP: +3 (total 3)
-- Certificados LC: +3 (total 3)
-- Certificados CHT: +3 (total 3)
-- Convalidações: +7 (total 7)
-- Distribuição Passageiros: +3 (total 4)
-- Financeiro: +8 (total 10)
-- Logs: +19 (total 27)
-- ============================================================