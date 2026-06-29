<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: index.php - Listagem de agendamentos (ADMIN ve todos, VISTORIADOR ve apenas os atribuidos a ele)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao
verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$usuario_id = $_SESSION['usuario_id'];

// Filtros via GET
$filtro_status    = $_GET['status'] ?? '';
$filtro_data      = $_GET['data'] ?? '';
$filtro_cliente   = $_GET['cliente'] ?? '';
$filtro_embarcacao = $_GET['embarcacao'] ?? '';
$busca            = $_GET['busca'] ?? '';

try {
    $where = [];
    $params = [];

    // VISTORIADOR ve apenas os agendamentos onde ele e o vistoriador
    if ($cargo === 'VISTORIADOR') {
        $where[] = "a.vistoriador_id = :vistoriador_id";
        $params[':vistoriador_id'] = $usuario_id;
    }

    if (!empty($filtro_status)) {
        $where[] = "a.status = :status";
        $params[':status'] = $filtro_status;
    }

    if (!empty($filtro_data)) {
        $where[] = "a.data_vistoria = :data_vistoria";
        $params[':data_vistoria'] = $filtro_data;
    }

    if (!empty($filtro_cliente)) {
        $where[] = "c.nome LIKE :cliente_nome";
        $params[':cliente_nome'] = '%' . $filtro_cliente . '%';
    }

    if (!empty($filtro_embarcacao)) {
        $where[] = "e.nome LIKE :emb_nome";
        $params[':emb_nome'] = '%' . $filtro_embarcacao . '%';
    }

    if (!empty($busca)) {
        $where[] = "(c.nome LIKE :busca1 OR e.nome LIKE :busca2 OR a.tipo_vistoria LIKE :busca3 OR a.local LIKE :busca4)";
        $params[':busca1'] = '%' . $busca . '%';
        $params[':busca2'] = '%' . $busca . '%';
        $params[':busca3'] = '%' . $busca . '%';
        $params[':busca4'] = '%' . $busca . '%';
    }

    $sql = "
        SELECT a.*, 
               c.nome AS cliente_nome,
               e.nome AS embarcacao_nome,
               u.nome AS vistoriador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status,
               v.status AS vistoria_status
        FROM agendamentos a
        LEFT JOIN vistorias v ON v.agendamento_id = a.id
        INNER JOIN clientes c ON a.cliente_id = c.id
        INNER JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN usuarios u ON a.vistoriador_id = u.id
        LEFT JOIN ordens_servico os ON os.agendamento_id = a.id
    ";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    $sql .= " ORDER BY a.data_vistoria DESC, a.hora_vistoria ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $agendamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Carregar vistoriadores para o filtro (apenas ADMIN)
    $vistoriadores = [];
    if ($cargo === 'ADMIN') {
        $stmtV = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 AND cargo = 'VISTORIADOR' ORDER BY nome ASC");
        $vistoriadores = $stmtV->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (Exception $e) {
    error_log('Erro ao listar agendamentos: ' . $e->getMessage());
    $agendamentos = [];
    $vistoriadores = [];
}

// Labels de status com cores
$status_labels = [
    'pendente'     => ['label' => 'Pendente',     'class' => 'badge-warning'],
    'confirmado'   => ['label' => 'Confirmado',   'class' => 'badge-primary'],
    'em_andamento' => ['label' => 'Em Andamento',  'class' => 'badge-info'],
    'concluido'    => ['label' => 'Concluído',     'class' => 'badge-success'],
    'cancelado'    => ['label' => 'Cancelado',     'class' => 'badge-danger'],
];

$os_status_labels = [
    'pendente'     => ['label' => 'OS Pendente',   'class' => 'badge-warning'],
    'em_andamento' => ['label' => 'OS Em Andamento','class' => 'badge-info'],
    'executado'    => ['label' => 'OS Executada',   'class' => 'badge-success'],
    'cancelado'    => ['label' => 'OS Cancelada',   'class' => 'badge-danger'],
];

$titulo_page = 'Agendamentos - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-calendar-check"></i> Gerenciar Agendamentos</h3>
            <a href="<?php echo APP_URL; ?>agendamentos/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Agendamento
            </a>
        </div>

        <!-- Filtros -->
        <div class="filtros" style="margin: 15px 20px; display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end;">
            <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 200px;">
                <label><i class="fas fa-search"></i> Buscar</label>
                <input type="text" 
                       id="buscaAgendamento" 
                       value="<?php echo h($busca); ?>"
                       placeholder="Cliente, embarcação, tipo ou local..."
                       onkeyup="filtrarTabela('buscaAgendamento', 'tabelaAgendamentos')">
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label>Status</label>
                <select id="filtroStatus" onchange="filtrarPorStatus(this.value)">
                    <option value="">Todos</option>
                    <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="confirmado" <?php echo $filtro_status === 'confirmado' ? 'selected' : ''; ?>>Confirmado</option>
                    <option value="em_andamento" <?php echo $filtro_status === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                    <option value="concluido" <?php echo $filtro_status === 'concluido' ? 'selected' : ''; ?>>Concluído</option>
                    <option value="cancelado" <?php echo $filtro_status === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom: 0; min-width: 150px;">
                <label>Data</label>
                <input type="date" id="filtroData" value="<?php echo h($filtro_data); ?>" 
                       onchange="filtrarPorData(this.value)">
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <button type="button" class="btn btn-secondary btn-sm" onclick="limparFiltros()">
                    <i class="fas fa-times"></i> Limpar
                </button>
            </div>
        </div>

        <?php if (empty($agendamentos)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-calendar-check"></i>
                <h3>Nenhum agendamento encontrado</h3>
                <p>Clique em "Novo Agendamento" para criar o primeiro.</p>
            </div>
        <?php else: ?>
            <table id="tabelaAgendamentos">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Cliente</th>
                        <th>Embarcação</th>
                        <th>Tipo de Vistoria</th>
                        <th>Vistoriador</th>
                        <th>Status</th>
                        <th>OS</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agendamentos as $a): ?>
                    <tr>
                        <td>
                            <strong><?php echo formatarData($a['data_vistoria']); ?></strong>
                            <?php if ($a['hora_vistoria']): ?>
                                <br><small><?php echo h(substr($a['hora_vistoria'], 0, 5)); ?></small>
                            <?php endif; ?>
                            <?php if ($a['local']): ?>
                                <br><small class="text-muted"><i class="fas fa-map-marker-alt"></i> <?php echo h($a['local']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo h($a['cliente_nome']); ?></td>
                        <td><?php echo h($a['embarcacao_nome']); ?></td>
                        <td><?php echo h($a['tipo_vistoria']); ?></td>
                        <td><?php echo h($a['vistoriador_nome'] ?? '-'); ?></td>
                        <td>
                            <?php 
                            $st = $status_labels[$a['status']] ?? ['label' => $a['status'], 'class' => 'badge-secondary'];
                            ?>
                            <span class="badge <?php echo $st['class']; ?>"><?php echo $st['label']; ?></span>
                        </td>
                        <td>
                            <?php if (!empty($a['os_id'])): ?>
                                <a href="<?php echo APP_URL; ?>agendamentos/os?id=<?php echo urlencode($a['os_id']); ?>" 
                                   class="badge <?php echo ($os_status_labels[$a['os_status']]['class'] ?? 'badge-secondary'); ?>"
                                   style="text-decoration: none; cursor: pointer;"
                                   title="Visualizar Ordem de Serviço">
                                    <?php echo h($a['os_numero']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <?php if ($cargo !== 'VISTORIADOR'): ?>
<a href="<?php echo APP_URL; ?>agendamentos/form?id=<?php echo urlencode($a['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($a['status'] === 'pendente' && ($cargo === 'ADMIN' || $cargo === 'VENDEDOR')): ?>
                                <a href="<?php echo APP_URL; ?>agendamentos/actions?action=confirmar&id=<?php echo urlencode($a['id']); ?>" 
                                   class="btn btn-primary btn-sm" 
                                   title="Confirmar e gerar OS"
                                   onclick="return confirm('Confirmar agendamento e gerar Ordem de Serviço?')">
                                    <i class="fas fa-check-double"></i>
                                </a>
<?php endif; ?>
                                <?php endif; ?>
                                <a href="<?php echo APP_URL; ?>vistorias/relatorio?agendamento_id=<?php echo urlencode($a['id']); ?>" 
                                   class="btn btn-success btn-sm" 
                                   title="Relatório Técnico">
                                    <i class="fas fa-clipboard-list"></i>
                                </a>
                                <?php if (in_array($a['status'], ['pendente', 'confirmado']) && $cargo !== 'VISTORIADOR'): ?>
                                <a href="<?php echo APP_URL; ?>agendamentos/actions?action=cancelar&id=<?php echo urlencode($a['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Cancelar"
                                   onclick="return confirm('Tem certeza que deseja cancelar este agendamento?')">
                                    <i class="fas fa-ban"></i>
                                </a>
                                <?php endif; ?>
                            </div>
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
                Total: <?php echo count($agendamentos); ?> agendamento(s)
                <?php if ($cargo === 'VISTORIADOR'): ?>
                    — Exibindo apenas seus agendamentos
                <?php endif; ?>
            </small>
        </div>
    </div>
</div>

<script>
function filtrarPorStatus(status) {
    const url = new URL(window.location.href);
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    window.location.href = url.toString();
}

function filtrarPorData(data) {
    const url = new URL(window.location.href);
    if (data) {
        url.searchParams.set('data', data);
    } else {
        url.searchParams.delete('data');
    }
    window.location.href = url.toString();
}

function limparFiltros() {
    window.location.href = '<?php echo APP_URL; ?>agendamentos';
}

// Busca via Enter
document.getElementById('buscaAgendamento').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') {
        const url = new URL(window.location.href);
        if (this.value.trim()) {
            url.searchParams.set('busca', this.value.trim());
        } else {
            url.searchParams.delete('busca');
        }
        window.location.href = url.toString();
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>