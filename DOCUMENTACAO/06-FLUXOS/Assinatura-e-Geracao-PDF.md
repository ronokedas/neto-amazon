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