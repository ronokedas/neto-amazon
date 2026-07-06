# Controle de Acesso por Cargo

## Cargos existentes
- ADMIN: Controle total e emissão de certificados.
- VENDEDOR: Acesso comercial, clientes e propostas.
- VISTORIADOR: Preenchimento de laudos em campo.

## Tabela de permissões

| Ação no Sistema | ADMIN | VENDEDOR | VISTORIADOR |
|---|---|---|---|
| Cadastrar proprietário | Sim | Sim | Não |
| Editar proprietário | Sim | Sim | Não |
| Excluir proprietário | Sim | Não | Não |
| Cadastrar embarcação | Sim | Sim | Não |
| Cadastrar vistoria | Sim | Não | Sim |
| Emitir certificado | Sim | Não | Não |
| Assinar certificado | Não identificado | Não identificado | Não identificado |
| Cancelar certificado | Sim | Não | Não |
| Ver relatórios | Sim | Não | Não |
| Gerenciar usuários | Sim | Não | Não |

## Como a verificação é feita no código
Através de variáveis na `$_SESSION['usuario_logado']` e exibição condicional (IF/ELSE) de botões de interface.

## Rotas sem proteção de cargo
- `/login`
- `/assinar/{token}`