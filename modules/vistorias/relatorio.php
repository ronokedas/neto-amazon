<?php
/**
 * MODULO: VISTORIAS (EXPANSAO)
 * Arquivo: relatorio.php - Formulario de relatorio tecnico com
 *           tabela dinamica de exigencias, vinculado ao agendamento.
 * ACESSO: ?agendamento_id=UUID — ADMIN e VISTORIADOR
 * REGRA: Ao salvar, avanca status da OS para "Executado"
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$usuario_id = $_SESSION['usuario_id'];
$agendamento_id = $_GET['agendamento_id'] ?? '';

if (empty($agendamento_id)) {
    setMensagem('error', 'ID do agendamento nao informado.');
    redirecionar(APP_URL . 'agendamentos');
}

// ============================================
// BUSCAR DADOS DO AGENDAMENTO + CLIENTE + EMBARCACAO + OS
// ============================================
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
               c.telefone AS cliente_telefone, c.email AS cliente_email,
               e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
               e.tipo_embarcacao, e.tipo, e.ano AS embarcacao_ano,
               e.comprimento_total, e.boca_moldada, e.pontal_moldado,
               e.material_casco, e.arqueacao_bruta, e.possui_propulsao,
               e.numero_passageiros_n1, e.numero_passageiros_n2,
               u.nome AS vistoriador_nome,
               arm.nome AS armador_nome,
               a.operador_nome AS agendamento_operador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status
        FROM agendamentos a
        INNER JOIN clientes c     ON a.cliente_id = c.id
        INNER JOIN embarcacoes e  ON a.embarcacao_id = e.id
        LEFT  JOIN usuarios u     ON a.vistoriador_id = u.id
        LEFT  JOIN clientes arm   ON a.armador_id = arm.id
        LEFT  JOIN ordens_servico os ON os.agendamento_id = a.id
        WHERE a.id = :id
    ");
    $stmt->execute([':id' => $agendamento_id]);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ag) {
        setMensagem('error', 'Agendamento nao encontrado.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // VISTORIADOR so pode ver relatorio dos proprios agendamentos
    if ($cargo === 'VISTORIADOR' && $ag['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Este agendamento nao esta atribuido a voce.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // Se estiver aprovada, vistoriador não pode mais editar
    $stmtV_check = $pdo->prepare("SELECT status FROM vistorias WHERE agendamento_id = :id LIMIT 1");
    $stmtV_check->execute([':id' => $agendamento_id]);
    $vistoria_check = $stmtV_check->fetch(PDO::FETCH_ASSOC);
    if ($vistoria_check && in_array($vistoria_check['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS']) && $cargo === 'VISTORIADOR') {
        setMensagem('error', 'Este relatório já foi aprovado e não pode mais ser modificado.');
        redirecionar(APP_URL . 'agendamentos');
    }

} catch (Exception $e) {
    error_log('Erro ao carregar agendamento relatorio: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar dados do agendamento.');
    redirecionar(APP_URL . 'agendamentos');
}

// ============================================
// VERIFICAR SE JA EXISTE UMA VISTORIA VINCULADA
// ============================================
$vistoria = null;
$exigencias_avulsas = [];
$checklist_respostas = [];
$prazo_padrao_exigencias = '';

try {
    $stmtV = $pdo->prepare("SELECT * FROM vistorias WHERE agendamento_id = :agendamento_id LIMIT 1");
    $stmtV->execute([':agendamento_id' => $agendamento_id]);
    $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

    if ($vistoria) {
        // Carregar exigencias da vistoria (Avulsas são as que não tem catalogo_id OU tratadas diferente,
        // mas para manter compatibilidade, vamos tratar itens manuais como avulsos e itens do catalogo pelo checklist)
        $stmtE = $pdo->prepare("SELECT * FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id AND (catalogo_id IS NULL OR catalogo_id = '') ORDER BY ordem ASC");
        $stmtE->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias_avulsas = $stmtE->fetchAll(PDO::FETCH_ASSOC);

        // Carregar respostas do checklist
        $stmtResp = $pdo->prepare("SELECT * FROM vistoria_checklist_respostas WHERE vistoria_id = :v");
        $stmtResp->execute([':v' => $vistoria['id']]);
        while ($r = $stmtResp->fetch(PDO::FETCH_ASSOC)) {
            $checklist_respostas[$r['catalogo_id']] = $r;
            if (empty($prazo_padrao_exigencias) && !empty($r['vencimento'])) {
                $prazo_padrao_exigencias = $r['vencimento'];
            }
        }
    }
} catch (Exception $e) {
    error_log('Erro ao buscar vistoria: ' . $e->getMessage());
}

$editando = !empty($vistoria);
$admin_review_mode = ($cargo === 'ADMIN' && $editando);
$exigencias_relatorio = [];
$total_exigencias_relatorio = 0;
$total_nao_conformes_relatorio = 0;
$armador_relatorio_nome = '';

if ($admin_review_mode) {
    try {
        if (!empty($vistoria['armador_id'])) {
            $stmtArmadorReview = $pdo->prepare("SELECT nome FROM clientes WHERE id = :id LIMIT 1");
            $stmtArmadorReview->execute([':id' => $vistoria['armador_id']]);
            $armador_relatorio_nome = (string)($stmtArmadorReview->fetchColumn() ?: '');
        }

        $stmtReviewEx = $pdo->prepare("
            SELECT ve.*, ec.descricao AS catalogo_descricao, ec.item_normam AS catalogo_item_normam
            FROM vistoria_exigencias ve
            LEFT JOIN exigencias_catalogo ec ON ve.catalogo_id = ec.id
            WHERE ve.vistoria_id = :vistoria_id
            ORDER BY ve.ordem ASC
        ");
        $stmtReviewEx->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias_relatorio = $stmtReviewEx->fetchAll(PDO::FETCH_ASSOC);
        $total_exigencias_relatorio = count($exigencias_relatorio);
        foreach ($exigencias_relatorio as $exReview) {
            if (($exReview['conforme'] ?? '') === 'nao') {
                $total_nao_conformes_relatorio++;
            }
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar revisao admin do relatorio: ' . $e->getMessage());
        $exigencias_relatorio = [];
    }
}

// --- DETERMINAR ETAPA ATUAL ---
$status_vistoria = $vistoria['status'] ?? 'PENDENTE';
$pode_ir_etapa2 = in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS']);
$etapa_atual = 1;
if ($pode_ir_etapa2) $etapa_atual = 2;

// Se ainda nao tem exigencias avulsas, inicializa vazia (sem a primeira linha em branco se possível, ou controlada via JS)
$relatorio_anterior_id = $vistoria['relatorio_anterior_id'] ?? '';

// ============================================
// CLASSIFICAÇ?O DA EMBARCAÇ?O E CHECKLIST
// ============================================
function determinarCategoriaEmbarcacao($emb) {
    $ab = (float)str_replace(',', '.', $emb['arqueacao_bruta'] ?? '0');
    $prop = (bool)$emb['possui_propulsao'];
    $pass1 = (int)($emb['numero_passageiros_n1'] ?? 0);
    $pass2 = (int)($emb['numero_passageiros_n2'] ?? 0);
    $passageiros = ($pass1 + $pass2) > 0;

    $tipo = strtolower($emb['tipo_embarcacao'] ?? '');
    $tipo_str = strtolower($emb['tipo'] ?? '');
    $flutuante = (strpos($tipo, 'flutuante') !== false || strpos($tipo_str, 'flutuante') !== false);

    if ($prop && $ab >= 500) return 'd';
    if (!$prop && $ab >= 500) return 'e';
    if ($flutuante) {
        if (($passageiros && $ab >= 50 && $ab < 500) || ($ab >= 100 && $ab < 500)) return 'c';
    }
    if ($prop) {
        if ($passageiros && $ab >= 20 && $ab < 500) return 'a';
        if (!$passageiros && $ab >= 50 && $ab < 500) return 'a';
    }
    if (!$prop && $ab >= 50 && $ab < 500) return 'b';
    return 'f';
}

$categoria_embarcacao = determinarCategoriaEmbarcacao($ag);
$coluna_aplicabilidade = "aplicabilidade_" . $categoria_embarcacao;

$blocos_vistoria_todos = [
    'seco' => 'Vistoria em Seco',
    'flutuando' => 'Vistoria Flutuando',
    'borda_livre' => 'Vistoria de Borda Livre',
    'arqueacao' => 'Vistoria de Arqueação',
];

function normalizarTextoVistoria(string $texto): string
{
    $texto = mb_strtolower($texto, 'UTF-8');
    return strtr($texto, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a',
        'é' => 'e', 'ê' => 'e', 'í' => 'i',
        'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ç' => 'c',
    ]);
}

function blocosDisponiveisPorTipoVistoria(string $tipoVistoria, array $todos): array
{
    $texto = normalizarTextoVistoria($tipoVistoria);
    $blocos = [];

    if (strpos($texto, 'seco') !== false) {
        $blocos['seco'] = $todos['seco'];
    }
    if (strpos($texto, 'flutu') !== false || strpos($texto, 'agua') !== false || strpos($texto, 'licenca provisoria') !== false) {
        $blocos['flutuando'] = $todos['flutuando'];
    }
    if (strpos($texto, 'borda') !== false || strpos($texto, 'cnbl') !== false) {
        $blocos['borda_livre'] = $todos['borda_livre'];
    }
    if (strpos($texto, 'arquea') !== false || strpos($texto, 'cnarq') !== false) {
        $blocos['arqueacao'] = $todos['arqueacao'];
    }

    return !empty($blocos) ? $blocos : $todos;
}

$blocos_vistoria_disponiveis = blocosDisponiveisPorTipoVistoria((string)($ag['tipo_vistoria'] ?? ''), $blocos_vistoria_todos);
$bloco_vistoria_padrao = array_key_first($blocos_vistoria_disponiveis) ?: 'flutuando';

$checklist_categorias = [];
try {
    $stmtCat = $pdo->query("SELECT * FROM exigencias_categorias ORDER BY nome ASC");
    $categorias_bd = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

    $blocosFiltro = array_keys($blocos_vistoria_disponiveis);
    $placeholdersBlocos = implode(',', array_fill(0, count($blocosFiltro), '?'));
    $stmtItens = $pdo->prepare("
        SELECT *
        FROM exigencias_catalogo
        WHERE ativo = 1
          AND {$coluna_aplicabilidade} = 1
          AND bloco_vistoria IN ({$placeholdersBlocos})
        ORDER BY codigo_interno ASC
    ");
    $stmtItens->execute($blocosFiltro);
    $itens_bd = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

    foreach ($categorias_bd as $c) {
        $c['itens'] = [];
        $checklist_categorias[$c['id']] = $c;
    }
    foreach ($itens_bd as $it) {
        if (isset($checklist_categorias[$it['categoria_id']])) {
            $checklist_categorias[$it['categoria_id']]['itens'][] = $it;
        }
    }

    // Remove categorias vazias
    foreach ($checklist_categorias as $k => $c) {
        if (empty($c['itens'])) {
            unset($checklist_categorias[$k]);
        }
    }
} catch (Exception $e) {
    error_log('Erro ao carregar catalogo: ' . $e->getMessage());
}

$titulo_page = 'Relatório Técnico - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal flow-shell">
<div class="flow-hero">
    <div>
        <span class="flow-eyebrow"><i class="fas fa-route"></i> Etapa 3 do fluxo</span>
        <h1><i class="fas fa-clipboard-list"></i> Relatório técnico de vistoria</h1>
        <p>Registre a vistoria, marque conformidades, detalhe exigências e envie o relatório para aprovação administrativa.</p>
    </div>
    <div class="flow-actions">
        <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>
</div>

<div class="flow-track">
    <div class="flow-track-step"><span>01</span>Proposta</div>
    <div class="flow-track-step"><span>02</span>Agendamento</div>
    <div class="flow-track-step is-active"><span>03</span>Vistoria</div>
    <div class="flow-track-step"><span>04</span>Aprovação</div>
    <div class="flow-track-step"><span>05</span>Certificados</div>
</div>

<!-- BARRA DE ETAPAS -->
<div class="etapas-fluxo mb-4" style="display: flex; align-items: center; padding: 20px 0;">
    <div class="etapa <?= $etapa_atual >= 1 ? 'ativa' : '' ?>">
        <span class="etapa-numero">1</span>
        <span class="etapa-label">Relatório</span>
    </div>
    <div class="etapa-linha <?= $pode_ir_etapa2 ? 'completa' : '' ?>" style="flex: 1; height: 3px; background: #444; margin: 0 8px; margin-bottom: 20px;"></div>
    <div class="etapa <?= $pode_ir_etapa2 ? 'ativa' : 'bloqueada' ?>">
        <span class="etapa-numero">2</span>
        <span class="etapa-label">Certificado</span>
    </div>
</div>

<style>
.etapa { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.etapa-numero { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
.etapa.ativa .etapa-numero { background: #2ECC71; color: #000; }
.etapa.bloqueada .etapa-numero { background: #444; color: #888; }
.etapa-label { font-size: 12px; color: #ccc; }
.etapa-linha.completa { background: #2ECC71 !important; }

/* Checklist UI */
.checklist-section { margin-bottom: 15px; border: 1px solid var(--cor-borda, #444); border-radius: 6px; overflow: hidden; }
.checklist-header { background: var(--cor-sidebar, #1a1a2e); padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; font-weight: bold; }
.checklist-header:hover { background: #2a2a3e; }
.checklist-body { padding: 0; display: none; background: var(--cor-fundo, #121212); }
.checklist-item { padding: 12px 15px; border-top: 1px solid var(--cor-borda, #444); }
.checklist-item:first-child { border-top: none; }
.item-text { margin-bottom: 8px; font-size: 0.95rem; }
.item-normam { font-size: 0.8rem; color: #aaa; margin-bottom: 10px; display: block; }
.item-actions { display: flex; gap: 10px; flex-wrap: wrap; }

.btn-toggle { flex: 1; padding: 8px 12px; border: 1px solid var(--cor-borda, #444); background: #2a2a3e; color: #ccc; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; }
.btn-toggle:hover { background: #3a3a4e; }
.btn-toggle.active.conforme { background: #2ECC71; color: #000; border-color: #2ECC71; }
.btn-toggle.active.nao-conforme { background: #E74C3C; color: #fff; border-color: #E74C3C; }
.btn-toggle.active.na { background: #95a5a6; color: #fff; border-color: #95a5a6; }

.item-details { margin-top: 15px; padding: 15px; background: rgba(0,0,0,0.2); border-left: 3px solid #E74C3C; border-radius: 0 4px 4px 0; }
.item-details label { display: block; margin-bottom: 5px; font-size: 0.85rem; color: #aaa; }
.item-details input { width: 100%; padding: 8px 10px; margin-bottom: 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd); }
.admin-review-grid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr); gap: 18px; padding: 20px; }
.admin-review-panel { border: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); border-radius: 10px; background: rgba(255,255,255,0.025); overflow: hidden; }
.admin-review-panel h4 { margin: 0; padding: 14px 16px; border-bottom: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); color: var(--cor-texto); font-size: 1rem; }
.admin-review-body { padding: 16px; }
.admin-review-kpis { display: grid; grid-template-columns: repeat(auto-fit, minmax(145px, 1fr)); gap: 10px; margin-bottom: 16px; }
.admin-review-kpi { padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.18); border: 1px solid rgba(255,255,255,0.06); }
.admin-review-kpi small { display: block; color: var(--cor-texto-secundario, #aaa); margin-bottom: 4px; }
.admin-review-kpi strong { color: var(--cor-texto); font-size: 1.05rem; }
.admin-review-text { white-space: pre-wrap; line-height: 1.55; color: var(--cor-texto); background: rgba(0,0,0,0.16); border-radius: 8px; padding: 14px; min-height: 68px; }
.admin-review-exigencias { display: grid; gap: 10px; }
.admin-review-exigencia { padding: 12px; border-radius: 8px; background: rgba(0,0,0,0.14); border: 1px solid rgba(255,255,255,0.06); }
.admin-review-exigencia strong { display: block; margin-bottom: 6px; color: var(--cor-texto); }
.admin-review-exigencia small { display: block; color: var(--cor-texto-secundario, #aaa); }
.admin-review-pdf { width: 100%; min-height: 760px; border: 1px solid var(--cor-borda, rgba(255,255,255,0.08)); border-radius: 8px; background: #111; }
.admin-decision-card { border-color: rgba(243, 156, 18, 0.55); background: linear-gradient(135deg, rgba(243,156,18,0.12), rgba(255,255,255,0.02)); }
@media (max-width: 980px) {
    .admin-review-grid { grid-template-columns: 1fr; }
    .admin-review-pdf { min-height: 520px; }
}
</style>

<!-- BOT?O ETAPA 2 (somente ADMIN, somente quando aprovado) -->
<?php if (getCargo() === 'ADMIN' && $pode_ir_etapa2): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <strong>Relatório aprovado.</strong> Você pode gerar os certificados agora.
        <a href="<?= APP_URL ?>documentacao/novo_certificado?agendamento_id=<?= urlencode($agendamento_id) ?>"
           class="btn btn-success ms-3">
            <i class="fas fa-certificate"></i> Ir para Etapa 2 — Gerar Certificado
        </a>
    </div>
<?php endif; ?>
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-clipboard-list"></i>
                Relatório Técnico de Vistoria
            </h3>
            <span class="help-text">Checklist, exigências e resultado final</span>
        </div>

        <!-- ===== DADOS DO AGENDAMENTO ===== -->
        <div style="padding: 20px; border-bottom: 1px solid var(--cor-borda, rgba(255,255,255,0.08));">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                <div>
                    <small class="text-muted"><i class="fas fa-file-invoice"></i> OS</small>
                    <div style="font-weight: 600;"><?php echo $ag['os_numero'] ? h($ag['os_numero']) : '<em class="text-muted">Pendente</em>'; ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-calendar-day"></i> Data da Vistoria</small>
                    <div style="font-weight: 600;"><?php echo formatarData($ag['data_vistoria']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-user-check"></i> Vistoriador</small>
                    <div style="font-weight: 600;"><?php echo h($ag['vistoriador_nome'] ?? 'Não atribuído'); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-ship"></i> Categoria Normam</small>
                    <div><span class="badge bg-info">Tipo <?php echo strtoupper($categoria_embarcacao); ?></span></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-top: 12px;">
                <div>
                    <small class="text-muted"><i class="fas fa-user-tie"></i> Cliente</small>
                    <div style="font-weight: 600;"><?php echo h($ag['cliente_nome']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-ship"></i> Embarcação</small>
                    <div style="font-weight: 600;"><?php echo h($ag['embarcacao_nome']); ?> <?php echo $ag['embarcacao_registro'] ? '(' . h($ag['embarcacao_registro']) . ')' : ''; ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-clipboard-check"></i> Tipo de Vistoria</small>
                    <div><?php echo h($ag['tipo_vistoria']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Status Agendamento</small>
                    <div><?php echo h($ag['status']); ?></div>
                </div>
            </div>
        </div>

        <?php if ($admin_review_mode): ?>
            <?php
            $responsavelVistoria = trim((string)($vistoria['operador_nome'] ?? ''));
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $armador_relatorio_nome ?: ($ag['armador_nome'] ?? '');
            }
            if ($responsavelVistoria === '') {
                $responsavelVistoria = $ag['cliente_nome'] ?? 'Nao informado';
            }
            $statusLabelsAdmin = [
                'PENDENTE' => 'Pendente',
                'AGUARDANDO_APROVACAO' => 'Aguardando aprovacao',
                'APROVADA' => 'Aprovada',
                'APROVADA_COM_EXIGENCIAS' => 'Aprovada com exigencias',
                'REPROVADA' => 'Reprovada',
                'CANCELADA' => 'Cancelada',
            ];
            ?>
            <div class="admin-review-grid">
                <div class="admin-review-panel">
                    <h4><i class="fas fa-file-signature"></i> Revisao do relatorio enviado</h4>
                    <div class="admin-review-body">
                        <div class="admin-review-kpis">
                            <div class="admin-review-kpi">
                                <small>Numero do relatorio</small>
                                <strong><?= h($vistoria['numero'] ?? 'S/N') ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Data da vistoria</small>
                                <strong><?= !empty($vistoria['data_vistoria']) ? formatarData($vistoria['data_vistoria']) : '-' ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Responsavel informado</small>
                                <strong><?= h($responsavelVistoria) ?></strong>
                            </div>
                            <div class="admin-review-kpi">
                                <small>Status atual</small>
                                <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>
                            </div>
                        </div>

                        <div style="margin-bottom: 16px;">
                            <h5 style="margin: 0 0 8px; color: var(--cor-texto);">Observacoes tecnicas do vistoriador</h5>
                            <div class="admin-review-text"><?= h($vistoria['observacoes_tecnicas'] ?: 'Nenhuma observacao tecnica informada.') ?></div>
                        </div>

                        <div>
                            <h5 style="margin: 0 0 8px; color: var(--cor-texto);">Relatorio original gerado pelo vistoriador</h5>
                            <a href="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>" target="_blank" class="btn btn-info" style="color:#fff; margin-bottom: 12px;">
                                <i class="fas fa-up-right-from-square"></i> Abrir PDF completo
                            </a>
                            <iframe class="admin-review-pdf" src="<?= APP_URL ?>vistorias/relatorio_pdf.php?id=<?= urlencode($vistoria['id']); ?>"></iframe>
                        </div>
                    </div>
                </div>

                <div style="display: grid; gap: 18px; align-content: start;">
                    <div class="admin-review-panel admin-decision-card">
                        <h4><i class="fas fa-gavel"></i> Resultado final da vistoria</h4>
                        <div class="admin-review-body">
                            <?php if (($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
                                <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=aprovar_ou_reprovar" id="formDecisaoAdmin">
                                    <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
                                    <input type="hidden" name="id" value="<?= h($vistoria['id']); ?>">
                                    <div class="form-group mb-3">
                                        <label for="status_vistoria_admin">Resultado Final da Vistoria *</label>
                                        <select id="status_vistoria_admin" name="status_vistoria" class="form-control" required>
                                            <option value="">Selecione o resultado...</option>
                                            <option value="PENDENTE" <?= ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : '' ?>>Pendente (relatorio em andamento)</option>
                                            <option value="AGUARDANDO_APROVACAO" <?= ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : '' ?>>Aguardando Aprovacao</option>
                                            <option value="APROVADA" <?= ($vistoria['status'] ?? '') === 'APROVADA' ? 'selected' : '' ?>>Aprovada</option>
                                            <option value="APROVADA_COM_EXIGENCIAS" <?= ($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS' ? 'selected' : '' ?>>Aprovada c/ Exigencias</option>
                                            <option value="REPROVADA" <?= ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : '' ?>>Reprovada</option>
                                            <option value="CANCELADA" <?= ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : '' ?>>Cancelada</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label for="observacao_admin">Observacao do admin</label>
                                        <textarea id="observacao_admin" name="observacao_admin" class="form-control" rows="4" placeholder="Obrigatoria para reprovar. Opcional para aprovar."></textarea>
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-check"></i> Confirmar decisao
                                        </button>
                                        <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                            Voltar para aprovacoes
                                        </a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="admin-review-text">
                                    Este relatorio ja foi finalizado como <strong><?= h($statusLabelsAdmin[$vistoria['status'] ?? ''] ?? ($vistoria['status'] ?? '-')) ?></strong>.
                                    <?php if (!empty($vistoria['observacao_admin'])): ?>
                                        <br><br>Observacao do admin: <?= h($vistoria['observacao_admin']) ?>
                                    <?php endif; ?>
                                </div>
                                <div style="margin-top: 12px;">
                                    <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn btn-secondary">
                                        Voltar para aprovacoes
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
        <?php else: ?>
        <!-- ===== FORMULARIO RELATORIO TECNICO ===== -->
        <form action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_relatorio" method="POST" class="form-padrao" id="formRelatorio">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="agendamento_id" value="<?php echo h($agendamento_id); ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="vistoria_id" value="<?php echo h($vistoria['id']); ?>">
            <?php endif; ?>

            <!-- ===== DATA DA VISTORIA E ARMADOR ===== -->
            <div style="padding: 20px 20px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label for="data_vistoria">
                        <i class="fas fa-calendar-check"></i> Data da Realização da Vistoria *
                    </label>
                    <input type="date" id="data_vistoria" name="data_vistoria" class="form-control"
                           value="<?php echo h($vistoria['data_vistoria'] ?? $ag['data_vistoria']); ?>" required
                           style="background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                </div>

                <div class="form-group">
                    <label for="armador_id">
                        <i class="fas fa-user-tie"></i> Armador na data da Vistoria (Operador)
                    </label>
                    <select id="armador_id" name="armador_id" class="form-control" style="background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                        <option value="" style="background: #2a2a3e; color: #ddd;">-- Nenhum Armador Específico --</option>
                        <?php
                        try {
                            $stmtArm = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE perfil = 'armador' AND status = 'ATIVO' ORDER BY nome ASC");
                            while ($a = $stmtArm->fetch(PDO::FETCH_ASSOC)) {
                                $armadorAtualId = $vistoria['armador_id'] ?? $ag['armador_id'] ?? '';
                                $selected = ($armadorAtualId === $a['id']) ? 'selected' : '';
                                echo "<option value='".h($a['id'])."' $selected style='background: #2a2a3e; color: #ddd;'>".h($a['nome'])." (".h($a['cpf_cnpj']).")</option>";
                            }
                        } catch (Exception $e) {
                            error_log('Erro ao carregar armadores: ' . $e->getMessage());
                        }
                        ?>
                    </select>
                    <small class="text-muted" style="display:block; margin-top: 6px;">
                        Se o responsável presente for funcionário ou outra pessoa, digite o nome abaixo.
                    </small>
                    <input type="text"
                           id="operador_nome"
                           name="operador_nome"
                           class="form-control"
                           value="<?php echo h($vistoria['operador_nome'] ?? $ag['agendamento_operador_nome'] ?? ''); ?>"
                           placeholder="Nome do operador/responsável presente na vistoria"
                           style="margin-top: 8px; background: var(--cor-input-bg, #2a2a3e); color: var(--cor-texto, #ddd); border: 1px solid var(--cor-borda, #444);">
                </div>
            </div>

            <!-- ===== CHECKLIST DINAMICO ===== -->
            <div style="padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 1.1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-clipboard-check"></i> Checklist de Vistoria
                </h4>

                <div style="margin-bottom: 20px;">
                    <input type="text" id="buscaChecklist" class="form-control" placeholder="Buscar exigência pelo texto (filtra todas as seções)..." style="background: var(--cor-input-bg); color: var(--cor-texto); border: 1px solid var(--cor-borda); font-size: 1rem; padding: 12px;">
                </div>

                <div style="margin-bottom: 20px; padding: 14px; border: 1px solid var(--cor-borda, #444); border-radius: 6px; background: var(--cor-sidebar, #1a1a2e);">
                    <label for="prazo_padrao_exigencias" style="display:block; margin-bottom: 6px; font-weight: 600;">
                        Data de Vencimento (opcional)
                    </label>
                    <input type="date"
                           id="prazo_padrao_exigencias"
                           name="prazo_padrao_exigencias"
                           value="<?= h($prazo_padrao_exigencias) ?>"
                           style="max-width: 240px; padding: 8px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                    <small style="display:block; margin-top: 6px; color: var(--cor-texto-secundario, #aaa);">
                        Esta data será aplicada a todas as exigências não conformes, exceto as marcadas como AS / sem prazo.
                    </small>
                </div>

                <div id="checklist-container">
                    <?php foreach ($checklist_categorias as $cat): ?>
                    <div class="checklist-section" data-cat="<?= $cat['id'] ?>">
                        <div class="checklist-header" onclick="toggleSection('cat_<?= $cat['id'] ?>')">
                            <span><?= h($cat['nome']) ?> <span style="color:#aaa; font-weight:normal;">(<?= count($cat['itens']) ?> itens)</span></span>
                            <i class="fas fa-chevron-down icone-toggle"></i>
                        </div>
                        <div class="checklist-body" id="cat_<?= $cat['id'] ?>">
                            <?php foreach ($cat['itens'] as $item):
                                $resp = $checklist_respostas[$item['id']] ?? null;
                                $status = $resp['status'] ?? '';
                                $obs = $resp['observacao'] ?? '';
                                $venc = $resp['vencimento'] ?? '';
                                $semPrazo = ($status === 'NAO_CONFORME' && empty($venc));
                            ?>
                            <div class="checklist-item" data-id="<?= $item['id'] ?>" data-text="<?= htmlspecialchars(strtolower($item['descricao'] . ' ' . $item['item_normam'])) ?>">
                                <div class="item-text"><?= h($item['descricao']) ?></div>
                                <?php if($item['item_normam']): ?>
                                    <span class="item-normam">Normam: <?= h($item['item_normam']) ?></span>
                                <?php endif; ?>

                                <div class="item-actions">
                                    <button type="button" class="btn-toggle conforme <?= $status === 'CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'CONFORME', this)">CONFORME</button>
                                    <button type="button" class="btn-toggle nao-conforme <?= $status === 'NAO_CONFORME' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_CONFORME', this)">NÃO CONFORME</button>
                                    <button type="button" class="btn-toggle na <?= $status === 'NAO_SE_APLICA' ? 'active' : '' ?>" onclick="setStatus('<?= $item['id'] ?>', 'NAO_SE_APLICA', this)">N/A</button>
                                </div>

                                <input type="hidden" name="checklist_id[]" value="<?= $item['id'] ?>">
                                <input type="hidden" name="checklist_status[]" id="status_<?= $item['id'] ?>" value="<?= h($status) ?>">

                                <div class="item-details" id="details_<?= $item['id'] ?>" style="display: <?= $status === 'NAO_CONFORME' ? 'block' : 'none' ?>;">
                                    <label>Referência da NORMAM (Sobrescreve o padrão do catálogo)</label>
                                    <input type="text" name="checklist_item_normam[]" id="normam_<?= $item['id'] ?>" value="<?= h($resp['item_normam'] ?? $item['item_normam'] ?? '') ?>" placeholder="Ex: NORMAM-202/DPC, Cap. 02, Item 2.1.">

                                    <label>Observação curta (vai para o relatório)</label>
                                    <input type="text" name="checklist_observacao[]" id="obs_<?= $item['id'] ?>" value="<?= h($obs) ?>" placeholder="Especifique o problema encontrado...">

                                    <input type="hidden" name="checklist_sem_prazo[]" id="sem_prazo_<?= $item['id'] ?>" value="<?= $semPrazo ? '1' : '0' ?>">
                                    <label style="display:flex; align-items:center; gap:8px; margin-top: 10px;">
                                        <input type="checkbox"
                                               class="checklist-sem-prazo"
                                               data-target="sem_prazo_<?= $item['id'] ?>"
                                               <?= $semPrazo ? 'checked' : '' ?>>
                                        AS / sem prazo
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ===== EXIGÊNCIAS AVULSAS ===== -->
            <div style="padding: 20px;">
                <h4 style="margin: 0 0 15px 0; font-size: 1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-plus-circle"></i> Exigências Avulsas (Fora do Catálogo)
                </h4>
                <small class="text-muted">Adicione itens pendentes que não constam no checklist acima.</small>

                <div style="margin: 15px 0;" class="no-print">
                    <button type="button" class="btn btn-sm btn-primary" onclick="adicionarLinhaAvulsa()">
                        <i class="fas fa-plus"></i> Adicionar Item Avulso
                    </button>
                </div>

                <table id="tabelaExigenciasAvulsas" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--cor-sidebar, #1a1a2e); border-bottom: 2px solid var(--cor-borda);">
                            <th style="width: 40px; text-align: center; padding: 8px 6px;">#</th>
                            <th style="width: 170px; text-align: left; padding: 8px 6px;">Tipo de Vistoria</th>
                            <th style="text-align: left; padding: 8px 6px;">Descrição da Exigência *</th>
                            <th style="text-align: left; padding: 8px 6px;">Item da NORMAM</th>
                            <th style="width: 120px; text-align: center; padding: 8px 6px;">Situação</th>
                            <th style="text-align: left; padding: 8px 6px;">Observacao / Justificativa</th>
                            <th style="width: 100px; text-align: center; padding: 8px 6px;">AS / Sem Prazo</th>
                            <th style="width: 40px; text-align: center; padding: 8px 6px;" class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exigencias_avulsas as $idx => $ex): ?>
                        <tr class="linha-exigencia-avulsa">
                            <td style="text-align: center; padding: 6px;">
                                <span class="ordem-num-avulsa"><?php echo (int)$ex['ordem']; ?></span>
                                <input type="hidden" name="exigencia_id[]" value="<?php echo h($ex['id'] ?? ''); ?>">
                                <input type="hidden" name="exigencia_ordem[]" value="<?php echo (int)$ex['ordem']; ?>" class="ordem-input-avulsa">
                            </td>
                            <td style="padding: 6px;">
                                <?php $blocoAtual = $ex['bloco_vistoria'] ?? $bloco_vistoria_padrao; ?>
                                <select name="exigencia_bloco[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <?php foreach ($blocos_vistoria_disponiveis as $valorBloco => $rotuloBloco): ?>
                                        <option value="<?php echo h($valorBloco); ?>" <?php echo $blocoAtual === $valorBloco ? 'selected' : ''; ?>>
                                            <?php echo h($rotuloBloco); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_descricao[]" value="<?php echo h($ex['descricao'] ?? ''); ?>"
                                       placeholder="Ex.: nao tem seguranca" required
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_item[]" value="<?php echo h($ex['item']); ?>"
                                       placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <select name="status_item[]"
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                                    <option value="inserida" <?php echo ($ex['status_item'] ?? 'inserida') === 'inserida' ? 'selected' : ''; ?>>Inserida / N/A</option>
                                    <option value="pendente" <?php echo ($ex['status_item'] ?? '') === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                                    <option value="cumprida" <?php echo ($ex['status_item'] ?? '') === 'cumprida' ? 'selected' : ''; ?>>Cumprida</option>
                                </select>
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_observacao[]" value="<?php echo h($ex['observacao'] ?? ''); ?>"
                                       placeholder="Observacao"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <?php $avulsaSemPrazo = empty($ex['vencimento']); ?>
                                <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="<?php echo $avulsaSemPrazo ? '1' : '0'; ?>">
                                <label style="display:inline-flex; align-items:center; gap:6px;">
                                    <input type="checkbox" class="avulsa-sem-prazo-check" <?php echo $avulsaSemPrazo ? 'checked' : ''; ?>>
                                    AS
                                </label>
                            </td>
                            <td style="text-align: center; padding: 6px;" class="no-print">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== OBSERVACOES TECNICAS ===== -->
            <div style="padding: 0 20px 20px;">
                <div class="form-group">
                    <label for="observacoes_tecnicas">
                        <i class="fas fa-sticky-note"></i> Observações Técnicas
                    </label>
                    <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"
                              placeholder="Observações técnicas gerais, recomendações, restrições encontradas..."
                              style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); resize: vertical;"><?php echo h($vistoria['observacoes_tecnicas'] ?? ''); ?></textarea>
                </div>

                <!-- Status da vistoria (resultado final) -->
                <div class="form-group" style="margin-top: 15px;">
                    <label for="status_vistoria">
                        <i class="fas fa-gavel"></i> Resultado Final da Vistoria *
                    </label>
                    <select id="status_vistoria" name="status_vistoria" required
                            style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); font-size: 1rem;">
                        <option value="PENDENTE" <?php echo ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>Pendente (relatório em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO" <?php echo ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <?php if (getCargo() === 'ADMIN'): ?>
                        <option value="APROVADA" <?php echo ($vistoria['status'] ?? '') === 'APROVADA' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="APROVADA_COM_EXIGENCIAS" <?php echo ($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS' ? 'selected' : ''; ?>>Aprovada c/ Exigências</option>
                        <option value="REPROVADA" <?php echo ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                        <option value="CANCELADA" <?php echo ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- ===== BOTOES ===== -->
            <div class="form-actions" style="padding: 0 20px 20px; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i class="fas fa-save"></i>
                    <?php echo $editando ? 'Atualizar Relatorio' : 'Salvar Relatorio'; ?>
                </button>
                <?php if ($editando && !empty($vistoria['id'])): ?>
                    <a href="<?php echo APP_URL; ?>vistorias/relatorio_pdf.php?id=<?php echo urlencode($vistoria['id']); ?>" target="_blank" class="btn btn-info" style="color: #fff;">
                        <i class="fas fa-file-pdf"></i> Visualizar Relatório
                    </a>
                <?php endif; ?>
                <?php if ($editando && $pode_ir_etapa2): ?>
                    <a href="<?php echo APP_URL; ?>documentacao/novo_certificado?agendamento_id=<?php echo urlencode($agendamento_id); ?>" class="btn btn-success">
                        <i class="fas fa-certificate"></i> Gerar Certificado
                    </a>
                <?php endif; ?>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <?php if ($editando): ?>
                    <span class="text-muted" style="margin-left: 15px; font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i>
                        Ao salvar com status <strong>Aprovada</strong> ou <strong>Reprovada</strong>,
                        a OS avanca para <strong>"Executada"</strong> automaticamente.
                    </span>
                <?php endif; ?>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php if (!$admin_review_mode): ?>
<script>
// Toggle Accordions
function toggleSection(id) {
    const body = document.getElementById(id);
    const icon = body.previousElementSibling.querySelector('.icone-toggle');
    if (body.style.display === 'block') {
        body.style.display = 'none';
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    } else {
        body.style.display = 'block';
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    }
}

// Checklist Item Status
function setStatus(itemId, status, btnElement) {
    // Atualiza input hidden
    document.getElementById('status_' + itemId).value = status;

    // Atualiza botoes
    const parent = btnElement.closest('.item-actions');
    parent.querySelectorAll('.btn-toggle').forEach(b => b.classList.remove('active'));
    btnElement.classList.add('active');

    // Mostra div de observação e vencimento se N?O CONFORME
    const detailsDiv = document.getElementById('details_' + itemId);
    if (status === 'NAO_CONFORME') {
        detailsDiv.style.display = 'block';
        // Foca no campo observação se acabou de abrir
        const obsInput = detailsDiv.querySelector('input[name="checklist_observacao[]"]');
        if(obsInput) obsInput.focus();
    } else {
        detailsDiv.style.display = 'none';
        // Limpa campos para não enviar dados lixo caso mude de ideia
        document.getElementById('obs_' + itemId).value = '';
        const semPrazoInput = document.getElementById('sem_prazo_' + itemId);
        if (semPrazoInput) semPrazoInput.value = '0';
        const semPrazoCheck = detailsDiv.querySelector('.checklist-sem-prazo');
        if (semPrazoCheck) semPrazoCheck.checked = false;
    }
}

document.querySelectorAll('.checklist-sem-prazo').forEach(function(checkbox) {
    checkbox.addEventListener('change', function() {
        const target = document.getElementById(this.dataset.target);
        if (target) target.value = this.checked ? '1' : '0';
    });
});

// Busca / Filtro do Checklist
document.getElementById('buscaChecklist').addEventListener('input', function() {
    const term = this.value.toLowerCase();
    const sections = document.querySelectorAll('.checklist-section');

    sections.forEach(section => {
        let hasVisible = false;
        const items = section.querySelectorAll('.checklist-item');

        items.forEach(item => {
            const text = item.getAttribute('data-text');
            if (term === '' || text.indexOf(term) > -1) {
                item.style.display = 'block';
                hasVisible = true;
            } else {
                item.style.display = 'none';
            }
        });

        if (hasVisible) {
            section.style.display = 'block';
            // Se está buscando algo, abre o accordion automaticamente
            if (term !== '') {
                const body = section.querySelector('.checklist-body');
                const icon = section.querySelector('.icone-toggle');
                body.style.display = 'block';
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        } else {
            section.style.display = 'none';
        }
    });
});

// Tabela Avulsa
let contadorLinhasAvulsa = <?php echo count($exigencias_avulsas); ?>;
const blocosVistoriaAvulsa = <?php echo json_encode($blocos_vistoria_disponiveis, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
const blocoVistoriaPadrao = <?php echo json_encode($bloco_vistoria_padrao); ?>;

function opcoesBlocoVistoriaAvulsa(valorSelecionado) {
    return Object.entries(blocosVistoriaAvulsa).map(function([valor, rotulo]) {
        const selected = valor === valorSelecionado ? ' selected' : '';
        return `<option value="${valor}"${selected}>${rotulo}</option>`;
    }).join('');
}

function adicionarLinhaAvulsa() {
    contadorLinhasAvulsa++;
    const tbody = document.querySelector('#tabelaExigenciasAvulsas tbody');
    const tr = document.createElement('tr');
    tr.className = 'linha-exigencia-avulsa';

    tr.innerHTML = `
        <td style="text-align: center; padding: 6px;">
            <span class="ordem-num-avulsa">${contadorLinhasAvulsa}</span>
            <input type="hidden" name="exigencia_id[]" value="">
            <input type="hidden" name="exigencia_ordem[]" value="${contadorLinhasAvulsa}" class="ordem-input-avulsa">
        </td>
        <td style="padding: 6px;">
            <select name="exigencia_bloco[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                ${opcoesBlocoVistoriaAvulsa(blocoVistoriaPadrao)}
            </select>
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_descricao[]" value=""
                   placeholder="Ex.: nao tem seguranca" required
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_item[]" value=""
                   placeholder="Ex.: NORMAM-202/DPC, Cap. 03"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px; text-align: center;">
            <select name="status_item[]"
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                <option value="pendente">Pendente</option>
                <option value="cumprida">Cumprida</option>
            </select>
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_observacao[]" value=""
                   placeholder="Observacao"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px; text-align: center;">
            <input type="hidden" name="exigencia_sem_prazo[]" class="avulsa-sem-prazo-input" value="1">
            <label style="display:inline-flex; align-items:center; gap:6px;">
                <input type="checkbox" class="avulsa-sem-prazo-check" checked>
                AS
            </label>
        </td>
        <td style="text-align: center; padding: 6px;" class="no-print">
            <button type="button" class="btn btn-danger btn-sm" onclick="removerLinhaAvulsa(this)" title="Remover">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    vincularSemPrazoAvulsa(tr);
    renumerarLinhasAvulsas();
}

function vincularSemPrazoAvulsa(contexto) {
    contexto.querySelectorAll('.avulsa-sem-prazo-check').forEach(function(checkbox) {
        checkbox.addEventListener('change', function() {
            const input = this.closest('td').querySelector('.avulsa-sem-prazo-input');
            if (input) input.value = this.checked ? '1' : '0';
        });
    });
}

document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr').forEach(vincularSemPrazoAvulsa);

function removerLinhaAvulsa(btn) {
    btn.closest('tr').remove();
    renumerarLinhasAvulsas();
}

function renumerarLinhasAvulsas() {
    const rows = document.querySelectorAll('#tabelaExigenciasAvulsas tbody tr.linha-exigencia-avulsa');
    rows.forEach((row, i) => {
        const num = i + 1;
        row.querySelector('.ordem-num-avulsa').textContent = num;
        row.querySelector('.ordem-input-avulsa').value = num;
    });
    contadorLinhasAvulsa = rows.length;
}

// Confirmacao ao salvar com status final
document.getElementById('formRelatorio').addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria').value;
    if (status === 'APROVADA' || status === 'REPROVADA') {
        const msg = status === 'APROVADA'
            ? 'Ao salvar como APROVADA, a Ordem de Servico sera marcada como EXECUTADA e os certificados serao liberados. Deseja continuar?'
            : 'Ao salvar como REPROVADA, a Ordem de Servico sera marcada como EXECUTADA. Deseja continuar?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});
</script>
<?php else: ?>
<script>
document.getElementById('formDecisaoAdmin')?.addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria_admin').value;
    const observacao = document.getElementById('observacao_admin').value.trim();

    if (!status) {
        e.preventDefault();
        alert('Selecione o resultado final da vistoria.');
        return;
    }

    if (status === 'REPROVADA' && !observacao) {
        e.preventDefault();
        alert('Informe uma observacao para reprovar o relatorio.');
        return;
    }

    const labels = {
        PENDENTE: 'Pendente',
        AGUARDANDO_APROVACAO: 'Aguardando Aprovacao',
        APROVADA: 'Aprovada',
        APROVADA_COM_EXIGENCIAS: 'Aprovada com Exigencias',
        REPROVADA: 'Reprovada',
        CANCELADA: 'Cancelada'
    };
    const texto = 'Confirmar resultado final como ' + (labels[status] || status) + '?';
    if (!confirm(texto)) {
        e.preventDefault();
    }
});
</script>
<?php endif; ?>

<?php if (!$admin_review_mode && getCargo() === 'ADMIN' && ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
<div class="card mt-4" style="border: 2px solid #f39c12; max-width: 950px; margin: 20px auto;">
    <div class="card-header" style="background:#f39c12;color:#000">
        <h4><i class="fas fa-gavel"></i> Decisão de Aprovação</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=aprovar_ou_reprovar">
            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
            <input type="hidden" name="id" value="<?= h($vistoria['id']); ?>">
            <div class="form-group mb-3">
                <label>Observação (obrigatória para reprovar, opcional para aprovar):</label>
                <textarea name="observacao_admin" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" name="decisao" value="aprovar" class="btn btn-success"
                onclick="return confirm('Confirmar APROVAÇ?O deste relatório?')">
                <i class="fas fa-check"></i> Aprovar Relatório
            </button>
            <button type="submit" name="decisao" value="reprovar" class="btn btn-danger ms-2"
                onclick="return confirm('Confirmar REPROVAÇ?O? A observação é obrigatória.')">
                <i class="fas fa-times"></i> Reprovar Relatório
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
