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
