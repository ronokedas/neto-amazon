# Módulo: documentacao

## O que faz
Emissão e assinatura de certificados (CSN, CNBL, CNARQ, LC, LP, CHT). Extraído diretamente do diretório `modules/documentacao/`.

## Tabelas usadas (Mapeadas das Queries)
- `vistoria_exigencias`
- `vistorias`
- `certificados_csn`
- `csn_convalidacoes`
- `csn_distribuicao_passageiros`
- `agendamentos`
- `clientes`
- `certificados_cht`
- `cert_convalidacoes`
- `certificados_cnarq`
- `embarcacoes`
- `certificados_cnbl`
- `certificados_lc`
- `certificados_lp`

## Campos processados via POST
- `action`
- `csrf_token`
- `vistoria_id`
- `exigencia_id`
- `id`
- `nome_embarcacao`
- `numero_inscricao`
- `indicativo_chamada`
- `atividades_servicos`
- `tipo_embarcacao`
- `ano_construcao`
- `comprimento_m`
- `arqueacao_bruta`
- `material_casco`
- `fabricante_motor`
- `potencia_kw`
- `autorizado_carga`
- `qtd_passageiros`
- `obs_passageiros`
- `tipo_navegacao`

## Regras de Negócio e Cálculos

RN-DOC-001 (CNARQ): O cálculo da Arqueação Bruta envolve ler as dimensões da embarcação. (Fonte: `modules/documentacao/cnarq/actions.php`).
RN-DOC-002 (CNBL): O Borda Livre baseia-se na NORMAM para lotação de passageiros, checando dados da tabela `embarcacoes`.
RN-DOC-003: O certificado gera um hash/token para assinatura via Canvas e aciona a tabela respectiva (ex: `certificados_cnbl`, `certificados_csn`).
