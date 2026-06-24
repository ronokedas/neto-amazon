<?php
/**
 * MODULO: DASHBOARD
 * Arquivo: index.php - Painel inicial com cards de resumo
 */

// Headers anti-cache para garantir dados atualizados
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticacao
requireLogin();

$usuario = [
    'id'    => $_SESSION['usuario_id'],
    'nome'  => $_SESSION['usuario_nome'],
    'email' => $_SESSION['usuario_email'],
    'cargo' => $_SESSION['usuario_cargo']
];

$cargo = getCargo();
$is_admin = ($cargo === 'ADMIN');

// Garantir que a tabela configuracoes existe (cria se nao existir)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$')");
} catch (Exception $e) {}

// Buscar valor da meta da tabela configuracoes (para todos os usuarios, antes de qualquer if)
$meta_mensal_valor = 50000.00;
try {
    $stmtMeta = $pdo->prepare("SELECT valor FROM configuracoes WHERE chave = 'meta_mensal'");
    $stmtMeta->execute();
    $metaValorDb = $stmtMeta->fetchColumn();
    if ($metaValorDb !== false) {
        $meta_mensal_valor = (float)$metaValorDb;
    }
} catch (Exception $e) {
    $meta_mensal_valor = 50000.00;
}

// Buscar estatisticas
try {
    // Total de embarcacoes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM embarcacoes WHERE ativo = 1");
    $total_embarcacoes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de pessoas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM pessoas WHERE ativo = 1");
    $total_pessoas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Total de vistorias (este mes)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vistorias WHERE MONTH(data_vistoria) = MONTH(CURRENT_DATE()) AND YEAR(data_vistoria) = YEAR(CURRENT_DATE())");
    $total_vistorias_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Vistorias pendentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM vistorias WHERE status = 'PENDENTE'");
    $vistorias_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Variaveis padrao
    $total_receitas = 0.00;
    $total_despesas = 0.00;
    $propostas_mes = 0;
    $valor_propostas_mes = 0.00;
    $perc_meta = 0;
    
    // Propostas do mes (para todos — usado no card de meta)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM propostas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $propostas_mes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) as total FROM propostas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $valor_propostas_mes = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Percentual da meta
    $perc_meta = ($meta_mensal_valor > 0) ? round(($valor_propostas_mes / $meta_mensal_valor) * 100, 1) : 0;
    
    // Dados financeiros (apenas ADMIN)
    if ($is_admin) {
        $stmt = $pdo->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'RECEITA' AND MONTH(data) = MONTH(CURRENT_DATE()) AND YEAR(data) = YEAR(CURRENT_DATE())");
        $total_receitas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'DESPESA' AND MONTH(data) = MONTH(CURRENT_DATE()) AND YEAR(data) = YEAR(CURRENT_DATE())");
        $total_despesas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
    
} catch (Exception $e) {
    $total_embarcacoes = 0;
    $total_pessoas = 0;
    $total_vistorias_mes = 0;
    $vistorias_pendentes = 0;
    $total_receitas = 0.00;
    $total_despesas = 0.00;
    $propostas_mes = 0;
    $valor_propostas_mes = 0.00;
    $perc_meta = 0;
}

$titulo_page = 'Dashboard - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <!-- Cabecalho de boas-vindas -->
    <div class="welcome-section">
        <div>
            <h1>Bem-vindo, <?php echo h($usuario['nome']); ?>!</h1>
            <p>Confira o resumo geral do sistema</p>
        </div>
        <div class="welcome-date">
            <i class="fas fa-calendar-alt"></i>
            <span><?php 
                $dias_semana = ['Domingo', 'Segunda-Feira', 'Terça-Feira', 'Quarta-Feira', 'Quinta-Feira', 'Sexta-Feira', 'Sábado'];
                $dia_semana = $dias_semana[date('w')];
                echo $dia_semana . ' ' . date('d/m/Y'); 
            ?></span>
        </div>
    </div>

    <!-- Cards de estatisticas -->
    <div class="stats-grid">
        <a href="<?php echo APP_URL; ?>embarcacoes" class="stat-card stat-card-blue">
            <div class="stat-icon blue">
                <i class="fas fa-ship"></i>
            </div>
            <div class="stat-info">
                <h4>Total Embarcacoes</h4>
                <span class="stat-valor"><?php echo number_format($total_embarcacoes, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="<?php echo APP_URL; ?>pessoas" class="stat-card stat-card-green">
            <div class="stat-icon green">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h4>Total Pessoas</h4>
                <span class="stat-valor"><?php echo number_format($total_pessoas, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="<?php echo APP_URL; ?>vistorias" class="stat-card stat-card-orange">
            <div class="stat-icon orange">
                <i class="fas fa-clipboard-check"></i>
            </div>
            <div class="stat-info">
                <h4>Vistorias (mes)</h4>
                <span class="stat-valor"><?php echo number_format($total_vistorias_mes, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="<?php echo APP_URL; ?>vistorias?status=PENDENTE" class="stat-card stat-card-red">
            <div class="stat-icon red">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div class="stat-info">
                <h4>Vistorias Pendentes</h4>
                <span class="stat-valor"><?php echo number_format($vistorias_pendentes, 0, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <?php if ($is_admin): ?>
        <a href="<?php echo APP_URL; ?>financeiro?tipo=RECEITA" class="stat-card stat-card-green-light">
            <div class="stat-icon green">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="stat-info">
                <h4>Receitas (mes)</h4>
                <span class="stat-valor">R$ <?php echo number_format($total_receitas, 2, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <a href="<?php echo APP_URL; ?>financeiro?tipo=DESPESA" class="stat-card stat-card-red-light">
            <div class="stat-icon red">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="stat-info">
                <h4>Despesas (mes)</h4>
                <span class="stat-valor">R$ <?php echo number_format($total_despesas, 2, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <!-- Card: Propostas do Mês -->
        <a href="<?php echo APP_URL; ?>comercial" class="stat-card stat-card-purple">
            <div class="stat-icon purple">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-info">
                <h4>Propostas (mes)</h4>
                <span class="stat-valor"><?php echo $propostas_mes; ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <!-- Card: Valor Total Propostas -->
        <a href="<?php echo APP_URL; ?>comercial" class="stat-card stat-card-teal">
            <div class="stat-icon teal">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <h4>Valor Propostas (mes)</h4>
                <span class="stat-valor">R$ <?php echo number_format($valor_propostas_mes, 2, ',', '.'); ?></span>
            </div>
            <div class="stat-arrow">
                <i class="fas fa-arrow-right"></i>
            </div>
        </a>

        <?php endif; ?>

        <!-- Card: Meta Atingida (visivel para todos os cargos) -->
        <a href="<?php echo APP_URL; ?>configuracoes" class="stat-card stat-card-gold" style="flex-direction: column; align-items: stretch; gap: 6px; padding: 18px 20px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="stat-icon gold" style="width: 44px; height: 44px; font-size: 1.1rem;">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-info" style="flex: 1;">
                    <h4 style="font-size: 0.75rem; letter-spacing: 0.3px;">Meta Atingida</h4>
                    <span class="stat-valor" style="font-size: 1.5rem;"><?php echo $perc_meta; ?>%</span>
                </div>
            </div>
            <!-- Barra de progresso -->
            <div style="height: 5px; border-radius: 3px; background: rgba(255,255,255,0.12); overflow: hidden; margin: 0;">
                <div style="width: <?php echo min(100, $perc_meta); ?>%; height: 100%; background: linear-gradient(90deg, #F39C12, #E67E22); border-radius: 3px; transition: width 0.6s ease;"></div>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <?php if ($is_admin): ?>
                <span style="font-size: 0.7rem; color: var(--cor-texto-secundario);">
                    <i class="fas fa-bullseye" style="margin-right: 3px;"></i>Meta: R$ <?php echo number_format($meta_mensal_valor, 2, ',', '.'); ?>
                </span>
                <?php else: ?>
                <span style="font-size: 0.7rem; color: var(--cor-texto-secundario);">
                    <i class="fas fa-chart-line"></i> <?php echo $propostas_mes; ?> proposta(s) no mês
                </span>
                <?php endif; ?>
                <?php if ($perc_meta >= 100): ?>
                    <span style="font-size: 0.7rem; font-weight: 600; color: var(--cor-destaque);">
                        <i class="fas fa-check-circle"></i> Atingida
                    </span>
                <?php endif; ?>
            </div>
        </a>
    </div>

    <!-- Acoes rapidas -->
    <div class="quick-actions-section">
        <h2><i class="fas fa-bolt"></i> Acoes Rapidas</h2>
        <div class="quick-actions-grid">
            <a href="<?php echo APP_URL; ?>vistorias?action=nova" class="quick-action-btn">
                <i class="fas fa-plus-circle"></i>
                <span>Nova Vistoria</span>
            </a>
            <a href="<?php echo APP_URL; ?>embarcacoes?action=nova" class="quick-action-btn">
                <i class="fas fa-ship"></i>
                <span>Cadastrar Embarcacao</span>
            </a>
            <a href="<?php echo APP_URL; ?>pessoas?action=nova" class="quick-action-btn">
                <i class="fas fa-user-plus"></i>
                <span>Cadastrar Pessoa</span>
            </a>
            <?php if ($is_admin): ?>
            <a href="<?php echo APP_URL; ?>financeiro?action=novo" class="quick-action-btn">
                <i class="fas fa-money-bill-wave"></i>
                <span>Novo Lancamento</span>
            </a>
            <a href="<?php echo APP_URL; ?>comercial/nova" class="quick-action-btn">
                <i class="fas fa-file-invoice"></i>
                <span>Nova Proposta</span>
            </a>
            <?php else: ?>
            <a href="<?php echo APP_URL; ?>agendamentos" class="quick-action-btn">
                <i class="fas fa-calendar-check"></i>
                <span>Ver Agendamentos</span>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ultimas vistorias -->
    <div class="recent-section">
        <div class="section-header">
            <h2><i class="fas fa-clock"></i> Ultimas Vistorias</h2>
            <a href="<?php echo APP_URL; ?>vistorias" class="btn btn-secondary btn-sm">
                Ver todas <i class="fas fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="table-container">
            <table id="tabela-vistorias">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Embarcacao</th>
                        <th>Pessoa</th>
                        <th>Status</th>
                        <th>Vistoriador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $sql = "
                            SELECT v.*, e.nome as embarcacao, p.nome_completo as pessoa, u.nome as vistoriador
                            FROM vistorias v
                            LEFT JOIN embarcacoes e ON e.id = v.embarcacao_id
                            LEFT JOIN pessoas p ON p.id = v.pessoa_id
                            LEFT JOIN usuarios u ON u.id = v.criado_por
                        ";
                        // Vistoriador so ve suas proprias vistorias
                        if (!$is_admin) {
                            $sql .= " WHERE v.criado_por = " . intval($_SESSION['usuario_id']);
                        }
                        $sql .= " ORDER BY v.criado_em DESC LIMIT 5";
                        $stmt = $pdo->query($sql);
                        $vistorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    } catch (Exception $e) {
                        $vistorias = [];
                    }

                    if (empty($vistorias)):
                    ?>
                        <tr>
                            <td colspan="5" class="no-data">
                                <i class="fas fa-inbox"></i>
                                Nenhuma vistoria registrada ainda.
                            </td>
                        </tr>
                    <?php else:
                        foreach ($vistorias as $v):
                            $status_classes = [
                                'PENDENTE' => 'badge-warning',
                                'APROVADA' => 'badge-success',
                                'REPROVADA' => 'badge-danger',
                                'CANCELADA' => 'badge-secondary'
                            ];
                            $class = $status_classes[$v['status']] ?? 'badge-secondary';
                    ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($v['data_vistoria'])); ?></td>
                            <td><?php echo h($v['embarcacao'] ?? '-'); ?></td>
                            <td><?php echo h($v['pessoa'] ?? '-'); ?></td>
                            <td><span class="badge <?php echo $class; ?>"><?php echo h($v['status']); ?></span></td>
                            <td><?php echo h($v['vistoriador'] ?? '-'); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>