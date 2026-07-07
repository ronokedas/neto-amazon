<?php
/**
 * MODULO: CONTRATOS
 * Arquivo: form.php - Adicionar / Editar contrato
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (getCargo() !== 'ADMIN' && getCargo() !== 'VENDEDOR') {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$id = $_GET['id'] ?? '';
$contrato = [
    'id' => '',
    'numero' => '',
    'cliente_id' => '',
    'proposta_id' => '',
    'data_emissao' => date('Y-m-d'),
    'data_vencimento' => '',
    'valor_total' => '',
    'status' => 'MINUTA',
    'conteudo' => ''
];

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM contratos WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $registro = $stmt->fetch();
    if ($registro) {
        $contrato = $registro;
    } else {
        setMensagem('error', 'Contrato nÃ£o encontrado.');
        redirecionar(APP_URL . 'contratos');
    }
}

if (empty($contrato['data_vencimento']) && !empty($contrato['data_emissao'])) {
    $contrato['data_vencimento'] = date('Y-m-d', strtotime($contrato['data_emissao'] . ' +30 days'));
}

$stmtClientes = $pdo->query("SELECT id, nome AS nome_completo, cpf_cnpj AS cpf, NULL AS cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC");
$clientes = $stmtClientes->fetchAll();

$stmtPropostas = $pdo->query("SELECT id, numero, cliente_id, valor_total FROM propostas WHERE status IN ('aprovada', 'enviada') ORDER BY created_at DESC");
$propostas = $stmtPropostas->fetchAll();

$csrf = gerarCSRF();

$titulo_page = ($id ? 'Editar' : 'Novo') . ' Contrato - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <div>
            <a href="<?= APP_URL ?>contratos" class="btn-link mb-1"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
            <h1 class="page-title"><?= $id ? 'Editar' : 'Novo' ?> Contrato</h1>
            <p class="page-subtitle">Preencha os dados do contrato</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="<?= APP_URL ?>contratos/actions" method="POST">
                <input type="hidden" name="action" value="salvar">
                <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                <input type="hidden" name="id" value="<?= h($contrato['id']) ?>">

                <div class="grid-2">
                    <div class="form-group">
                        <label for="cliente_id">Cliente <span class="text-danger">*</span></label>
                        <select id="cliente_id" name="cliente_id" required>
                            <option value="">Selecione um cliente...</option>
                            <?php foreach ($clientes as $cliente):
                                $doc = $cliente['cnpj'] ?: $cliente['cpf'];
                                $docStr = $doc ? " - $doc" : '';
                            ?>
                                <option value="<?= $cliente['id'] ?>" <?= $contrato['cliente_id'] === $cliente['id'] ? 'selected' : '' ?>>
                                    <?= h($cliente['nome_completo']) ?><?= $docStr ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="proposta_id">Proposta Vinculada</label>
                        <select id="proposta_id" name="proposta_id">
                            <option value="">Nenhuma</option>
                            <?php foreach ($propostas as $proposta): ?>
                                <option value="<?= $proposta['id'] ?>"
                                    data-cliente="<?= $proposta['cliente_id'] ?>"
                                    data-valor="<?= $proposta['valor_total'] ?>"
                                    <?= $contrato['proposta_id'] === $proposta['id'] ? 'selected' : '' ?>>
                                    <?= h($proposta['numero']) ?> - R$ <?= number_format($proposta['valor_total'], 2, ',', '.') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Opcional. Vincular a uma proposta preencherÃ¡ o valor automaticamente.</small>
                    </div>

                    <div class="form-group">
                        <label for="status">Status do Contrato <span class="text-danger">*</span></label>
                        <select id="status" name="status" required>
                            <option value="MINUTA" <?= $contrato['status'] === 'MINUTA' ? 'selected' : '' ?>>Minuta</option>
                            <option value="AGUARDANDO_ASSINATURA" <?= $contrato['status'] === 'AGUARDANDO_ASSINATURA' ? 'selected' : '' ?>>Aguardando Assinatura</option>
                            <option value="ASSINADO" <?= $contrato['status'] === 'ASSINADO' ? 'selected' : '' ?>>Assinado</option>
                            <option value="CANCELADO" <?= $contrato['status'] === 'CANCELADO' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="data_emissao">Data de EmissÃ£o <span class="text-danger">*</span></label>
                        <input type="date" id="data_emissao" name="data_emissao" value="<?= h($contrato['data_emissao']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="data_vencimento">Validade para Assinatura</label>
                        <input type="date" id="data_vencimento" name="data_vencimento" value="<?= h($contrato['data_vencimento']) ?>" readonly>
                        <small class="text-muted">O contrato fica disponÃ­vel para aceite por 30 dias a partir da emissÃ£o.</small>
                    </div>

                    <div class="form-group">
                        <label for="valor_total">Valor Total / Parcela (R$) <span class="text-danger">*</span></label>
                        <input type="number" id="valor_total" name="valor_total" step="0.01" value="<?= h($contrato['valor_total']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="frequencia">FrequÃªncia (RecorrÃªncia) <span class="text-danger">*</span></label>
                        <select id="frequencia" name="frequencia" required>
                            <option value="ÚNICA" <?= ($contrato['frequencia'] ?? 'ÚNICA') === 'ÚNICA' ? 'selected' : '' ?>>Cobrança Única</option>
                            <option value="MENSAL" <?= ($contrato['frequencia'] ?? '') === 'MENSAL' ? 'selected' : '' ?>>Mensal</option>
                            <option value="TRIMESTRAL" <?= ($contrato['frequencia'] ?? '') === 'TRIMESTRAL' ? 'selected' : '' ?>>Trimestral</option>
                            <option value="SEMESTRAL" <?= ($contrato['frequencia'] ?? '') === 'SEMESTRAL' ? 'selected' : '' ?>>Semestral</option>
                            <option value="ANUAL" <?= ($contrato['frequencia'] ?? '') === 'ANUAL' ? 'selected' : '' ?>>Anual</option>
                        </select>
                    </div>

                    <div class="form-group" id="box_dia_vencimento" style="<?= ($contrato['frequencia'] ?? 'ÚNICA') === 'ÚNICA' ? 'display: none;' : '' ?>">
                        <label for="dia_vencimento">Dia de Vencimento</label>
                        <input type="number" id="dia_vencimento" name="dia_vencimento" min="1" max="31" value="<?= h($contrato['dia_vencimento'] ?? '') ?>" placeholder="Ex: 5, 10, 20">
                    </div>

                    <div class="form-group" id="box_renovacao" style="<?= ($contrato['frequencia'] ?? 'ÚNICA') === 'ÚNICA' ? 'display: none;' : '' ?>">
                        <label for="renovacao_automatica">RenovaÃ§Ã£o AutomÃ¡tica</label>
                        <select id="renovacao_automatica" name="renovacao_automatica">
                            <option value="1" <?= ($contrato['renovacao_automatica'] ?? 1) == 1 ? 'selected' : '' ?>>Sim, renovar automaticamente</option>
                            <option value="0" <?= ($contrato['renovacao_automatica'] ?? 1) == 0 ? 'selected' : '' ?>>Não, encerra na data final</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mt-2">
                    <label for="conteudo">ConteÃºdo do Contrato</label>
                    <textarea id="conteudo" name="conteudo" rows="15" placeholder="Digite ou cole o texto do contrato aqui..."><?= h($contrato['conteudo']) ?></textarea>
                </div>

                <div class="form-group mt-3 d-flex gap-2" style="justify-content: flex-end;">
                    <a href="<?= APP_URL ?>contratos" class="btn btn-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-save"></i> Salvar Contrato
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('frequencia').addEventListener('change', function() {
    const boxDia = document.getElementById('box_dia_vencimento');
    const boxRenovacao = document.getElementById('box_renovacao');
    if (this.value === 'ÚNICA') {
        boxDia.style.display = 'none';
        boxRenovacao.style.display = 'none';
    } else {
        boxDia.style.display = 'block';
        boxRenovacao.style.display = 'block';
    }
});

document.getElementById('proposta_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    if (selected.value) {
        const clienteId = selected.getAttribute('data-cliente');
        const valorTotal = selected.getAttribute('data-valor');

        if (clienteId) {
            document.getElementById('cliente_id').value = clienteId;
        }
        if (valorTotal) {
            document.getElementById('valor_total').value = valorTotal;
        }
    }
});

function atualizarValidadeContrato() {
    const emissao = document.getElementById('data_emissao').value;
    if (!emissao) return;

    const data = new Date(emissao + 'T00:00:00');
    data.setDate(data.getDate() + 30);
    const yyyy = data.getFullYear();
    const mm = String(data.getMonth() + 1).padStart(2, '0');
    const dd = String(data.getDate()).padStart(2, '0');
    document.getElementById('data_vencimento').value = `${yyyy}-${mm}-${dd}`;
}

document.getElementById('data_emissao').addEventListener('change', atualizarValidadeContrato);
atualizarValidadeContrato();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
