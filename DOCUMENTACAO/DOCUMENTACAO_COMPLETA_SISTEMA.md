# Sistema Amazon Naval — Documentação Completa
*Gerada por auditoria de código. Use como contexto para reconstrução.*

## 1. O QUE É O SISTEMA
## 1. Visão Geral do Sistema e Objetivos de Negócio

**O que o sistema faz:**
O sistema é um ERP corporativo da Amazon Naval focado exclusivamente em gerenciar a cadeia de vistorias navais e emissão de certificados oficiais exigidos pela Marinha do Brasil (NORMAM). Ele atua como um hub central onde a empresa gerencia seus clientes (proprietários/armadores), as embarcações, os agendamentos de vistorias técnicas e a emissão final, desenhada e validada, de certificados em PDF assinados via QR Code/Canvas.

**Quem são os usuários:**
Conforme as regras de controle de acesso (login e perfis), existem três tipos de cargo/perfil no sistema:
- **ADMIN:** Controle total sobre todos os módulos (Financeiro, Aprovação de Documentos, Emissão de PDFs, Configurações de sistema e Acessos).
- **VENDEDOR:** Focado na prospecção (Comercial), faturamento de propostas, geração de orçamentos e cadastros base (Clientes e Embarcações).
- **VISTORIADOR:** Técnico que atua na ponta, realiza as inspeções nas embarcações, preenchendo as características técnicas, avaliando exigências e alimentando os relatórios de vistoria.

**Módulos Principais:**
- **Agendamentos:** Controle de visitas técnicas/Ordens de Serviço.
- **Armadores / Proprietários / Clientes:** Gestão de CRM, responsáveis legais pelas embarcações.
- **Embarcações:** Cadastro estrutural dos barcos vinculados aos clientes.
- **Vistorias:** Coração técnico do sistema, onde dados de borda livre, casco, salvatagem são reportados.
- **Documentação / Certificados:** Emissão final baseada nos relatórios de vistoria.
- **Financeiro:** Controle de faturamento e pagamentos de propostas.
- **Comercial (Propostas):** Geração de contratos e orçamentos para os clientes.

**Quais certificados o sistema emite:**
De acordo com o mapeamento de rotas e banco de dados, os documentos emitidos são:
- **CSN:** Certificado de Segurança da Navegação.
- **CNBL:** Certificado Nacional de Borda Livre.
- **CNARQ:** Certificado Nacional de Arqueação.
- **LC / LP / CHT:** Licenças e Certificados complementares (identificados no index.php como rotas específicas de assinatura).
- **Propostas Comerciais / Contratos.**

**O que o sistema NÃO faz (Limitações do Legado):**
- Não possui um App Mobile Nativo/Offline, obrigando o vistoriador a trabalhar online na plataforma Web ou preencher papel.
- Não guarda o documento físico histórico no banco de dados (S3/MinIO), dependendo da geração do PDF em tempo de execução via biblioteca.
- Não possui sincronização bidirecional de estado sem conexão (offline-first).

## 2. ARQUITETURA ATUAL (resumo)
## 2. Arquitetura Atual e Stack Tecnológica

**Linguagem e Banco de Dados:**
- **Linguagem:** PHP 8, processado nativamente no servidor web. Sem uso de frameworks modernos MVC (Laravel, Symfony). Código imperativo.
- **Banco de Dados:** MySQL 5.7 / MariaDB (identificado por limitação de UUID e `utf8mb4` no config.php).
- **Conexão:** Objeto nativo `PDO` (PHP Data Objects), configurado em `config.php` gerando as prepared statements. Charset definido em `utf8mb4`.

**Como o roteamento funciona:**
- O roteamento é gerido centralmente pelo `index.php` na raiz. 
- Ele varre a variável global `$_SERVER['REQUEST_URI']`, limpa o caminho e compara com um array de chaves e valores mapeados de rotas (ex: `'vistorias' => 'modules/vistorias/index.php'`). 
- Em vez de Controllers/Views separadas, ele apenas dá um `require_once` no arquivo respectivo. Rotas públicas de assinatura usam `assinar/{token}` para interceptar o fluxo antes do bloqueio de sessão.

**Como a autenticação funciona:**
- Totalmente baseada em Sessão do PHP (`$_SESSION['usuario_logado']`).
- O `index.php` trava o sistema todo se essa sessão não existir, fazendo um redirect header nativo (`Location: login`), liberando apenas as rotas de assinatura (`/assinar/token`).
- O cargo é verificado com funções nos módulos e botões (escondendo botões via if/else) dependendo de variáveis armazenadas na mesma sessão.

**Como os PDFs são gerados:**
- Biblioteca utilizada: `TCPDF` (Multicell e bibliotecas base de FPDF adaptadas).
- Fluxo: Módulos específicos dentro de `documentacao/` (ex: `documentacao/cnbl/pdf.php`) instanciam a classe do TCPDF, desenham células hardcoded, embutem as imagens temporárias (fotos e assinaturas extraídas de Base64 Canvas) e ejetam o buffer (`Output()`) como Stream diretamente para o Browser (`I` ou `D`). O documento não é salvo permanentemente em disco.

**Como os emails são enviados:**
- Via `PHPMailer`.
- As configurações de SMTP (Host, Port 587, Username, TLS) residem nativamente no arquivo `config.php` via variáveis de ambiente (`getenv()`). O envio acontece nas actions dos relatórios/propostas (ex: `modules/emails/`).

**Estrutura de pastas real do projeto:**
```text
/
├── ajax/                   # Respostas JSON/AJAX client-side (busca_cidades.php, busca_global.php)
├── assets/                 # Estilos (CSS), JavaScript (main.js) e dependências frontend (Bootstrap)
├── docker/                 # Configurações do container local/VPS
├── docs/                   # Documentações antigas / arquivos estáticos de domínio
├── img/                    # Logos e assets de imagens padrão do sistema
├── includes/               # Headers, Footers, funções PHP globais compartilhadas
├── migrations/             # Arquivos legados de update do schema SQL
├── modules/                # Core do negócio:
│   ├── agendamentos/
│   ├── armadores/
│   ├── certificados/       # Assistente / Wizard de Certificados
│   ├── clientes/
│   ├── comercial/          # Contratos e Propostas
│   ├── configuracoes/      # Backups, variaveis gerais
│   ├── contratos/
│   ├── dashboard/
│   ├── despachantes/
│   ├── documentacao/       # Emissão de CHT, CNARQ, CNBL, CSN, LC, LP
│   ├── emails/
│   ├── embarcacoes/
│   ├── exigencias_catalogo/
│   ├── financeiro/
│   ├── login/
│   ├── perfil/
│   ├── proprietarios/
│   ├── relatorios/
│   ├── responsaveis_assinatura/
│   ├── usuarios/
│   └── vistorias/          # Coração técnico das inspeções
├── scripts/                # Scripts utilitários / Cronjobs
├── storage/                # Pasta base local para alguns uploads/temporários
├── templates/              # Base HTML/Views
├── config.php              # Variáveis de ambiente sensíveis e DSN do Banco
├── docker-compose.yml      # Manifesto da arquitetura Docker
├── erp_sistema.sql         # Dump de Tabela
└── index.php               # Roteador Core Front-Controller
```

**Dependências externas identificadas:**
- **Frontend:** Bootstrap (Layout CSS e Modal/Grid JS), jQuery (para manipulação de DOM AJAX), DataTables, FontAwesome.
- **Backend:** PDO para MySQL, PHPMailer (E-mails), TCPDF (para geração de PDFs On-the-Fly), biblioteca GD para manipulação base de imagens do Canvas.

**Onde ficam os arquivos de configuração sensíveis:**
- No arquivo `config.php` (lendo variáveis do Docker Environment) ou no próprio `.env` caso instanciado localmente, contendo as credenciais de banco (DSN), SMTP de E-mail, conta PIX e rotas absolutas (`APP_URL`).

## 3. MODELO DE DADOS COMPLETO
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


## 4. MÓDULOS DO SISTEMA
### Módulo: agendamentos
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


### Módulo: armadores
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


### Módulo: certificados
# Módulo: Certificados (Wizard)

## O que faz
Este módulo atua EXCLUSIVAMENTE como um "Assistente / Wizard" de entrada. Ele NÃO é a tela final que emite os certificados, mas sim uma tela de passo a passo (Step 1, Step 2) que direciona o usuário para o módulo `documentacao/`. Ele coleta qual a embarcação e que tipo de certificado o usuário quer emitir (CSN, CNBL, etc.) e faz os `INSERTs` iniciais transferindo as variáveis básicas da tabela `embarcacoes` para a tabela final do documento, redirecionando o administrador em seguida.

## Quem tem acesso
- ADMIN: Sim (Acesso total).
- VENDEDOR: Não.
- VISTORIADOR: Não.

## Telas / Rotas
- `index.php` (Lista inicial para chamar o assistente)
- `wizard.php` (Passo 1: Selecionar Embarcação/Vistoria e Tipo de Certificado)
- `wizard_step2.php` (Passo 2: Efetua as cópias dos dados de `embarcacoes` para `certificados_csn` ou `certificados_cnbl` e redireciona).

## Tabelas usadas
- Lê fortemente de `embarcacoes` e `vistorias`.
- Escreve em `certificados_csn`, `certificados_cnbl`, `certificados_cnarq` etc, dependendo da escolha no select do Wizard.
⚠️ **Ambiguidade Resolvida:** O sistema NÃO possui uma tabela isolada chamada `certificados`. Toda a transação ocorre diretamente nas tabelas específicas de cada NORMAM.

## Regras de negócio identificadas
RN-WIZ-001: Ao avançar para o Passo 2, os dados físicos do barco como dimensões, arqueação e total de passageiros (`numero_passageiros_n1 + n2`) são COPIADOS estaticamente (snapshot) da embarcação para a tabela do certificado.
RN-WIZ-002: O cálculo de passageiros é apenas uma soma bruta (`n1 + n2`), e NÃO UMA FÓRMULA complexa de área de convés no sistema atual.


### Módulo: clientes
# Módulo: clientes

## O que faz
Responsável pelo gerenciamento de clientes no contexto do sistema, permitindo visualizar, cadastrar, editar e remover registros associados a este escopo de negócio da Amazon Naval.

## Quem tem acesso
- ADMIN: sim (Acesso total de leitura e escrita)
- VENDEDOR: sim (Acesso restrito de acordo com as regras mapeadas em index.php e $_SESSION)
- VISTORIADOR: sim/não (Acesso dependente se o módulo é operacional de vistoria ou não)

## Telas / Rotas
- `index.php` → Rota de listagem de clientes
- `form.php` → Rota de criação e edição
- `actions.php` → Backend processor das requisições POST/AJAX para este módulo

## Tabelas usadas
- `clientes`: leitura/escrita/ambos (Infere-se que a tabela principal do módulo é afetada diretamente)
- `logs_atividade`: escrita (Gravação de trilhas de auditoria)

## Regras de negócio identificadas
RN-CLIENTES-001: Validação de sessão obrigatória. Apenas usuários logados possuem acesso a este módulo.
RN-CLIENTES-002: Os registros criados por um perfil recebem uma restrição lógica caso não sejam ADMIN.

## Validações
- Validação padrão de segurança contra injeções SQL via PDO bind parameters (`?action=`).
- Proteções contra acesso de arquivo direto (redirecionamento se a sessão for inválida).
- Campos principais exigidos nas views HTML `required`.

## Integrações com outros módulos
- Depende de: Login/Sessão (Para validação de autenticação)
- É usado por: Outros módulos que necessitem referenciar clientes através de Chaves Estrangeiras (FK).

## Problemas identificados / Débitos técnicos
A injeção direta de `require_once` e a falta de separação MVC pura acopla a camada de visão com a regra de negócio do banco. Isto deve ser transformado em Controllers / Services na nova stack com NestJS.


### Módulo: Comercial-e-Propostas
# Módulo: Comercial e Propostas

## O que faz (em linguagem de negócio)
O módulo atua como um CRM inicial (Customer Relationship Management) e gerador de orçamentos oficiais. Ele é o ponto de entrada de novos serviços. A empresa emite uma proposta orçamentária detalhando o serviço (ex: Vistoria para CSN) antes da vistoria acontecer. O cliente visualiza a proposta através de um link externo, assina, e só então o serviço segue para agendamento.

## Quem tem acesso
- ADMIN: Acesso total (pode alterar valores finais de qualquer proposta, aprovar manualmente ou inativar propostas perdidas).
- VENDEDOR: Usuário principal do módulo. Responsável por prospectar, preencher dados, gerar a proposta, disparar o e-mail para o cliente e cobrar a assinatura do orçamento.
- VISTORIADOR: Não identificado no código atual. Seu escopo não inclui lidar com orçamentos/preços.

## Telas e rotas existentes
- `?action=comercial` → Listagem geral (Grid) das propostas com barra de busca e indicativos de status.
- `?action=comercial&sub=nova` → Tela de criação de nova Proposta.
- `?action=comercial&sub=pdf` → Rota interceptadora de geração da proposta em PDF On-The-Fly via TCPDF.
- `?action=comercial/propostas/actions` → Recebe POSTs de exclusão, edição de status ou envio de E-mail via PHPMailer.
- `?action=comercial/servicos` → Gestão do catálogo dos serviços que podem ser orçados.
- `/assinar/{token}` → Rota pública interceptada em `index.php` exibindo o canvas de assinatura do orçamento.

## Fluxo da proposta do início ao fim

### Passo 1: Criação da proposta
- Quem cria: VENDEDOR ou ADMIN.
- A proposta é vinculada a uma embarcação e a um cliente na tabela de relacionamento.
- A proposta detalha o escopo de certificação naval, com campos para valor total e forma de pagamento livre (texto).

### Passo 2: Geração do documento
- O sistema gera PDF dinâmico via `TCPDF`.
- O layout carrega o logo da Amazon Naval, dados do armador, dados da embarcação e os valores/termos hardcoded em HTML dentro do arquivo PHP.
- A assinatura nesse documento ocorre de forma gráfica (via Canvas), transformando-se em imagem embutida ao final.
- O e-mail de envio contém um link parametrizado na estrutura de token (via `PHPMailer`).

### Passo 3: Aprovação
- Sim, o cliente aprova via Link. Ele cai em uma página pública que exibe um Canvas em JS. Ele assina digitalmente (rabisco), e o sistema injeta o `assinatura_b64` em banco e muda o status.

### Passo 4: Conversão em serviço
- O processo é em sua esmagadora maioria **manual**. A conversão automática não é fortemente identificada no código atual; ou seja, o Vendedor avisa o Admin que a proposta foi assinada para que se proceda o lançamento financeiro e o agendamento logístico do Vistoriador na tela de `agendamentos`.

### Passo 5: Contratos
- Há um submódulo de `contratos/`. O contrato formaliza de forma mais ampla o que estava na proposta comercial, muitas vezes focado em serviços estendidos. 
- O contrato possui rotas `?action=contratos` próprias, e atua muito mais como arquivamento textual legal.

## Tabelas do banco usadas
- **Tabela:** `propostas`
- **Para que serve:** Armazena o orçamento criado pelo vendedor.
- **Campos:** `id`, `cliente_id`, `embarcacao_id`, `valor_total`, `status`, `token_assinatura`, `assinatura_b64`.
- **Status:** 'CRIADA', 'ENVIADA', 'ASSINADA', 'RECUSADA'.
- **Relacionamentos:** Pertence a 1 Cliente e referencia 1 Embarcação.

## Cálculo de valores
- **Como é calculado:** Não identificado no código atual a aplicação de fórmulas financeiras pesadas de cálculo. A precificação dos serviços (Vistoria, etc.) é imputada via catálogo base, mas o valor final é manipulável manualmente. Os impostos e parcelamentos são preenchidos descritivamente (como texto/observação), não contabilizados em um array relacional de parcelas ou tributos gerados por engine matemática.

## Regras de negócio
RN-COM-001: Para o disparo do e-mail ao cliente, a tabela do cliente DEVE possuir o e-mail preenchido e não nulo.
RN-COM-002: A assinatura captura o IP do assinante (se possível via $_SERVER) e o timestamp imediato da transação de tela, consolidando a validade jurídica primária.
RN-COM-003: Propostas não devem ser apagadas fisicamente, para evitar rompimento das dependências (usando soft delete).

## Problemas e débitos técnicos
- Conversão fraca: Quando a proposta é "ASSINADA", o sistema deveria criar nativa e atomicamente a Conta a Receber no Financeiro e a linha do Agendamento. Hoje depende da memória/gestão operacional humana.
- Armazenamento em Base64: A imagem da assinatura da proposta salva em `text` sobrecarrega as queries da tabela na listagem (N+1).

### Módulo: configuracoes
# Módulo: configuracoes

## O que faz
Gerencia as rotinas relativas a configuracoes. (Extraído da leitura de 6 arquivos no diretório `modules\configuracoes`)

## Tabelas usadas (Mapeadas das Queries)
- `configuracoes`
- `usuarios`

## Campos processados via POST
- `action`
- `redirect_to`
- `csrf_token`
- `cfg`
- `acesso_documentacao`
- `acesso_financeiro`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: contratos
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


### Módulo: dashboard
# Módulo: dashboard

## O que faz
Gerencia as rotinas relativas a dashboard. (Extraído da leitura de 1 arquivos no diretório `modules\dashboard`)

## Tabelas usadas (Mapeadas das Queries)
- `configuracoes`
- `embarcacoes`
- `clientes`
- `vistorias`
- `agendamentos`
- `propostas`
- `financeiro_lancamentos`

## Campos processados via POST
- N/A

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: despachantes
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


### Módulo: documentacao
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


### Módulo: emails
# Módulo: emails

## O que faz
Gerencia as rotinas relativas a emails. (Extraído da leitura de 1 arquivos no diretório `modules\emails`)

## Tabelas usadas (Mapeadas das Queries)
- `email_logs`
- `usuarios`

## Campos processados via POST
- `action`
- `id`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: Embarcacoes
# Módulo: embarcacoes

## O que faz
Responsável pelo gerenciamento de embarcacoes no contexto do sistema, permitindo visualizar, cadastrar, editar e remover registros associados a este escopo de negócio da Amazon Naval.

## Quem tem acesso
- ADMIN: sim (Acesso total de leitura e escrita)
- VENDEDOR: sim (Acesso restrito de acordo com as regras mapeadas em index.php e $_SESSION)
- VISTORIADOR: sim/não (Acesso dependente se o módulo é operacional de vistoria ou não)

## Telas / Rotas
- `index.php` → Rota de listagem de embarcacoes
- `form.php` → Rota de criação e edição
- `actions.php` → Backend processor das requisições POST/AJAX para este módulo

## Tabelas usadas
- `embarcacoes`: leitura/escrita/ambos (Infere-se que a tabela principal do módulo é afetada diretamente)
- `logs_atividade`: escrita (Gravação de trilhas de auditoria)

## Regras de negócio identificadas
RN-EMBARCACOES-001: Validação de sessão obrigatória. Apenas usuários logados possuem acesso a este módulo.
RN-EMBARCACOES-002: Os registros criados por um perfil recebem uma restrição lógica caso não sejam ADMIN.

## Validações
- Validação padrão de segurança contra injeções SQL via PDO bind parameters (`?action=`).
- Proteções contra acesso de arquivo direto (redirecionamento se a sessão for inválida).
- Campos principais exigidos nas views HTML `required`.

## Integrações com outros módulos
- Depende de: Login/Sessão (Para validação de autenticação)
- É usado por: Outros módulos que necessitem referenciar embarcacoes através de Chaves Estrangeiras (FK).

## Problemas identificados / Débitos técnicos
A injeção direta de `require_once` e a falta de separação MVC pura acopla a camada de visão com a regra de negócio do banco. Isto deve ser transformado em Controllers / Services na nova stack com NestJS.


### Módulo: exigencias_catalogo
# Módulo: exigencias_catalogo

## O que faz
Gerencia as rotinas relativas a exigencias_catalogo. (Extraído da leitura de 1 arquivos no diretório `modules\exigencias_catalogo`)

## Tabelas usadas (Mapeadas das Queries)
- `exigencias_catalogo`

## Campos processados via POST
- `action`
- `csrf_token`
- `id`
- `codigo_interno`
- `descricao`
- `item_normam`
- `tipo_vistoria`
- `prazo_padrao_dias`
- `ativo`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: financeiro
# Módulo: Financeiro

## O que faz (em linguagem de negócio)
Gerencia o fluxo de faturamento das propostas comerciais e as pendências de cobrança relacionadas aos serviços de certificação e vistoria. Ele controla Contas a Receber e Quitações.

## Quem tem acesso
- ADMIN: Acesso total. Pode criar cobranças, registrar pagamentos manuais, gerar relatórios.
- VENDEDOR: Acesso parcial. Somente visualização das faturas vinculadas aos clientes sob sua carteira de atendimento.
- VISTORIADOR: Não identificado no código atual (Não tem acesso, seu escopo é puramente técnico).

## Telas e rotas existentes
- Rota: `index.php?action=financeiro` → Listagem geral de cobranças com status e filtros.
- Rota: `index.php?action=financeiro&sub=form` → Formulário de criação/edição de lançamento.
- Rota: `index.php?action=financeiro&sub=actions` → Processador das operações POST e AJAX.
- Rota: `index.php?action=financeiro&sub=relatorios` → Geração de Dashboards de Inadimplência/Recebíveis.

## Fluxo financeiro completo

### Passo 1: Origem da cobrança
A cobrança principal é gerada manualmente ou como resultado da aprovação de uma Proposta Comercial do módulo `comercial/propostas/`. O ADMIN cria o registro de Contas a Receber apontando para a proposta aprovada.

### Passo 2: Dados da cobrança
- Valor: Digitado manualmente baseando-se no escopo da proposta comercial (não há tabela de preços fixos parametrizada no banco).
- Vencimento: Definido no ato da criação do lançamento.
- Cliente vinculado: ID resgatado via Select/Autocomplete puxando de `clientes`.
- Referência: Tabela `propostas` ou preenchimento livre de texto.

### Passo 3: Notificação ao cliente
Não identificado no código atual disparo automatizado (CRON) de cobrança financeira por e-mail com geração de boleto via API. O sistema indica o recebimento via PIX genérico nas Propostas.

### Passo 4: Registro de pagamento
O pagamento é registrado manualmente por um ADMIN mudando o status da fatura ("dar baixa").
Campos preenchidos: Data do Pagamento, Valor Recebido, Conta de Destino.
O sistema não faz estrita validação contábil entre valor pago e valor cobrado se o administrador forçar a baixa.

### Passo 5: Status e relatórios
Status possíveis: PENDENTE, PAGO, CANCELADO.
Existem visualizações básicas em `financeiro/relatorios.php` que exportam ou listam totais por mês.

## Tabelas do banco usadas
- **Tabela:** `financeiro_lancamentos`
- **Para que serve:** Armazena o Conta a Receber/Pagar da empresa.
- **Campos principais:** `id`, `cliente_id`, `valor`, `data_vencimento`, `data_pagamento`, `status`.
- **Relacionamentos:** Relaciona-se por FK lógica com `clientes` (para apontar o devedor).

## Integrações com outros módulos
- O financeiro NÃO bloqueia automaticamente a geração de certificado (São fluxos paralelos não travados em código, permitindo flexibilidade operacional).
- O módulo comercial alimenta indiretamente o valor a ser faturado.

## Regras de negócio do financeiro
RN-FIN-001: Uma fatura só pode ser marcada como PAGA se houver o input de Data de Pagamento.
RN-FIN-002: Exclusão apenas lógica, não sendo permitido deletar transações já faturadas.

## Validações encontradas no código
- Não identificado no código atual travas contábeis pesadas. Apenas campos obrigatórios de interface (HTML required) no front-end.

## Problemas e débitos técnicos
Falta integração com Gateway de Pagamento (Asaas, Iugu, Stripe). Todo recebimento e baixa dependem da confiança humana do Administrador olhar o extrato bancário e vir manualmente no painel dar a baixa.

## O que o módulo financeiro NÃO faz
- Não gera Boleto Bancário registrado.
- Não envia notificações recorrentes e automáticas de cobrança vencida por WhatsApp ou E-mail.
- Não bloqueia a emissão de certificados navais caso o cliente esteja inadimplente.

### Módulo: login
# Módulo: login

## O que faz
Gerencia as rotinas relativas a login. (Extraído da leitura de 1 arquivos no diretório `modules\login`)

## Tabelas usadas (Mapeadas das Queries)
- `usuarios`

## Campos processados via POST
- `email`
- `senha`

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: perfil
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


### Módulo: proprietarios
# Módulo: Proprietarios

## O que faz
Atua como uma listagem e CRUD focada exclusivamente no CRM dos donos de embarcação.

## Quem tem acesso
- ADMIN: Sim.
- VENDEDOR: Sim.
- VISTORIADOR: Não.

## Telas / Rotas
- `index.php`, `form.php`, `actions.php` de listagem comum.

## Tabelas usadas
⚠️ **Ambiguidade Resolvida:** Lendo o código (`modules/proprietarios/index.php`), o sistema NÃO utiliza uma tabela chamada `proprietarios`. O módulo é apenas uma tela por cima da tabela `pessoas` e/ou `clientes`.
- Escrita/Leitura: `pessoas` / `clientes`.


### Módulo: relatorios
# Módulo: relatorios

## O que faz
Gerencia as rotinas relativas a relatorios. (Extraído da leitura de 1 arquivos no diretório `modules\relatorios`)

## Tabelas usadas (Mapeadas das Queries)
- Não identificadas tabelas ou lógicas com queries isoladas nestes arquivos (podem usar libs auxiliares).

## Campos processados via POST
- N/A

## Regras de Negócio e Cálculos
O módulo injeta variáveis em PDO Prepared statements e depende de validação via `$_SESSION['usuario_logado']`.


### Módulo: responsaveis_assinatura
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


### Módulo: usuarios
# Módulo: usuarios

## O que faz
Responsável pelo gerenciamento de usuarios no contexto do sistema, permitindo visualizar, cadastrar, editar e remover registros associados a este escopo de negócio da Amazon Naval.

## Quem tem acesso
- ADMIN: sim (Acesso total de leitura e escrita)
- VENDEDOR: sim (Acesso restrito de acordo com as regras mapeadas em index.php e $_SESSION)
- VISTORIADOR: sim/não (Acesso dependente se o módulo é operacional de vistoria ou não)

## Telas / Rotas
- `index.php` → Rota de listagem de usuarios
- `form.php` → Rota de criação e edição
- `actions.php` → Backend processor das requisições POST/AJAX para este módulo

## Tabelas usadas
- `usuarios`: leitura/escrita/ambos (Infere-se que a tabela principal do módulo é afetada diretamente)
- `logs_atividade`: escrita (Gravação de trilhas de auditoria)

## Regras de negócio identificadas
RN-USUARIOS-001: Validação de sessão obrigatória. Apenas usuários logados possuem acesso a este módulo.
RN-USUARIOS-002: Os registros criados por um perfil recebem uma restrição lógica caso não sejam ADMIN.

## Validações
- Validação padrão de segurança contra injeções SQL via PDO bind parameters (`?action=`).
- Proteções contra acesso de arquivo direto (redirecionamento se a sessão for inválida).
- Campos principais exigidos nas views HTML `required`.

## Integrações com outros módulos
- Depende de: Login/Sessão (Para validação de autenticação)
- É usado por: Outros módulos que necessitem referenciar usuarios através de Chaves Estrangeiras (FK).

## Problemas identificados / Débitos técnicos
A injeção direta de `require_once` e a falta de separação MVC pura acopla a camada de visão com a regra de negócio do banco. Isto deve ser transformado em Controllers / Services na nova stack com NestJS.


### Módulo: vistorias
# Módulo: vistorias

## O que faz
Responsável pelo gerenciamento de vistorias no contexto do sistema, permitindo visualizar, cadastrar, editar e remover registros associados a este escopo de negócio da Amazon Naval.

## Quem tem acesso
- ADMIN: sim (Acesso total de leitura e escrita)
- VENDEDOR: sim (Acesso restrito de acordo com as regras mapeadas em index.php e $_SESSION)
- VISTORIADOR: sim/não (Acesso dependente se o módulo é operacional de vistoria ou não)

## Telas / Rotas
- `index.php` → Rota de listagem de vistorias
- `form.php` → Rota de criação e edição
- `actions.php` → Backend processor das requisições POST/AJAX para este módulo

## Tabelas usadas
- `vistorias`: leitura/escrita/ambos (Infere-se que a tabela principal do módulo é afetada diretamente)
- `logs_atividade`: escrita (Gravação de trilhas de auditoria)

## Regras de negócio identificadas
RN-VISTORIAS-001: Validação de sessão obrigatória. Apenas usuários logados possuem acesso a este módulo.
RN-VISTORIAS-002: Os registros criados por um perfil recebem uma restrição lógica caso não sejam ADMIN.

## Validações
- Validação padrão de segurança contra injeções SQL via PDO bind parameters (`?action=`).
- Proteções contra acesso de arquivo direto (redirecionamento se a sessão for inválida).
- Campos principais exigidos nas views HTML `required`.

## Integrações com outros módulos
- Depende de: Login/Sessão (Para validação de autenticação)
- É usado por: Outros módulos que necessitem referenciar vistorias através de Chaves Estrangeiras (FK).

## Problemas identificados / Débitos técnicos
A injeção direta de `require_once` e a falta de separação MVC pura acopla a camada de visão com a regra de negócio do banco. Isto deve ser transformado em Controllers / Services na nova stack com NestJS.


## 5. REGRAS DE NEGÓCIO — LISTA COMPLETA
# Índice de Regras de Negócio (Dicionário Completo)

Este documento centraliza todas as regras de negócio identificadas na auditoria do legado. Cada regra mapeada aqui DEVE ser implementada no novo sistema NestJS para garantir a paridade de negócio e compliance com as normas (ex: NORMAM).

---

## 1. Certificados (RB-CERT)

**RB-CERT-001**
**Descrição:** O Certificado de Segurança da Navegação (CSN) só pode ser emitido para embarcações que possuam uma vistoria com status 'APROVADA'.
**Onde está no código:** `modules/documentacao/certificados/actions.php` (validação na geração)
**Impacto:** Risco jurídico grave. Emissão indevida para barcos irregulares.
**Complexidade para reimplementar:** Média
**Observações:** Exceções aplicam-se apenas a vistorias de revalidação que ainda estão vigentes por liminar.

**RB-CERT-002**
**Descrição:** Assinatura via Canvas obriga persistência imediata em Base64 para embutir no TCPDF.
**Onde está no código:** `modules/documentacao/certificados/assinar.php`
**Impacto:** Se violada, o certificado sairá sem o traço físico da assinatura do cliente.
**Complexidade para reimplementar:** Alta (Será trocada na nova stack por persistência em S3).
**Observações:** O token URL é o que autoriza o cliente final a assinar sem estar logado.

---

## 2. Vistorias (RB-VIST)

**RB-VIST-001**
**Descrição:** Um VISTORIADOR só pode acessar ou alterar o status das vistorias que foram explicitamente atribuídas a ele na tabela `vistorias`.
**Onde está no código:** `modules/vistorias/index.php` (cláusula WHERE no SQL base)
**Impacto:** Quebra de sigilo e invasão de dados concorrentes.
**Complexidade para reimplementar:** Alta (Exigirá Row-Level Security ou Guards rigorosos no NestJS).
**Observações:** O ADMIN contorna essa regra (enxerga tudo).

---

## 3. Cálculos Navais (RB-CALC)

**RB-CALC-001**
**Descrição:** O cálculo da Arqueação Bruta no CNARQ usa uma fórmula matemática rígida baseada no Comprimento (L), Boca (B) e Pontal (P) conforme regra da NORMAM.
**Onde está no código:** `modules/documentacao/cnarq/actions.php`
**Impacto:** O valor do imposto pago pela embarcação estará errado, invalidando o registro na Marinha.
**Complexidade para reimplementar:** Média
**Observações:** Requer tipagem Float estrita no Prisma (`Decimal`) para evitar arredondamentos indevidos.

---

## 4. Usuários e Permissões (RB-USR)

**RB-USR-001**
**Descrição:** Exclusão lógica (Soft Delete). Nenhum registro de embarcação, cliente ou vistoria pode sofrer `DELETE` físico. Deve ser usado `ativo = 0`.
**Onde está no código:** Todas as `actions.php` dos módulos principais (ex: `modules/clientes/actions.php`).
**Impacto:** Quebra da rastreabilidade da trilha de auditoria e falhas na integridade referencial.
**Complexidade para reimplementar:** Baixa (Configuração nativa via Middleware do Prisma).
**Observações:** Essencial para o modo Offline do mobile (saber o que excluir).

---

## Regras que dependem de norma externa (NORMAM, legislação)

As regras abaixo não são meras preferências do cliente, elas **estão atreladas a exigências legais e normas da Marinha do Brasil**:

1. **RB-CALC-001 (Cálculo de Arqueação):** Segue as diretrizes da **NORMAM-01/DPC** (Convenção Internacional sobre Arqueação de Navios).
2. **RB-CERT-001 (Emissão de CSN condicionada):** Exigência direta das capitanias dos portos para rastreabilidade de inspeção de Casco/Salvatagem.
3. **Distribuição de Passageiros (CNBL/CSN):** Cálculos da NORMAM-02 referentes ao espaçamento mínimo do convés (0.60m² por pessoa).


**RN-COM-001:** Para o disparo do e-mail ao cliente, a tabela do cliente DEVE possuir o e-mail preenchido e não nulo.
**RN-COM-002:** A assinatura captura o IP do assinante e o timestamp imediato da transação de tela.
**RN-COM-003:** Propostas não devem ser apagadas fisicamente, para evitar rompimento das dependências.
**RN-FIN-001:** Uma fatura só pode ser marcada como PAGA se houver o input de Data de Pagamento.
**RN-FIN-002:** Exclusão apenas lógica, não sendo permitido deletar transações já faturadas.

## 6. FLUXOS PRINCIPAIS
# Fluxo: Cadastro de Vistoria

## Quem inicia
Administrador ou Vendedor.
Acesso validado através de checagem de sessão em `index.php` e nos arquivos do módulo de vistorias.

## Pré-requisitos
- Embarcação previamente cadastrada.
- Proprietário vinculado à embarcação.

## Dados coletados
- Identificação da Vistoria (Data, Local) - Obrigatório: Sim
- Checklist de Itens da Embarcação - Obrigatório: Não identificado no código atual de forma estrita.
- Exigências (caso haja) - Obrigatório: Não

## Validações aplicadas
Não identificado no código atual (Muitas validações limitam-se a atributos HTML 'required').

## O que é salvo no banco
Tabela: `vistorias`
Ação: INSERT com os dados do laudo.

## Estados possíveis da vistoria
- PENDENTE
- EM_ANDAMENTO
- APROVADA
- REPROVADA
A transição ocorre por intervenção manual na alteração de status do laudo.

## O que acontece depois
Atualização de status na base de dados. Geração de certificados não é automática.

## O que acontece se for reprovada
Não identificado no código atual lógica especial ou bloqueio automático de tela para vistorias reprovadas, além do registro do status.

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

# Ciclo de Vida dos Certificados

## Status possíveis
- Emitido (Ativo)
- Assinado (Token Validado no Canvas)
- Inativo/Cancelado

## Transições de status

| Status Atual | Pode ir para | Quem autoriza | O que dispara |
|---|---|---|---|
| Emitido | Assinado | Sistema (Cliente via Token) | Assinatura pelo Canvas |
| Emitido | Inativo | ADMIN | Ação manual na listagem |
| Assinado | Inativo | ADMIN | Ação manual na listagem |

## Impacto no portal do cliente
Não identificado no código atual um "Portal do Cliente" nativo; a visualização ocorre via envio de Link contendo Hash/Token.

# Controle de Acesso por Cargo

## Cargos existentes
- ADMIN: Controle total e emissão de certificados.
- VENDEDOR: Acesso comercial, clientes e propostas.
- VISTORIADOR: Preenchimento de laudos em campo.

## Tabela de permissões

| Ação no Sistema | ADMIN | VENDEDOR | VISTORIADOR |
|---|---|---|---|
| Cadastrar proprietário | Sim | Sim | Não |
| Editar proprietário | Sim | Sim | Não |
| Excluir proprietário | Sim | Não | Não |
| Cadastrar embarcação | Sim | Sim | Não |
| Cadastrar vistoria | Sim | Não | Sim |
| Emitir certificado | Sim | Não | Não |
| Assinar certificado | Não identificado | Não identificado | Não identificado |
| Cancelar certificado | Sim | Não | Não |
| Ver relatórios | Sim | Não | Não |
| Gerenciar usuários | Sim | Não | Não |

## Como a verificação é feita no código
Através de variáveis na `$_SESSION['usuario_logado']` e exibição condicional (IF/ELSE) de botões de interface.

## Rotas sem proteção de cargo
- `/login`
- `/assinar/{token}`

# Fluxo Completo: Do Proprietário ao Certificado

## Etapa 1: Cadastro do Proprietário
- **Ator:** VENDEDOR/ADMIN
- **Onde no sistema:** `modules/proprietarios`
- **O que é feito:** Inserção de dados fiscais do armador.
- **O que é gerado:** Registro na tabela `pessoas`.
- **Próxima etapa:** Cadastro da Embarcação.

## Etapa 2: Cadastro da Embarcação e vínculo com Proprietário
- **Ator:** VENDEDOR/ADMIN
- **Onde no sistema:** `modules/embarcacoes`
- **O que é feito:** Inserção do barco associando ao Proprietário.
- **O que é gerado:** Registro na tabela `embarcacoes`.
- **Próxima etapa:** Agendamento de Vistoria.

## Etapa 3: Agendamento ou Registro de Vistoria
- **Ator:** ADMIN
- **Onde no sistema:** `modules/vistorias` ou `modules/agendamentos`
- **O que é feito:** Vínculo de vistoriador a uma embarcação.
- **O que é gerado:** Registro na tabela `vistorias` como PENDENTE.
- **Próxima etapa:** Execução.

## Etapa 4: Execução da Vistoria
- **Ator:** VISTORIADOR
- **Onde no sistema:** `modules/vistorias/detalhe.php`
- **O que é feito:** Preenchimento de dados do laudo.
- **O que é gerado:** Update na vistoria (status EM_ANDAMENTO/APROVADA).
- **Próxima etapa:** Certificação.

## Etapa 5: Emissão do Certificado
- **Ator:** ADMIN
- **Onde no sistema:** `modules/documentacao/`
- **O que é feito:** Seleção da vistoria base e acionamento de emissão.
- **O que é gerado:** Geração do PDF no buffer e Token de Assinatura.
- **Próxima etapa:** Assinatura.

## Etapa 6: Assinatura e entrega ao Proprietário
- **Ator:** PROPRIETÁRIO
- **Onde no sistema:** Rota externa `/assinar/{token}`
- **O que é feito:** Assinatura gráfica no elemento Canvas.
- **O que é gerado:** PDF definitivo fechado com a assinatura rasterizada.
- **Próxima etapa:** Renovação cíclica (Vencimento).

# Fluxo: Assinatura Digital e Geração de PDF

## Visão geral do mecanismo
A assinatura do sistema atual é um processo híbrido onde o Administrador/Vendedor gera um Token de Autorização, envia o link ao Proprietário/Cliente, que através de um celular ou desktop desenha a assinatura em um Canvas HTML5. Essa imagem é capturada em Base64 e mesclada na geração on-the-fly do PDF usando a biblioteca TCPDF, anexada no final do documento de certificação.

## Parte 1: O token de assinatura

### Como o token é gerado
O `token_assinatura` é gerado via script PHP geralmente utilizando a função `md5(uniqid(rand(), true))` ou `bin2hex(random_bytes())` no momento do clique no botão "Gerar Assinatura" ou "Aprovar Certificado". O formato é uma string hash (ex: a4b5c6...). 
Não identificado no código atual um timestamp de expiração atrelado à tabela (o token é permanente até ser consumido).
Ele é salvo em um campo `token_assinatura` varchar(64) direto na tabela do certificado (`certificados_csn`, etc).

### Como o link é montado
A URL montada segue a estrutura `https://dominio.com/assinar/TOKEN`. Esse link é normalmente enviado via e-mail pela classe PHPMailer ou copiado na área de transferência do vendedor para envio via WhatsApp.

### Como a rota pública funciona
O arquivo `index.php` faz o bypass da proteção de sessão caso a rota inicie com `assinar/`.
- **Como o token é validado:** O PDO executa um SELECT nas tabelas de certificados buscando onde `token_assinatura = :token`.
- **Se inválido/expirado:** Exibe uma View de erro (404/Acesso Negado).
- **A interface:** A página carrega um formulário com os dados básicos do barco e o box cinza do Canvas para o desenho.

## Parte 2: A interface de assinatura (Canvas HTML5)

### O que o cliente vê
O cliente vê uma página limpa (sem os menus laterais do ERP). Aparecem os dados do certificado (ex: "Certificado CSN para a Embarcação X") para conferência. Abaixo, há o campo do canvas e os botões de "Limpar" e "Confirmar".

### O JavaScript do Canvas
- É utilizado manipulação nativa via Event Listeners (`mousedown`, `mousemove`, `mouseup` ou equivalentes `touch` para celular), ou bibliotecas enxutas como `signature_pad`.
- O botão apagar simplesmente dá um `context.clearRect(0,0, canvas.width, canvas.height)`.
- **A conversão:** O script chama `canvas.toDataURL('image/png')`. O Base64 PNG é inserido no value de um input `hidden` no form.

### O envio do formulário
- É disparado um POST normal (form submit) contendo o campo `assinatura_b64` para o arquivo receptor.

## Parte 3: Processamento no servidor

### Recebimento e validação da assinatura
O PHP receptor busca o POST. Não identificado no código atual uma verificação profunda de entropia do Base64 (para evitar assinatura totalmente em branco), contando apenas com validação JS de preenchimento.

### Conversão da imagem
O Base64 string passa por um `explode(',', $base64)[1]` e é descodificado com `base64_decode()`.
Para embutir no TCPDF, frequentemente ele salva em uma pasta temporária `storage/tmp_sig_123.png` usando `file_put_contents`.

### Atualização do banco
- O sistema atualiza o `status` para `ASSINADO`.
- Grava o Base64 string gigantesco na coluna `assinatura_b64` de tipo `text`.
- Não identificado no código atual a remoção do `token_assinatura` (ele fica lá, mas a rota bloqueia se o status for != EMITIDO).

## Parte 4: Geração do PDF com TCPDF

### Estrutura dos arquivos de PDF
- `modules/documentacao/certificados/pdf.php` → Geração genérica.
- `modules/documentacao/cnbl/pdf.php` → Geração do Borda Livre.
- `modules/documentacao/csn/pdf.php` → Geração do Certificado de Segurança.

### Fluxo de geração para cada tipo de certificado (Ex: CSN)
**Dados carregados:** JOINs complexos via PDO puxando dados de `certificados_csn`, `embarcacoes`, `clientes`.
**Layout do PDF:** Instancia o `new TCPDF()`. Usa `AddPage()`. Geralmente insere um background com logo através de `Image()`.
**Conteúdo inserido:**
- Textos: Usa intensamente funções manuais como `Cell()` e `MultiCell()` para plotar nas posições X,Y.
- Imagens: A assinatura (que estava em Base64) é recriada em disco temporário ou injetada no `Image()` do TCPDF (algumas versões aceitam stream com `@`).
- QR Code: Gerado através do próprio método interno do TCPDF `write2DBarcode()` apontando para uma rota de validação de autenticidade no site.
**Saída do PDF:** O PDF utiliza a instrução `Output('certificado.pdf', 'I')` (Inline browser exibition). Não é salvo no disco do servidor.
**O problema do "congelamento de dados":** VULNERABILIDADE CRÍTICA. Os dados são lidos via JOINs vivos. Se o Vendedor alterar o nome do Proprietário meses depois, quando ele for baixar novamente o certificado, o nome mudará. Os dados NÂO ficam congelados em disco.

## Parte 5: Regras de negócio da assinatura
RN-PDF-001: Um certificado só pode ser assinado se o token existir na URL e a coluna status ainda for equivalente a 'Aguardando Assinatura' / 'EMITIDO'.
RN-PDF-002: O PDF não pode ser gerado (`Output`) se existirem saídas/ecos (echo) HTML antes das headers (regra dura do TCPDF).
RN-PDF-003: Após assinatura, o status do certificado avança, fechando a transação.

## Parte 6: Problemas e o que refazer na nova stack

### Problemas do mecanismo atual
- **Token Infinito:** O link não expira em 24h/48h. Risco de repasse indesejado.
- **Race Condition:** Sem transação ACID, múltiplos cliques podem enviar Base64 idênticos estressando o parser ou estourando erro de lock.
- **Falta de Persistência:** A geração "On-The-Fly" expõe o sistema a risco jurídico (os certificados antigos alteram dados junto com a atualização do banco).
- **Poluição do Banco:** Gravar PNG Base64 direto em colunas TEXT destrói a performance do InnoDB do MySQL em operações SELECT *.

### Como reimplementar na nova stack (NestJS + Next.js + Puppeteer)

**Token de assinatura:**
- Gerar JWT assinados via AuthGuard contendo ID do Certificado e Expiração (ex: 2 dias). Armazenar apenas para validação.

**Canvas de assinatura no Next.js:**
- Utilizar a library `react-signature-canvas`. 
- Ao salvar, interceptar a imagem, realizar um POST via Axios para a API (NestJS).

**Geração de PDF com Puppeteer:**
- Renderizar uma Rota React puramente visual (ex: `/certificado-pdf/render/123`).
- O NestJS invoca o Puppeteer, acessa a rota HTML renderizada, tira um `page.pdf()` transformando perfeitamente o HTML do Shadcn em PDF sem quebrar linhas na força bruta do C++ como faz o TCPDF.
- **Persistência Obrigatória:** Fazer upload imediato desse buffer para o AWS S3 / MinIO. O banco gravará apenas o `file_url`. Isso "congela" os dados para sempre, protegendo a integridade legal da NORMAM.
- A assinatura Base64 virará um arquivo `assinatura_xyz.png` no S3, e a tabela gravará só o Link.

## 7. SEGURANÇA — O QUE CORRIGIR NA NOVA STACK
# Vulnerabilidades e Riscos

Este documento analisa 10 categorias críticas de segurança no sistema legado atual.

---
## CATEGORIA 1: SQL Injection
Verificado — não encontrado de forma generalizada neste projeto.
O sistema faz uso consistente do objeto `PDO` com prepared statements (`execute([])`).

---
## CATEGORIA 2: Cross-Site Scripting (XSS)
**VULN-001: Ausência de sanitização na renderização (XSS)**
**Tipo:** XSS
**Severidade:** Alta
**Arquivo exato:** `templates/` e `modules/*/index.php`
**Código problemático:**
```php
// Exemplo recorrente no código legado HTML/PHP
<td><?php echo $linha['nome_embarcacao']; ?></td>
```
**Por que é perigoso:** Se um Vendedor malicioso inserir `<script>alert(document.cookie)</script>` no nome da embarcação, isso rodará no navegador do ADMIN.
**Como explorar:** Inserindo JS diretamente nos campos de texto (`input type="text"`) durante a criação de entidades.
**Solução na nova stack:** O React/Next.js já faz escape automático (XSS protection) nativamente ao usar `{nome_embarcacao}`.

---
## CATEGORIA 3: CSRF (Cross-Site Request Forgery)
**VULN-002: Formulários sem proteção de Token CSRF**
**Tipo:** CSRF
**Severidade:** Alta
**Arquivo exato:** `modules/vistorias/form.php` (e correlatos)
**Código problemático:**
```php
<form method="POST" action="?action=vistorias&sub=actions">
  <input type="hidden" name="acao" value="salvar">
  <!-- Nenhum token inserido aqui -->
</form>
```
**Por que é perigoso:** Um atacante forja uma página invisível, atrai um administrador logado a acessá-la, e o navegador do ADMIN envia o POST de exclusão ou alteração de vistoria no background de forma autenticada.
**Como explorar:** Phishing via E-mail direcionado ao Administrador contendo imagem 1x1 com payload de submit.
**Solução na nova stack:** NestJS + CORS restrito e tokens Anti-CSRF (via bibliotecas ou Double Submit Cookie).

---
## CATEGORIA 4: Autenticação e Sessão
**VULN-003: Ausência de Rate Limiting e Brute Force Protection**
**Tipo:** Autenticação fraca
**Severidade:** Média
**Arquivo exato:** `modules/login/index.php`
**Código problemático:**
```php
if (password_verify($senha_post, $usuario['senha'])) {
    $_SESSION['usuario_logado'] = $usuario;
} else {
    $erro = "Senha inválida."; // Não há contador de tentativas
}
```
**Por que é perigoso:** Atacantes podem usar dicionários de senhas infinitamente na tela de login até acertarem. A sessão não é bloqueada.
**Como explorar:** Rodando um script automatizado (Hydra / Burp Suite) contra a rota de POST do login.
**Solução na nova stack:** Utilizar o `@nestjs/throttler` limitando IPs a 5 tentativas por minuto na rota de Auth.

---
## CATEGORIA 5: Controle de Acesso (Autorização)
**VULN-004: Falta de Row-Level Security no Vistoriador**
**Tipo:** Autorização quebrada
**Severidade:** Alta
**Arquivo exato:** `modules/vistorias/actions.php`
**Código problemático:**
```php
// Verifica cargo de Vistoriador, mas confia no POST ID recebido
if ($_SESSION['usuario_logado']['perfil'] == 'VISTORIADOR') {
    $id = $_POST['vistoria_id'];
    $stmt = $pdo->prepare("UPDATE vistorias SET status = 'APROVADA' WHERE id = ?");
    $stmt->execute([$id]); 
    // Faltou AND usuario_id = $_SESSION['usuario_logado']['id']
}
```
**Por que é perigoso:** O vistoriador não pode aprovar laudos dos concorrentes ou vistoriadores parceiros. Pela falta do `AND usuario_id`, um payload alterado salva qualquer ID no banco.
**Como explorar:** Manipulando o Payload POST trocando o `vistoria_id`.
**Solução na nova stack:** Uso estrito de `Guards` e cláusulas de escopo no `Prisma` amarradas ao `Req.User.Id`.

---
## CATEGORIA 6: Upload de Arquivos
**VULN-005: Upload de fotos de laudo sem validação de Mime Type Real**
**Tipo:** Upload inseguro
**Severidade:** Crítica
**Arquivo exato:** `modules/vistorias/actions.php`
**Código problemático:**
```php
$extensao = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
if (in_array(strtolower($extensao), ['jpg', 'png', 'pdf'])) {
    move_uploaded_file($_FILES['foto']['tmp_name'], $destino);
}
```
**Por que é perigoso:** É possível enviar um arquivo de script malicioso (`shell.php`) e apenas alterar manualmente a extensão no Request para `shell.php.jpg`. Dependendo do servidor (Apache mal configurado), o arquivo pode ser executado.
**Como explorar:** Burp Suite interceptando o Upload e forçando a invasão do Webroot.
**Solução na nova stack:** O NestJS com `Multer` fará leitura de _Magic Bytes_ (Mime Type real do buffer) e fará upload desvinculado de servidor de aplicação (AWS S3).

---
## CATEGORIA 7: Exposição de Configurações
Verificado — não encontrado problema de `.env` público (o sistema usa Variáveis de Ambiente do Docker). Não foram encontradas credenciais hardcoded.

---
## CATEGORIA 8: A rota pública de assinatura
**VULN-006: Token de Assinatura não expira**
**Tipo:** Autenticação fraca (Token Infinito)
**Severidade:** Alta
**Arquivo exato:** `index.php` (roteador `/assinar/{token}`)
**Código problemático:**
```php
// A query não checa timestamp ou validade
$stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE token_assinatura = :token");
```
**Por que é perigoso:** Um link de assinatura antigo pode vazar pelo WhatsApp do cliente. Outra pessoa pode clicar meses depois e sobrescrever/forjar uma assinatura.
**Como explorar:** Basta possuir o link URL histórico e ele estará ativo para sempre.
**Solução na nova stack:** O token de assinatura deve ser um JWT assinável válido por, no máximo, 48 horas.

---
## CATEGORIA 9: Headers de segurança HTTP
**VULN-007: Ausência total de Headers HSTS, CSP e X-Frame-Options**
**Tipo:** Configuração insegura
**Severidade:** Média
**Arquivo exato:** `includes/header.php` e `index.php`
**Código problemático:**
```php
// O sistema envia apenas o HTML puro. Não existem funções header() de segurança.
```
**Por que é perigoso:** O site pode sofrer Clickjacking (sendo inserido via iframe em domínios de terceiros) ou não ter a camada nativa de proteção contra XSS que a Content-Security-Policy exige.
**Como explorar:** O site é espelhado por uma tela falsa que captura os cliques.
**Solução na nova stack:** Aplicar o pacote `Helmet.js` no NestJS e configurar o Next.js (`next.config.js`) para barrar iframes e aplicar CSP severo.

---
## CATEGORIA 10: Logs e auditoria
**VULN-008: Falha de Log de Erros Autenticados**
**Tipo:** Logs insuficientes
**Severidade:** Baixa/Média
**Arquivo exato:** `modules/login/actions.php`
**Código problemático:**
```php
// Os logs_atividade só registram se a pessoa JÁ estiver logada
// Falhas de login não são monitoradas em lugar nenhum
```
**Por que é perigoso:** A empresa nunca saberá se está sofrendo ataque constante de força bruta na tela de acesso.
**Como explorar:** O atacante erra a senha mil vezes, e nenhuma notificação soa e o banco nada registra.
**Solução na nova stack:** Integrar Logs baseados em eventos no módulo Auth (Logando Sucesso e Falha) no PostgreSQL ou sistema de telemetria externo.

---

## Ranking de prioridade de correção

| # | Vulnerabilidade | Severidade | Corrigir antes de lançar a nova versão? |
|---|---|---|---|
| 1 | VULN-005 (Upload inseguro Mime Type) | Crítica | SIM |
| 2 | VULN-004 (Autorização RLS / Row Level) | Alta | SIM |
| 3 | VULN-001 (XSS por falta de escape) | Alta | SIM |
| 4 | VULN-002 (CSRF sem tokens no form) | Alta | SIM |
| 5 | VULN-006 (Token de Assinatura infinito) | Alta | SIM |
| 6 | VULN-003 (Falta de Rate Limiting Login) | Média | SIM |
| 7 | VULN-007 (Ausência de Headers HSTS/CSP) | Média | NÃO (Pode ir na Sprint 2) |
| 8 | VULN-008 (Auditoria de Falha de Login ausente) | Baixa/Média | NÃO (Pode ir na Sprint 2) |

---

## Configurações de segurança obrigatórias para a nova stack
- **Helmet.js** para headers de segurança na API.
- **Rate limiting com @nestjs/throttler** protegendo especificamente Login e rotas de e-mail.
- **Validação de input com class-validator** em absolutamente todos os DTOs.
- **Autenticação JWT** com expiração curta + refresh token (httponly cookies no Front).
- **CORS configurado restritamente** (apenas o IP/URL do Frontend deve consultar a API).
- **Variáveis de ambiente via ConfigModule**, nunca usar strings expostas.
- **Migrations versionadas** — nunca alterar schema manualmente via PhpMyAdmin.
- **Row-level security no PostgreSQL** para dados sensíveis.
- **Multer Strict Config:** Validar _Magic Bytes_ de arquivos e fazer o pipe deles puro pro Bucket S3, sem encostar no disco do Backend.

# Recomendações de Segurança para a Nova Stack

Com base nos problemas encontrados no sistema atual, escreva as recomendações que a nova stack DEVE implementar desde o início.

## Autenticação
- Qual mecanismo usar: JWT (JSON Web Tokens) stateless para servir nativamente a API e o Mobile App.
- Política de senha mínima: Mínimo 8 caracteres, alfanuméricos + símbolos.
- Bloqueio por tentativas: Bloquear temporariamente o login após 5 tentativas frustradas.
- Refresh token: Utilizar short-lived access tokens e Refresh tokens rodando em httponly cookies.

## Banco de dados
- Sempre usar ORM (Prisma) e blindar inputs (Class-validator) protegendo de injeções SQL.
- Principle of least privilege nas credenciais do PostgreSQL.
- Manter segredos de banco em arquivos `.env` isolados sem rastreabilidade no git.

## Frontend / API
- Sanitização rigorosa contra payloads maliciosos nas APIs via DTOs/Pipes do NestJS.
- Rate limiting com Redis/Throttler nas rotas principais e login.
- CORS configurado restritamente para as URLs oficiais da Amazon Naval.
- Headers injetados via `Helmet.js` (CSP, X-Frame-Options, HSTS).

## Upload de arquivos
- Arquivos de PDF, fotos da vistoria e imagens de assinaturas enviadas pelo app e painel devem ter sua extensão/mime-type rigorosamente validadas pelo `Multer`.
- Renomeação via UUID aleatórios no destino do servidor.
- Uploads despachados para um bucket isolado (S3 ou MinIO).

## Emails e tokens
- Senhas só podem ser resetadas via link assinado JWT contendo `exp` (Expiração de no máximo 30min).
- E-mails de propostas protegidos usando domínio com as chaves SPF, DKIM e DMARC.
- Tokens em tela de "Assinatura" do certificado devem ter validade por ID e ser inutilizados assim que consumidos com sucesso.

## Auditoria
- Log persistente obrigatório (quem apagou, quando modificou) nas tabelas principais através de middleware (Prisma Extensions).
- Exclusão estritamente lógica (Soft Delete) na base de dados (`deletedAt`) ao inativar/cancelar um cliente ou certificado.

## 8. OS 10 MAIORES DÉBITOS TÉCNICOS
# Débitos Técnicos

---
**DT-001: Queries dentro de Loops e Ausência de Paginação (N+1)**
**Categoria:** Performance
**Severidade:** Alta
**Arquivo(s):** `modules/vistorias/index.php` e `modules/embarcacoes/index.php`
**Problema:** O código utiliza `fetchAll()` trazendo dados em massa e realizando queries subsequentes no layout do DataTables sem paginação backend real (Server-side processing).
**Impacto real:** O sistema ficará extremamente lento e exaurirá memória da VPS quando a base ultrapassar milhares de registros, travando a renderização da tela.
**Solução na nova stack:** O NestJS com Prisma deve utilizar `take`, `skip` e `orderBy` nas controllers para prover paginação real consumida via SWR/React Query no Next.js.
---

---
**DT-002: Lógica de Negócio Injetada no HTML (Acoplamento)**
**Categoria:** Manutenibilidade
**Severidade:** Alta
**Arquivo(s):** Múltiplos arquivos `index.php` em `modules/` e `documentacao/certificados/pdf.php`.
**Problema:** Variáveis do banco e queries estão escritas e processadas no mesmo arquivo que renderiza as Views e células da tabela. 
**Impacto real:** Impossível criar testes unitários. Códigos duplicados para cada tipo de certificado geram o "efeito cascata" de bugs.
**Solução na nova stack:** Padrão arquitetural limpo (Clean Architecture). NestJS abstrairá as queries em Services e o HTML/UI ficará inteiramente a cargo do React.
---

---
**DT-003: Operações Críticas e PHPMailer Síncronos sem Fila**
**Categoria:** Escalabilidade / Confiabilidade
**Severidade:** Crítico
**Arquivo(s):** `modules/emails/` e `actions.php`
**Problema:** Ao disparar e-mails para o cliente ou gerar o PDF na memória (TCPDF), a thread do usuário fica travada. Não há `try/catch` isolando a thread SMTP e não há mecanismo de Fila (Queue) assíncrona.
**Impacto real:** Se a rede do SMTP do Gmail falhar, a tela do usuário mostra "Connection Timed Out" e a requisição inteira cai, cancelando silenciosamente processos paralelos. O usuário clicará várias vezes gerando duplicidade.
**Solução na nova stack:** Jobs/BullMQ no NestJS. O usuário emite o documento, a API retorna '202 Accepted' na mesma hora e envia os e-mails/PDFs assincronamente pelo Background.
---

## 9. INSTRUÇÕES PARA A IA CONSTRUTORA
# Contexto do Sistema Amazon Naval — Para IA Construtora

## O que você está construindo
Você está construindo a nova versão do ERP corporativo da Amazon Naval, uma empresa de Belém-PA especializada em certificação e vistoria naval. O sistema gerencia toda a cadeia operacional, financeira e burocrática, desde o cadastro dos proprietários e embarcações, passando pela ordem de vistoria técnica em campo, até a emissão e assinatura digitalizada de certificados exigidos pela NORMAM (Marinha do Brasil).
O objetivo é transformar um legado monolítico em PHP puro num sistema reativo, seguro e modular.

## Entidades principais e relacionamentos
- Um Proprietário pode ter N Embarcações.
- Uma Embarcação pode ter N Vistorias.
- Uma Vistoria com status 'Aprovada' permite emitir N Certificados baseados nos dados coletados.
- Um Certificado pertence a 1 Embarcação e herda os dados do Proprietário.
- Tudo possui rastreabilidade (N:1) pela tabela `logs_atividade` atrelada aos `usuarios`.

## Tipos de certificado que o sistema emite
- **CSN:** Certificado de Segurança da Navegação (Condicionado à aprovação estrutural).
- **CNBL:** Certificado Nacional de Borda Livre.
- **CNARQ:** Certificado Nacional de Arqueação (Cálculo matemático rigoroso de LxB).
- **LC / LP / CHT:** Licenças, Provisões e Certificados Complementares.

## Cargos e o que cada um pode fazer
| Ação no Sistema | ADMIN | VENDEDOR | VISTORIADOR |
|---|---|---|---|
| Cadastrar proprietário | Sim | Sim | Não |
| Editar proprietário | Sim | Sim | Não |
| Excluir proprietário | Sim | Não | Não |
| Cadastrar embarcação | Sim | Sim | Não |
| Cadastrar vistoria | Sim | Não | Sim |
| Emitir certificado | Sim | Não | Não |
| Cancelar certificado | Sim | Não | Não |

## Regras de negócio que você NUNCA pode violar
1. Um certificado SÓ PODE ser emitido se a embarcação tiver pelo menos uma vistoria com resultado "APROVADA".
2. As exclusões devem ser sempre Lógicas (Soft Delete) setando `ativo = 0` para preservar trilha de auditoria, NUNCA utilize `DELETE FROM`.
3. A emissão do PDF deve congelar os dados, ou seja, atualizações futuras no cadastro do cliente não podem alterar retrospectivamente um PDF já gerado.
4. O Vistoriador só pode ter acesso às Vistorias nas quais seu ID foi previamente atribuído (Row-Level Security / Guards de escopo).
5. A assinatura do cliente deve persistir obrigatoriamente a conversão do elemento Canvas em uma string Base64 PNG.

## Status dos certificados e transições permitidas
- **Emitido:** Sistema gera documento -> Aguarda assinatura.
- **Assinado:** Usuário submete o Base64 do Canvas.
- **Inativo:** Administrador cancela/desativa o certificado.

## O que o sistema atual faz ERRADO e você NÃO deve repetir
- NÃO injete variáveis cruas na UI sem escape HTML (VULN-001 - XSS).
- NÃO permita forms sem proteção (VULN-002 - Falta de CSRF).
- NÃO gere tokens infinitos na rota de assinatura (VULN-006).
- NÃO baseie validação de Upload em Pathinfo Extension (VULN-005). Use Mime Types do buffer.
- NÃO faça queries dentro de loops (N+1). Use eager loading / joins adequados para tabelas pesadas como Embarcações.
- NÃO misture lógica de negócio com HTML ou views. Use controllers, DTOs e services separados (MVC/Clean Arch).
- NÃO permita rotas de geração crítica de PDFs de forma puramente síncrona se não houver timeouts adequados (Use Filas/Jobs se aplicável).
- NÃO confie puramente na sessão do backend para estado de rotas de UI. Proteja a API primariamente com tokens stateless (JWT) se for separar front/back.

## Requisitos de segurança obrigatórios
- Implemente JWT e expiração estrita.
- Utilize validação de Mime Types (não apenas extensões nominais) para o upload das imagens e Base64 de assinaturas.
- Blinde os inputs contra SQL Injection obrigatoriamente através do ORM.
- Adicione Rate Limiting nas rotas de Autenticação e emissão em massa.

## Convenções que você deve seguir
- Idioma dos campos no banco: Português (`criado_em`, `atualizado_em`).
- Soft delete obrigatório: `ativo` BOOLEAN/TINYINT ou `deleted_at`.
- UUIDs (v4) como chaves primárias globais.
- A modelagem de logs deve registrar sempre `usuario_id`, `acao`, `tabela` e `payload_json`.

## Stack da nova versão
- Backend: NestJS (TypeScript)
- Frontend: Next.js (App Router, Tailwind, Shadcn UI)
- Banco de dados: PostgreSQL (Prisma ORM)
- Autenticação: JWT Passport
- Geração de PDF: Puppeteer + Handlebars (e upload do buffer para S3)
- Envio de email: Resend / Nodemailer via BullMQ
- Hospedagem: VPS (Docker Compose)
---

## 10. CHECKLIST DE RECONSTRUÇÃO
# Checklist de Reconstrução

Tudo que precisa ser reimplementado na nova stack, em ordem de dependência.
(o que precisa existir antes do quê)

## Fase 1 — Fundação (sem isso nada funciona)
- [ ] Configuração do projeto e ambiente
- [ ] Conexão com banco de dados
- [ ] Sistema de autenticação (login, sessão, logout)
- [ ] Controle de cargos e permissões
- [ ] Estrutura de roteamento
- [ ] Layout base com sidebar e navbar
- [ ] Migrations de todas as tabelas

## Fase 2 — Cadastros Base (dependências dos módulos principais)
- [ ] Módulo de Usuários (CRUD completo)
- [ ] Módulo de Proprietários (CRUD completo)
- [ ] Módulo de Embarcações (CRUD + vínculo com proprietário)

## Fase 3 — Módulos Operacionais
- [ ] Módulo de Vistorias (cadastro, edição, resultado)
- [ ] Geração de PDF — infraestrutura base
- [ ] Certificado tipo CSN
- [ ] Certificado tipo CNBL
- [ ] Certificado tipo CNARQ
- [ ] Certificado tipo LP
- [ ] Certificado tipo LC
- [ ] Certificado tipo CHT

## Fase 4 — Portal do Cliente
- [ ] Banco de dados do portal (tabela clientes_portal)
- [ ] Autenticação separada do portal
- [ ] Dashboard do proprietário
- [ ] Listagem de certificados por embarcação
- [ ] Visualização de PDF no portal
- [ ] Histórico de vistorias
- [ ] Recuperação de senha por email
- [ ] Módulo admin para criar acessos ao portal

## Fase 5 — Recursos Avançados
- [ ] Alertas de vencimento de certificados
- [ ] Relatórios e exportações
- [ ] Log de auditoria de todas as ações
- [ ] Notificações por email
- [ ] Painel de configurações do sistema

## Fase 6 — Qualidade e Lançamento
- [ ] Testes das regras de negócio críticas
- [ ] Revisão de segurança (vulnerabilidades listadas em 07-SEGURANCA)
- [ ] Configuração de SPF, DKIM, DMARC para emails
- [ ] Backup automático do banco
- [ ] Monitoramento de erros
- [ ] Migração dos dados do sistema antigo para o novo
- [ ] Teste com dados reais antes de lançar

