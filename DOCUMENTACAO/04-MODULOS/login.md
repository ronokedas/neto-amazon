# Módulo: login

## O que faz
Gerencia as rotinas relativas a login. (Extraído da leitura de 1 arquivos no diretório `modules\login`)

## Tabelas usadas (Mapeadas das Queries)
- `usuarios`

## Campos processados via POST
- `email`
- `senha`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
