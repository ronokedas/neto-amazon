# 🚢 Sistema Amazon — ERP para Certificação Naval

## Apresentação Comercial

---

<p align="center">
  <img src="../img/logo-amazon-certificadora.svg" alt="Amazon Certificadora" width="250">
</p>

---

## 1. Apresentação

O **Sistema Amazon** é um ERP (Sistema de Gestão Empresarial) **sob medida**, desenvolvido exclusivamente para empresas que atuam com **vistorias navais**, **certificação de embarcações** e serviços regulados pela **Marinha do Brasil**.

Ele foi construído do zero para automatizar todo o fluxo operacional, desde a emissão de uma proposta comercial até a geração dos certificados oficiais e o controle financeiro. Chega de planilhas, papéis soltos e processos manuais — o Sistema Amazon centraliza tudo em um lugar só.

---

## 2. O Problema (Processo Manual)

Empresas de vistoria naval que ainda operam de forma manual enfrentam desafios diários:

| Problema | Consequência |
|----------|-------------|
| Propostas feitas no Word/Excel | Retrabalho, erros de digitação, sem rastreabilidade |
| Agendamento por telefone/whatsapp | Conflito de horários, perda de informações |
| Vistorias com papel e caneta | Dados perdidos, ilegibilidade, sem padrão |
| Relatórios feitos manualmente | Demora na entrega, retrabalho |
| Certificados preenchidos à mão | Erros, inconsistência com a Marinha |
| Controle financeiro em planilha | Inadimplência, sem visão de caixa |
| Comunicados por telefone | Clientes sem acesso a documentos |

**O Sistema Amazon resolve tudo isso com um fluxo automatizado e integrado.**

---

## 3. Fluxo Principal do Sistema

```
┌─────────────────────────────────────────────────────────────┐
│                   FLUXO OPERACIONAL COMPLETO                  │
├───────────┬───────────┬───────────┬───────────┬──────────────┤
│  01       │  02       │  03       │  04       │  05          │
│ PROPOSTA  │AGENDAMENTO│ VISTORIA  │APROVAÇÃO  │ CERTIFICADOS │
│           │           │           │           │              │
│ • Criação │ • Agenda  │ • Check-  │ • Admin   │ • CSN       │
│ • Serviços│   vistoria│   list    │   aprova  │ • CNBL      │
│ • Envio   │ • Define  │ • Fotos   │ • Exigên- │ • CNARQ     │
│   p/      │   vistoria│ • Relató- │   cias    │ • LP        │
│   cliente │   dor     │   rio PDF │ • Baixa   │ • LC        │
│ • Assina- │ • Status  │           │   exigên- │ • CHT       │
│   tura    │   em tempo│           │   cias    │ • Assinatura│
│   digital │   real    │           │           │   digital   │
└─────┬─────┴─────┬─────┴─────┬─────┴─────┬─────┴──────┬──────┘
      │           │           │           │            │
      └───────────┴───────────┴───────────┴────────────┘
                              │
                  ┌───────────┴───────────┐
                  │        06             │
                  │     FINANCEIRO        │
                  │                       │
                  │ • Contas a receber    │
                  │ • Contas a pagar      │
                  │ • Comprovantes/Notas  │
                  │ • Relatórios          │
                  │ • Status de cobrança  │
                  └───────────────────────┘
```

---

## 4. Módulos do Sistema (Detalhamento)

### 4.1 Dashboard
Painel principal com indicadores estratégicos em tempo real:
- ✅ Total de embarcações cadastradas
- ✅ Total de proprietários ativos
- ✅ Vistorias realizadas no mês
- ✅ Vistorias pendentes
- ✅ Receitas e despesas do período
- ✅ Valor de propostas emitidas no mês
- ✅ Meta mensal de faturamento (barra de progresso)
- ✅ Gráfico de receitas vs despesas dos últimos 6 meses
- ✅ Vistorias recentes e pendentes
- ✅ Agendamentos abertos
- ✅ Relatórios aguardando aprovação

### 4.2 Embarcações
Cadastro completo de todas as embarcações:
- ✅ Nome, tipo, registro, ano de fabricação
- ✅ Proprietário vinculado
- ✅ Observações técnicas
- ✅ Status ativo/inativo
- ✅ Busca rápida por nome ou registro
- ✅ Filtro de tipos de embarcação
- ✅ Integração com vistorias, agendamentos e certificados

### 4.3 Vistorias
Módulo principal de operação com checklist completo:
- ✅ Check-in da vistoria com data e responsável
- ✅ Checklist dinâmico com perguntas por tipo de vistoria
- ✅ Respostas de checklist únicas por vistoria (sem duplicidade)
- ✅ Status: Pendente → Aguardando Aprovação → Aprovada / Reprovada / Cancelada
- ✅ Geração automática de relatório em PDF
- ✅ Relatório para aprovação administrativa
- ✅ Aprovação com ou sem exigências
- ✅ Histórico completo de vistorias por embarcação
- ✅ Controle de acesso por cargo (Vistoriador vê só as suas)

### 4.4 Agendamentos
Agenda inteligente de vistorias:
- ✅ Cadastro de agendamento com data, hora, local
- ✅ Vinculação com embarcação, cliente, vistoriador e vendedor
- ✅ Controle de status: pendente, confirmado, em andamento, concluído, cancelado
- ✅ Contato de emergência (nome e telefone)
- ✅ Geração de Ordem de Serviço (OS)
- ✅ Vistoriador vê apenas seus agendamentos
- ✅ Vendedor vê agendamentos vinculados a ele
- ✅ Filtros por status, data, cliente e embarcação

### 4.5 Financeiro
Módulo financeiro completo e avançado:
- ✅ Lançamentos de receitas e despesas
- ✅ Categorização por tipo e categoria
- ✅ Upload de comprovantes e notas fiscais (imagens, PDFs)
- ✅ Status de cobrança (pendente, pago, atrasado, cancelado)
- ✅ Filtros por data, tipo e categoria
- ✅ Relatórios financeiros com totais
- ✅ Cálculo de saldo (receitas - despesas)
- ✅ Soft delete (nada é perdido)
- ✅ Frequência de lançamentos
- ✅ Integração com propostas (valor de entrada)

### 4.6 Comercial (Propostas)
Gestão comercial completa com meta mensal:
- ✅ Criação de propostas com múltiplos serviços
- ✅ Serviços vinculados a tipos de embarcação
- ✅ Número sequencial automático (ex: AM-001/26)
- ✅ Envio de proposta por e-mail com PDF anexado
- ✅ Assinatura digital pública (cliente assina online via token)
- ✅ Dashboard comercial com meta de faturamento (R$ 50.000/mês)
- ✅ Indicador de desempenho (% da meta atingida)
- ✅ Histórico de propostas por cliente/embarcação
- ✅ Busca e filtros avançados

### 4.7 Contratos
Gestão de contratos e recorrências:
- ✅ Cadastro de contratos vinculados a propostas e clientes
- ✅ Número de contrato único
- ✅ Status: ativo, vigente, encerrado
- ✅ Contratos recorrentes com renovação automática
- ✅ Integração com o módulo financeiro
- ✅ Visualização detalhada de cada contrato

### 4.8 Certificados (CSN)
Certificado de Segurança de Navegação:
- ✅ Geração baseada em relatórios aprovados
- ✅ Número de relatório oficial (AM-CSN-XXX/ANO)
- ✅ Persistência de PDF com hash de integridade
- ✅ Assinatura digital com token único
- ✅ Status: rascunho, emitido, assinado, cancelado
- ✅ Armazenamento seguro no MinIO/S3

### 4.9 CNBL
Certificado de Navegação de Bacia Leste:
- ✅ Modelo oficial com campos da Marinha
- ✅ Numeração automática (AM-CNBL-XXX/ANO)
- ✅ Validade conforme tipo de embarcação (5 ou 10 anos)
- ✅ Convalidações anuais automáticas
- ✅ Assinatura digital
- ✅ PDF com hash de segurança

### 4.10 CNARQ
Certificado de Arqueação:
- ✅ Campos oficiais da autoridade marítima
- ✅ Numeração automática (AM-CNARQ-XXX/ANO)
- ✅ Assinatura digital
- ✅ PDF oficial com hash
- ✅ Convalidações conforme norma

### 4.11 LP
Licença de Portos (LP):
- ✅ Modelo oficial completo
- ✅ Numeração automática (AM-LP-XXX/ANO)
- ✅ Assinatura digital com token
- ✅ PDF persistido com hash
- ✅ Convalidações anuais

### 4.12 LC
Licença de Comercialização (LC):
- ✅ Modelo oficial completo
- ✅ Numeração automática (AM-LC-XXX/ANO)
- ✅ Assinatura digital com token
- ✅ PDF oficial

### 4.13 CHT
Certificado de Homologação Técnica:
- ✅ Certificado para profissionais/empresas homologadas
- ✅ Atividade homologada descritiva
- ✅ Número de relatório HT (AM-REL-HT-XXX/ANO)
- ✅ Assinatura digital com token
- ✅ Envio por e-mail ao destinatário

### 4.14 Clientes / Proprietários
Cadastro unificado de pessoas:
- ✅ Nome, CPF/CNPJ, e-mail, telefone
- ✅ Validação de CPF e CNPJ
- ✅ Perfil: proprietário
- ✅ Status: ativo, inativo
- ✅ Vinculação com embarcações
- ✅ Busca por nome, documento ou e-mail
- ✅ Portal do cliente integrado

### 4.15 Armadores
Cadastro de armadores (empresas armadoras):
- ✅ Dados completos da empresa armadora
- ✅ CNPJ, contato, endereço
- ✅ Vinculação com agendamentos e vistorias
- ✅ Status ativo/inativo

### 4.16 Despachantes
Cadastro de despachantes marítimos:
- ✅ Dados completos de cadastro
- ✅ Vinculação com certificados e processos
- ✅ Agilidade no fluxo documental

### 4.17 Usuários
Gestão de usuários com controle de acesso por cargo:
- ✅ Cargos: ADMIN, VENDEDOR, VISTORIADOR
- ✅ Permissões granulares por módulo
- ✅ Acesso à documentação e financeiro configurável
- ✅ Cadastro completo com e-mail e senha
- ✅ Log de atividades por usuário
- ✅ Sessão com expiração por inatividade (30 min)

### 4.18 Portal do Cliente
Área exclusiva para o cliente acessar:
- ✅ Login próprio (não precisa de cadastro interno)
- ✅ Visualização de documentos e certificados
- ✅ Troca de senha forçada no primeiro acesso
- ✅ Controle de ativação/desativação pelo admin
- ✅ Último login registrado
- ✅ Embarcações vinculadas visíveis

### 4.19 E-mails
Módulo de comunicação:
- ✅ Envio de e-mails pelo sistema (PHPMailer)
- ✅ Logs de envio com status
- ✅ Templates de e-mail personalizados
- ✅ Anexo automático de PDFs (propostas, certificados)
- ✅ Suporte a SMTP (Gmail, outros)

### 4.20 Configurações
Central de configurações do sistema:
- ✅ Configurações gerais
- ✅ Configurações básicas do sistema
- ✅ Backup do banco de dados manual e automático
- ✅ Scripts de backup Docker
- ✅ Meta mensal configurável

---

## 5. Diferenciais Técnicos

### 🔐 Assinatura Digital
- Geração de token único criptográfico para cada documento
- Rota pública de assinatura (`assinar/{token_assinatura}`) — o cliente recebe um link e assina online
- Aplicável a: Propostas, CSN, CNBL, CNARQ, LP, LC, CHT
- Registro de IP, data/hora e imagem da assinatura
- Hash de integridade em cada PDF gerado

### ☁️ Armazenamento em Nuvem (MinIO / S3)
- Armazenamento de PDFs, comprovantes e imagens em storage S3
- Compatível com Amazon S3, MinIO (servidor próprio) ou qualquer S3-compatible
- Fallback automático para armazenamento local caso o S3 esteja indisponível
- URLs públicas para acesso direto aos arquivos

### 📄 Geração de PDFs
- Propostas comerciais em PDF
- Relatórios de vistoria em PDF
- Certificados oficiais em PDF (CSN, CNBL, CNARQ, LP, LC, CHT)
- Hash de integridade (SHA-256) em cada PDF
- Persistência segura dos PDFs

### 🔒 Segurança
- Proteção CSRF em todos os formulários
- Validação de CPF e CNPJ
- Sessão com expiração automática
- Senhas com hash seguro
- Controle de acesso por cargo
- Log de atividades completo
- Soft delete em dados sensíveis

### 🐳 Dockerizado
- Ambiente completo em Docker (PHP 8.2 + MySQL 8.0 + MinIO)
- Script de deploy automatizado para Ubuntu/Debian
- Configuração por variáveis de ambiente
- Fácil migração entre servidores
- Backup do banco de dados com script shell

### 🔄 Numeração Sequencial
- Geração atômica de números de documentos com `SELECT FOR UPDATE`
- Sem risco de duplicidade mesmo em acesso simultâneo
- Formatos: AM-CSN-7/26, AM-CNBL-12/26, AM-REL-HT-3/26, etc.
- Prefixo personalizável

### 🔍 Busca Global
- Campo de busca disponível em todo o sistema
- Resultados em tempo real via AJAX

---

## 6. Proposta Comercial

### 💰 Investimento

| Item | Valor |
|------|-------|
| **Sistema completo (licença + código-fonte)** | **R$ 10.000,00** |
| Entrada (primeiro pagamento) | **R$ 1.000,00** |
| Saldo restante | **R$ 9.000,00** |
| Parcelamento | **9 parcelas de R$ 1.000,00** |
| **Total de parcelas** | **10 parcelas de R$ 1.000,00** |

### 📋 Condições

1. **Entrada de R$ 1.000,00** no fechamento do negócio
2. **9 parcelas mensais de R$ 1.000,00** (totalizando R$ 10.000)
3. **Acesso ao código-fonte e sistema completo liberado** somente após a **quitação total** do valor
4. Enquanto estiver pagando as parcelas, o sistema já estará instalado e funcionando para uso, mas o acesso completo aos arquivos do sistema será concedido apenas quando todas as parcelas forem pagas
5. Formas de pagamento: PIX, boleto bancário ou transferência

> 💡 **Pensando na sua empresa:** o parcelamento foi pensado para não pesar no caixa. Você já começa a usar o sistema desde o primeiro mês e vai pagando de forma suave enquanto o sistema já está gerando resultados.

### 🛠️ Suporte Incluso (3 meses)

O valor inclui **3 meses de suporte total** com:

| O que está incluso | Detalhes |
|-------------------|----------|
| ✅ Instalação completa | Servidor configurado e sistema rodando |
| ✅ Suporte via WhatsApp | Respostas em horário comercial |
| ✅ Correção de bugs | Todo bug encontrado será corrigido |
| ✅ Melhorias contínuas | Refinamentos e melhorias no que já existe |
| ✅ Ajustes e refinamentos | Pequenas adaptações no sistema atual |
| ✅ Treinamento básico | Orientações de uso para a equipe |
| ✅ Ambiente Docker | Configuração e deploy automatizado |

### ⚠️ Fora do Escopo (valor adicional)

- **Novos módulos** que não existem atualmente no sistema
- **Novas funcionalidades** além do que já está implementado
- **Integrações com sistemas externos** (API de terceiros, etc.)
- **Hospedagem/servidor** (o cliente deve providenciar a VPS ou servidor)
- Domínio e certificado SSL
- **Suporte além de 3 meses** (pode ser contratado à parte)

> Qualquer necessidade de **novo módulo ou funcionalidade extra** é combinada à parte e orçada separadamente.

---

## 7. Tecnologias Utilizadas

| Tecnologia | Versão | Finalidade |
|------------|--------|------------|
| **PHP** | 8.2 | Linguagem principal do sistema |
| **MySQL** | 8.0 | Banco de dados relacional |
| **Docker** | Última | Containerização do ambiente |
| **MinIO** | Última | Storage S3 para arquivos |
| **PHPMailer** | Última | Envio de e-mails |
| **JavaScript (Vanilla)** | — | Interatividade no frontend |
| **CSS3** | — | Estilização responsiva |
| **HTML5** | — | Estrutura das páginas |
| **AWS SDK** | — | Integração com S3/MinIO |
| **PDO** | — | Conexão segura com MySQL |

---

## 8. Próximos Passos

1. ✅ **Fechamento comercial** — definição do parcelamento e entrada
2. ✅ **Pagamento da entrada** (R$ 1.000,00)
3. ✅ **Instalação do sistema** em servidor VPS (cliente providencia)
4. ✅ **Configuração do ambiente** (Docker, banco de dados, domínio, SSL)
5. ✅ **Treinamento da equipe** (3 meses de suporte incluso)
6. ✅ **Início da operação** com suporte contínuo
7. ✅ **Acompanhamento e refinamentos** durante os 3 meses de suporte
8. ✅ **Quitação total** (R$ 10.000) → liberação do código-fonte completo

---

## 9. Contato

**Desenvolvedor:** Rosano Souza  
**E-mail:** contato@amazonnaval.com.br  
**WhatsApp / Telefone:** (91) 98934-0275  

---

<p align="center">
  <strong>Sistema Amazon — O ERP que sua empresa de certificação naval merece.</strong><br>
  <em>Automatize. Organize. Certifique.</em>
</p>