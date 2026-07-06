# Módulo: dashboard

## O que faz
Gerencia as rotinas relativas a dashboard. (Extraído da leitura de 1 arquivos no diretório `modules\dashboard`)

## Tabelas usadas (Mapeadas das Queries)
- `configuracoes`
- `embarcacoes`
- `clientes`
- `vistorias`
- `agendamentos`
- `propostas`
- `financeiro_lancamentos`

## Campos processados via POST
- N/A

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
