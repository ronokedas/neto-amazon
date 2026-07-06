# Módulo: Financeiro

## O que faz (em linguagem de negócio)
Gerencia o fluxo de faturamento das propostas comerciais e as pendências de cobrança relacionadas aos serviços de certificação e vistoria. Ele controla Contas a Receber e Quitações.

## Quem tem acesso
- ADMIN: Acesso total. Pode criar cobranças, registrar pagamentos manuais, gerar relatórios.
- VENDEDOR: Acesso parcial. Somente visualização das faturas vinculadas aos clientes sob sua carteira de atendimento.
- VISTORIADOR: Não identificado no código atual (Não tem acesso, seu escopo é puramente técnico).

## Telas e rotas existentes
- Rota: `index.php?action=financeiro` → Listagem geral de cobranças com status e filtros.
- Rota: `index.php?action=financeiro&sub=form` → Formulário de criação/edição de lançamento.
- Rota: `index.php?action=financeiro&sub=actions` → Processador das operações POST e AJAX.
- Rota: `index.php?action=financeiro&sub=relatorios` → Geração de Dashboards de Inadimplência/Recebíveis.

## Fluxo financeiro completo

### Passo 1: Origem da cobrança
A cobrança principal é gerada manualmente ou como resultado da aprovação de uma Proposta Comercial do módulo `comercial/propostas/`. O ADMIN cria o registro de Contas a Receber apontando para a proposta aprovada.

### Passo 2: Dados da cobrança
- Valor: Digitado manualmente baseando-se no escopo da proposta comercial (não há tabela de preços fixos parametrizada no banco).
- Vencimento: Definido no ato da criação do lançamento.
- Cliente vinculado: ID resgatado via Select/Autocomplete puxando de `clientes`.
- Referência: Tabela `propostas` ou preenchimento livre de texto.

### Passo 3: Notificação ao cliente
Não identificado no código atual disparo automatizado (CRON) de cobrança financeira por e-mail com geração de boleto via API. O sistema indica o recebimento via PIX genérico nas Propostas.

### Passo 4: Registro de pagamento
O pagamento é registrado manualmente por um ADMIN mudando o status da fatura ("dar baixa").
Campos preenchidos: Data do Pagamento, Valor Recebido, Conta de Destino.
O sistema não faz estrita validação contábil entre valor pago e valor cobrado se o administrador forçar a baixa.

### Passo 5: Status e relatórios
Status possíveis: PENDENTE, PAGO, CANCELADO.
Existem visualizações básicas em `financeiro/relatorios.php` que exportam ou listam totais por mês.

## Tabelas do banco usadas
- **Tabela:** `financeiro_lancamentos`
- **Para que serve:** Armazena o Conta a Receber/Pagar da empresa.
- **Campos principais:** `id`, `cliente_id`, `valor`, `data_vencimento`, `data_pagamento`, `status`.
- **Relacionamentos:** Relaciona-se por FK lógica com `clientes` (para apontar o devedor).

## Integrações com outros módulos
- O financeiro NÃO bloqueia automaticamente a geração de certificado (São fluxos paralelos não travados em código, permitindo flexibilidade operacional).
- O módulo comercial alimenta indiretamente o valor a ser faturado.

## Regras de negócio do financeiro
RN-FIN-001: Uma fatura só pode ser marcada como PAGA se houver o input de Data de Pagamento.
RN-FIN-002: Exclusão apenas lógica, não sendo permitido deletar transações já faturadas.

## Validações encontradas no código
- Não identificado no código atual travas contábeis pesadas. Apenas campos obrigatórios de interface (HTML required) no front-end.

## Problemas e débitos técnicos
Falta integração com Gateway de Pagamento (Asaas, Iugu, Stripe). Todo recebimento e baixa dependem da confiança humana do Administrador olhar o extrato bancário e vir manualmente no painel dar a baixa.

## O que o módulo financeiro NÃO faz
- Não gera Boleto Bancário registrado.
- Não envia notificações recorrentes e automáticas de cobrança vencida por WhatsApp ou E-mail.
- Não bloqueia a emissão de certificados navais caso o cliente esteja inadimplente.