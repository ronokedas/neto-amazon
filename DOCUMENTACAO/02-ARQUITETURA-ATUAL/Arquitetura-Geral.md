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