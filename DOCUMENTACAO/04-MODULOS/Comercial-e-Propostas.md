# Módulo: Comercial e Propostas

## O que faz (em linguagem de negócio)
O módulo atua como um CRM inicial (Customer Relationship Management) e gerador de orçamentos oficiais. Ele é o ponto de entrada de novos serviços. A empresa emite uma proposta orçamentária detalhando o serviço (ex: Vistoria para CSN) antes da vistoria acontecer. O cliente visualiza a proposta através de um link externo, assina, e só então o serviço segue para agendamento.

## Quem tem acesso
- ADMIN: Acesso total (pode alterar valores finais de qualquer proposta, aprovar manualmente ou inativar propostas perdidas).
- VENDEDOR: Usuário principal do módulo. Responsável por prospectar, preencher dados, gerar a proposta, disparar o e-mail para o cliente e cobrar a assinatura do orçamento.
- VISTORIADOR: Não identificado no código atual. Seu escopo não inclui lidar com orçamentos/preços.

## Telas e rotas existentes
- `?action=comercial` → Listagem geral (Grid) das propostas com barra de busca e indicativos de status.
- `?action=comercial&sub=nova` → Tela de criação de nova Proposta.
- `?action=comercial&sub=pdf` → Rota interceptadora de geração da proposta em PDF On-The-Fly via TCPDF.
- `?action=comercial/propostas/actions` → Recebe POSTs de exclusão, edição de status ou envio de E-mail via PHPMailer.
- `?action=comercial/servicos` → Gestão do catálogo dos serviços que podem ser orçados.
- `/assinar/{token}` → Rota pública interceptada em `index.php` exibindo o canvas de assinatura do orçamento.

## Fluxo da proposta do início ao fim

### Passo 1: Criação da proposta
- Quem cria: VENDEDOR ou ADMIN.
- A proposta é vinculada a uma embarcação e a um cliente na tabela de relacionamento.
- A proposta detalha o escopo de certificação naval, com campos para valor total e forma de pagamento livre (texto).

### Passo 2: Geração do documento
- O sistema gera PDF dinâmico via `TCPDF`.
- O layout carrega o logo da Amazon Naval, dados do armador, dados da embarcação e os valores/termos hardcoded em HTML dentro do arquivo PHP.
- A assinatura nesse documento ocorre de forma gráfica (via Canvas), transformando-se em imagem embutida ao final.
- O e-mail de envio contém um link parametrizado na estrutura de token (via `PHPMailer`).

### Passo 3: Aprovação
- Sim, o cliente aprova via Link. Ele cai em uma página pública que exibe um Canvas em JS. Ele assina digitalmente (rabisco), e o sistema injeta o `assinatura_b64` em banco e muda o status.

### Passo 4: Conversão em serviço
- O processo é em sua esmagadora maioria **manual**. A conversão automática não é fortemente identificada no código atual; ou seja, o Vendedor avisa o Admin que a proposta foi assinada para que se proceda o lançamento financeiro e o agendamento logístico do Vistoriador na tela de `agendamentos`.

### Passo 5: Contratos
- Há um submódulo de `contratos/`. O contrato formaliza de forma mais ampla o que estava na proposta comercial, muitas vezes focado em serviços estendidos. 
- O contrato possui rotas `?action=contratos` próprias, e atua muito mais como arquivamento textual legal.

## Tabelas do banco usadas
- **Tabela:** `propostas`
- **Para que serve:** Armazena o orçamento criado pelo vendedor.
- **Campos:** `id`, `cliente_id`, `embarcacao_id`, `valor_total`, `status`, `token_assinatura`, `assinatura_b64`.
- **Status:** 'CRIADA', 'ENVIADA', 'ASSINADA', 'RECUSADA'.
- **Relacionamentos:** Pertence a 1 Cliente e referencia 1 Embarcação.

## Cálculo de valores
- **Como é calculado:** Não identificado no código atual a aplicação de fórmulas financeiras pesadas de cálculo. A precificação dos serviços (Vistoria, etc.) é imputada via catálogo base, mas o valor final é manipulável manualmente. Os impostos e parcelamentos são preenchidos descritivamente (como texto/observação), não contabilizados em um array relacional de parcelas ou tributos gerados por engine matemática.

## Regras de negócio
RN-COM-001: Para o disparo do e-mail ao cliente, a tabela do cliente DEVE possuir o e-mail preenchido e não nulo.
RN-COM-002: A assinatura captura o IP do assinante (se possível via $_SERVER) e o timestamp imediato da transação de tela, consolidando a validade jurídica primária.
RN-COM-003: Propostas não devem ser apagadas fisicamente, para evitar rompimento das dependências (usando soft delete).

## Problemas e débitos técnicos
- Conversão fraca: Quando a proposta é "ASSINADA", o sistema deveria criar nativa e atomicamente a Conta a Receber no Financeiro e a linha do Agendamento. Hoje depende da memória/gestão operacional humana.
- Armazenamento em Base64: A imagem da assinatura da proposta salva em `text` sobrecarrega as queries da tabela na listagem (N+1).