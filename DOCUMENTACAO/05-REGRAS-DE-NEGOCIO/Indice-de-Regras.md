# Índice de Regras de Negócio (Dicionário Completo)

Este documento centraliza todas as regras de negócio identificadas na auditoria do legado. Cada regra mapeada aqui DEVE ser implementada no novo sistema NestJS para garantir a paridade de negócio e compliance com as normas (ex: NORMAM).

---

## 1. Certificados (RB-CERT)

**RB-CERT-001**
**Descrição:** O Certificado de Segurança da Navegação (CSN) só pode ser emitido para embarcações que possuam uma vistoria com status 'APROVADA'.
**Onde está no código:** `modules/documentacao/certificados/actions.php` (validação na geração)
**Impacto:** Risco jurídico grave. Emissão indevida para barcos irregulares.
**Complexidade para reimplementar:** Média
**Observações:** Exceções aplicam-se apenas a vistorias de revalidação que ainda estão vigentes por liminar.

**RB-CERT-002**
**Descrição:** Assinatura via Canvas obriga persistência imediata em Base64 para embutir no TCPDF.
**Onde está no código:** `modules/documentacao/certificados/assinar.php`
**Impacto:** Se violada, o certificado sairá sem o traço físico da assinatura do cliente.
**Complexidade para reimplementar:** Alta (Será trocada na nova stack por persistência em S3).
**Observações:** O token URL é o que autoriza o cliente final a assinar sem estar logado.

---

## 2. Vistorias (RB-VIST)

**RB-VIST-001**
**Descrição:** Um VISTORIADOR só pode acessar ou alterar o status das vistorias que foram explicitamente atribuídas a ele na tabela `vistorias`.
**Onde está no código:** `modules/vistorias/index.php` (cláusula WHERE no SQL base)
**Impacto:** Quebra de sigilo e invasão de dados concorrentes.
**Complexidade para reimplementar:** Alta (Exigirá Row-Level Security ou Guards rigorosos no NestJS).
**Observações:** O ADMIN contorna essa regra (enxerga tudo).

---

## 3. Cálculos Navais (RB-CALC)

**RB-CALC-001**
**Descrição:** O cálculo da Arqueação Bruta no CNARQ usa uma fórmula matemática rígida baseada no Comprimento (L), Boca (B) e Pontal (P) conforme regra da NORMAM.
**Onde está no código:** `modules/documentacao/cnarq/actions.php`
**Impacto:** O valor do imposto pago pela embarcação estará errado, invalidando o registro na Marinha.
**Complexidade para reimplementar:** Média
**Observações:** Requer tipagem Float estrita no Prisma (`Decimal`) para evitar arredondamentos indevidos.

---

## 4. Usuários e Permissões (RB-USR)

**RB-USR-001**
**Descrição:** Exclusão lógica (Soft Delete). Nenhum registro de embarcação, cliente ou vistoria pode sofrer `DELETE` físico. Deve ser usado `ativo = 0`.
**Onde está no código:** Todas as `actions.php` dos módulos principais (ex: `modules/clientes/actions.php`).
**Impacto:** Quebra da rastreabilidade da trilha de auditoria e falhas na integridade referencial.
**Complexidade para reimplementar:** Baixa (Configuração nativa via Middleware do Prisma).
**Observações:** Essencial para o modo Offline do mobile (saber o que excluir).

---

## Regras que dependem de norma externa (NORMAM, legislação)

As regras abaixo não são meras preferências do cliente, elas **estão atreladas a exigências legais e normas da Marinha do Brasil**:

1. **RB-CALC-001 (Cálculo de Arqueação):** Segue as diretrizes da **NORMAM-01/DPC** (Convenção Internacional sobre Arqueação de Navios).
2. **RB-CERT-001 (Emissão de CSN condicionada):** Exigência direta das capitanias dos portos para rastreabilidade de inspeção de Casco/Salvatagem.
3. **Distribuição de Passageiros (CNBL/CSN):** Cálculos da NORMAM-02 referentes ao espaçamento mínimo do convés (0.60m² por pessoa).