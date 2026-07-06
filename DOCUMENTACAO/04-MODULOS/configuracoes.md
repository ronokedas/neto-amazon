# Módulo: configuracoes

## O que faz
Gerencia as rotinas relativas a configuracoes. (Extraído da leitura de 6 arquivos no diretório `modules\configuracoes`)

## Tabelas usadas (Mapeadas das Queries)
- `configuracoes`
- `usuarios`

## Campos processados via POST
- `action`
- `redirect_to`
- `csrf_token`
- `cfg`
- `acesso_documentacao`
- `acesso_financeiro`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
