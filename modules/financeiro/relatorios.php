<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: relatorios.php - Dashboard Financeiro (DRE, Fluxo, Inadimplencia)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

$mes_atual = date('Y-m');
$filtro_mes = $_GET['mes'] ?? $mes_atual;

// Definir data inicio e fim do mes selecionado
$data_ini = $filtro_mes . '-01';
$data_fim = date('Y-m-t', strtotime($data_ini));

// 1. INADIMPLENCIA (Atrasados vs Pagos no periodo, ou geral vencidos)
$sqlInadimplencia = "
    SELECT 
        SUM(CASE WHEN status = 'PENDENTE' AND data_vencimento < CURRENT_DATE THEN valor ELSE 0 END) as total_atrasado,
        SUM(CASE WHEN status = 'PENDENTE' AND data_vencimento >= CURRENT_DATE AND data_vencimento <= :fim THEN valor ELSE 0 END) as total_a_vencer,
        SUM(CASE WHEN status = 'PAGO' AND data >= :ini AND data <= :fim2 THEN valor ELSE 0 END) as total_recebido
    FROM financeiro_lancamentos 
    WHERE tipo = 'RECEITA' AND ativo = 1
";
$stmtInad = $pdo->prepare($sqlInadimplencia);
$stmtInad->execute([':ini' => $data_ini, ':fim' => $data_fim, ':fim2' => $data_fim]);
$inad = $stmtInad->fetch();

// 2. FLUXO DE CAIXA (Soma geral ate o fim do mes, considerando o que ja foi PAGO ou que ainda vai entrar/sair no mes)
$sqlFluxoPrevisto = "
    SELECT tipo, SUM(valor) as total
    FROM financeiro_lancamentos
    WHERE ativo = 1 AND status != 'CANCELADO' AND data_vencimento >= :ini AND data_vencimento <= :fim
    GROUP BY tipo
";
$stmtFluxoP = $pdo->prepare($sqlFluxoPrevisto);
$stmtFluxoP->execute([':ini' => $data_ini, ':fim' => $data_fim]);
$fluxoPrevistoArr = $stmtFluxoP->fetchAll(PDO::FETCH_KEY_PAIR);
$receitaPrevista = floatval($fluxoPrevistoArr['RECEITA'] ?? 0);
$despesaPrevista = floatval($fluxoPrevistoArr['DESPESA'] ?? 0);

$sqlFluxoRealizado = "
    SELECT tipo, SUM(valor) as total
    FROM financeiro_lancamentos
    WHERE ativo = 1 AND status = 'PAGO' AND data >= :ini AND data <= :fim
    GROUP BY tipo
";
$stmtFluxoR = $pdo->prepare($sqlFluxoRealizado);
$stmtFluxoR->execute([':ini' => $data_ini, ':fim' => $data_fim]);
$fluxoRealizadoArr = $stmtFluxoR->fetchAll(PDO::FETCH_KEY_PAIR);
$receitaRealizada = floatval($fluxoRealizadoArr['RECEITA'] ?? 0);
$despesaRealizada = floatval($fluxoRealizadoArr['DESPESA'] ?? 0);

// 3. DRE POR CATEGORIA (Despesas Pagas no mes)
$sqlDRE = "
    SELECT categoria, SUM(valor) as total
    FROM financeiro_lancamentos
    WHERE ativo = 1 AND status = 'PAGO' AND tipo = 'DESPESA' AND data >= :ini AND data <= :fim
    GROUP BY categoria
    ORDER BY total DESC
";
$stmtDRE = $pdo->prepare($sqlDRE);
$stmtDRE->execute([':ini' => $data_ini, ':fim' => $data_fim]);
$dreDespesas = $stmtDRE->fetchAll();

// 4. EVOLUCAO MENSAL (Ultimos 6 meses)
$evolucao = [];
for ($i = 5; $i >= 0; $i--) {
    // Corrigido bug no strtotime que quebrava o grafico
    $m = date('Y-m', strtotime("-$i months", strtotime($data_ini)));
    $m_ini = $m . '-01';
    $m_fim = date('Y-m-t', strtotime($m_ini));

    $sqlMes = "SELECT tipo, SUM(valor) as total FROM financeiro_lancamentos WHERE ativo = 1 AND status = 'PAGO' AND data >= :ini AND data <= :fim GROUP BY tipo";
    $stmtMes = $pdo->prepare($sqlMes);
    $stmtMes->execute([':ini' => $m_ini, ':fim' => $m_fim]);
    $resMes = $stmtMes->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $evolucao['labels'][] = date('m/Y', strtotime($m_ini));
    $evolucao['receitas'][] = floatval($resMes['RECEITA'] ?? 0);
    $evolucao['despesas'][] = floatval($resMes['DESPESA'] ?? 0);
}

$titulo_page = 'Relatorios Financeiros - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<!-- Import Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="main-content">
    <div class="page-header d-flex" style="justify-content: space-between; align-items: center;">
        <div>
            <h1 class="page-title">Dashboard Financeiro</h1>
            <p class="page-subtitle">DRE, Fluxo de Caixa e Inadimplência</p>
        </div>
        <div>
            <form method="GET" class="d-flex gap-2">
                <input type="month" name="mes" value="<?= h($filtro_mes) ?>" class="form-control bg-dark text-light border-secondary">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filtrar</button>
            </form>
        </div>
    </div>

    <!-- CARDS DE INADIMPLENCIA / RECEITAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-hand-holding-dollar"></i></div>
            <div class="stat-info">
                <h4>Recebido no Mes</h4>
                <div class="stat-valor">R$ <?= number_format($inad['total_recebido'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
            <div class="stat-info">
                <h4>A Receber (Mes)</h4>
                <div class="stat-valor">R$ <?= number_format($inad['total_a_vencer'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
        <div class="stat-card" style="border: 1px solid var(--cor-erro);">
            <div class="stat-icon red"><i class="fa-solid fa-circle-exclamation"></i></div>
            <div class="stat-info">
                <h4 style="color: var(--cor-erro);">Inadimplencia (Atrasado Geral)</h4>
                <div class="stat-valor" style="color: var(--cor-erro);">R$ <?= number_format($inad['total_atrasado'] ?? 0, 2, ',', '.') ?></div>
            </div>
        </div>
    </div>

    <div class="grid-2">
        <!-- FLUXO DE CAIXA -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fa-solid fa-money-bill-transfer"></i> Fluxo de Caixa (<?= date('m/Y', strtotime($data_ini)) ?>)</h3>
            </div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Indicador</th>
                            <th class="text-right">Previsto</th>
                            <th class="text-right">Realizado (Pago)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong class="text-success">(+) Receitas</strong></td>
                            <td class="text-right text-success">R$ <?= number_format($receitaPrevista, 2, ',', '.') ?></td>
                            <td class="text-right text-success">R$ <?= number_format($receitaRealizada, 2, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <td><strong class="text-danger">(-) Despesas</strong></td>
                            <td class="text-right text-danger">R$ <?= number_format($despesaPrevista, 2, ',', '.') ?></td>
                            <td class="text-right text-danger">R$ <?= number_format($despesaRealizada, 2, ',', '.') ?></td>
                        </tr>
                        <tr style="background: rgba(255,255,255,0.05);">
                            <td><strong>(=) Saldo</strong></td>
                            <td class="text-right"><strong>R$ <?= number_format($receitaPrevista - $despesaPrevista, 2, ',', '.') ?></strong></td>
                            <td class="text-right"><strong>R$ <?= number_format($receitaRealizada - $despesaRealizada, 2, ',', '.') ?></strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- DRE POR CATEGORIA -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fa-solid fa-chart-pie"></i> Despesas por Categoria (DRE)</h3>
            </div>
            <div class="card-body" style="position: relative; height: 300px;">
                <canvas id="dreChart"></canvas>
            </div>
        </div>
    </div>

    <!-- EVOLUCAO 6 MESES -->
    <div class="card mb-3">
        <div class="card-header">
            <h3><i class="fa-solid fa-chart-line"></i> Evolucao de Receitas vs Despesas (Realizado nos ultimos 6 meses)</h3>
        </div>
        <div class="card-body" style="position: relative; height: 350px;">
            <canvas id="evolucaoChart"></canvas>
        </div>
    </div>

</div>

<script>
// Forçar conversão UTF-8 para evitar problemas de encoding na hora de renderizar categorias com acento (ex: Manutenção)
<?php
$labelsUtf8 = array_map(function($str) {
    return mb_check_encoding($str, 'UTF-8') ? $str : utf8_encode($str);
}, array_column($dreDespesas, 'categoria'));
?>

// Dados DRE
const dreLabels = <?= json_encode($labelsUtf8, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR) ?>;
const dreData = <?= json_encode(array_column($dreDespesas, 'total')) ?>;

// Cores para o grafico de pizza
const bgColors = ['#f85149', '#d29922', '#58a6ff', '#8b949e', '#39d353', '#9B59B6', '#1ABC9C', '#E67E22'];

new Chart(document.getElementById('dreChart'), {
    type: 'doughnut',
    data: {
        labels: dreLabels.length ? dreLabels : ['Sem despesas'],
        datasets: [{
            data: dreData.length ? dreData : [1],
            backgroundColor: dreData.length ? bgColors : ['#21262d'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'right', labels: { color: '#e6edf3' } }
        }
    }
});

// Dados Evolucao
const evoLabels = <?= json_encode($evolucao['labels']) ?>;
const evoReceitas = <?= json_encode($evolucao['receitas']) ?>;
const evoDespesas = <?= json_encode($evolucao['despesas']) ?>;

new Chart(document.getElementById('evolucaoChart'), {
    type: 'bar',
    data: {
        labels: evoLabels,
        datasets: [
            {
                label: 'Receitas',
                data: evoReceitas,
                backgroundColor: '#39d353',
                borderRadius: 4
            },
            {
                label: 'Despesas',
                data: evoDespesas,
                backgroundColor: '#f85149',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#e6edf3' } }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: { color: 'rgba(255, 255, 255, 0.1)' },
                ticks: { color: '#8b949e' }
            },
            x: {
                grid: { display: false },
                ticks: { color: '#8b949e' }
            }
        }
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>