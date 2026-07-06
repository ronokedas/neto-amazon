# Checklist de Reconstrução

Tudo que precisa ser reimplementado na nova stack, em ordem de dependência.
(o que precisa existir antes do quê)

## Fase 1 — Fundação (sem isso nada funciona)
- [ ] Configuração do projeto e ambiente
- [ ] Conexão com banco de dados
- [ ] Sistema de autenticação (login, sessão, logout)
- [ ] Controle de cargos e permissões
- [ ] Estrutura de roteamento
- [ ] Layout base com sidebar e navbar
- [ ] Migrations de todas as tabelas

## Fase 2 — Cadastros Base (dependências dos módulos principais)
- [ ] Módulo de Usuários (CRUD completo)
- [ ] Módulo de Proprietários (CRUD completo)
- [ ] Módulo de Embarcações (CRUD + vínculo com proprietário)

## Fase 3 — Módulos Operacionais
- [ ] Módulo de Vistorias (cadastro, edição, resultado)
- [ ] Geração de PDF — infraestrutura base
- [ ] Certificado tipo CSN
- [ ] Certificado tipo CNBL
- [ ] Certificado tipo CNARQ
- [ ] Certificado tipo LP
- [ ] Certificado tipo LC
- [ ] Certificado tipo CHT

## Fase 4 — Portal do Cliente
- [ ] Banco de dados do portal (tabela clientes_portal)
- [ ] Autenticação separada do portal
- [ ] Dashboard do proprietário
- [ ] Listagem de certificados por embarcação
- [ ] Visualização de PDF no portal
- [ ] Histórico de vistorias
- [ ] Recuperação de senha por email
- [ ] Módulo admin para criar acessos ao portal

## Fase 5 — Recursos Avançados
- [ ] Alertas de vencimento de certificados
- [ ] Relatórios e exportações
- [ ] Log de auditoria de todas as ações
- [ ] Notificações por email
- [ ] Painel de configurações do sistema

## Fase 6 — Qualidade e Lançamento
- [ ] Testes das regras de negócio críticas
- [ ] Revisão de segurança (vulnerabilidades listadas em 07-SEGURANCA)
- [ ] Configuração de SPF, DKIM, DMARC para emails
- [ ] Backup automático do banco
- [ ] Monitoramento de erros
- [ ] Migração dos dados do sistema antigo para o novo
- [ ] Teste com dados reais antes de lançar