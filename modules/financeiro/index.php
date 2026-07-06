<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: index.php - Listagem com filtros (data, tipo, categoria) e cards de resumo
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Filtros
$filtro_tipo      = $_GET['tipo'] ?? '';
$filtro_data_ini  = $_GET['data_ini'] ?? '';
$filtro_data_fim  = $_GET['data_fim'] ?? '';
$filtro_categoria = $_GET['categoria'] ?? '';

// Construir query com filtros
$sql = "SELECT id, tipo, descricao, valor, data, categoria, observacoes, criado_em, atualizado_em FROM financeiro_lancamentos WHERE ativo = 1";
$params = [];

if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
    $sql .= " AND tipo = :tipo";
    $params[':tipo'] = $filtro_tipo;
}

if (!empty($filtro_data_ini)) {
    $sql .= " AND data >= :data_ini";
    $params[':data_ini'] = $filtro_data_ini;
}

if (!empty($filtro_data_fim)) {
    $sql .= " AND data <= :data_fim";
    $params[':data_fim'] = $filtro_data_fim;
}

if (!empty($filtro_categoria)) {
    $sql .= " AND categoria LIKE :categoria";
    $params[':categoria'] = '%' . $filtro_categoria . '%';
}

$sql .= " ORDER BY data DESC, criado_em DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $lancamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar lancamentos: ' . $e->getMessage());
    $lancamentos = [];
}

// Calcular totais (usando os mesmos filtros)
try {
    $sqlTotais = "SELECT tipo, SUM(valor) as total FROM financeiro_lancamentos WHERE ativo = 1 AND status != 'CANCELADO'";
    $paramsTotais = [];

    if (!empty($filtro_tipo) && in_array($filtro_tipo, ['RECEITA', 'DESPESA'])) {
        $sqlTotais .= " AND tipo = :tipo";
        $paramsTotais[':tipo'] = $filtro_tipo;
    }
    if (!empty($filtro_data_ini)) {
        $sqlTotais .= " AND data >= :data_ini";
        $paramsTotais[':data_ini'] = $filtro_data_ini;
    }
    if (!empty($filtro_data_fim)) {
        $sqlTotais .= " AND data <= :data_fim";
        $paramsTotais[':data_fim'] = $filtro_data_fim;
    }
    if (!empty($filtro_categoria)) {
        $sqlTotais .= " AND categoria LIKE :categoria";
        $paramsTotais[':categoria'] = '%' . $filtro_categoria . '%';
    }

    $sqlTotais .= " GROUP BY tipo";
    $stmtTotais = $pdo->prepare($sqlTotais);
    $stmtTotais->execute($paramsTotais);
    $totais = $stmtTotais->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Exception $e) {
    $totais = [];
}

$totalReceitas = floatval($totais['RECEITA'] ?? 0);
$totalDespesas = floatval($totais['DESPESA'] ?? 0);
$saldo = $totalReceitas - $totalDespesas;

// Buscar categorias distintas para o filtro
try {
    $categorias = $pdo->query("SELECT DISTINCT categoria FROM financeiro_lancamentos WHERE categoria IS NOT NULL AND categoria != '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categorias = [];
}

$titulo_page = 'Financeiro - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-dollar-sign"></i> Financeiro</h3>
            <a href="<?php echo APP_URL; ?>financeiro/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Lancamento
            </a>
        </div>

        <!-- Cards de resumo -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 15px 20px;">
            <!-- Total Receitas -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid var(--cor-sucesso);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(46, 204, 113, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-arrow-up" style="color: var(--cor-sucesso); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Receitas</div>
                        <div style="color: var(--cor-sucesso); font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($totalReceitas); ?></div>
                    </div>
                </div>
            </div>

            <!-- Total Despesas -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid var(--cor-erro);">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: rgba(231, 76, 60, 0.15); display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-arrow-down" style="color: var(--cor-erro); font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Despesas</div>
                        <div style="color: var(--cor-erro); font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($totalDespesas); ?></div>
                    </div>
                </div>
            </div>

            <!-- Saldo -->
            <div style="background: linear-gradient(135deg, var(--cor-painel), var(--cor-sidebar)); border: 1px solid var(--cor-borda); border-radius: 12px; padding: 18px; border-left: 4px solid <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 45px; height: 45px; border-radius: 10px; background: <?php echo $saldo >= 0 ? 'rgba(46, 204, 113, 0.15)' : 'rgba(231, 76, 60, 0.15)'; ?>; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-balance-scale" style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>; font-size: 1.2rem;"></i>
                    </div>
                    <div>
                        <div style="color: var(--cor-texto-secundario); font-size: 0.8rem; font-weight: 500; text-transform: uppercase;">Saldo</div>
                        <div style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>; font-size: 1.4rem; font-weight: 700;"><?php echo formatarMoeda($saldo); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <form method="GET" action="<?php echo APP_URL; ?>financeiro" style="margin: 0 20px 15px;">
            <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end;">
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-tag"></i> Tipo</label>
                    <select name="tipo" style="width: 100%; padding: 8px 10px;">
                        <option value="">Todos</option>
                        <option value="RECEITA" <?php echo $filtro_tipo === 'RECEITA' ? 'selected' : ''; ?>>Receita</option>
                        <option value="DESPESA" <?php echo $filtro_tipo === 'DESPESA' ? 'selected' : ''; ?>>Despesa</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Data Inicio</label>
                    <input type="date" name="data_ini" value="<?php echo h($filtro_data_ini); ?>" style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-calendar"></i> Data Fim</label>
                    <input type="date" name="data_fim" value="<?php echo h($filtro_data_fim); ?>" style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0; flex: 1; min-width: 120px;">
                    <label style="font-size: 0.8rem;"><i class="fas fa-folder"></i> Categoria</label>
                    <input type="text" name="categoria" value="<?php echo h($filtro_categoria); ?>" placeholder="Buscar..." style="width: 100%; padding: 8px 10px;">
                </div>
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
                <?php if (!empty($filtro_tipo) || !empty($filtro_data_ini) || !empty($filtro_data_fim) || !empty($filtro_categoria)): ?>
                <div class="form-group" style="margin-bottom: 0;">
                    <a href="<?php echo APP_URL; ?>financeiro" class="btn btn-secondary btn-sm">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </form>

        <?php if (empty($lancamentos)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-dollar-sign"></i>
                <h3>Nenhum lancamento encontrado</h3>
                <p>Clique em "Novo Lancamento" para cadastrar o primeiro.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Tipo</th>
                        <th>Descricao</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lancamentos as $l): ?>
                    <tr>
                        <td><?php echo formatarData($l['data']); ?></td>
                        <td>
                            <?php if ($l['tipo'] === 'RECEITA'): ?>
                                <span class="badge badge-success"><i class="fas fa-arrow-up"></i> Receita</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-arrow-down"></i> Despesa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo h($l['descricao']); ?></strong>
                            <?php if (!empty($l['observacoes'])): ?>
                                <br><small class="text-muted"><?php echo h(mb_strimwidth($l['observacoes'], 0, 60, '...')); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($l['categoria'])): ?>
                                <span class="badge badge-info"><?php echo h($l['categoria']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: 700; color: <?php echo $l['tipo'] === 'RECEITA' ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;">
                            <?php echo $l['tipo'] === 'RECEITA' ? '+' : '-'; ?> <?php echo formatarMoeda($l['valor']); ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>financeiro/form?id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>financeiro/actions?action=excluir&id=<?php echo urlencode($l['id']); ?>" 
                                   class="btn btn-danger btn-sm" title="Excluir"
                                   onclick="return confirm('Excluir este lancamento?')">
                                    <i class="fas fa-trash"></i>
                                </a>
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
                Total: <?php echo count($lancamentos); ?> lancamento(s) | 
                Receitas: <?php echo formatarMoeda($totalReceitas); ?> | 
                Despesas: <?php echo formatarMoeda($totalDespesas); ?> | 
                Saldo: <strong style="color: <?php echo $saldo >= 0 ? 'var(--cor-sucesso)' : 'var(--cor-erro)'; ?>;"><?php echo formatarMoeda($saldo); ?></strong>
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>