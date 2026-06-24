<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: index.php - Listagem de embarcacoes (ADMIN e VISTORIADOR)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

// Buscar apenas embarcacoes ativas
try {
    $stmt = $pdo->query("SELECT id, nome, tipo, registro, proprietario, ano, observacoes, ativo, criado_em, atualizado_em FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC");
    $embarcacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar embarcacoes: ' . $e->getMessage());
    $embarcacoes = [];
}

$titulo_page = 'Embarcacoes - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-ship"></i> Gerenciar Embarcacoes</h3>
            <a href="<?php echo APP_URL; ?>embarcacoes/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Embarcacao
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar embarcacao</label>
                <input type="text" 
                       id="buscaEmbarcacao" 
                       placeholder="Nome ou registro..." 
                       onkeyup="filtrarTabela('buscaEmbarcacao', 'tabelaEmbarcacoes')">
            </div>
        </div>

        <?php if (empty($embarcacoes)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-ship"></i>
                <h3>Nenhuma embarcacao encontrada</h3>
                <p>Clique em "Nova Embarcacao" para cadastrar a primeira embarcacao.</p>
            </div>
        <?php else: ?>
            <table id="tabelaEmbarcacoes">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tipo</th>
                        <th>Registro</th>
                        <th>Proprietario</th>
                        <th>Ano</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($embarcacoes as $e): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($e['nome']); ?></strong>
                        </td>
                        <td><?php echo h($e['tipo'] ?? '-'); ?></td>
                        <td><?php echo h($e['registro'] ?? '-'); ?></td>
                        <td><?php echo h($e['proprietario'] ?? '-'); ?></td>
                        <td><?php echo h($e['ano'] ?? '-'); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>embarcacoes/form?id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>embarcacoes/actions?action=desativar&id=<?php echo urlencode($e['id']); ?>" 
                                   class="btn btn-danger btn-sm" 
                                   title="Desativar"
                                   onclick="return confirm('Tem certeza que deseja desativar esta embarcacao?')">
                                    <i class="fas fa-ban"></i>
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
                Total: <?php echo count($embarcacoes); ?> embarcacao(oes) ativa(s)
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>