# Banco de Dados - Tabelas e Dicionário de Dados

---
### `clientes` (Proprietários/Armadores)
**O que armazena:** Representa as Pessoas Físicas e Jurídicas que são proprietárias legais das embarcações, e responsáveis financeiramente pelas vistorias e laudos.
**Criada por:** Módulo Clientes/Proprietários.
**Lida por:** Módulo de Vistorias, Certificados e Comercial/Financeiro.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) | Sim — PK | Identificador único UUID gerado no PHP. |
| tipo_pessoa | varchar(2) | Sim | 'PF' ou 'PJ', define as obrigatoriedades fiscais. |
| nome_razao | varchar(200) | Sim | Nome do armador ou Razão Social da empresa. |
| cpf_cnpj | varchar(20) | Sim | Identificador fiscal da Receita Federal. |
| email | varchar(150) | Sim | Contato usado para disparo automático de links de assinatura. |
| telefone | varchar(20) | Não | Celular ou contato comercial. |
| logradouro, numero, bairro, cidade, uf, cep | varchar | Sim | Endereço completo para faturamento e laudos oficiais. |
| data_cadastro | datetime | Não | Timestamp do momento do insert no sistema. |

**Regra de Obrigatório:** NOT NULL explícito no banco para as informações fiscais básicas.
**Relacionamentos reais:**
- Vinculado indiretamente por chaves na tabela `clientes_embarcacoes`.

**Soft delete:**
Não possui campo ativo explícito, usa deleção lógica nas referências.

---
### `embarcacoes`
**O que armazena:** O cadastro físico e estrutural dos barcos. É o coração do negócio.
**Criada por:** Módulo de Embarcações.
**Lida por:** Vistorias, Agendamentos, Certificados de Borda Livre e Arqueação.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) | Sim — PK | UUID único da embarcação. |
| nome_embarcacao | varchar(200) | Sim | Nome oficial registrado na Capitania dos Portos. |
| tipo_embarcacao | varchar(100) | Sim | Categoria aberta da NORMAM (ex: Balsa, Empurrador). |
| numero_inscricao | varchar(50) | Não | Número TIE/Registro oficial da Marinha. |
| comprimento | decimal(10,2) | Não | Comprimento Total (m) usado para cálculo de arqueação. |
| boca | decimal(10,2) | Não | Largura máxima da embarcação (m). |
| pontal | decimal(10,2) | Não | Altura do convés até a quilha (m). |
| material_casco | varchar(100) | Não | Ex: Aço, Madeira, Alumínio. |
| arqueacao_bruta | decimal(10,2) | Não | Volume total interno fechado do navio. |
| arqueacao_liquida | decimal(10,2) | Não | Volume útil de carga do navio. |
| motor_marca, motor_potencia | varchar | Não | Dados de motorização usados em vistorias mecânicas. |
| ano_construcao | int | Não | Ano do término do barco. |

**Campos calculados ou especiais:**
- `arqueacao_bruta`: Geralmente gerada pelo cálculo matemático `Comprimento * Boca * Pontal * Coeficiente de Forma`.

---
### `vistorias`
**O que armazena:** A folha de campo (Laudo Técnico). Cada vez que o técnico visita o barco, ele abre um registro aqui preenchendo as condições estruturais.
**Criada por:** Módulo de Vistorias.
**Lida por:** Emissão de Certificados e Dashboard Operacional.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) | Sim — PK | UUID da vistoria. |
| embarcacao_id | char(36) | Sim — FK | ID da Embarcação vistoriada. |
| usuario_id | char(36) | Sim — FK | O Vistoriador técnico responsável pelo laudo. |
| data_vistoria | date | Sim | Data em que a inspeção ocorreu in-loco. |
| status | enum | Sim | 'PENDENTE', 'EM_ANDAMENTO', 'APROVADA', 'REPROVADA'. |
| exigencias | text | Não | Checklist não aprovado que o proprietário precisa consertar. |
| fotos_json | json | Não | Array de caminhos de fotos tiradas durante a inspeção. |
| local_vistoria | varchar(255)| Não | Município/Porto onde a inspeção ocorreu. |

**Relacionamentos reais:**
- `embarcacao_id` referencia `embarcacoes.id` — Cada vistoria foca em um barco específico.
- `usuario_id` referencia `usuarios.id` — Rastreia qual inspetor preencheu o laudo.

---
### `certificados_csn` (Certificado de Segurança da Navegação)
**O que armazena:** Documento físico digital emitido pelo ADMIN comprovando que a embarcação pode navegar com segurança.
**Criada por:** Módulo Documentação / Certificados.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) | Sim — PK | UUID do CSN. |
| vistoria_id | char(36) | Sim — FK | A Vistoria APROVADA que lastreia este certificado. |
| data_emissao | date | Sim | Início da vigência do certificado. |
| data_validade | date | Sim | Vencimento. Geralmente calculado +5 anos da emissão. |
| numero_documento | varchar | Sim | Sequencial oficial impresso no PDF (Ex: CSN-001/2026). |
| area_navegacao | varchar | Não | Mar aberto, Navegação Interior (Rios), etc. |
| limite_passageiros| int | Não | Teto NORMAM de pessoas autorizadas no convés. |
| token_assinatura | varchar(64) | Sim | Hash usado na rota `/assinar/{token}`. |
| assinatura_b64 | text | Não | PNG em Base64 gravado via Canvas no portal. |
| status | varchar(20) | Sim | 'EMITIDO', 'ASSINADO', 'CANCELADO'. |

---
### `usuarios`
**O que armazena:** Contas de acesso ao painel interno.
**Criada por:** Painel Admin (Gestão de Usuários).

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) | Sim — PK | UUID interno. |
| nome | varchar(150) | Sim | Nome de exibição. |
| email | varchar(150) | Sim | Usado no Login. |
| senha | varchar(255) | Sim | Hash Bcrypt da senha. |
| perfil | enum | Sim | 'ADMIN', 'VENDEDOR', 'VISTORIADOR'. |
| ativo | tinyint(1) | Sim | Flag para bloqueio de login sem perder histórico (Soft Delete). |

---
## Campos que precisam ser RENOMEADOS na nova stack
- `numero_inscricao` (Embarcacoes): Deveria ser `registro_tie`, pois é o nome oficial que a capitania dos portos usa para a inscrição do barco.
- `nome_razao` (Clientes): Quebrar em `nome_fantasia` e `razao_social`, além de separar `cpf_cnpj` em dois campos explícitos para facilitar validação de ponta.
- `assinatura_b64`: Em vez de armazenar o binário gigante no banco que pesa a query, renomear para `assinatura_url` e salvar no Bucket do S3.

## Tabelas que deveriam ser UNIFICADAS ou SEPARADAS
- **Unificação Polimórfica:** As tabelas `certificados_csn`, `certificados_cnbl`, `certificados_cnarq` compartilham 90% da estrutura base (token_assinatura, data_emissao, vistoria_id). Elas deveriam ser uma tabela principal chamada `certificados_navais` com relacionamentos 1:1 para as tabelas filhas (que manteriam apenas as regras específicas de sua NORMAM), reduzindo espaguete de código.
- **Tabela de Arquivos:** `fotos_json` (Vistorias) deve ser separada numa tabela N:N `vistorias_fotos`, para não manter blob/arquivos massivos acoplados na entidade core do banco.
---
### `csn_convalidacoes`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade csn_convalidacoes.
**Criada/Lida por:** Módulos que referenciam csn_convalidacoes.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) NOT NULL DEFAULT uuid(), | Não | - |
| certificado_id | char(36) NOT NULL, | Não | - |
| numero_vistoria | varchar(50) DEFAULT NULL, | Não | - |
| data_inicio | date DEFAULT NULL, | Não | - |
| data_fim | date DEFAULT NULL, | Não | - |
| local_data | varchar(200) DEFAULT NULL, | Não | - |
| vistoriador | varchar(200) DEFAULT NULL | Não | - |

---
### `csn_distribuicao_passageiros`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade csn_distribuicao_passageiros.
**Criada/Lida por:** Módulos que referenciam csn_distribuicao_passageiros.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) NOT NULL DEFAULT uuid(), | Não | - |
| certificado_id | char(36) NOT NULL, | Não | - |
| local_nome | varchar(150) DEFAULT NULL, | Não | - |
| quantidade | int(11) DEFAULT 0 | Não | - |

---
### `clientes_embarcacoes`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade clientes_embarcacoes.
**Criada/Lida por:** Módulos que referenciam clientes_embarcacoes.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) NOT NULL DEFAULT uuid(), | Não | - |
| cliente_id | char(36) NOT NULL, | Não | - |
| embarcacao_id | char(36) NOT NULL, | Não | - |
| criado_em | datetime DEFAULT current_timestamp() | Não | - |

---
### `financeiro_lancamentos`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade financeiro_lancamentos.
**Criada/Lida por:** Módulos que referenciam financeiro_lancamentos.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) NOT NULL DEFAULT uuid(), | Não | - |
| tipo | enum( | Não | - |
| descricao | varchar(300) NOT NULL, | Não | - |
| valor | decimal(10,2) NOT NULL, | Não | - |
| data | date NOT NULL, | Não | - |
| categoria | varchar(100) DEFAULT NULL, | Não | - |
| observacoes | text DEFAULT NULL, | Não | - |
| criado_por | char(36) DEFAULT NULL, | Não | - |
| criado_em | datetime DEFAULT current_timestamp(), | Não | - |
| atualizado_em | datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() | Não | - |

---
### `sequenciais_documentos`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade sequenciais_documentos.
**Criada/Lida por:** Módulos que referenciam sequenciais_documentos.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | int(11) NOT NULL, | Não | - |
| tipo_documento | varchar(50) NOT NULL, | Não | - |
| ano | int(4) NOT NULL, | Não | - |
| ultimo_numero | int(11) NOT NULL DEFAULT 0, | Não | - |
| criado_em | datetime DEFAULT current_timestamp(), | Não | - |
| atualizado_em | datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() | Não | - |

---
### `logs_atividade`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade logs_atividade.
**Criada/Lida por:** Módulos que referenciam logs_atividade.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | int(11) NOT NULL, | Não | - |
| usuario_id | varchar(36) DEFAULT NULL, | Não | - |
| acao | varchar(100) DEFAULT NULL, | Não | - |
| descricao | text DEFAULT NULL, | Não | - |
| ip | varchar(45) DEFAULT NULL, | Não | - |
| criado_em | datetime DEFAULT current_timestamp() | Não | - |

---
### `pessoas`
**O que armazena:** [Extraído automaticamente] Armazena registros associados à entidade pessoas.
**Criada/Lida por:** Módulos que referenciam pessoas.

| Campo | Tipo real do SQL | Obrigatório | O que significa na prática |
|-------|-----------------|-------------|---------------------------|
| id | char(36) NOT NULL DEFAULT uuid(), | Não | - |
| tipo_pessoa | enum( | Não | - |
| nome_completo | varchar(200) NOT NULL, | Não | - |
| cpf | varchar(14) DEFAULT NULL, | Não | - |
| cnpj | varchar(18) DEFAULT NULL, | Não | - |
| rg | varchar(20) DEFAULT NULL, | Não | - |
| telefone | varchar(20) DEFAULT NULL, | Não | - |
| email | varchar(150) DEFAULT NULL, | Não | - |
| sexo | enum( | Não | - |
| endereco | text DEFAULT NULL, | Não | - |
| observacoes | text DEFAULT NULL, | Não | - |
| ativo | tinyint(1) NOT NULL DEFAULT 1, | Não | - |
| criado_por | char(36) DEFAULT NULL, | Não | - |
| criado_em | datetime DEFAULT current_timestamp(), | Não | - |
| atualizado_em | datetime DEFAULT current_timestamp() ON UPDATE current_timestamp() | Não | - |
