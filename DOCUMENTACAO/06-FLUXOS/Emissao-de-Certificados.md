# Fluxo: Emissão de Certificados

---
## Certificado: CSN

**O que é:** Certificado de Segurança da Navegação.

**Pré-requisitos para emitir:**
Aprovação prévia da vistoria vinculada à embarcação.

**Quem pode emitir:**
ADMIN.

**Dados obrigatórios:**
Área de Navegação, Lotação de Passageiros e Tripulantes.

**Como a validade é calculada:**
Não identificado no código atual com clareza matemática; geralmente 5 anos somados à data de emissão.

**Numeração do documento:**
Sequencial extraído da tabela `sequenciais_documentos`.

**O que muda no banco ao emitir:**
INSERT na tabela `certificados_csn`.

**O que acontece com o certificado anterior:**
Não identificado no código atual um trigger que inativa os anteriores automaticamente de forma generalizada.

**Como o PDF é gerado:**
Biblioteca: TCPDF.
Template hardcoded no arquivo de ação do PDF da pasta de documentação, injetando variáveis vindas do banco de dados via PDO.
---