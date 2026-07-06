# Módulo: emails

## O que faz
Gerencia as rotinas relativas a emails. (Extraído da leitura de 1 arquivos no diretório `modules\emails`)

## Tabelas usadas (Mapeadas das Queries)
- `email_logs`
- `usuarios`

## Campos processados via POST
- `action`
- `id`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
