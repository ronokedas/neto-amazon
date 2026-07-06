<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: index.php - Listagem de vistorias com filtro por status
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

// Filtro de status
$filtro_status = $_GET['status'] ?? '';
$cargo = getCargo();

// Buscar vistorias com JOINs para mostrar nomes
try {
    $sql = "SELECT v.id, v.data_vistoria, v.status, v.observacoes, v.criado_em, v.atualizado_em,
                   e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
                   p.nome AS pessoa_nome, p.cpf_cnpj AS pessoa_cpf,
                   u.nome AS criado_por_nome
            FROM vistorias v
            LEFT JOIN embarcacoes e ON v.embarcacao_id = e.id
            LEFT JOIN clientes p ON v.pessoa_id = p.id
            LEFT JOIN usuarios u ON v.criado_por = u.id
            LEFT JOIN agendamentos a ON v.agendamento_id = a.id";

    $params = [];
    $where_extra = '';

    if (getCargo() === 'VISTORIADOR') {
        $where_extra = " AND a.vistoriador_id = :vistoriador_id";
        $params[':vistoriador_id'] = $_SESSION['usuario_id'];
    } elseif (getCargo() === 'VENDEDOR') {
        $where_extra = " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
        $params[':vendedor_id'] = $_SESSION['usuario_id'];
        $params[':agend_vendedor_id'] = $_SESSION['usuario_id'];
    }

    if (!empty($filtro_status) && in_array($filtro_status, ['PENDENTE', 'APROVADA', 'REPROVADA', 'CANCELADA'])) {
        $sql .= " WHERE v.status = :status" . $where_extra;
        $params[':status'] = $filtro_status;
    } elseif ($where_extra !== '') {
        $sql .= " WHERE 1=1" . $where_extra;
    }

    $sql .= " ORDER BY v.criado_em DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $vistorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar vistorias: ' . $e->getMessage());
    $vistorias = [];
}

// Contadores para os cards de filtro
try {
    $sql_contadores = "SELECT v.status, COUNT(*) as total FROM vistorias v LEFT JOIN agendamentos a ON v.agendamento_id = a.id WHERE 1=1";
    if ($cargo === 'VISTORIADOR') {
        $sql_contadores .= " AND a.vistoriador_id = :vistoriador_id";
    } elseif ($cargo === 'VENDEDOR') {
        $sql_contadores .= " AND (a.vendedor_id = :vendedor_id OR a.id IN (SELECT id FROM agendamentos WHERE vendedor_id = :agend_vendedor_id))";
    }
    $sql_contadores .= " GROUP BY v.status";
    
    if ($cargo === 'VISTORIADOR') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':vistoriador_id' => $_SESSION['usuario_id']]);
    } elseif ($cargo === 'VENDEDOR') {
        $stmt = $pdo->prepare($sql_contadores);
        $stmt->execute([':vendedor_id' => $_SESSION['usuario_id'], ':agend_vendedor_id' => $_SESSION['usuario_id']]);
    } else {
        $stmt = $pdo->query($sql_contadores);
    }
    $contadores = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $contadores = [];
}
$total_geral = array_sum($contadores);

$titulo_page = 'Vistorias - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-clipboard-check"></i> Gerenciar Vistorias</h3>
            <a href="<?php echo APP_URL; ?>vistorias/nova" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Vistoria
            </a>
        </div>

        <!-- Cards de filtro por status -->
        <div style="display: flex; gap: 10px; margin: 15px 20px; flex-wrap: wrap;">
            <a href="<?php echo APP_URL; ?>vistorias" 
               class="btn btn-sm <?php echo empty($filtro_status) ? 'btn-primary' : 'btn-secondary'; ?>"
               style="text-decoration: none;">
                <i class="fas fa-list"></i> Todos 
                <span class="badge" style="background: rgba(255,255,255,0.2); margin-left: 4px;"><?php echo $total_geral; ?></span>
            </a>
            <a href="<?php echo APP_URL; ?>vistorias?status=PENDENTE" 
               class="btn btn-sm <?php echo $filtro_status === 'PENDENTE' ? 'btn-warning' : 'btn-secondary'; ?>"
               style="text-decoration: none;">
                <i class="fas fa-clock"></i> Pendentes 
                <span class="badge" style="background: rgba(255,255,255,0.2); margin-left: 4px;"><?php echo $contadores['PENDENTE'] ?? 0; ?></span>
            </a>
            <a href="<?php echo APP_URL; ?>vistorias?status=APROVADA" 
               class="btn btn-sm <?php echo $filtro_status === 'APROVADA' ? 'btn-success' : 'btn-secondary'; ?>"
               style="text-decoration: none;">
                <i class="fas fa-check-circle"></i> Aprovadas 
                <span class="badge" style="background: rgba(255,255,255,0.2); margin-left: 4px;"><?php echo $contadores['APROVADA'] ?? 0; ?></span>
            </a>
            <a href="<?php echo APP_URL; ?>vistorias?status=REPROVADA" 
               class="btn btn-sm <?php echo $filtro_status === 'REPROVADA' ? 'btn-danger' : 'btn-secondary'; ?>"
               style="text-decoration: none;">
                <i class="fas fa-times-circle"></i> Reprovadas 
                <span class="badge" style="background: rgba(255,255,255,0.2); margin-left: 4px;"><?php echo $contadores['REPROVADA'] ?? 0; ?></span>
            </a>
            <a href="<?php echo APP_URL; ?>vistorias?status=CANCELADA" 
               class="btn btn-sm <?php echo $filtro_status === 'CANCELADA' ? 'btn-secondary' : 'btn-secondary'; ?>"
               style="text-decoration: none;">
                <i class="fas fa-ban"></i> Canceladas 
                <span class="badge" style="background: rgba(255,255,255,0.2); margin-left: 4px;"><?php echo $contadores['CANCELADA'] ?? 0; ?></span>
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 0 20px 15px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar vistoria</label>
                <input type="text" 
                       id="buscaVistoria" 
                       placeholder="Embarcacao, pessoa ou registro..." 
                       onkeyup="filtrarTabela('buscaVistoria', 'tabelaVistorias')">
            </div>
        </div>

        <?php if (empty($vistorias)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-clipboard-check"></i>
                <h3>Nenhuma vistoria encontrada</h3>
                <p>Clique em "Nova Vistoria" para iniciar o cadastro.</p>
            </div>
        <?php else: ?>
            <table id="tabelaVistorias">
                <thead>
                    <tr>
                        <th>Embarcacao</th>
                        <th>Pessoa</th>
                        <th>Data</th>
                        <th>Status</th>
                        <th>Criado por</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vistorias as $v): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($v['embarcacao_nome'] ?? 'N/A'); ?></strong>
                            <?php if (!empty($v['embarcacao_registro'])): ?>
                                <br><small class="text-muted">Reg: <?php echo h($v['embarcacao_registro']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo h($v['pessoa_nome'] ?? 'N/A'); ?>
                            <?php if (!empty($v['pessoa_cpf'])): ?>
                                <br><small class="text-muted">CPF: <?php echo h(formatarCPF($v['pessoa_cpf'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarData($v['data_vistoria']); ?></td>
                        <td>
                            <?php
                            $badgeClass = 'badge-info';
                            $iconClass = 'fa-clock';
                            switch ($v['status']) {
                                                                case 'PENDENTE':
                                    $badgeClass = 'badge-warning';
                                    $iconClass = 'fa-clock';
                                    break;
                                case 'AGUARDANDO_APROVACAO':
                                    $badgeClass = 'badge-warning';
                                    $iconClass = 'fa-hourglass-half';
                                    break;
                                case 'APROVADA_COM_EXIGENCIAS':
                                    $badgeClass = 'badge-primary';
                                    $iconClass = 'fa-clipboard-check';
                                    break;
                                case 'APROVADA':
                                    $badgeClass = 'badge-success';
                                    $iconClass = 'fa-check-circle';
                                    break;
                                case 'REPROVADA':
                                    $badgeClass = 'badge-danger';
                                    $iconClass = 'fa-times-circle';
                                    break;
                                case 'CANCELADA':
                                    $badgeClass = 'badge-secondary';
                                    $iconClass = 'fa-ban';
                                    break;
                            }
                            ?>
                            <span class="badge <?php echo $badgeClass; ?>">
                                <i class="fas <?php echo $iconClass; ?>"></i> <?php echo h($v['status']); ?>
                            </span>
                        </td>
                        <td><?php echo h($v['criado_por_nome'] ?? 'N/A'); ?></td>
                        <td><?php echo formatarDataCompleta($v['criado_em']); ?></td>
                        <td>
                            <a href="<?php echo APP_URL; ?>vistorias/detalhe?id=<?php echo urlencode($v['id']); ?>" 
                               class="btn btn-secondary btn-sm" title="Ver detalhes">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Resumo -->
        <div class="card-footer" style="padding: 12px 20px;">
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Total: <?php echo count($vistorias); ?> vistoria(s) | 
                <?php
                $pendentes = count(array_filter($vistorias, function($v) { return $v['status'] === 'PENDENTE'; }));
                $aprovadas = count(array_filter($vistorias, function($v) { return $v['status'] === 'APROVADA'; }));
                $reprovadas = count(array_filter($vistorias, function($v) { return $v['status'] === 'REPROVADA'; }));
                echo $pendentes . ' pendente(s), ' . $aprovadas . ' aprovada(s), ' . $reprovadas . ' reprovada(s)';
                ?>
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>