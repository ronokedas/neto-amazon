# Módulo: Proprietarios

## O que faz
Atua como uma listagem e CRUD focada exclusivamente no CRM dos donos de embarcação.

## Quem tem acesso
- ADMIN: Sim.
- VENDEDOR: Sim.
- VISTORIADOR: Não.

## Telas / Rotas
- `index.php`, `form.php`, `actions.php` de listagem comum.

## Tabelas usadas
⚠️ **Ambiguidade Resolvida:** Lendo o código (`modules/proprietarios/index.php`), o sistema NÃO utiliza uma tabela chamada `proprietarios`. O módulo é apenas uma tela por cima da tabela `pessoas` e/ou `clientes`.
- Escrita/Leitura: `pessoas` / `clientes`.
