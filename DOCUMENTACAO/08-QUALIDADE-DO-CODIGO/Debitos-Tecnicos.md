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