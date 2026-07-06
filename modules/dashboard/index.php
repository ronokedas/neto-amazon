<?php
/**
 * MODULO: DASHBOARD
 * Arquivo: index.php - Painel inicial com cards de resumo
 */

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireLogin();

$cargo = getCargo();
$is_admin = ($cargo === 'ADMIN');
$is_vistoriador = ($cargo === 'VISTORIADOR');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracoes (
        chave VARCHAR(100) NOT NULL PRIMARY KEY,
        valor TEXT NOT NULL,
        descricao VARCHAR(255) DEFAULT NULL,
        atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
    $pdo->exec("INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES ('meta_mensal', '50000.00', 'Meta mensal de faturamento comercial em R$')");
} catch (Exception $e) {
}

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

$total_embarcacoes = 0;
$total_proprietarios = 0;
$total_vistorias_mes = 0;
$vistorias_pendentes = 0;
$total_receitas = 0.00;
$total_despesas = 0.00;
$propostas_mes = 0;
$valor_propostas_mes = 0.00;
$perc_meta = 0;
$vistorias_recentes = [];
$vistorias_pendentes_lista = [];
$agendamentos_pendentes_lista = [];
$relatorios_aprovacao_lista = [];
$total_relatorios_aprovacao = 0;
$meus_agendamentos_abertos = 0;
$labels_6meses = [];
$receitas_6meses = [];
$despesas_6meses = [];

try {
    if ($is_vistoriador) {
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT e.id) as total
            FROM embarcacoes e
            INNER JOIN agendamentos a ON a.embarcacao_id = e.id
            WHERE e.ativo = 1
              AND a.vistoriador_id = :vistoriador_id
        ");
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        $total_embarcacoes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT c.id) as total
            FROM clientes c
            INNER JOIN agendamentos a ON a.cliente_id = c.id
            WHERE c.perfil = 'proprietario'
              AND c.status = 'ATIVO'
              AND a.vistoriador_id = :vistoriador_id
        ");
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        $total_proprietarios = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM agendamentos a
            LEFT JOIN vistorias v ON v.agendamento_id = a.id
            WHERE a.vistoriador_id = :vistoriador_id
              AND a.status IN ('pendente', 'confirmado', 'em_andamento')
              AND v.id IS NULL
        ");
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        $meus_agendamentos_abertos = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM embarcacoes WHERE ativo = 1");
        $total_embarcacoes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->query("SELECT COUNT(*) as total FROM clientes WHERE perfil = 'proprietario' AND status = 'ATIVO'");
        $total_proprietarios = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }

    $sql_vistorias_mes = "
        SELECT COUNT(*) as total
        FROM vistorias v
        LEFT JOIN agendamentos a ON v.agendamento_id = a.id
        WHERE MONTH(v.data_vistoria) = MONTH(CURRENT_DATE())
          AND YEAR(v.data_vistoria) = YEAR(CURRENT_DATE())
    ";
    if ($cargo === 'VISTORIADOR') {
        $sql_vistorias_mes .= " AND a.vistoriador_id = :vistoriador_id";
        $stmt = $pdo->prepare($sql_vistorias_mes);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_vistorias_mes);
    }
    $total_vistorias_mes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    $sql_pendentes = "
        SELECT COUNT(*) as total
        FROM vistorias v
        LEFT JOIN agendamentos a ON v.agendamento_id = a.id
        WHERE v.status = 'PENDENTE'
    ";
    if ($cargo === 'VISTORIADOR') {
        $sql_pendentes .= " AND a.vistoriador_id = :vistoriador_id";
        $stmt = $pdo->prepare($sql_pendentes);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_pendentes);
    }
    $vistorias_pendentes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

    try {
        $sql_recentes = "
            SELECT v.data_vistoria AS data,
                   e.nome AS embarcacao,
                   cl.nome AS proprietario,
                   v.status,
                   u.nome AS vistoriador
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN clientes cl ON v.pessoa_id = cl.id
            LEFT JOIN usuarios u ON v.criado_por = u.id
        ";
        if ($cargo === 'VISTORIADOR') {
            $sql_recentes .= " WHERE EXISTS (
                SELECT 1
                FROM agendamentos a
                WHERE a.id = v.agendamento_id
                  AND a.vistoriador_id = :vistoriador_id
            )";
        }
        $sql_recentes .= " ORDER BY v.criado_em DESC LIMIT 5";

        $stmt = $pdo->prepare($sql_recentes);
        $stmt->execute($cargo === 'VISTORIADOR' ? [':vistoriador_id' => $_SESSION['usuario_id']] : []);
        $vistorias_recentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vistorias_recentes = [];
    }

    try {
        $sql_pendentes_lista = "
            SELECT v.id,
                   v.data_vistoria,
                   e.nome AS embarcacao,
                   cl.nome AS proprietario,
                   v.status
            FROM vistorias v
            INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN clientes cl ON v.pessoa_id = cl.id
            WHERE v.status = 'PENDENTE'
        ";
        if ($cargo === 'VISTORIADOR') {
            $sql_pendentes_lista = str_replace(
                "WHERE v.status = 'PENDENTE'",
                "WHERE v.status IN ('PENDENTE', 'AGUARDANDO_APROVACAO')",
                $sql_pendentes_lista
            );
            $sql_pendentes_lista .= " AND EXISTS (
                SELECT 1
                FROM agendamentos a
                WHERE a.id = v.agendamento_id
                  AND a.vistoriador_id = :vistoriador_id
            )";
        }
        $sql_pendentes_lista .= " ORDER BY v.data_vistoria ASC LIMIT 5";

        $stmt = $pdo->prepare($sql_pendentes_lista);
        $stmt->execute($cargo === 'VISTORIADOR' ? [':vistoriador_id' => $_SESSION['usuario_id']] : []);
        $vistorias_pendentes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $vistorias_pendentes_lista = [];
    }

    if ($is_admin) {
        try {
            $sql_aprovacao_lista = "
                SELECT v.id,
                       v.numero,
                       v.agendamento_id,
                       v.data_vistoria,
                       COALESCE(v.atualizado_em, v.criado_em) AS data_envio,
                       e.nome AS embarcacao,
                       cl.nome AS proprietario,
                       u.nome AS vistoriador
                FROM vistorias v
                INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
                LEFT JOIN clientes cl ON v.pessoa_id = cl.id
                INNER JOIN agendamentos a ON v.agendamento_id = a.id
                INNER JOIN usuarios u ON a.vistoriador_id = u.id
                WHERE v.status = 'AGUARDANDO_APROVACAO'
                ORDER BY COALESCE(v.atualizado_em, v.criado_em) ASC
                LIMIT 5
            ";
            $stmt = $pdo->prepare($sql_aprovacao_lista);
            $stmt->execute();
            $relatorios_aprovacao_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stmtCountAprovacao = $pdo->query("
                SELECT COUNT(*)
                FROM vistorias v
                INNER JOIN embarcacoes e ON v.embarcacao_id = e.id
                INNER JOIN agendamentos a ON v.agendamento_id = a.id
                INNER JOIN usuarios u ON a.vistoriador_id = u.id
                WHERE v.status = 'AGUARDANDO_APROVACAO'
            ");
            $total_relatorios_aprovacao = (int)$stmtCountAprovacao->fetchColumn();
        } catch (Exception $e) {
            $relatorios_aprovacao_lista = [];
            $total_relatorios_aprovacao = 0;
        }
    }

    try {
        if ($cargo === 'VISTORIADOR') {
            $sql_ag_pendentes = "
                SELECT a.id,
                       a.created_at,
                       a.data_vistoria,
                       a.hora_vistoria,
                       a.vistoriador_id,
                       e.nome AS embarcacao,
                       cl.nome AS proprietario,
                       a.status
                FROM agendamentos a
                INNER JOIN embarcacoes e ON a.embarcacao_id = e.id
                LEFT JOIN clientes cl ON a.cliente_id = cl.id
                LEFT JOIN vistorias v ON v.agendamento_id = a.id
                WHERE a.vistoriador_id = :vistoriador_id
                  AND a.status IN ('pendente', 'confirmado', 'em_andamento')
                  AND v.id IS NULL
                ORDER BY
                    CASE
                        WHEN a.data_vistoria IS NULL THEN 2
                        WHEN a.data_vistoria < CURDATE() THEN 1
                        ELSE 0
                    END,
                    a.data_vistoria ASC,
                    a.hora_vistoria ASC,
                    a.created_at DESC
                LIMIT 10
            ";
            $stmt = $pdo->prepare($sql_ag_pendentes);
            $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
        } else {
            $sql_ag_pendentes = "
                SELECT a.id,
                       a.created_at,
                       a.data_vistoria,
                       a.hora_vistoria,
                       a.vistoriador_id,
                       e.nome AS embarcacao,
                       cl.nome AS proprietario,
                       a.status
                FROM agendamentos a
                INNER JOIN embarcacoes e ON a.embarcacao_id = e.id
                LEFT JOIN clientes cl ON a.cliente_id = cl.id
                WHERE a.status = 'pendente'
                  AND (a.vistoriador_id IS NULL OR a.data_vistoria IS NULL)
                ORDER BY a.created_at DESC
                LIMIT 10
            ";
            $stmt = $pdo->prepare($sql_ag_pendentes);
            $stmt->execute();
        }
        $agendamentos_pendentes_lista = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $agendamentos_pendentes_lista = [];
    }

    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM propostas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $propostas_mes = (int)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $stmt = $pdo->query("SELECT COALESCE(SUM(valor_total), 0) as total FROM propostas WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
        $valor_propostas_mes = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        $propostas_mes = 0;
        $valor_propostas_mes = 0.00;
    }

    $perc_meta = ($meta_mensal_valor > 0) ? round(($valor_propostas_mes / $meta_mensal_valor) * 100, 1) : 0;

    if ($is_admin) {
        try {
            $stmt = $pdo->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'RECEITA' AND MONTH(data) = MONTH(CURRENT_DATE()) AND YEAR(data) = YEAR(CURRENT_DATE())");
            $total_receitas = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];

            $stmt = $pdo->query("SELECT COALESCE(SUM(valor), 0) as total FROM financeiro_lancamentos WHERE tipo = 'DESPESA' AND MONTH(data) = MONTH(CURRENT_DATE()) AND YEAR(data) = YEAR(CURRENT_DATE())");
            $total_despesas = (float)$stmt->fetch(PDO::FETCH_ASSOC)['total'];
        } catch (Exception $e) {
            $total_receitas = 0.00;
            $total_despesas = 0.00;
        }

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
            $labels_6meses = [];
            $receitas_6meses = [];
            $despesas_6meses = [];
        }
    }
} catch (Exception $e) {
}

$dias_portugues = [
    'Sunday' => 'Domingo',
    'Monday' => 'Segunda-feira',
    'Tuesday' => 'Terça-feira',
    'Wednesday' => 'Quarta-feira',
    'Thursday' => 'Quinta-feira',
    'Friday' => 'Sexta-feira',
    'Saturday' => 'Sábado',
];

$titulo_page = 'Dashboard - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="main-content" id="mainContent">
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">
                Bem-vindo, <?= h($_SESSION['usuario_nome'] ?? 'Usuário') ?> ·
                <?= $dias_portugues[date('l')] ?? date('l') ?>, <?= date('d/m/Y') ?>
            </p>
        </div>
        <div class="page-actions">
            <a href="<?= APP_URL ?>agendamentos/form" class="btn-secondary">
                <i class="fa-solid fa-plus"></i> Novo Agendamento
            </a>
            <a href="<?= APP_URL ?>vistorias/nova" class="btn-primary">
                Nova Vistoria <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>

    <?php if ($is_admin && $total_relatorios_aprovacao > 0): ?>
        <section class="approval-priority-card" style="margin-bottom: 24px; padding: 18px 20px; border: 1px solid rgba(248, 81, 73, 0.55); border-left: 5px solid #f85149; border-radius: 14px; background: linear-gradient(135deg, rgba(248, 81, 73, 0.18), rgba(243, 156, 18, 0.10)); box-shadow: 0 18px 45px rgba(248, 81, 73, 0.14);">
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 18px; flex-wrap: wrap;">
                <div style="display: flex; gap: 14px; align-items: flex-start; min-width: 260px; flex: 1;">
                    <div style="width: 42px; height: 42px; border-radius: 12px; display: grid; place-items: center; background: rgba(248, 81, 73, 0.18); color: #ff8b81; flex-shrink: 0;">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <div style="font-size: 12px; font-weight: 800; letter-spacing: 0.08em; text-transform: uppercase; color: #ff8b81; margin-bottom: 4px;">Prioridade máxima</div>
                        <h2 style="margin: 0; color: var(--cor-texto); font-size: 1.25rem; line-height: 1.25;">
                            <?= $total_relatorios_aprovacao ?> relatório<?= $total_relatorios_aprovacao === 1 ? '' : 's' ?> aguardando aprovação do admin
                        </h2>
                        <p style="margin: 6px 0 0; color: var(--cor-texto-secundario);">
                            Revise estes relatórios antes de seguir para certificados e assinatura.
                        </p>
                    </div>
                </div>
                <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" class="btn-primary" style="white-space: nowrap; text-decoration: none;">
                    Aprovar relatórios <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>

            <div style="display: grid; gap: 10px; margin-top: 16px;">
                <?php foreach ($relatorios_aprovacao_lista as $rel): ?>
                    <a href="<?= APP_URL ?>documentacao/aprovacao_relatorios" style="display: grid; grid-template-columns: minmax(160px, 1.4fr) minmax(120px, 1fr) auto; gap: 12px; align-items: center; padding: 12px 14px; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; background: rgba(0,0,0,0.16); color: inherit; text-decoration: none;">
                        <div>
                            <strong style="display: block; color: var(--cor-texto);"><?= h($rel['embarcacao'] ?? 'Embarcação não informada') ?></strong>
                            <small style="color: var(--cor-texto-secundario);"><?= h($rel['proprietario'] ?? 'Proprietário não informado') ?></small>
                        </div>
                        <div>
                            <small style="display: block; color: var(--cor-texto-secundario);">Vistoriador</small>
                            <span style="color: var(--cor-texto);"><?= h($rel['vistoriador'] ?? 'Não informado') ?></span>
                        </div>
                        <div style="text-align: right;">
                            <small style="display: block; color: #f39c12;">Enviado</small>
                            <span style="color: var(--cor-texto);"><?= !empty($rel['data_envio']) ? formatarDataCompleta($rel['data_envio']) : '-' ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <div class="kpi-grid">
        <?php if ($is_vistoriador): ?>
            <div class="kpi-card kpi-card--inspections">
                <div class="kpi-topline">
                    <div class="kpi-label">MEUS AGENDAMENTOS</div>
                    <div class="kpi-icon"><i class="fa-solid fa-calendar-check"></i></div>
                </div>
                <div class="kpi-value"><?= number_format($meus_agendamentos_abertos, 0, ',', '.') ?></div>
                <div class="kpi-delta kpi-delta--warn">
                    <i class="fa-solid fa-clock"></i>
                    Pendentes, confirmados e em andamento
                </div>
            </div>
        <?php else: ?>
            <div class="kpi-card kpi-card--revenue">
                <div class="kpi-topline">
                    <div class="kpi-label">RECEITAS DO M&Ecirc;S</div>
                    <div class="kpi-icon"><i class="fa-solid fa-sack-dollar"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($total_receitas, 2, ',', '.') ?></div>
                <div class="kpi-delta kpi-delta--up">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    Receita registrada no m&ecirc;s
                </div>
            </div>
        <?php endif; ?>

        <div class="kpi-card kpi-card--inspections">
            <div class="kpi-topline">
                <div class="kpi-label">VISTORIAS DO M&Ecirc;S</div>
                <div class="kpi-icon"><i class="fa-solid fa-clipboard-check"></i></div>
            </div>
            <div class="kpi-value"><?= number_format($total_vistorias_mes, 0, ',', '.') ?></div>
            <div class="kpi-delta kpi-delta--warn">
                <i class="fa-solid fa-clock"></i>
                <?= number_format($vistorias_pendentes, 0, ',', '.') ?> pendentes
            </div>
        </div>

        <?php if ($is_vistoriador): ?>
            <div class="kpi-card kpi-card--proposals">
                <div class="kpi-topline">
                    <div class="kpi-label">MINHAS EMBARCA&Ccedil;&Otilde;ES</div>
                    <div class="kpi-icon"><i class="fa-solid fa-ship"></i></div>
                </div>
                <div class="kpi-value"><?= number_format($total_embarcacoes, 0, ',', '.') ?></div>
                <div class="kpi-delta kpi-delta--up">
                    <i class="fa-solid fa-clipboard-check"></i>
                    Com agendamento vinculado a voc&ecirc;
                </div>
            </div>

            <div class="kpi-card kpi-card--goal">
                <div class="kpi-topline">
                    <div class="kpi-label">META ATINGIDA</div>
                    <div class="kpi-icon"><i class="fa-solid fa-bullseye"></i></div>
                </div>
                <div class="kpi-value"><?= $perc_meta ?>%</div>
                <div class="kpi-meta-bar">
                    <div class="kpi-meta-fill" style="width: <?= min($perc_meta, 100) ?>%"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="kpi-card kpi-card--proposals">
                <div class="kpi-topline">
                    <div class="kpi-label">PROPOSTAS DO M&Ecirc;S</div>
                    <div class="kpi-icon"><i class="fa-solid fa-file-invoice-dollar"></i></div>
                </div>
                <div class="kpi-value">R$ <?= number_format($valor_propostas_mes, 2, ',', '.') ?></div>
                <div class="kpi-delta kpi-delta--up">
                    <i class="fa-solid fa-arrow-trend-up"></i>
                    <?= $propostas_mes ?> propostas
                </div>
            </div>

            <div class="kpi-card kpi-card--goal">
                <div class="kpi-topline">
                    <div class="kpi-label">META ATINGIDA</div>
                    <div class="kpi-icon"><i class="fa-solid fa-bullseye"></i></div>
                </div>
                <div class="kpi-value"><?= $perc_meta ?>%</div>
                <div class="kpi-meta-bar">
                    <div class="kpi-meta-fill" style="width: <?= min($perc_meta, 100) ?>%"></div>
                </div>
                <?php if ($cargo !== 'VENDEDOR'): ?>
                    <div class="kpi-delta kpi-delta--up">
                        <i class="fa-solid fa-arrow-trend-up"></i>
                        Meta R$ <?= number_format($meta_mensal_valor, 0, ',', '.') ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="stats-secondary">
        <span><?= $is_vistoriador ? 'Minhas embarca&ccedil;&otilde;es' : 'Embarca&ccedil;&otilde;es' ?> <strong><?= number_format($total_embarcacoes, 0, ',', '.') ?></strong></span>
        <span class="dot">&middot;</span>
        <span><?= $is_vistoriador ? 'Propriet&aacute;rios vinculados' : 'Propriet&aacute;rios' ?> <strong><?= number_format($total_proprietarios, 0, ',', '.') ?></strong></span>
        <?php if (!$is_vistoriador): ?>
            <span class="dot">&middot;</span>
            <span>Despesas <strong>R$ <?= number_format($total_despesas, 2, ',', '.') ?></strong></span>
            <span class="dot">&middot;</span>
            <span>Valor propostas <strong>R$ <?= number_format($valor_propostas_mes, 2, ',', '.') ?></strong></span>
        <?php endif; ?>
    </div>
    <div class="dashboard-grid <?= $is_admin && !empty($labels_6meses) ? 'dashboard-grid--admin' : 'dashboard-grid--compact' ?>">
        <?php if ($is_admin && !empty($labels_6meses)): ?>
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
                <canvas id="chartReceitasDespesas" style="display:block; width:100%; height:220px; max-height:220px;"></canvas>
            </div>
        <?php endif; ?>

        <div class="pending-card">
            <div class="card-header">
                <div>
                    <div class="card-title"><?= $cargo === 'VISTORIADOR' ? 'Meus agendamentos' : 'Agendamentos pendentes' ?></div>
                    <div class="card-subtitle"><?= $cargo === 'VISTORIADOR' ? 'Ainda sem relatório' : 'Sem vistoriador ou data' ?></div>
                </div>
                <i class="fa-solid fa-calendar-times" style="color: var(--status-pending)"></i>
            </div>

            <div class="pending-list">
                <?php if (empty($agendamentos_pendentes_lista)): ?>
                    <div class="pending-empty">Nenhum agendamento pendente.</div>
                <?php endif; ?>

                <?php foreach ($agendamentos_pendentes_lista as $ag): ?>
                    <?php
                    $linkAgendamento = $cargo === 'VISTORIADOR'
                        ? APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($ag['id'])
                        : APP_URL . 'agendamentos/form?id=' . urlencode($ag['id']);
                    $tituloAgendamento = $cargo === 'VISTORIADOR'
                        ? 'Abrir relatório deste agendamento'
                        : 'Clique para definir data e vistoriador';
                    ?>
                    <a href="<?= $linkAgendamento ?>" class="pending-item" title="<?= h($tituloAgendamento) ?>">
                        <div class="pending-info">
                            <span class="pending-embarcacao"><?= h($ag['embarcacao'] ?? 'Embarcação não informada') ?></span>
                            <span class="pending-pessoa"><?= h($ag['proprietario'] ?? 'Não informado') ?></span>
                            <?php
                            if ($cargo === 'VISTORIADOR' && !empty($ag['data_vistoria'])) {
                                $textoAgendamento = 'Agendado para ' . formatarData($ag['data_vistoria']);
                                if (!empty($ag['hora_vistoria'])) {
                                    $textoAgendamento .= ' às ' . substr($ag['hora_vistoria'], 0, 5);
                                }
                            } else {
                                $faltasAgendamento = [];
                                if (empty($ag['vistoriador_id'])) {
                                    $faltasAgendamento[] = 'vistoriador';
                                }
                                if (empty($ag['data_vistoria'])) {
                                    $faltasAgendamento[] = 'data';
                                }
                                $textoAgendamento = !empty($faltasAgendamento)
                                    ? 'Definir ' . implode(' e ', $faltasAgendamento)
                                    : 'Pendente';
                            }
                            ?>
                            <span style="font-size: 11px; color: #f39c12; margin-top: 4px; display: block;">
                                <i class="fas fa-calendar"></i> <?= h($textoAgendamento) ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="pending-card">
            <div class="card-header">
                <div>
                    <div class="card-title">Vistorias em andamento</div>
                    <div class="card-subtitle">Top 5 mais antigas</div>
                </div>
                <i class="fa-solid fa-clipboard-list" style="color: var(--status-pending)"></i>
            </div>

            <div class="pending-list">
                <?php if (empty($vistorias_pendentes_lista)): ?>
                    <div class="pending-empty">Nenhuma vistoria pendente.</div>
                <?php endif; ?>

                <?php foreach ($vistorias_pendentes_lista as $v): ?>
                    <a href="<?= APP_URL ?>vistorias/detalhe?id=<?= urlencode($v['id']) ?>" class="pending-item" title="Ver vistoria">
                        <div class="pending-info">
                            <span class="pending-embarcacao"><?= h($v['embarcacao'] ?? 'Embarcação não informada') ?></span>
                            <span class="pending-pessoa"><?= h($v['proprietario'] ?? 'Não informado') ?></span>
                            <span style="font-size: 11px; color: #17a2b8; margin-top: 4px; display: block;">
                                <i class="fas fa-clock"></i> <?= h($v['status'] ?? 'PENDENTE') ?>
                            </span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section class="table-section">
        <div class="table-header">
            <div>
                <div class="card-title">Vistorias recentes</div>
                <div class="card-subtitle">Últimas atividades do mês</div>
            </div>
            <a href="<?= APP_URL ?>vistorias" class="btn-link">
                Ver todas <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>EMBARCAÇÃO</th>
                    <th>PROPRIETÁRIO</th>
                    <th>STATUS</th>
                    <th>VISTORIADOR</th>
                    <th>DATA</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vistorias_recentes)): ?>
                    <tr>
                        <td colspan="5" class="td-secondary">Nenhuma vistoria recente encontrada.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($vistorias_recentes as $v): ?>
                    <tr>
                        <td class="td-primary"><?= h($v['embarcacao'] ?? '-') ?></td>
                        <td><?= !empty($v['proprietario']) ? h($v['proprietario']) : '<span class="em-dash">—</span>' ?></td>
                        <td>
                            <span class="badge badge--<?= h(strtolower($v['status'] ?? 'pendente')) ?>">
                                <span class="badge-dot"></span>
                                <?= h(ucfirst(strtolower($v['status'] ?? 'pendente'))) ?>
                            </span>
                        </td>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?= !empty($v['vistoriador']) ? h(strtoupper(substr($v['vistoriador'], 0, 1))) : '-' ?></div>
                                <span><?= h($v['vistoriador'] ?? 'Não definido') ?></span>
                            </div>
                        </td>
                        <td class="td-secondary">
                            <?= !empty($v['data']) ? date('d/m/Y', strtotime($v['data'])) : '-' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</div>

<?php if ($is_admin && !empty($labels_6meses)): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('chartReceitasDespesas');
    if (!canvas || typeof Chart === 'undefined') {
        return;
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels_6meses, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [
                {
                    label: 'Receitas',
                    data: <?= json_encode($receitas_6meses) ?>,
                    borderColor: '#56e0ad',
                    backgroundColor: 'rgba(86, 224, 173, 0.12)',
                    tension: 0.35,
                    fill: true
                },
                {
                    label: 'Despesas',
                    data: <?= json_encode($despesas_6meses) ?>,
                    borderColor: '#f85149',
                    backgroundColor: 'rgba(248, 81, 73, 0.08)',
                    tension: 0.35,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(230,237,243,0.62)' }
                },
                y: {
                    grid: { color: 'rgba(255,255,255,0.04)' },
                    ticks: { color: 'rgba(230,237,243,0.62)' }
                }
            }
        }
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
