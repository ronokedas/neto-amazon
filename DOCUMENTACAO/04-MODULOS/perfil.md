# Módulo: perfil

## O que faz
Gerencia as rotinas relativas a perfil. (Extraído da leitura de 1 arquivos no diretório `modules\perfil`)

## Tabelas usadas (Mapeadas das Queries)
- `usuarios`

## Campos processados via POST
- `atualizar_perfil`
- `csrf_token`
- `nome`
- `email`
- `senha_atual`
- `nova_senha`
- `confirma_senha`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
