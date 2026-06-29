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
    $sql_vistorias_mes = "SELECT COUNT(*) as total FROM vistorias v LEFT JOIN agendamentos a ON v.agendamento_id = a.id WHERE MONTH(v.data_vistoria) = MONTH(CURRENT_DATE()) AND YEAR(v.data_vistoria) = YEAR(CURRENT_DATE())";
    if ($cargo === 'VISTORIADOR') {
        $sql_vistorias_mes .= " AND a.vistoriador_id = :vistoriador_id";
        $stmt = $pdo->prepare($sql_vistorias_mes);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_vistorias_mes);
    }
    $total_vistorias_mes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Vistorias pendentes
    $sql_pendentes = "SELECT COUNT(*) as total FROM vistorias v LEFT JOIN agendamentos a ON v.agendamento_id = a.id WHERE v.status = 'PENDENTE'";
    if ($cargo === 'VISTORIADOR') {
        $sql_pendentes .= " AND a.vistoriador_id = :vistoriador_id";
        $stmt = $pdo->prepare($sql_pendentes);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_pendentes);
    }
    $vistorias_pendentes = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Variaveis padrao
    $total_receitas = 0.00;
    $total_despesas = 0.00;
    $propostas_mes = 0;
    $valor_propostas_mes = 0.00;
    $perc_meta = 0;
    
    // Vistorias recentes (últimas 5)
    $vistorias_recentes = [];
    try {
        $sql_recentes = "
            SELECT v.data_vistoria as data, e.nome as embarcacao, 
                   p.nome_completo as pessoa, v.status, u.nome as vistoriador
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN pessoas p ON v.pessoa_id = p.id
            LEFT JOIN usuarios u ON v.criado_por = u.id
        ";
        if ($cargo === 'VISTORIADOR') {
            $sql_recentes .= " WHERE EXISTS (SELECT 1 FROM agendamentos a WHERE a.id = v.agendamento_id AND a.vistoriador_id = :vistoriador_id)";
        }
        $sql_recentes .= " ORDER BY v.criado_em DESC LIMIT 5";
        
        $stmt = $pdo->prepare($sql_recentes);
        if ($cargo === 'VISTORIADOR') {
            $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        } else {
            $stmt->execute();
        }
        $vistorias_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vistorias_recentes = [];
    }
    
    // Lista de vistorias pendentes (top 5 mais antigas)
    $vistorias_pendentes_lista = [];
    try {
        $sql_pendentes_lista = "
            SELECT v.id, e.nome as embarcacao, p.nome_completo as pessoa,
                   DATEDIFF(NOW(), v.criado_em) as dias
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN pessoas p ON v.pessoa_id = p.id
            WHERE v.status = 'PENDENTE'
        ";
        if ($cargo === 'VISTORIADOR') {
            $sql_pendentes_lista .= " AND EXISTS (SELECT 1 FROM agendamentos a WHERE a.id = v.agendamento_id AND a.vistoriador_id = :vistoriador_id)";
        }
        $sql_pendentes_lista .= " ORDER BY v.criado_em ASC LIMIT 5";
        
        $stmt = $pdo->prepare($sql_pendentes_lista);
        if ($cargo === 'VISTORIADOR') {
            $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        } else {
            $stmt->execute();
        }
        $vistorias_pendentes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Adicionar campo urgencia
        foreach ($vistorias_pendentes_lista as &$v) {
            $dias = (int)$v['dias'];
            if ($dias <= 2) {
                $v['urgencia'] = 'ok';
            } elseif ($dias <= 5) {
                $v['urgencia'] = 'warn';
            } else {
                $v['urgencia'] = 'critical';
            }
        }
    } catch (Exception $e) {
        $vistorias_pendentes_lista = [];
    }
    
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
    
    // Dados para gráfico dos últimos 6 meses (apenas ADMIN)
    $labels_6meses = [];
    $receitas_6meses = [];
    $despesas_6meses = [];
    
    if ($is_admin) {
        try {
            for ($i = 5; $i >= 0; $i--) {
                $mes = date('m', strtotime("-$i months"));
                $ano = date('Y', strtotime("-$i months"));
                $labels_6meses[] = date('M/Y', strtotime("-$i months"));
                
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'RECEITA' AND MONTH(data) = :mes AND YEAR(data) = :ano");
                $stmt->execute([':mes' => $mes, ':ano' => $ano]);
                $receitas_6meses[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                $stmt = $pdo->prepare("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'DESPESA' AND MONTH(data) = :mes AND YEAR(data) = :ano");
                $stmt->execute([':mes' => $mes, ':ano' => $ano]);
                $despesas_6meses[] = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
            }
        } catch (Exception $e) {
            $labels_6meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun'];
            $receitas_6meses = [0, 0, 0, 0, 0, 0];
            $despesas_6meses = [0, 0, 0, 0, 0, 0];
        }
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
    <!-- Cabecalho da pagina -->
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">
                Bem-vindo, <?= h($_SESSION['usuario_nome'] ?? 'Usuário') ?> · <?php
                $dias_portugues = ['Sunday' => 'Domingo', 'Monday' => 'Segunda-feira', 'Tuesday' => 'Terça-feira', 'Wednesday' => 'Quarta-feira', 'Thursday' => 'Quinta-feira', 'Friday' => 'Sexta-feira', 'Saturday' => 'Sábado'];
                echo $dias_portugues[date('l')] . date(', d/m/Y');
                ?>
            </p>
        </div>
        <div class="page-actions">
            <a href="<?php echo APP_URL; ?>agendamentos/form" class="btn-secondary">
                <i class="fa-solid fa-plus"></i> Novo Agendamento
            </a>
            <a href="<?php echo APP_URL; ?>vistorias/nova" class="btn-primary">
                Nova Vistoria <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <!-- KPI Cards Principais -->
    <div class="kpi-grid">
        <!-- Card 1: Receitas -->
        <div class="kpi-card">
            <div class="kpi-label">RECEITAS DO MÊS</div>
            <div class="kpi-value">R$ <?= number_format($total_receitas, 2, ',', '.') ?></div>
            <div class="kpi-delta kpi-delta--up">
                <i class="fa-solid fa-arrow-trend-up"></i>
                +12,4% vs mês anterior
            </div>
        </div>

        <!-- Card 2: Vistorias -->
        <div class="kpi-card">
            <div class="kpi-label">VISTORIAS DO MÊS</div>
            <div class="kpi-value"><?= number_format($total_vistorias_mes, 0, ',', '.') ?></div>
            <div class="kpi-delta kpi-delta--warn">
                <i class="fa-solid fa-clock"></i>
                <?= number_format($vistorias_pendentes, 0, ',', '.') ?> pendentes
            </div>
        </div>

        <!-- Card 3: Propostas -->
        <div class="kpi-card">
            <div class="kpi-label">PROPOSTAS EM ABERTO</div>
            <div class="kpi-value">R$ <?= number_format($valor_propostas_mes, 2, ',', '.') ?></div>
            <div class="kpi-delta kpi-delta--up">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <?= $propostas_mes ?> propostas
            </div>
        </div>

        <!-- Card 4: Meta -->
        <div class="kpi-card">
            <div class="kpi-label">META ATINGIDA</div>
            <div class="kpi-value"><?= $perc_meta ?>%</div>
            <div class="kpi-meta-bar">
                <div class="kpi-meta-fill" style="width: <?= min($perc_meta, 100) ?>%"></div>
            </div>
            <div class="kpi-delta kpi-delta--up">
                <i class="fa-solid fa-arrow-trend-up"></i>
                Meta R$ <?= number_format($meta_mensal_valor, 0, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Linha de stats secundários -->
    <div class="stats-secondary">
        <span>Embarcações <strong><?= number_format($total_embarcacoes, 0, ',', '.') ?></strong></span>
        <span class="dot">·</span>
        <span>Pessoas <strong><?= number_format($total_pessoas, 0, ',', '.') ?></strong></span>
        <span class="dot">·</span>
        <span>Despesas <strong>R$ <?= number_format($total_despesas, 2, ',', '.') ?></strong></span>
        <span class="dot">·</span>
        <span>Valor propostas <strong>R$ <?= number_format($valor_propostas_mes, 2, ',', '.') ?></strong></span>
    </div>

    <?php if ($is_admin && !empty($labels_6meses)): ?>
    <!-- Dashboard Grid: Gráfico + Pendentes -->
    <div class="dashboard-grid">
        <!-- Gráfico Receitas vs Despesas -->
        <div class="chart-card">
            <div class="card-header">
                <div>
                    <div class="card-title">Receitas vs Despesas</div>
                    <div class="card-subtitle">Últimos 6 meses</div>
                </div>
                <div class="chart-legend">
                    <span class="legend-dot" style="background:#39d353"></span> Receitas
                    <span class="legend-dot" style="background:#f85149"></span> Despesas
                </div>
            </div>
            <canvas id="chartReceitasDespesas"
                style="display:block; width:100%; height:220px; max-height:220px;">
            </canvas>
        </div>

        <!-- Card de Pendentes (Top 5 mais antigas) -->
        <div class="pending-card">
            <div class="card-header">
                <div>
                    <div class="card-title">Vistorias pendentes</div>
                    <div class="card-subtitle">Top 5 mais antigas</div>
                </div>
                <i class="fa-solid fa-circle-exclamation" style="color: var(--status-pending)"></i>
            </div>

            <div class="pending-list">
                <?php foreach ($vistorias_pendentes_lista as $v): ?>
                <a href="?page=vistorias&action=detalhe&id=<?= $v['id'] ?>" class="pending-item">
                    <div class="pending-info">
                        <span class="pending-embarcacao"><?= htmlspecialchars($v['embarcacao']) ?></span>
                        <span class="pending-pessoa"><?= htmlspecialchars($v['pessoa']) ?></span>
                    </div>
                    <span class="pending-days pending-days--<?= $v['urgencia'] ?>">
                        <?= $v['dias'] ?>d
                    </span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- RELATORIOS AGUARDANDO APROVACAO (apenas para ADMIN) -->
<?php if ($cargo === 'ADMIN'):
    try {
        $stmtRel = $pdo->prepare("
            SELECT v.*, e.nome AS embarcacao_nome, u.nome AS vistoriador_nome
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id
            LEFT JOIN usuarios u ON a.vistoriador_id = u.id
            WHERE v.status = 'AGUARDANDO_APROVACAO'
            ORDER BY v.atualizado_em ASC
            LIMIT 5
        ");
        $stmtRel->execute();
        $relatorios_pendentes = $stmtRel->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $relatorios_pendentes = [];
    }
    if (!empty($relatorios_pendentes)):
?>
<div class="card mb-4" style="border-left: 4px solid #e74c3c; border-radius: 8px; background: linear-gradient(135deg, rgba(231,76,60,0.08) 0%, rgba(231,76,60,0.02) 100%);">
    <div class="card-header" style="border-bottom: 1px solid rgba(231,76,60,0.3);">
        <h4 style="margin: 0; color: #e74c3c;">
            <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i> Relatórios Aguardando Aprovação
            <span class="badge" style="background: #e74c3c; color: #fff; font-size: 14px; margin-left: 10px;">
                <?php echo count($relatorios_pendentes); ?> pendente(s)
            </span>
        </h4>
    </div>
    <div class="card-body" style="padding: 16px;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(231,76,60,0.3);">
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Data Vistoria</th>
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Vistoriador</th>
                        <th style="padding: 10px 8px; text-align: left; color: #e74c3c;">Embarcação</th>
                        <th style="padding: 10px 8px; text-align: center; color: #e74c3c;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($relatorios_pendentes as $rel): ?>
                        <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                            <td style="padding: 12px 8px;"><?php echo formatarData($rel['data_vistoria']); ?></td>
                            <td style="padding: 12px 8px;"><?php echo h($rel['vistoriador_nome'] ?? '-'); ?></td>
                            <td style="padding: 12px 8px;"><?php echo h($rel['embarcacao_nome'] ?? '-'); ?></td>
                            <td style="padding: 12px 8px; text-align: center;">
                                <a href="<?php echo APP_URL; ?>documentacao/aprovacao_relatorios" class="btn btn-sm btn-danger">
                                    <i class="fas fa-gavel"></i> Analisar
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php endif; endif; ?>

    <!-- AGENDAMENTOS EM DESTAQUE (apenas para VISTORIADOR) -->
<?php if ($cargo === 'VISTORIADOR'): 
    try {
        $stmtAg = $pdo->prepare("
            SELECT a.*, c.nome AS cliente_nome, e.nome AS embarcacao_nome
            FROM agendamentos a
            INNER JOIN clientes c ON a.cliente_id = c.id
            INNER JOIN embarcacoes e ON a.embarcacao_id = e.id
            WHERE a.vistoriador_id = :vistoriador_id
              AND a.status IN ('pendente', 'confirmado')
              AND (a.data_vistoria >= CURDATE() OR (a.data_vistoria = CURDATE() AND a.hora_vistoria >= CURTIME()))
            ORDER BY a.data_vistoria ASC, a.hora_vistoria ASC
            LIMIT 5
        ");
        $stmtAg->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        $meus_agendamentos = $stmtAg->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $meus_agendamentos = [];
    }
    if (!empty($meus_agendamentos)): 
?>
<div class="card mb-4" style="border-left: 4px solid #f39c12; border-radius: 8px; background: linear-gradient(135deg, rgba(243,156,18,0.08) 0%, rgba(243,156,18,0.02) 100%);">
    <div class="card-header" style="border-bottom: 1px solid rgba(243,156,18,0.3);">
        <h4 style="margin: 0; color: #f39c12;">
            <i class="fas fa-star" style="color: #f39c12;"></i> Próximos Agendamentos
            <span class="badge" style="background: #f39c12; color: #000; font-size: 14px; margin-left: 10px;">
                <i class="fas fa-calendar"></i> <?php echo count($meus_agendamentos); ?> agendamento(s)
            </span>
        </h4>
    </div>
    <div class="card-body" style="padding: 16px;">
        <div class="table-responsive">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid rgba(243,156,18,0.3);">
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Data</th>
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Hora</th>
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Cliente</th>
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Embarcação</th>
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Tipo</th>
                        <th style="padding: 10px 8px; text-align: left; color: #f39c12;">Status</th>
                        <th style="padding: 10px 8px; text-align: center; color: #f39c12;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($meus_agendamentos as $ag): 
                        $status_class = match($ag['status']) {
                            'pendente' => 'badge-warning',
                            'confirmado' => 'badge-primary',
                            default => 'badge-secondary'
                        };
                    ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.05);">
                        <td style="padding: 10px 8px;"><?php echo date('d/m/Y', strtotime($ag['data_vistoria'])); ?></td>
                        <td style="padding: 10px 8px;"><?php echo $ag['hora_vistoria'] ? date('H:i', strtotime($ag['hora_vistoria'])) : '-'; ?></td>
                        <td style="padding: 10px 8px; font-weight: 600;"><?php echo h($ag['cliente_nome'] ?? '-'); ?></td>
                        <td style="padding: 10px 8px;"><?php echo h($ag['embarcacao_nome'] ?? '-'); ?></td>
                        <td style="padding: 10px 8px;"><?php echo h($ag['tipo_vistoria'] ?? '-'); ?></td>
                        <td style="padding: 10px 8px;"><span class="badge <?php echo $status_class; ?>"><?php echo ucfirst($ag['status']); ?></span></td>
                        <td style="padding: 10px 8px; text-align: center;">
                            <a href="<?php echo APP_URL; ?>vistorias/relatorio?agendamento_id=<?php echo urlencode($ag['id']); ?>"
                               class="btn btn-success btn-sm" title="Abrir Relatório Técnico"
                               style="padding: 4px 12px; font-size: 12px;">
                                <i class="fas fa-clipboard-list"></i> Iniciar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer" style="padding: 10px 16px; border-top: 1px solid rgba(243,156,18,0.15); text-align: right;">
        <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-sm" style="background: #f39c12; color: #000;">
            <i class="fas fa-arrow-right"></i> Ver todos os agendamentos
            </a>
    </div>
</div>
<?php endif; ?>
<?php endif; ?>
<!-- Tabela de Vistorias Recentes -->
<section class="table-section">
    <div class="table-header">
        <div>
            <div class="card-title">Vistorias recentes</div>
            <div class="card-subtitle">Últimas atividades do mês</div>
        </div>
        <a href="<?php echo APP_URL; ?>vistorias" class="btn-link">
            Ver todas <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>EMBARCAÇÃO</th>
                <th>PESSOA</th>
                <th>STATUS</th>
                <th>VISTORIADOR</th>
                <th>DATA</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($vistorias_recentes as $v): ?>
            <tr>
                <td class="td-primary"><?= htmlspecialchars($v['embarcacao']) ?></td>
                <td><?= $v['pessoa'] ? htmlspecialchars($v['pessoa']) : '<span class="em-dash">—</span>' ?></td>
                <td>
                    <span class="badge badge--<?= strtolower($v['status']) ?>">
                        <span class="badge-dot"></span>
                        <?= ucfirst(strtolower($v['status'])) ?>
                    </span>
                </td>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar"><?= strtoupper(substr($v['vistoriador'],0,1)) ?></div>
                        <span><?= htmlspecialchars($v['vistoriador']) ?></span>
                    </div>
                </td>
                <td class="td-secondary"><?= date('d/m/Y', strtotime($v['data'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>