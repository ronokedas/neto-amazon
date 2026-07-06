# Módulo: armadores

## O que faz
Gerencia as rotinas relativas a armadores. (Extraído da leitura de 3 arquivos no diretório `modules\armadores`)

## Tabelas usadas (Mapeadas das Queries)
- `clientes_embarcacoes`
- `clientes`
- `embarcacoes`

## Campos processados via POST
- `csrf_token`
- `action`
- `nome`
- `tipo_pessoa`
- `cpf_cnpj`
- `perfil`
- `telefone`
- `email`
- `endereco`
- `embarcacoes_ids`
- `id`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
