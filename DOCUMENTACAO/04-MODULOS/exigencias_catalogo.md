# Módulo: exigencias_catalogo

## O que faz
Gerencia as rotinas relativas a exigencias_catalogo. (Extraído da leitura de 1 arquivos no diretório `modules\exigencias_catalogo`)

## Tabelas usadas (Mapeadas das Queries)
- `exigencias_catalogo`

## Campos processados via POST
- `action`
- `csrf_token`
- `id`
- `codigo_interno`
- `descricao`
- `item_normam`
- `tipo_vistoria`
- `prazo_padrao_dias`
- `ativo`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
