# ERP Sistema de Gestão — Guia para Criação de Módulos

## Visão Geral do Projeto

Sistema ERP em PHP + MySQL com tema verde escuro, hospedado no Hostgator (cPanel).  
Para adicionar um novo módulo, siga o padrão abaixo passo a passo.

---

## Arquitetura

```
c:\sistema\
├── index.php              ← Roteador principal (mapear novas rotas aqui)
├── config.php             ← Conexão PDO, constantes (APP_URL, APP_NAME)
├── database.sql           ← Schema completo do banco
├── .htaccess              ← Rewrite rules
├── assets/css/style.css   ← Tema verde escuro (CSS variables)
├── includes/
│   ├── auth.php           ← verificar_sessao(), verificar_cargo(), podeAcessar()
│   ├── functions.php      ← h(), gerarUUID(), gerarCSRF(), validarCPF(), formatarMoeda(), etc.
│   ├── header.php         ← <head>, CSS, abertura do body
│   ├── footer.php         ← JS, fechamento do body
│   └── sidebar.php        ← Menu lateral (adicionar novo módulo aqui)
└── modules/
    ├── login/
    ├── dashboard/
    ├── embarcacoes/       ← CRUD padrão (index, form, actions)
    ├── pessoas/           ← CRUD padrão (index, form, actions)
    ├── vistorias/         ← Wizard multi-step (index, nova, detalhe, actions)
    ├── financeiro/        ← CRUD com cards de resumo (index, form, actions)
    └── usuarios/          ← CRUD padrão (index, form, actions) — ADMIN only
```

---

## Padrão para Criar um Novo Módulo

### 1. Criar a pasta: `modules/{nome}/`

### 2. Criar os 3 arquivos base:

#### `modules/{nome}/actions.php`
```php
<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// PERMISSÃO — escolher UMA opção:
verificar_sessao();
verificar_cargo('ADMIN');                    // ← apenas ADMIN
// OU
if (!podeAcessar('{nome}')) { ... }          // ← ADMIN + VISTORIADOR

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'salvar':
        // Verificar CSRF com verificarCSRF()
        // Validar campos
        // Inserir: INSERT com gerarUUID()
        // Atualizar: UPDATE WHERE id = :id
        // setMensagem('success', '...') ou setMensagem('error', '...')
        // redirecionar(APP_URL . '{nome}')
        break;

    case 'excluir':
        // DELETE WHERE id = :id
        break;

    case 'alternar_status':  // opcional
        // UPDATE ativo = NOT ativo
        break;

    default:
        redirecionar(APP_URL . '{nome}');
}
```

#### `modules/{nome}/form.php`
```php
<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Mesma verificação de permissão do actions.php

// Buscar registro se edição: $_GET['id'] → SELECT * WHERE id = :id
$csrf = gerarCSRF();

// include header + sidebar
// Formulário com:
//   - <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
//   - <input type="hidden" name="id" value="<?php echo h($registro['id'] ?? ''); ?>">
//   - action = APP_URL . '{nome}/actions?action=salvar'
//   - Botões: Salvar + Voltar
// include footer
```

#### `modules/{nome}/index.php`
```php
<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Mesma verificação de permissão

// Buscar registros: SELECT ... ORDER BY ... ASC
// include header + sidebar
// Estrutura:
//   <div class="conteudo-principal">
//     <div class="tabela-container">
//       <div class="tabela-header"> ← título + botão "Novo"
//       filtros (opcional)
//       <table> ← listagem
//       <div class="card-footer"> ← resumo
//     </div>
//   </div>
// include footer
```

### 3. Atualizar o roteador (`index.php` na raiz):
Adicionar no array `$rotas`:
```php
'{nome}'          => 'modules/{nome}/index.php',
'{nome}/form'     => 'modules/{nome}/form.php',
'{nome}/actions'  => 'modules/{nome}/actions.php',
```

### 4. Atualizar o sidebar (`includes/sidebar.php`):
Adicionar no array `$modulos` do cargo correspondente:
```php
['icon' => 'fa-ICONE', 'label' => 'Label', 'page' => '{nome}'],
```

### 5. Atualizar as permissões (`includes/auth.php`):
Se o módulo deve ser acessível por VISTORIADOR, adicionar em `$modulosPermitidos`:
```php
$modulosPermitidos = ['dashboard', 'login', 'embarcacoes', 'pessoas', 'vistorias', '{nome}'];
```

### 6. Criar tabela no banco (`database.sql`):
```sql
CREATE TABLE {tabela} (
    id            CHAR(36)     PRIMARY KEY DEFAULT (UUID()),
    -- campos aqui --
    ativo         TINYINT(1)   NOT NULL DEFAULT 1,
    criado_por    CHAR(36),
    criado_em     DATETIME     DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (criado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## Convenções de Código

### PHP
- Todas as queries usam **prepared statements** (`$pdo->prepare()`)
- IDs são **UUID** gerados por `gerarUUID()`
- XSS protection: sempre usar `h()` para output
- CSRF: `gerarCSRF()` no form, `verificarCSRF()` no action
- Mensagens: `setMensagem('success'|'error', 'texto')` + `redirecionar()`
- Datas: `formatarData()` (dd/mm/YYYY), `formatarDataCompleta()` (dd/mm/YYYY - H:i)
- Moeda: `formatarMoeda($valor)` → R$ 1.234,56

### CSS / Tema Verde Escuro
**NÃO usar cores fixas claras** (ex: `#f8f9fa`, `white`) — o tema é escuro.

Variáveis CSS disponíveis:
```css
--cor-fundo: #0F1F1A;          /* fundo principal */
--cor-painel: #162822;         /* cards, painéis */
--cor-sidebar: #1A3028;        /* sidebar, caixas de destaque */
--cor-destaque: #2ECC71;       /* verde principal (botões, links) */
--cor-hover: #27AE60;          /* verde hover */
--cor-texto: #E8F5E9;          /* texto principal (claro) */
--cor-texto-secundario: #A5D6A7; /* texto secundário */
--cor-borda: #2D4A3E;          /* bordas */
--cor-erro: #E74C3C;           /* vermelho */
--cor-sucesso: #2ECC71;        /* verde */
--cor-aviso: #F39C12;          /* amarelo/laranja */
--cor-info: #3498DB;           /* azul */
```

Para fundos de caixas/painéis, SEMPRE usar:
```css
background: var(--cor-sidebar);     /* caixas de destaque */
background: var(--cor-painel);      /* cards */
```

Classes CSS prontas:
- `.btn .btn-primary .btn-secondary .btn-danger .btn-success .btn-sm`
- `.badge .badge-success .badge-danger .badge-warning .badge-info .badge-secondary`
- `.card .card-header .card-body .card-footer`
- `.tabela-container .tabela-header .tabela-vazia`
- `.form-group .grid-2 .d-flex .gap-1 .gap-2`
- `.text-muted .conteudo-principal`

### Botões de Ação (na tabela)
```php
<div class="d-flex gap-1">
    <a href=".../form?id=..." class="btn btn-secondary btn-sm" title="Editar">
        <i class="fas fa-edit"></i>
    </a>
    <a href=".../actions?action=excluir&id=..." class="btn btn-danger btn-sm" title="Excluir"
       onclick="return confirm('Confirmar?')">
        <i class="fas fa-trash"></i>
    </a>
</div>
```

---

## Funções Disponíveis (includes/functions.php)

| Função | Uso |
|--------|-----|
| `h($texto)` | Escape HTML (output seguro) |
| `gerarUUID()` | Gera UUID v4 |
| `gerarCSRF()` / `verificarCSRF($token)` | Tokens CSRF |
| `setMensagem($tipo, $texto)` | Flash messages |
| `redirecionar($url)` | Redirect + exit |
| `formatarMoeda($valor)` | R$ 1.234,56 |
| `formatarData($data)` | dd/mm/YYYY |
| `formatarDataCompleta($data)` | dd/mm/YYYY - H:i |
| `validarCPF($cpf)` | Validação de CPF |
| `formatarCPF($cpf)` | 000.000.000-00 |
| `validarEmail($email)` | Validação de email |

---

## Funções de Permissão (includes/auth.php)

| Função | Uso |
|--------|-----|
| `verificar_sessao()` | Exige login |
| `verificar_cargo('ADMIN')` | Exige cargo específico |
| `podeAcessar('modulo')` | Verifica permissão (ADMIN + módulos permitidos) |
| `getCargo()` | Retorna cargo do usuário logado |
| `estaLogado()` | Retorna true/false |

---

## Módulos Existentes (referência)

| Módulo | Arquivos | Permissão | Tipo |
|--------|----------|-----------|------|
| `embarcacoes` | index, form, actions | ADMIN + VISTORIADOR | CRUD padrão |
| `pessoas` | index, form, actions | ADMIN + VISTORIADOR | CRUD com validação CPF |
| `vistorias` | index, nova, detalhe, actions | ADMIN + VISTORIADOR | Wizard 3 passos |
| `financeiro` | index, form, actions | ADMIN only | CRUD + cards resumo |
| `usuarios` | index, form, actions | ADMIN only | CRUD + senhas |

---

## Checklist ao Criar Novo Módulo

- [ ] Criar pasta `modules/{nome}/`
- [ ] Criar `actions.php` com salvar + excluir (+ CSRF + validações)
- [ ] Criar `form.php` com formulário criar/editar
- [ ] Criar `index.php` com listagem
- [ ] Adicionar rotas no `index.php` (roteador)
- [ ] Adicionar no `includes/sidebar.php`
- [ ] Verificar permissão em `includes/auth.php`
- [ ] Criar/atualizar tabela no `database.sql`
- [ ] Usar `var(--cor-sidebar)` para fundos (NÃO cores claras)
- [ ] Testar: criar, editar, excluir, busca/filtros, permissão