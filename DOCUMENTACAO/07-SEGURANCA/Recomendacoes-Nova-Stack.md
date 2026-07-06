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