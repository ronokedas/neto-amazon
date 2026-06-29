# Prompt Base - Contexto do Ambiente de Desenvolvimento

Copie este prompt no início de novos chats para eu já ter todo o contexto necessário.

---

## 🐳 Docker e Banco de Dados
- **Docker**: Projeto roda via Docker Compose
- **Banco de dados**: MySQL/MariaDB dentro do Docker (não é local)
- **Nome do container/banco**: (preencher quando souber)
- **Como acessar banco**: `docker exec -it <container> mysql -u<user> -p<pass> <database>` ou via Workbench conectando no localhost com porta exposta
- **Migrações**: Na pasta `migrations/` — executar em ordem numérica (001_, 002_, etc.)
- **Backup**: (adicionar comando se houver)

## 💻 Terminal e Comandos
- **SO**: Windows (cmd/powershell)
- **Shell preferido**: PowerShell ou CMD?
- **Path do projeto**: `C:\sistema\` (raiz)
- **Composer**: Sim ou Não?
- **Node.js**: Sim ou Não?
- **Testing**: PHPUnit ou outro?

## ⚠️ Limitações do Ambiente
- **PowerShell**: Strings com aspas simples/duplas quebram comandos complexos
- **Chars especiais**: Letras acentuadas (ã, ç, é) podem causar erro de encoding no terminal
- **Docker Desktop**: Pode estar rodando/parado — verificar antes de comandos que dependem do banco
- **Backup antes de mudar**: Sempre pedir confirmação antes de mexer em produção

## 📁 Estrutura do Projeto
- **Framework**: PHP puro (sem Laravel/Symfony aparente)
- **Padrão**: MVC simples com folders `modules/`, `includes/`, `templates/`
- **Auth**: Sessions em `includes/auth.php` com funções: `verificar_sessao()`, `getCargo()`, `verificar_cargo()`
- **CSRF**: Token em forms via `gerarCSRF()` e `verificarCSRF()`
- **Frontend**: Bootstrap + Font Awesome (classes como `btn-primary`, `fas fa-icon`)
- **Rotas**: Via `?action=` em arquivos `actions.php` de cada módulo

## 🔐 Permissões por Cargo (ENUM usuarios.cargo)
- `ADMIN`: Acesso total
- `VENDEDOR`: Comercial + operacional (sem usuários/configurações)
- `VISTORIADOR`: Restrito a vistorias e agendamentos
- **Função helper**: `getCargo()` retorna cargo do usuário logado
- **Verificação**: `in_array($cargo, ['ADMIN', 'VENDEDOR'])` — NÃO existe `is_admin()` ou `cargo_atual()`, usa `$cargo === 'X'`

### Regras de Filtro por Cargo (implementado em 2026-06-27)
- **VISTORIADOR**: vê apenas registros onde `vistoriador_id = usuario_id()` (via JOIN com `agendamentos` quando necessário)
- **VENDEDOR**: vê apenas registros onde `vendedor_id = usuario_id()` OU onde o `agendamento_id` pertence a um agendamento criado por ele
- **ADMIN**: vê tudo (sem filtro)
- **Padrão de implementação**:
  ```php
  $where_extra = '';
  $params_cargo = [];
  if ($cargo === 'VISTORIADOR') {
      $where_extra = ' AND a.vistoriador_id = :id_usuario';
      $params_cargo[':id_usuario'] = usuario_id();
  } elseif ($cargo === 'VENDEDOR') {
      $where_extra = ' AND (a.vendedor_id = :id_usuario OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :id_usuario2))';
      $params_cargo[':id_usuario'] = usuario_id();
      $params_cargo[':id_usuario2'] = usuario_id();
  }
  ```

## 🗄️ Convenções de Banco
- **UUID**: IDs são `char(36)` com `UUID()` no INSERT
- **Timestamps**: Campos `criado_em`, `atualizado_em` podem existir
- **Soft delete**: Campo `ativo` (0/1) em vez de DELETE
- **Foreign keys**: Nomes como `cliente_id`, `embarcacao_id`, etc.
- **Ordens de Serviço**: Tabela `ordens_servico`, numero gerado por função `gerarNumeroDocumento('OS', 'AM-OS')`

## 🧪 Como Executar Testes
1. **Sintaxe PHP**: `php -l arquivo.php` antes de salvar
2. **Visual**: Abrir no navegador `http://localhost` (porta 80 ou 8080)
3. **Logs**: `error_log()` grava em arquivo do PHP/Docker
4. **NÃO usar**: `php artisan`, `npm run`, `symfony` (não é esses frameworks)

## 📝 Estilo de Código
- **Indentação**: 4 espaços
- **Aspas**: Strings SQL usam `"`, strings PHP usam `'`
- **Variáveis**: `$camelCase`
- **Sanitização**: Função `sanitizar()` para inputs
- **Moeda**: Função `formatarMoeda()` para valores monetários
- **HTMLEscape**: Função `h()` para output

## 🚨 Regras ao Editar
1. **Sempre manter lógica existente** — só adicionar/modificar o necessário
2. **Não quebrar funcionalidades existentes** (ex: VISTORIADOR não pode confirmar OS)
3. **Testar via leitura de arquivo** antes de executar (ambiente Windows é instável)
4. **Verificar encoding**: Arquivos são UTF-8, mas PowerShell pode corromper acentos
5. **Backup implícito**: Sempre guardar o código ANTES das alterações em script separado

## 🔧 Comandos Úteis (Windows)
```cmd
:: Ver containers rodando
docker ps

:: Acessar banco
docker exec -i nome_container mysql -uuser -ppass database

:: Ver logs do PHP
docker logs nome_container --tail 100

:: Parar/start containers
docker-compose down
docker-compose up -d

:: Ver estrutura de pasta
dir /b pasta

:: Ler arquivo sem corromper encoding
php -r "echo file_get_contents('arquivo.php');"
```

## 📋 Checklist Antes de Entregar
- [ ] Criar script de alteração em PHP separado (não editar direto no arquivo quando possível)
- [ ] Validar com `php -l arquivo.php`
- [ ] Verificar se não quebrou permissões de outros cargos
- [ ] Mostrar diff/trechos alterados
- [ ] Confirmar que migration/banco está atualizado se adicionou coluna

---

**Preencher quando souber:**
- Nome do container Docker: `sistema_php` / `sistema_mysql` / outro?
- Portas expostas: MySQL 3306? PHP 80?
- Nome do banco e usuário/senha
