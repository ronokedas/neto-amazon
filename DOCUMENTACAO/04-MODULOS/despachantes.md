# Módulo: despachantes

## O que faz
Gerencia as rotinas relativas a despachantes. (Extraído da leitura de 3 arquivos no diretório `modules\despachantes`)

## Tabelas usadas (Mapeadas das Queries)
- `clientes`
- `clientes_embarcacoes`
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
- `tipo_recebimento`
- `chave_pix`
- `banco`
- `agencia`
- `conta`
- `id`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
