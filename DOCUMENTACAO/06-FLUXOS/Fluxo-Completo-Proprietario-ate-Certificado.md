# Fluxo Completo: Do Proprietário ao Certificado

## Etapa 1: Cadastro do Proprietário
- **Ator:** VENDEDOR/ADMIN
- **Onde no sistema:** `modules/proprietarios`
- **O que é feito:** Inserção de dados fiscais do armador.
- **O que é gerado:** Registro na tabela `pessoas`.
- **Próxima etapa:** Cadastro da Embarcação.

## Etapa 2: Cadastro da Embarcação e vínculo com Proprietário
- **Ator:** VENDEDOR/ADMIN
- **Onde no sistema:** `modules/embarcacoes`
- **O que é feito:** Inserção do barco associando ao Proprietário.
- **O que é gerado:** Registro na tabela `embarcacoes`.
- **Próxima etapa:** Agendamento de Vistoria.

## Etapa 3: Agendamento ou Registro de Vistoria
- **Ator:** ADMIN
- **Onde no sistema:** `modules/vistorias` ou `modules/agendamentos`
- **O que é feito:** Vínculo de vistoriador a uma embarcação.
- **O que é gerado:** Registro na tabela `vistorias` como PENDENTE.
- **Próxima etapa:** Execução.

## Etapa 4: Execução da Vistoria
- **Ator:** VISTORIADOR
- **Onde no sistema:** `modules/vistorias/detalhe.php`
- **O que é feito:** Preenchimento de dados do laudo.
- **O que é gerado:** Update na vistoria (status EM_ANDAMENTO/APROVADA).
- **Próxima etapa:** Certificação.

## Etapa 5: Emissão do Certificado
- **Ator:** ADMIN
- **Onde no sistema:** `modules/documentacao/`
- **O que é feito:** Seleção da vistoria base e acionamento de emissão.
- **O que é gerado:** Geração do PDF no buffer e Token de Assinatura.
- **Próxima etapa:** Assinatura.

## Etapa 6: Assinatura e entrega ao Proprietário
- **Ator:** PROPRIETÁRIO
- **Onde no sistema:** Rota externa `/assinar/{token}`
- **O que é feito:** Assinatura gráfica no elemento Canvas.
- **O que é gerado:** PDF definitivo fechado com a assinatura rasterizada.
- **Próxima etapa:** Renovação cíclica (Vencimento).