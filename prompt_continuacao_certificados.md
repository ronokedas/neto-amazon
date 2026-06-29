# PROMPT DE CONTINUAÇÃO - CORREÇÃO DE CERTIFICADOS

## CONTEXTO DO PROJETO

Sistema ERP para a **Amazon Naval Ltda** (empresa de navegação). Precisamos corrigir a geração de PDF de 5 tipos de certificados para que o layout fique IDÊNTICO aos documentos de exemplo.

## ARQUITETURA

- Sistema PHP rodando em Docker
- Cada certificado tem 3 arquivos em `modules/documentacao/{tipo}/`:
  - `form.php` - formulário de criação/edição
  - `actions.php` - backend (salvar/excluir)
  - `pdf.php` - geração do PDF com TCPDF
- Tabelas no banco: `certificados_cnbl`, `certificados_cnarq`, `certificados_lp`, `certificados_lc`, `certificados_cht`

## O QUE JÁ FOI FEITO NO CNBL

### Banco de Dados
Colunas adicionadas na tabela `certificados_cnbl`:
- `aresta_superior_linha_conves`
- `centro_disco_situado`
- `dist_linha_conves_bico_proa`
- `dist_linha_conves_abaixo_disco`
- `marca_linha_carga_area1`
- `marca_linha_carga_area2`
- `acrescimo_agua_salgada`

### Arquivos modificados
- `modules/documentacao/cnbl/form.php` - seção "Marcas da Linha de Carga" adicionada
- `modules/documentacao/cnbl/actions.php` - salvamento dos novos campos
- `modules/documentacao/cnbl/pdf.php` - PDF populado com novos campos

### ⚠️ PROBLEMA
O layout do PDF **não ficou igual ao modelo original**. 
Arquivo de especificação: **`C:\sistema\correcao\prompt-correcao-certificado-CNBL.md`** (LEIA PRIMEIRO!)

## O QUE PRECISA SER FEITO

### 1. CORRIGIR O CNBL
Siga `correcao/prompt-correcao-certificado-CNBL.md` para refazer `modules/documentacao/cnbl/pdf.php`.

### 2. APLICAR MESMO PADRÃO AOS DEMAIS
Após o CNBL estar correto, aplicar para:
1. **CNARQ** - `modules/documentacao/cnarq/`
2. **LP** - `modules/documentacao/lp/`
3. **LC** - `modules/documentacao/lc/`
4. **CHT** - `modules/documentacao/cht/`

## FLUXO DE TRABALHO PARA CADA CERTIFICADO

1. Examinar o PDF de exemplo em `docs/exemplos/` (se houver)
2. Comparar com o PDF gerado pelo sistema atual
3. Identificar diferenças de layout, posicionamento, fontes, cores
4. Ajustar o `pdf.php` para ficar IDÊNTICO ao exemplo
5. Verificar se `form.php` e `actions.php` precisam de ajustes

## DICAS TÉCNICAS

- Usa **TCPDF** (via Composer: `vendor/autoload.php`)
- Funções úteis: `dataPorExtenso()`, `formatarDataBR()`, `converterImagemParaJpeg()`
- Brasão: `assets/img/brasao.png`
- Logo: `assets/img/logo.png`
- Docker configurado: use `docker-compose exec -T app` para comandos
- `test_desc.php` - script para adicionar colunas faltantes no banco

## REFERÊNCIAS

- Especificação do CNBL: `correcao/prompt-correcao-certificado-CNBL.md`
- Exemplos: `docs/exemplos/`
- Relatório de vistorias (já corrigido): `modules/vistorias/relatorio.php`