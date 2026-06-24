<?php
/**
 * MÓDULO: COMERCIAL
 * Arquivo: index.php - Dashboard Comercial com indicadores e listagem de propostas
 * Acesso: ADMIN (completo) | VISTORIADOR (apenas visualização)
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

// ============================================
// FILTROS
// ============================================
$filtro_status   = $_GET['status'] ?? 'todos';
$filtro_busca    = trim($_GET['busca'] ?? '');
$filtro_data_ini = $_GET['data_ini'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

$where = [];
$params = [];

if ($filtro_status !== 'todos') {
    $where[] = 'p.status = :status';
    $params[':status'] = $filtro_status;
}

if (!empty($filtro_busca)) {
    $where[] = '(c.nome LIKE :busca OR p.numero LIKE :busca2)';
    $params[':busca']  = '%' . $filtro_busca . '%';
    $params[':busca2'] = '%' . $filtro_busca . '%';
}

if (!empty($filtro_data_ini)) {
    $where[] = 'p.data_emissao >= :data_ini';
    $params[':data_ini'] = $filtro_data_ini;
}

if (!empty($filtro_data_fim)) {
    $where[] = 'p.data_emissao <= :data_fim';
    $params[':data_fim'] = $filtro_data_fim;
}

$sqlWhere = '';
if (!empty($where)) {
    $sqlWhere = 'WHERE ' . implode(' AND ', $where);
}

// ============================================
// INDICADORES COMERCIAIS (APENAS ADMIN)
// ============================================
$indicadores = [];
if ($cargo === 'ADMIN') {
    // Mês atual
    $mesAtual = date('Y-m');

    try {
        // Total de propostas do mês
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM propostas WHERE DATE_FORMAT(created_at, '%Y-%m') = :mes");
        $stmt->execute([':mes' => $mesAtual]);
        $totalPropostasMes = (int)$stmt->fetchColumn();

        // Valor total do mês (propostas do mês corrente, independente de status)
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor_total), 0) FROM propostas WHERE DATE_FORMAT(created_at, '%Y-%m') = :mes");
        $stmt->execute([':mes' => $mesAtual]);
        $valorTotalMes = (float)$stmt->fetchColumn();

        // Meta mensal (buscar da tabela configuracoes)
        $metaMensal = 50000.00; // valor padrão
        try {
            $stmtMeta = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'meta_mensal'");
            $stmtMeta->execute();
            $metaValorDb = $stmtMeta->fetchColumn();
            if ($metaValorDb !== false) {
                $metaMensal = (float)$metaValorDb;
            }
        } catch (Exception $e) {
            // Se tabela não existir, usa o valor padrão
        }
        $percMeta = ($metaMensal > 0) ? round(($valorTotalMes / $metaMensal) * 100, 1) : 0;

        // Propostas aprovadas no mês
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM propostas WHERE status = 'aprovada' AND DATE_FORMAT(created_at, '%Y-%m') = :mes");
        $stmt->execute([':mes' => $mesAtual]);
        $aprovadasMes = (int)$stmt->fetchColumn();

        // Taxa de conversão (aprovadas / total * 100)
        $taxaConversao = ($totalPropostasMes > 0) ? round(($aprovadasMes / $totalPropostasMes) * 100, 1) : 0;

        // Total geral de propostas (todos os tempos)
        $stmt = $pdo->query("SELECT COUNT(*) FROM propostas");
        $totalGeralProp = (int)$stmt->fetchColumn();

        $indicadores = [
            'total_mes'        => $totalPropostasMes,
            'valor_total_mes'  => $valorTotalMes,
            'meta_mensal'      => $metaMensal,
            'perc_meta'        => $percMeta,
            'aprovadas_mes'    => $aprovadasMes,
            'taxa_conversao'   => $taxaConversao,
            'total_geral'      => $totalGeralProp,
        ];
    } catch (Exception $e) {
        error_log('Erro ao carregar indicadores comerciais: ' . $e->getMessage());
        $indicadores = [
            'total_mes'       => 0,
            'valor_total_mes' => 0,
            'meta_mensal'     => 50000.00,
            'perc_meta'       => 0,
            'aprovadas_mes'   => 0,
            'taxa_conversao'  => 0,
            'total_geral'     => 0,
        ];
    }
}

// ============================================
// BUSCAR PROPOSTAS
// ============================================
try {
    $sql = "SELECT p.*, c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj
            FROM propostas p
            INNER JOIN clientes c ON c.id = p.cliente_id
            {$sqlWhere}
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $propostas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Buscar embarcações de cada proposta (consulta separada para evitar GROUP_CONCAT pesado)
    $embarcacoesPorProposta = [];
    if (!empty($propostas)) {
        $ids = array_column($propostas, 'id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmtEmb = $pdo->prepare("
            SELECT pe.proposta_id, e.nome, e.registro
            FROM propostas_embarcacoes pe
            INNER JOIN embarcacoes e ON e.id = pe.embarcacao_id
            WHERE pe.proposta_id IN ({$placeholders})
            ORDER BY e.nome ASC
        ");
        $stmtEmb->execute(array_values($ids));
        $vinculos = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);

        foreach ($vinculos as $v) {
            $pid = $v['proposta_id'];
            if (!isset($embarcacoesPorProposta[$pid])) {
                $embarcacoesPorProposta[$pid] = [];
            }
            $embarcacoesPorProposta[$pid][] = $v['nome'] . (!empty($v['registro']) ? ' (' . $v['registro'] . ')' : '');
        }
    }
} catch (Exception $e) {
    error_log('Erro ao listar propostas: ' . $e->getMessage());
    $propostas = [];
    $embarcacoesPorProposta = [];
}

// Status com labels e cores
$statusConfig = [
    'rascunho'  => ['label' => 'Rascunho',  'cor' => 'secondary'],
    'enviada'   => ['label' => 'Enviada',   'cor' => 'info'],
    'aprovada'  => ['label' => 'Aprovada',  'cor' => 'success'],
    'recusada'  => ['label' => 'Recusada',  'cor' => 'danger'],
    'cancelada' => ['label' => 'Cancelada', 'cor' => 'warning'],
];

$titulo_page = 'Comercial - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">

    <!-- Cabeçalho -->
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-chart-line"></i> Módulo Comercial</h1>
            <p>Gerencie propostas comerciais, serviços e acompanhe indicadores.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nova Proposta
            </a>
            <a href="<?php echo APP_URL; ?>comercial/servicos" class="btn btn-secondary">
                <i class="fas fa-cogs"></i> Serviços
            </a>
        </div>
    </div>

    <?php if ($cargo === 'ADMIN'): ?>
    <!-- ===== INDICADORES COMERCIAIS ===== -->
    <div class="cards-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 25px;">
        <!-- Card: Propostas do Mês -->
        <div class="card" style="border-left: 4px solid #2ECC71;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(46,204,113,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-file-invoice" style="font-size: 1.3rem; color: #2ECC71;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Propostas do Mês</small>
                    <span style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo $indicadores['total_mes']; ?></span>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-top: 2px;">
                        <?php echo $indicadores['aprovadas_mes']; ?> aprovadas (<?php echo $indicadores['taxa_conversao']; ?>%)
                    </small>
                </div>
            </div>
        </div>

        <!-- Card: Valor Total do Mês -->
        <div class="card" style="border-left: 4px solid #3498DB;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(52,152,219,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-dollar-sign" style="font-size: 1.3rem; color: #3498DB;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Valor Total do Mês</small>
                    <span style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);">R$ <?php echo number_format($indicadores['valor_total_mes'], 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Card: Meta Mensal -->
        <div class="card" style="border-left: 4px solid #F39C12;">
            <div style="padding: 18px 20px;">
                <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 4px;">Meta Mensal (R$ <?php echo number_format($indicadores['meta_mensal'], 2, ',', '.'); ?>)</small>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo $indicadores['perc_meta']; ?>%</span>
                </div>
                <!-- Barra de progresso -->
                <div style="margin-top: 10px; background: var(--cor-borda); border-radius: 6px; height: 8px; overflow: hidden;">
                    <div style="width: <?php echo min(100, $indicadores['perc_meta']); ?>%; height: 100%; background: linear-gradient(90deg, #F39C12, #E67E22); border-radius: 6px; transition: width 0.6s ease;"></div>
                </div>
                <small style="display: block; color: var(--cor-texto-secundario); margin-top: 6px;">
                    Restam R$ <?php echo number_format(max(0, $indicadores['meta_mensal'] - $indicadores['valor_total_mes']), 2, ',', '.'); ?> para atingir a meta
                </small>
            </div>
        </div>

        <!-- Card: Total Geral -->
        <div class="card" style="border-left: 4px solid #9B59B6;">
            <div style="padding: 18px 20px; display: flex; align-items: center; gap: 14px;">
                <div style="width: 48px; height: 48px; border-radius: 12px; background: rgba(155,89,182,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fas fa-archive" style="font-size: 1.3rem; color: #9B59B6;"></i>
                </div>
                <div>
                    <small style="display: block; color: var(--cor-texto-secundario); margin-bottom: 2px;">Total Geral de Propostas</small>
                    <span style="font-size: 1.6rem; font-weight: 700; color: var(--cor-texto);"><?php echo $indicadores['total_geral']; ?></span>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== LISTAGEM DE PROPOSTAS ===== -->
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-file-invoice"></i> Propostas</h3>
            <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Proposta
            </a>
        </div>

        <!-- Filtros -->
        <div class="filtros" style="margin: 15px 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" id="buscaProposta" placeholder="Nome do cliente ou número da proposta..."
                       value="<?php echo h($filtro_busca); ?>"
                       onkeyup="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label><i class="fas fa-filter"></i> Status</label>
                <select id="filtroStatus" onchange="filtrarPropostas()">
                    <option value="todos" <?php echo $filtro_status === 'todos' ? 'selected' : ''; ?>>Todos</option>
                    <?php foreach ($statusConfig as $val => $cfg): ?>
                        <option value="<?php echo $val; ?>" <?php echo $filtro_status === $val ? 'selected' : ''; ?>>
                            <?php echo $cfg['label']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Início</label>
                <input type="date" id="filtroDataIni" value="<?php echo h($filtro_data_ini); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 130px;">
                <label><i class="fas fa-calendar"></i> Data Fim</label>
                <input type="date" id="filtroDataFim" value="<?php echo h($filtro_data_fim); ?>" onchange="filtrarPropostas()">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 90px; display: flex; align-items: flex-end;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()" style="width: 100%;">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>

        <?php if (empty($propostas)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-file-invoice"></i>
                <h3>Nenhuma proposta encontrada</h3>
                <p>
                    <?php if ($filtro_status !== 'todos' || !empty($filtro_busca)): ?>
                        Nenhum resultado para os filtros aplicados. Tente limpar os filtros.
                    <?php else: ?>
                        Clique em "Nova Proposta" para criar a primeira proposta do sistema.
                    <?php endif; ?>
                </p>
                <?php if ($cargo === 'ADMIN'): ?>
                <a href="<?php echo APP_URL; ?>comercial/nova" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nova Proposta
                </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table id="tabelaPropostas">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Embarcação(ões)</th>
                        <th>Valor Total</th>
                        <th>Parcelas</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th style="width: 130px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($propostas as $p): ?>
                    <?php
                        $pid = $p['id'];
                        $embarcacoesLista = $embarcacoesPorProposta[$pid] ?? [];
                        $embNomes = !empty($embarcacoesLista) ? implode(', ', $embarcacoesLista) : '<em class="text-muted">N/I</em>';
                        $statusCfg = $statusConfig[$p['status']] ?? ['label' => $p['status'], 'cor' => 'secondary'];
                    ?>
                    <tr>
                        <td>
                            <strong style="font-family: monospace; font-size: 0.9rem;"><?php echo h($p['numero']); ?></strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;"><?php echo h($p['cliente_nome']); ?></div>
                            <small style="color: var(--cor-texto-secundario);"><?php echo h($p['cliente_cpfcnpj'] ?? ''); ?></small>
                        </td>
                        <td>
                            <small><?php echo $embNomes; ?></small>
                        </td>
                        <td>
                            <strong style="color: var(--cor-destaque);">R$ <?php echo number_format((float)$p['valor_total'], 2, ',', '.'); ?></strong>
                        </td>
                        <td class="text-center"><?php echo (int)$p['parcelas']; ?>x</td>
                        <td>
                            <span class="badge badge-<?php echo $statusCfg['cor']; ?>">
                                <?php echo $statusCfg['label']; ?>
                            </span>
                        </td>
                        <td>
                            <small><?php echo date('d/m/Y', strtotime($p['data_emissao'])); ?></small>
                        </td>
                        <td>
                            <div class="d-flex gap-1" style="display: flex; gap: 4px;">
                                <a href="<?php echo APP_URL; ?>comercial/propostas?id=<?php echo urlencode($pid); ?>&visualizar=1"
                                   class="btn btn-secondary btn-sm" title="Visualizar" style="padding: 4px 8px;">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>comercial/pdf?id=<?php echo urlencode($pid); ?>"
                                   class="btn btn-primary btn-sm" title="Gerar PDF" target="_blank" style="padding: 4px 8px;">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                <?php if ($cargo === 'ADMIN' && $p['status'] !== 'cancelada'): ?>
                                <form method="POST" action="<?php echo APP_URL; ?>comercial/propostas/actions" style="display: inline;"
                                      onsubmit="return confirm('Enviar proposta <?php echo h(addslashes($p['numero'])); ?> por e-mail para o cliente?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                                    <input type="hidden" name="action" value="enviar_proposta">
                                    <input type="hidden" name="id" value="<?php echo h($p['id']); ?>">
                                    <button type="submit" class="btn btn-success btn-sm" title="Enviar Proposta por E-mail" style="padding: 4px 8px;">
                                        <i class="fas fa-envelope"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Rodapé com totalizador -->
        <div class="card-footer" style="padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total: <?php echo count($propostas); ?> proposta(s)
                <?php if ($filtro_status !== 'todos'): ?>
                    com status "<?php echo $statusConfig[$filtro_status]['label'] ?? $filtro_status; ?>"
                <?php endif; ?>
            </small>
            <?php
            $somaLista = array_sum(array_column($propostas, 'valor_total'));
            ?>
            <small class="text-muted">
                <strong>Soma:</strong> R$ <?php echo number_format($somaLista, 2, ',', '.'); ?>
            </small>
        </div>
    </div>
</div>

<script>
function filtrarPropostas() {
    const status   = document.getElementById('filtroStatus').value;
    const busca    = document.getElementById('buscaProposta').value.trim();
    const dataIni  = document.getElementById('filtroDataIni').value;
    const dataFim  = document.getElementById('filtroDataFim').value;

    let url = '<?php echo APP_URL; ?>comercial?';
    const params = [];

    if (status !== 'todos') {
        params.push('status=' + encodeURIComponent(status));
    }
    if (busca !== '') {
        params.push('busca=' + encodeURIComponent(busca));
    }
    if (dataIni !== '') {
        params.push('data_ini=' + encodeURIComponent(dataIni));
    }
    if (dataFim !== '') {
        params.push('data_fim=' + encodeURIComponent(dataFim));
    }

    window.location.href = url + params.join('&');
}

function limparFiltros() {
    window.location.href = '<?php echo APP_URL; ?>comercial';
}

// Permite buscar pressionando Enter no campo de busca
document.getElementById('buscaProposta')?.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        filtrarPropostas();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>