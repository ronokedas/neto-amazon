## 1. Visão Geral do Sistema e Objetivos de Negócio

**O que o sistema faz:**
O sistema é um ERP corporativo da Amazon Naval focado exclusivamente em gerenciar a cadeia de vistorias navais e emissão de certificados oficiais exigidos pela Marinha do Brasil (NORMAM). Ele atua como um hub central onde a empresa gerencia seus clientes (proprietários/armadores), as embarcações, os agendamentos de vistorias técnicas e a emissão final, desenhada e validada, de certificados em PDF assinados via QR Code/Canvas.

**Quem são os usuários:**
Conforme as regras de controle de acesso (login e perfis), existem três tipos de cargo/perfil no sistema:
- **ADMIN:** Controle total sobre todos os módulos (Financeiro, Aprovação de Documentos, Emissão de PDFs, Configurações de sistema e Acessos).
- **VENDEDOR:** Focado na prospecção (Comercial), faturamento de propostas, geração de orçamentos e cadastros base (Clientes e Embarcações).
- **VISTORIADOR:** Técnico que atua na ponta, realiza as inspeções nas embarcações, preenchendo as características técnicas, avaliando exigências e alimentando os relatórios de vistoria.

**Módulos Principais:**
- **Agendamentos:** Controle de visitas técnicas/Ordens de Serviço.
- **Armadores / Proprietários / Clientes:** Gestão de CRM, responsáveis legais pelas embarcações.
- **Embarcações:** Cadastro estrutural dos barcos vinculados aos clientes.
- **Vistorias:** Coração técnico do sistema, onde dados de borda livre, casco, salvatagem são reportados.
- **Documentação / Certificados:** Emissão final baseada nos relatórios de vistoria.
- **Financeiro:** Controle de faturamento e pagamentos de propostas.
- **Comercial (Propostas):** Geração de contratos e orçamentos para os clientes.

**Quais certificados o sistema emite:**
De acordo com o mapeamento de rotas e banco de dados, os documentos emitidos são:
- **CSN:** Certificado de Segurança da Navegação.
- **CNBL:** Certificado Nacional de Borda Livre.
- **CNARQ:** Certificado Nacional de Arqueação.
- **LC / LP / CHT:** Licenças e Certificados complementares (identificados no index.php como rotas específicas de assinatura).
- **Propostas Comerciais / Contratos.**

**O que o sistema NÃO faz (Limitações do Legado):**
- Não possui um App Mobile Nativo/Offline, obrigando o vistoriador a trabalhar online na plataforma Web ou preencher papel.
- Não guarda o documento físico histórico no banco de dados (S3/MinIO), dependendo da geração do PDF em tempo de execução via biblioteca.
- Não possui sincronização bidirecional de estado sem conexão (offline-first).