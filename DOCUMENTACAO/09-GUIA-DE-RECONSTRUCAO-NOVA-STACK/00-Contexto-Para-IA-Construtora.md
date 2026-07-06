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