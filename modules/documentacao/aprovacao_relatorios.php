<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
if ($cargo !== 'ADMIN') {
    setMensagem('error', 'Acesso negado. Apenas administradores podem acessar esta pagina.');
    redirecionar(APP_URL . 'dashboard');
}

global $pdo;

$stmt = $pdo->prepare("SELECT v.id, v.numero, v.status, v.data_vistoria, v.atualizado_em as data_envio, v.agendamento_id, e.nome as embarcacao, e.registro, u.nome as vistoriador, COUNT(ve.id) as total_itens, SUM(CASE WHEN ve.conforme = 'nao' THEN 1 ELSE 0 END) as itens_nao_conformes FROM vistorias v JOIN embarcacoes e ON v.embarcacao_id = e.id JOIN agendamentos a ON v.agendamento_id = a.id JOIN usuarios u ON a.vistoriador_id = u.id LEFT JOIN vistoria_exigencias ve ON v.id = ve.vistoria_id WHERE v.status = 'AGUARDANDO_APROVACAO' GROUP BY v.id ORDER BY v.atualizado_em ASC");
$stmt->execute();
$pendentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT v.id, v.numero, v.status, v.data_aprovacao, v.observacao_admin, v.agendamento_id, e.nome as embarcacao, u.nome as vistoriador, adm.nome as aprovado_por_nome FROM vistorias v JOIN embarcacoes e ON v.embarcacao_id = e.id JOIN agendamentos a ON v.agendamento_id = a.id JOIN usuarios u ON a.vistoriador_id = u.id LEFT JOIN usuarios adm ON v.aprovado_por = adm.id WHERE v.status IN ('APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA') ORDER BY v.data_aprovacao DESC LIMIT 20");
$stmt2->execute();
$historico = $stmt2->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = 'Aprovação de Relatórios - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>
<div class="conteudo-principal">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Aprovação de Relatórios</h1>
        <?php if (count($pendentes) > 0): ?>
            <span class="badge badge-warning"><?= count($pendentes) ?> aguardando</span>
        <?php endif; ?>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h3>Aguardando Aprovação</h3>
        </div>
        <div class="card-body">
            <?php if (empty($pendentes)): ?>
                <p class="text-muted text-center py-4">
                    <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                    Nenhum relatório aguardando aprovação.
                </p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Número</th>
                            <th>Embarcação</th>
                            <th>Vistoriador</th>
                            <th>Data Vistoria</th>
                            <th>Enviado em</th>
                            <th>Itens</th>
                            <th>Não Conformes</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendentes as $rel): ?>
                        <tr>
                            <td><?= h($rel['numero'] ?? 'S/N') ?></td>
                            <td><?= h($rel['embarcacao']) ?> <small class="text-muted"><?= h($rel['registro']) ?></small></td>
                            <td><?= h($rel['vistoriador']) ?></td>
                            <td><?= $rel['data_vistoria'] ? formatarData($rel['data_vistoria']) : '—' ?></td>
                            <td><?= formatarDataCompleta($rel['data_envio']) ?></td>
                            <td><?= $rel['total_itens'] ?></td>
                            <td>
                                <?php if ($rel['itens_nao_conformes'] > 0): ?>
                                    <span class="badge badge-danger"><?= $rel['itens_nao_conformes'] ?> não conforme(s)</span>
                                <?php else: ?>
                                    <span class="badge badge-success">Todos conformes</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($rel['agendamento_id'] ?? '') ?>" 
                                   class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> Revisar e Aprovar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header" style="cursor:pointer" >
            <h3><i class="fas fa-history"></i> Histórico de Aprovações <i class="fas fa-chevron-down" id="icone-historico"></i></h3>
        </div>
        <div class="card-body" id="historico-body" >
            <?php if (empty($historico)): ?>
                <p class="text-muted">Nenhuma aprovação registrada ainda.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr><th>Número</th><th>Embarcação</th><th>Vistoriador</th><th>Resultado</th><th>Data</th><th>Aprovado por</th><th>Obs.</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historico as $h): ?>
                        <tr>
                            <td><?= h($h['numero'] ?? 'S/N') ?></td>
                            <td><?= h($h['embarcacao']) ?></td>
                            <td><?= h($h['vistoriador']) ?></td>
                            <td>
                                <?php
                                $cores = ['APROVADA'=>'badge-success','APROVADA_COM_EXIGENCIAS'=>'badge-warning','REPROVADA'=>'badge-danger'];
                                $labels = ['APROVADA'=>'Aprovada','APROVADA_COM_EXIGENCIAS'=>'Aprovada c/ Exigências','REPROVADA'=>'Reprovada'];
                                $cor = $cores[$h['status']] ?? 'badge-secondary';
                                $label = $labels[$h['status']] ?? $h['status'];
                                ?>
                                <span class="badge <?= $cor ?>"><?= $label ?></span>
                            </td>
                            <td><?= $h['data_aprovacao'] ? formatarData($h['data_aprovacao']) : '—' ?></td>
                            <td><?= h($h['aprovado_por_nome'] ?? '—') ?></td>
                            <td><?= h(mb_strimwidth($h['observacao_admin'] ?? '', 0, 40, '...')) ?></td>
                            <td><a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($h['agendamento_id'] ?? '') ?>" class="btn btn-sm btn-secondary">Ver</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function toggleHistorico() {
    const body = document.getElementById('historico-body');
    const icone = document.getElementById('icone-historico');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        icone.className = 'fas fa-chevron-up';
    } else {
        body.style.display = 'none';
        icone.className = 'fas fa-chevron-down';
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
