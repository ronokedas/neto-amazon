# Módulo: agendamentos

## O que faz
Gerencia as rotinas relativas a agendamentos. (Extraído da leitura de 4 arquivos no diretório `modules\agendamentos`)

## Tabelas usadas (Mapeadas das Queries)
- `propostas`
- `propostas_embarcacoes`
- `propostas_servicos`
- `agendamentos`
- `ordens_servico`
- `email_logs`
- `clientes`
- `usuarios`
- `servicos`
- `embarcacoes`

## Campos processados via POST
- `csrf_token`
- `action`
- `proposta_id`
- `embarcacao_id`
- `cliente_id`
- `tipo_vistoria`
- `data_vistoria`
- `hora_vistoria`
- `local`
- `contato_nome`
- `contato_telefone`
- `observacoes`
- `vistoriador_id`
- `vendedor_id`
- `id`
- `status`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.
