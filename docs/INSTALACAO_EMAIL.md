# Configuração do Sistema de E-mails - Fase 5

## 1. PHPMailer via Composer

O PHPMailer já está instalado e listado no `composer.json`:
```json
"phpmailer/phpmailer": "^7.1"
```

O autoload é carregado em `includes/mailer.php`:
```php
require_once __DIR__ . '/../vendor/autoload.php';
```

## 2. Credenciais SMTP em config.php

Constantes definidas (sem senhas hardcoded):
- `MAIL_HOST`
- `MAIL_PORT`
- `MAIL_USERNAME`
- `MAIL_PASSWORD`
- `MAIL_FROM_NAME`
- `MAIL_ENCRYPTION`

Podem ser definidas via variáveis de ambiente Docker ou diretamente no config.php.

## 3. Função Central enviarEmail()

Local: `includes/mailer.php`

Assinatura:
```php
enviarEmail(string $destinatario, string $nome, string $assunto, string $htmlBody, array $anexos = []): array
```

Retorna: `['success' => bool, 'message' => string]`

## 4. Tabela email_logs

Migration: `migrations/012_email_logs.sql`

Campos:
- `id` (UUID, PK)
- `destinatario` (varchar 255)
- `assunto` (varchar 255)
- `tipo` (enum: proposta/agendamento/certificado/assinatura/alerta_vencimento)
- `referencia_tipo` (varchar 50)
- `referencia_id` (UUID)
- `status` (enum: enviado/erro)
- `mensagem_erro` (text)
- `enviado_por` (UUID)
- `created_at` (datetime)

## 5. Templates de E-mail

Local: `templates/email/`

- `proposta.html` - Proposta comercial com dados bancários
- `agendamento.html` - Confirmação de agendamento
- `certificado.html` - Certificado emitido (CSN/CNBL/CNARQ)
- `assinatura.html` - Link de assinatura digital
- `vencimento.html` - Alerta de vencimento

Todos seguem o padrão visual verde/escuro Amazon Naval.

## 6. Agendamento de Alertas de Vencimento

### Windows (Agendador de Tarefas)

1. Abrir "Agendador de Tarefas"
2. Criar tarefa básica
3. Nome: "Alerta Vencimento Certificados"
4. Gatilho: Diário às 08:00
5. Ação: Iniciar um programa
6. Programa: `C:\php\php.exe` (ou caminho do seu PHP)
7. Argumentos: `c:\sistema\scripts\alerta_vencimentos.php`
8. Iniciar em: `c:\sistema`

### Linux/Mac (Cron)

```bash
# Editar crontab
crontab -e

# Adicionar linha (diário às 08h)
0 8 * * * /usr/bin/php /caminho/para/sistema/scripts/alerta_vencimentos.php >> /caminho/para/sistema/logs/alerta_vencimentos_cron.log 2>&1
```

### Teste Manual

```bash
# Windows
php c:\sistema\scripts\alerta_vencimentos.php

# Linux/Mac
php /caminho/para/sistema/scripts/alerta_vencimentos.php
```

O script irá:
- Verificar certificados com vencimento em 30 e 7 dias
- Agrupar por cliente
- Enviar um e-mail por cliente com todos os certificados
- Registrar cada envio em `email_logs`
- Gerar log em `logs/alerta_vencimentos.log`

## 7. Módulo de Histórico de E-mails

Acesso: `APP_URL/emails` (apenas ADMIN)

Funcionalidades:
- Listagem de todos os e-mails enviados
- Filtros: tipo, status, período (data início/fim), busca textual
- Botão "Reenviar" para registros com status "erro"
- Visualização de mensagens de erro

## 8. Botões de Envio nos Módulos

### Comercial (Propostas)
- **Enviar Proposta**: anexa PDF, usa template `proposta.html`
- Local: listagem e detalhe da proposta

### Agendamentos
- **Envio automático**: ao confirmar agendamento, envia e-mail usando `agendamento.html`

### Certificados (CSN, CNBL, CNARQ)
- **Enviar Certificado**: anexa PDF, usa template `certificado.html`
- **Enviar Link de Assinatura**: usa template `assinatura.html`
- Ambos disponíveis na listagem de cada módulo

## 9. Logs

- `logs/alerta_vencimentos.log` - Execuções do script de alerta
- `logs/php_errors.log` - Erros PHP gerais
- Tabela `email_logs` - Histórico de todos os e-mails

## 10. Verificação Rápida

```bash
# Verificar sintaxe PHP dos arquivos principais
php -l includes/mailer.php
php -l includes/enviar_certificado.php
php -l includes/enviar_assinatura.php
php -l scripts/alerta_vencimentos.php
php -l modules/emails/index.php
php -l modules/comercial/propostas/actions.php
php -l modules/agendamentos/actions.php
php -l modules/documentacao/certificados/actions.php
php -l modules/documentacao/cnbl/actions.php
php -l modules/documentacao/cnarq/actions.php