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