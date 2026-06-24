# 📋 Documentação do Sistema ERP - Amazon Naval

> **Nome do Sistema:** Sistema Amazon  
> **Versão:** 1.0.0  
> **Tecnologia:** PHP 8.x + MySQL + HTML/CSS/JavaScript  
> **Arquitetura:** Monolito modular com roteador próprio  
> **Autenticação:** Sessão PHP com controle de acesso por cargo (ADMIN / VISTORIADOR)

---

## Índice

1. [Visão Geral](#1-visão-geral)
2. [Módulos do Sistema](#2-módulos-do-sistema)
   - [2.1 Login](#21-login)
   - [2.2 Dashboard](#22-dashboard)
   - [2.3 Clientes](#23-clientes)
   - [2.4 Embarcações](#24-embarcações)
   - [2.5 Pessoas](#25-pessoas)
   - [2.6 Vistorias](#26-vistorias)
   - [2.7 Agendamentos](#27-agendamentos)
   - [2.8 Comercial](#28-comercial)
   - [2.9 Financeiro](#29-financeiro)
   - [2.10 Documentação](#210-documentação)
   - [2.11 Usuários](#211-usuários)
   - [2.12 E-mails](#212-e-mails)
   - [2.13 Configurações](#213-configurações)
3. [Estrutura de Diretórios](#3-estrutura-de-diretórios)
4. [Banco de Dados](#4-banco-de-dados)
5. [Funcionalidades Transversais](#5-funcionalidades-transversais)
6. [Regras de Negócio](#6-regras-de-negócio)
7. [Tecnologias e Dependências](#7-tecnologias-e-dependências)

---

## 1. Visão Geral

O **Sistema Amazon** é um ERP (Enterprise Resource Planning) desenvolvido sob medida para gestão de serviços navais, focado em:

- **Vistorias técnicas** de embarcações
- **Gestão comercial** (propostas e serviços)
- **Controle financeiro** (receitas e despesas)
- **Emissão de certificados** (CSN, CNBL, CNARQ)
- **Agendamento e ordens de serviço**
- **Cadastro de clientes, embarcações e pessoas**

### Controle de Acesso

O sistema possui **dois níveis de acesso**:

| Cargo | Acesso |
|-------|--------|
| **ADMIN** | Acesso total a todos os módulos do sistema |
| **VISTORIADOR** | Acesso restrito a módulos operacionais (Dashboard, Embarcações, Pessoas, Vistorias, Agendamentos) |

---

## 2. Módulos do Sistema

### 2.1 Login

**Arquivo:** `modules/login/index.php`

- Tela de autenticação com email e senha
- Verificação de credenciais com `password_hash()` / `password_verify()`
- Controle de sessão com `$_SESSION`
- Redirecionamento automático para Dashboard após login
- Logout com destruição de sessão
- Rota pública para assinatura de certificados via token

---

### 2.2 Dashboard

**Arquivo:** `modules/dashboard/index.php`

Painel inicial com resumo geral do sistema. Exibe:

**Cards de Estatísticas (visíveis para todos):**
- Total de Embarcações ativas
- Total de Pessoas cadastradas
- Vistorias realizadas no mês
- Vistorias Pendentes
- **Meta Atingida** — percentual de faturamento em relação à meta mensal configurável

**Cards de Estatísticas (apenas ADMIN):**
- Total de Receitas do mês
- Total de Despesas do mês
- Quantidade de Propostas do mês
- Valor total das Propostas do mês

**Ações Rápidas:**
- Nova Vistoria
- Cadastrar Embarcação
- Cadastrar Pessoa
- Novo Lançamento Financeiro (ADMIN)
- Nova Proposta (ADMIN)
- Ver Agendamentos (VISTORIADOR)

**Últimas Vistorias:**
- Tabela com as 5 vistorias mais recentes
- Vistoriador vê apenas suas próprias vistorias
- ADMIN vê todas as vistorias

---

### 2.3 Clientes

**Arquivos:** `modules/clientes/`, `modules/clientes/actions.php`

**Funcionalidades:**
- Cadastro completo de clientes (Pessoa Física ou Jurídica)
- Perfis: **Armador**, **Proprietário**, **Despachante**
- Validação de CPF e CNPJ
- Vinculação de múltiplas embarcações por cliente
- Busca por nome, CPF/CNPJ ou email
- Desativação (soft delete) de clientes
- Log de atividades para todas as operações

**Campos do cadastro:**
- Nome, Tipo (PF/PJ), CPF/CNPJ, Perfil
- Telefone, Email, Endereço
- Embarcações vinculadas

---

### 2.4 Embarcações

**Arquivos:** `modules/embarcacoes/`, `modules/embarcacoes/actions.php`

**Funcionalidades:**
- Cadastro completo de embarcações
- Registro único (valida duplicidade)
- Desativação (soft delete)
- Vinculação a clientes (tabela `clientes_embarcacoes`)

**Campos do cadastro:**
- Nome, Registro, Tipo
- Proprietário, Ano, Observações

---

### 2.5 Pessoas

**Arquivos:** `modules/pessoas/`, `modules/pessoas/actions.php`

**Funcionalidades:**
- Cadastro de pessoas físicas (tripulantes, contatos, etc.)
- Vinculação a embarcações
- Desativação (soft delete)

---

### 2.6 Vistorias

**Arquivos:** `modules/vistorias/`, `modules/vistorias/actions.php`

**Funcionalidades:**

**Criação de Vistoria (Wizard em 3 passos):**
1. Selecionar Embarcação
2. Selecionar Pessoa responsável
3. Data e Observações

**Status disponíveis:**
- `PENDENTE` — vistoria criada, aguardando avaliação
- `APROVADA` — vistoria aprovada pelo ADMIN
- `REPROVADA` — vistoria reprovada
- `CANCELADA` — vistoria cancelada

**Relatório Técnico (vinculado a Agendamentos):**
- Observações técnicas detalhadas
- Lista de **Exigências** com:
  - Item, Descrição, Conforme (Sim/Não/Não se Aplica), Observação
- Geração automática de número de relatório (`AM-REL-V` ou `AM-REL-AP`)
- Ao aprovar/reprovar, avança automaticamente a Ordem de Serviço para "executado"
- Ao aprovar/reprovar, marca o agendamento como "concluído"

**Controle de Acesso:**
- VISTORIADOR: pode criar vistorias e salvar relatórios
- ADMIN: pode alterar status (aprovar/reprovar)

---

### 2.7 Agendamentos

**Arquivos:** `modules/agendamentos/`, `modules/agendamentos/actions.php`

**Funcionalidades:**

**CRUD completo:**
- Criação de agendamentos com dados de cliente, embarcação, tipo de vistoria
- Edição de agendamentos
- Visualização com filtros por status

**Fluxo de Confirmação e Geração de OS:**
1. Agendamento criado como `pendente`
2. ADMIN atribui vistoriador responsável
3. Ao **confirmar**, o sistema:
   - Gera número de Ordem de Serviço (`AM-OS-XXXX`)
   - Cria registro na tabela `ordens_servico`
   - Envia **e-mail automático de confirmação** para o cliente
   - Registra o envio no log de e-mails

**Cancelamento:**
- Cancela agendamento e OS vinculada
- Apenas agendamentos pendentes ou confirmados podem ser cancelados

**Status disponíveis:**
- `pendente`, `confirmado`, `em_andamento`, `concluido`, `cancelado`

**Endpoint AJAX:**
- `buscar_proposta` — carrega dados de cliente e embarcações a partir de uma proposta

---

### 2.8 Comercial

**Arquivos:** `modules/comercial/`

#### 2.8.1 Propostas

**Arquivos:** `modules/comercial/propostas/`, `modules/comercial/nova.php`, `modules/comercial/pdf.php`

**Funcionalidades:**
- Criação de propostas comerciais com:
  - Seleção de cliente
  - Múltiplas embarcações por proposta
  - Múltiplos serviços por embarcação
  - Preços baseados no cadastro de serviços
  - Desconto percentual global (0% a 100%)
  - Parcelamento (1 a 12x)
  - Forma de pagamento (à vista, parcelado, boleto, PIX)
- Geração automática de número (`AM-ORC-XXXX`)
- Validade de 30 dias
- Status: `rascunho`, `enviada`
- **Envio por e-mail** com:
  - Template HTML personalizado
  - PDF da proposta anexado (gerado dinamicamente)
  - Dados bancários e chave PIX no corpo do e-mail
- Geração de PDF para impressão
- Log de atividades

**Endpoint AJAX:**
- `embarcacoes_cliente` — retorna embarcações vinculadas a um cliente

#### 2.8.2 Serviços

**Arquivos:** `modules/comercial/servicos/`, `modules/comercial/servicos/actions.php`

**Funcionalidades:**
- Cadastro de serviços com preço padrão
- Ativação/desativação de serviços
- Utilizados na composição de propostas comerciais

---

### 2.9 Financeiro

**Arquivos:** `modules/financeiro/`, `modules/financeiro/actions.php`

**Funcionalidades:**
- Lançamentos de **Receitas** e **Despesas**
- Categorização por tipo
- Suporte a formato de valor brasileiro (1.234,56)
- Soft delete (exclusão lógica)
- Dashboard com totais do mês
- **Acesso exclusivo ADMIN**

**Campos do lançamento:**
- Tipo (Receita/Despesa), Descrição, Valor
- Data, Categoria, Observações

---

### 2.10 Documentação

**Arquivos:** `modules/documentacao/`

Módulo de emissão de certificados técnicos. Possui três tipos de certificados:

#### 2.10.1 Certificados CSN (Certificado de Segurança de Navegação)

**Arquivos:** `modules/documentacao/certificados/`

**Funcionalidades:**
- Cadastro completo com dados da embarcação:
  - Nome, Inscrição, Indicativo de Chamada
  - Atividades/Serviços, Tipo, Ano de Construção
  - Comprimento, Arqueação Bruta, Material do Casco
  - Fabricante do Motor, Potência (kW)
  - Autorização de Carga, Passageiros (com distribuição por local)
- Tipo de Navegação e Área de Navegação (múltipla escolha)
- Dados da Vistoria:
  - Número do Relatório, Local
  - Data da vistoria em seco e flutuando
  - Acessibilidade (Sim/Não)
- **Convalidações** — histórico de vistorias anteriores
- Geração de número automático (`AM-CSN-{seq}/{ano}`)
- Token de assinatura único (32 bytes hex)
- Status: `rascunho`, `emitido`, `cancelado`
- **Geração de PDF** do certificado
- **Envio de link para assinatura digital** por e-mail
- **Envio do certificado** por e-mail
- Soft delete

#### 2.10.2 Certificados CNBL (Certificado de Navegação em Bacias Lacustres)

**Arquivos:** `modules/documentacao/cnbl/`

Mesma estrutura do CSN, com dados específicos para navegação em bacias lacustres.

#### 2.10.3 Certificados CNARQ (Certificado de Navegação em Arquipélagos)

**Arquivos:** `modules/documentacao/cnarq/`

Mesma estrutura do CSN, com dados específicos para navegação em arquipélagos.

#### Assinatura Digital

**Arquivos:** `modules/documentacao/*/assinar.php`

- Rota pública: `assinar/{token_assinatura}`
- Identifica automaticamente o tipo de certificado pelo token
- Permite assinatura digital sem autenticação no sistema

---

### 2.11 Usuários

**Arquivos:** `modules/usuarios/`, `modules/usuarios/actions.php`

**Funcionalidades:**
- Cadastro de usuários com nome, email e senha
- Cargos: **ADMIN** ou **VISTORIADOR**
- Ativação/desativação de usuários
- Proteção contra auto-desativação
- Senha com hash (bcrypt via `password_hash`)
- Validação de email único
- **Acesso exclusivo ADMIN**

---

### 2.12 E-mails

**Arquivo:** `modules/emails/index.php`

**Funcionalidades:**
- Visualização do log de e-mails enviados pelo sistema
- Registro de: destinatário, assunto, tipo, status, data/hora
- Tipos de e-mail: `agendamento`, `proposta`, `certificado`

---

### 2.13 Configurações

**Arquivos:** `modules/configuracoes/`, `modules/configuracoes/actions.php`

**Funcionalidades:**
- Configuração da **Meta Mensal** de faturamento comercial
- Armazenamento em tabela chave-valor (`configuracoes`)
- Validação de formato brasileiro de valor
- **Acesso exclusivo ADMIN**

---

## 3. Estrutura de Diretórios

```
c:\sistema\
├── index.php                    # Roteador principal
├── config.php                   # Configurações do sistema
├── .htaccess                    # Configuração Apache
├── composer.json                # Dependências PHP
├── Dockerfile                   # Configuração Docker
├── docker-compose.yml           # Orquestração Docker
├── erp_sistema.sql              # Dump do banco de dados
│
├── assets/
│   ├── css/
│   │   └── style.css            # Estilos do sistema
│   ├── js/                      # Scripts JavaScript
│   └── img/                     # Imagens
│
├── includes/
│   ├── auth.php                 # Autenticação e controle de sessão
│   ├── functions.php            # Funções utilitárias
│   ├── header.php               # Header HTML padrão
│   ├── footer.php               # Footer HTML padrão
│   ├── sidebar.php              # Menu de navegação lateral
│   ├── mailer.php               # Envio de e-mails (PHPMailer)
│   ├── enviar_assinatura.php    # Envio de link de assinatura
│   └── enviar_certificado.php   # Envio de certificado por e-mail
│
├── modules/
│   ├── login/                   # Autenticação
│   ├── dashboard/               # Painel inicial
│   ├── clientes/                # Gestão de clientes
│   ├── embarcacoes/             # Gestão de embarcações
│   ├── pessoas/                 # Gestão de pessoas
│   ├── vistorias/               # Vistorias técnicas
│   ├── agendamentos/            # Agendamentos e OS
│   ├── comercial/               # Propostas e serviços
│   │   ├── propostas/           # Propostas comerciais
│   │   └── servicos/            # Cadastro de serviços
│   ├── financeiro/              # Controle financeiro
│   ├── documentacao/            # Certificados
│   │   ├── certificados/        # CSN
│   │   ├── cnbl/                # CNBL
│   │   └── cnarq/               # CNARQ
│   ├── usuarios/                # Gestão de usuários
│   ├── emails/                  # Log de e-mails
│   └── configuracoes/           # Configurações do sistema
│
├── migrations/                  # Scripts SQL de migração
├── templates/
│   └── email/                   # Templates HTML de e-mail
├── uploads/                     # Arquivos enviados
├── logs/                        # Logs do sistema
├── scripts/                     # Scripts auxiliares
├── docker/                      # Configurações Docker
└── docs/                        # Documentação
```

---

## 4. Banco de Dados

### Principais Tabelas

| Tabela | Descrição |
|--------|-----------|
| `usuarios` | Usuários do sistema (ADMIN/VISTORIADOR) |
| `clientes` | Clientes (PF/PJ) com perfis |
| `clientes_embarcacoes` | Vinculação cliente-embarcação |
| `embarcacoes` | Embarcações cadastradas |
| `pessoas` | Pessoas físicas (tripulantes/contatos) |
| `vistorias` | Vistorias técnicas |
| `vistoria_exigencias` | Itens de exigência do relatório técnico |
| `agendamentos` | Agendamentos de vistorias |
| `ordens_servico` | Ordens de Serviço geradas |
| `propostas` | Propostas comerciais |
| `propostas_embarcacoes` | Embarcações na proposta |
| `propostas_servicos` | Serviços na proposta (com preço aplicado) |
| `servicos` | Catálogo de serviços |
| `financeiro_lancamentos` | Lançamentos financeiros (receitas/despesas) |
| `certificados_csn` | Certificados CSN |
| `certificados_cnbl` | Certificados CNBL |
| `certificados_cnarq` | Certificados CNARQ |
| `csn_distribuicao_passageiros` | Distribuição de passageiros (CSN) |
| `csn_convalidacoes` | Convalidações (CSN) |
| `email_logs` | Log de envio de e-mails |
| `configuracoes` | Configurações chave-valor |
| `sequenciais_documentos` | Controle de numeração sequencial |

---

## 5. Funcionalidades Transversais

### 5.1 Controle de Sessão e Autenticação

- Sessão PHP com `session_start()`
- Função `verificar_sessao()` — redireciona para login se não autenticado
- Função `verificar_cargo('ADMIN')` — bloqueia acesso por cargo
- Token CSRF em todos os formulários POST

### 5.2 Log de Atividades

- Função `log_atividade($tipo, $descricao)`
- Registra todas as operações importantes no banco
- Tipos: criação, edição, exclusão, envio de e-mail, alteração de status

### 5.3 Envio de E-mails

- **PHPMailer** integrado
- Configuração SMTP (Gmail por padrão)
- Templates HTML personalizados:
  - `templates/email/agendamento.html` — confirmação de agendamento
  - `templates/email/proposta.html` — envio de proposta comercial
- Log de todos os e-mails enviados

### 5.4 Geração de Documentos

- **Números sequenciais** automáticos:
  - `AM-ORC-XXXX` — Propostas
  - `AM-OS-XXXX` — Ordens de Serviço
  - `AM-REL-V-XXXX` / `AM-REL-AP-XXXX` — Relatórios de Vistoria
  - `AM-CSN-{seq}/{ano}` — Certificados CSN
- **Geração de PDF** para propostas e certificados

### 5.5 Soft Delete

- Registros não são excluídos fisicamente
- Coluna `ativo` (boolean) ou `status` para controle
- Preserva integridade referencial e histórico

### 5.6 Segurança

- Senhas com hash bcrypt (`password_hash`)
- Validação de CPF/CNPJ
- Sanitização de inputs (`sanitizar()`)
- Proteção CSRF
- Controle de acesso por cargo
- Sessão com tempo limite

---

## 6. Regras de Negócio

### Fluxo de Vistoria

```
Agendamento (pendente)
    → Confirmação → Gera OS (pendente)
        → Vistoria realizada → Relatório Técnico
            → Aprovação/Reprovação (ADMIN)
                → OS executada
                → Agendamento concluído
                → Certificados liberados
```

### Regras Específicas

1. **Vistorias:** Vistoriador só vê suas próprias vistorias no dashboard
2. **Agendamentos:** Data não pode ser no passado; Vistoriador é auto-atribuído
3. **Propostas:** Preços são buscados do banco (não confiar no frontend)
4. **Financeiro:** Exclusivo ADMIN; valores em formato brasileiro
5. **Usuários:** ADMIN não pode desativar a si mesmo
6. **Certificados:** Token de assinatura único de 32 bytes; rota pública de assinatura
7. **OS:** Ao aprovar/reprovar vistoria, OS avança para "executado" automaticamente
8. **E-mail:** Envio automático na confirmação de agendamento

---

## 7. Tecnologias e Dependências

### Backend
- **PHP 8.x** — Linguagem principal
- **MySQL 8.x** — Banco de dados relacional
- **PDO** — Conexão segura com banco
- **PHPMailer** — Envio de e-mails

### Frontend
- **HTML5 + CSS3** — Interface responsiva
- **JavaScript** — Interatividade
- **Font Awesome** — Ícones
- **Tema:** `verde_escuro` (configurável)

### Infraestrutura
- **Apache** — Servidor web
- **Docker** — Containerização
- **Docker Compose** — Orquestração

### Ambiente de Desenvolvimento
- **Docker** com PHP 8.x + Apache + MySQL
- Porta padrão: `8080`
- Banco: `erp_sistema` / usuário: `erp_user`

---

> **Documentação gerada em:** Junho/2026  
> **Sistema:** Amazon Naval ERP