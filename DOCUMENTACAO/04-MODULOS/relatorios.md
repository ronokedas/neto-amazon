# Módulo: relatorios

## O que faz
Gerencia as rotinas relativas a relatorios. (Extraído da leitura de 1 arquivos no diretório `modules\relatorios`)

## Tabelas usadas (Mapeadas das Queries)
- Não identificadas tabelas ou lógicas com queries isoladas nestes arquivos (podem usar libs auxiliares).

## Campos processados via POST
- N/A

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
