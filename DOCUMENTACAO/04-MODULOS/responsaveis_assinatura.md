# Módulo: responsaveis_assinatura

## O que faz
Gerencia as rotinas relativas a responsaveis_assinatura. (Extraído da leitura de 3 arquivos no diretório `modules\responsaveis_assinatura`)

## Tabelas usadas (Mapeadas das Queries)
- `responsaveis_assinatura`

## Campos processados via POST
- `action`
- `nome_completo`
- `cargo_titulo`
- `registro_profissional`
- `ativo`
- `id`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
