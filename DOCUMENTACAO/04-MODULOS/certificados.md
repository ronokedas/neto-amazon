# Módulo: Certificados (Wizard)

## O que faz
Este módulo atua EXCLUSIVAMENTE como um "Assistente / Wizard" de entrada. Ele NÃO é a tela final que emite os certificados, mas sim uma tela de passo a passo (Step 1, Step 2) que direciona o usuário para o módulo `documentacao/`. Ele coleta qual a embarcação e que tipo de certificado o usuário quer emitir (CSN, CNBL, etc.) e faz os `INSERTs` iniciais transferindo as variáveis básicas da tabela `embarcacoes` para a tabela final do documento, redirecionando o administrador em seguida.

## Quem tem acesso
- ADMIN: Sim (Acesso total).
- VENDEDOR: Não.
- VISTORIADOR: Não.

## Telas / Rotas
- `index.php` (Lista inicial para chamar o assistente)
- `wizard.php` (Passo 1: Selecionar Embarcação/Vistoria e Tipo de Certificado)
- `wizard_step2.php` (Passo 2: Efetua as cópias dos dados de `embarcacoes` para `certificados_csn` ou `certificados_cnbl` e redireciona).

## Tabelas usadas
- Lê fortemente de `embarcacoes` e `vistorias`.
- Escreve em `certificados_csn`, `certificados_cnbl`, `certificados_cnarq` etc, dependendo da escolha no select do Wizard.
⚠️ **Ambiguidade Resolvida:** O sistema NÃO possui uma tabela isolada chamada `certificados`. Toda a transação ocorre diretamente nas tabelas específicas de cada NORMAM.

## Regras de negócio identificadas
RN-WIZ-001: Ao avançar para o Passo 2, os dados físicos do barco como dimensões, arqueação e total de passageiros (`numero_passageiros_n1 + n2`) são COPIADOS estaticamente (snapshot) da embarcação para a tabela do certificado.
RN-WIZ-002: O cálculo de passageiros é apenas uma soma bruta (`n1 + n2`), e NÃO UMA FÓRMULA complexa de área de convés no sistema atual.
