<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

$vistoria_id = $_GET['vistoria_id'] ?? '';

if (empty($vistoria_id)) {
    setMensagem('error', 'ID da vistoria não informado.');
    redirecionar(APP_URL . 'vistorias');
}

$stmt = $pdo->prepare("
    SELECT v.*, e.nome as emb_nome, e.registro, a.id as agendamento_id
    FROM vistorias v
    JOIN embarcacoes e ON v.embarcacao_id = e.id
    JOIN agendamentos a ON v.agendamento_id = a.id
    WHERE v.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $vistoria_id]);
$vistoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vistoria) {
    setMensagem('error', 'Vistoria não encontrada.');
    redirecionar(APP_URL . 'vistorias');
}

if ($vistoria['status'] !== 'APROVADA_COM_EXIGENCIAS') {
    setMensagem('error', 'Esta vistoria não possui exigências pendentes para baixa.');
    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($vistoria['agendamento_id']));
}

$stmtE = $pdo->prepare("
    SELECT * FROM vistoria_exigencias 
    WHERE vistoria_id = :vid AND conforme = 'nao' 
    ORDER BY ordem ASC
");
$stmtE->execute([':vid' => $vistoria_id]);
$exigencias_pendentes = $stmtE->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Baixa de Exigências - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="etapas-fluxo" style="display:flex; align-items:center; padding: 20px 0;">
        <div class="etapa">
            <span class="etapa-numero" style="background:#2ECC71;color:#000">1</span>
            <span class="etapa-label" style="font-size:12px;color:#ccc">Relatório</span>
        </div>
        <div class="etapa-linha" style="flex:1;height:3px;background:#2ECC71;margin:0 8px;margin-bottom:20px"></div>
        <div class="etapa">
            <span class="etapa-numero" style="background:#2ECC71;color:#000">2</span>
            <span class="etapa-label" style="font-size:12px;color:#ccc">Certificado</span>
        </div>
        <div class="etapa-linha" style="flex:1;height:3px;background:#2ECC71;margin:0 8px;margin-bottom:20px"></div>
        <div class="etapa">
            <span class="etapa-numero" style="background:#2ECC71;color:#000">3</span>
            <span class="etapa-label" style="font-size:12px;color:#fff;font-weight:bold">Exigências</span>
        </div>
    </div>

    <div class="tabela-header">
        <h2><i class="fas fa-clipboard-check"></i> Baixa de Exigências</h2>
        <p class="text-muted">
            Embarcação: <strong><?= h($vistoria['emb_nome']) ?></strong> 
            · Registro: <?= h($vistoria['registro']) ?>
            · Relatório: <?= h($vistoria['numero'] ?? 'S/N') ?>
        </p>
    </div>

    <?php if (empty($exigencias_pendentes)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> 
            Não há exigências pendentes. O certificado já possui validade total.
        </div>
        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($vistoria['agendamento_id']) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar ao Relatório
        </a>
    <?php else: ?>
        <div class="card mb-4">
            <div class="card-header" style="background:#f39c12;color:#000">
                <h4><i class="fas fa-exclamation-triangle"></i> Itens Não Conformes Pendentes</h4>
            </div>
            <div class="card-body">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Descrição</th>
                            <th>Observação</th>
                            <th>Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exigencias_pendentes as $idx => $ex): ?>
                        <tr>
                            <td><?= $idx + 1 ?></td>
                            <td><?= h($ex['item']) ?></td>
                            <td><?= h($ex['descricao'] ?? '') ?></td>
                            <td><?= h($ex['observacao'] ?? '') ?></td>
                            <td>
                                <form method="POST" action="<?= APP_URL ?>documentacao/actions?action=baixar_exigencia" 
                                      style="display:inline;"
                                      onsubmit="return confirm('Confirmar baixa desta exigência? O proprietário informou que corrigiu o problema.')">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()) ?>">
                                    <input type="hidden" name="vistoria_id" value="<?= h($vistoria_id) ?>">
                                    <input type="hidden" name="exigencia_id" value="<?= h($ex['id']) ?>">
                                    <button type="submit" class="btn btn-success btn-sm">
                                        <i class="fas fa-check"></i> Dar Baixa
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($vistoria['agendamento_id']) ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar ao Relatório
        </a>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
