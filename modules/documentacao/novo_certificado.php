<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

$agendamento_id = $_GET['agendamento_id'] ?? '';

if (empty($agendamento_id)) {
    setMensagem('error', 'ID do agendamento não informado.');
    redirecionar(APP_URL . 'vistorias');
}

$stmt = $pdo->prepare("
    SELECT a.*, e.nome as emb_nome, e.registro, e.tipo_embarcacao, 
           e.arqueacao_bruta, e.indicativo_chamada, v.id as vistoria_id, v.status, v.numero as relatorio_numero
    FROM agendamentos a
    JOIN embarcacoes e ON a.embarcacao_id = e.id
    LEFT JOIN vistorias v ON v.agendamento_id = a.id
    WHERE a.id = :agendamento_id
    LIMIT 1
");
$stmt->execute([':agendamento_id' => $agendamento_id]);
$dados = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$dados) {
    setMensagem('error', 'Agendamento não encontrado.');
    redirecionar(APP_URL . 'agendamentos');
}

$status = $dados['status'] ?? '';
$pode_etapa2 = in_array($status, ['APROVADA', 'APROVADA_COM_EXIGENCIAS']);
if (!$pode_etapa2) {
    setMensagem('error', 'Este relatório não está aprovado para emissão de certificado.');
    redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
}

$titulo_page = 'Emitir Certificado - ' . APP_NAME;
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
            <span class="etapa-label" style="font-size:12px;color:#fff;font-weight:bold">Certificado</span>
        </div>
        <div class="etapa-linha" style="flex:1;height:3px;background:#444;margin:0 8px;margin-bottom:20px"></div>
        <div class="etapa">
            <span class="etapa-numero" style="background:#444;color:#888">3</span>
            <span class="etapa-label" style="font-size:12px;color:#ccc">Exigências</span>
        </div>
    </div>
    
    <div class="tabela-header">
        <h2><i class="fas fa-certificate"></i> Emitir Certificado</h2>
        <p class="text-muted">
            Embarcação: <strong><?= h($dados['emb_nome']) ?></strong> 
            · Registro: <?= h($dados['registro']) ?>
            · Relatório: <?= h($dados['relatorio_numero'] ?? 'S/N') ?>
            · Status: <?= h($status) ?>
        </p>
    </div>
    
    <div class="card mb-4">
        <div class="card-header"><h4>Selecione o Tipo de Certificado</h4></div>
        <div class="card-body">
            <div class="row g-3">
                <?php
                $tipos = [
                    'CSN'  => ['label' => 'Certificado de Segurança de Navegação', 'icone' => 'fa-ship',      'url' => APP_URL . 'documentacao/certificados/form?agendamento_id=' . urlencode($agendamento_id)],
                    'CNBL' => ['label' => 'Certificado Nacional de Borda Livre', 'icone' => 'fa-water',      'url' => APP_URL . 'documentacao/cnbl/form?agendamento_id=' . urlencode($agendamento_id)],
                    'CNARQ'=> ['label' => 'Certificado Nacional de Arqueação',   'icone' => 'fa-ruler',      'url' => APP_URL . 'documentacao/cnarq/form?agendamento_id=' . urlencode($agendamento_id)],
                    'LP'   => ['label' => 'Licença Provisória',                 'icone' => 'fa-file-alt',   'url' => APP_URL . 'documentacao/lp/form?agendamento_id=' . urlencode($agendamento_id)],
                    'LC'   => ['label' => 'Licença de Construção',             'icone' => 'fa-hard-hat',   'url' => APP_URL . 'documentacao/lc/form?agendamento_id=' . urlencode($agendamento_id)],
                    'CHT'  => ['label' => 'Cert. de Homologação Técnica',      'icone' => 'fa-check-double','url' => APP_URL . 'documentacao/cht/form?agendamento_id=' . urlencode($agendamento_id)],
                ];
                foreach ($tipos as $tipo => $info):
                ?>
                <div class="col-md-4">
                    <a href="<?= $info['url'] ?>" 
                       class="card text-center p-3 text-decoration-none"
                       style="border: 2px solid #444; display:block; border-radius:8px; background: var(--cor-card-bg, #1e1e2e);">
                        <i class="fas <?= $info['icone'] ?> fa-2x mb-2" style="color:#2ECC71"></i>
                        <strong><?= $tipo ?></strong>
                        <small class="d-block text-muted"><?= $info['label'] ?></small>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <a href="<?= APP_URL ?>vistorias/relatorio?agendamento_id=<?= urlencode($agendamento_id) ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Voltar ao Relatório
    </a>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
