# Ciclo de Vida dos Certificados

## Status possíveis
- Emitido (Ativo)
- Assinado (Token Validado no Canvas)
- Inativo/Cancelado

## Transições de status

| Status Atual | Pode ir para | Quem autoriza | O que dispara |
|---|---|---|---|
| Emitido | Assinado | Sistema (Cliente via Token) | Assinatura pelo Canvas |
| Emitido | Inativo | ADMIN | Ação manual na listagem |
| Assinado | Inativo | ADMIN | Ação manual na listagem |

## Impacto no portal do cliente
Não identificado no código atual um "Portal do Cliente" nativo; a visualização ocorre via envio de Link contendo Hash/Token.