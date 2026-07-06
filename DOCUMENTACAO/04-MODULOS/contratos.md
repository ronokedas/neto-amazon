# Módulo: contratos

## O que faz
Gerencia as rotinas relativas a contratos. (Extraído da leitura de 4 arquivos no diretório `modules\contratos`)

## Tabelas usadas (Mapeadas das Queries)
- `contratos`
- `clientes`
- `propostas`

## Campos processados via POST
- `action`
- `csrf_token`
- `id`
- `cliente_id`
- `proposta_id`
- `numero`
- `status`
- `data_emissao`
- `data_vencimento`
- `valor_total`
- `conteudo`
- `frequencia`
- `dia_vencimento`
- `renovacao_automatica`
- `assinar`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
