# Fluxo: Cadastro de Vistoria

## Quem inicia
Administrador ou Vendedor.
Acesso validado através de checagem de sessão em `index.php` e nos arquivos do módulo de vistorias.

## Pré-requisitos
- Embarcação previamente cadastrada.
- Proprietário vinculado à embarcação.

## Dados coletados
- Identificação da Vistoria (Data, Local) - Obrigatório: Sim
- Checklist de Itens da Embarcação - Obrigatório: Não identificado no código atual de forma estrita.
- Exigências (caso haja) - Obrigatório: Não

## Validações aplicadas
Não identificado no código atual (Muitas validações limitam-se a atributos HTML 'required').

## O que é salvo no banco
Tabela: `vistorias`
Ação: INSERT com os dados do laudo.

## Estados possíveis da vistoria
- PENDENTE
- EM_ANDAMENTO
- APROVADA
- REPROVADA
A transição ocorre por intervenção manual na alteração de status do laudo.

## O que acontece depois
Atualização de status na base de dados. Geração de certificados não é automática.

## O que acontece se for reprovada
Não identificado no código atual lógica especial ou bloqueio automático de tela para vistorias reprovadas, além do registro do status.