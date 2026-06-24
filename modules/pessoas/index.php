<?php
/**
 * MODULO: PESSOAS
 * Arquivo: index.php - Listagem de pessoas com busca por nome/CPF
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('pessoas')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

// Buscar todas as pessoas
try {
    $stmt = $pdo->query("SELECT id, nome_completo, cpf, telefone, email, ativo, criado_em, atualizado_em FROM pessoas ORDER BY nome_completo ASC");
    $pessoas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar pessoas: ' . $e->getMessage());
    $pessoas = [];
}

$titulo_page = 'Pessoas - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-users"></i> Gerenciar Pessoas</h3>
            <a href="<?php echo APP_URL; ?>pessoas/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nova Pessoa
            </a>
        </div>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar pessoa</label>
                <input type="text" 
                       id="buscaPessoa" 
                       placeholder="Nome ou CPF..." 
                       onkeyup="filtrarTabela('buscaPessoa', 'tabelaPessoas')">
            </div>
        </div>

        <?php if (empty($pessoas)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-users"></i>
                <h3>Nenhuma pessoa encontrada</h3>
                <p>Clique em "Nova Pessoa" para cadastrar a primeira pessoa.</p>
            </div>
        <?php else: ?>
            <table id="tabelaPessoas">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pessoas as $p): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($p['nome_completo']); ?></strong>
                        </td>
                        <td><?php echo h(formatarCPF($p['cpf'])); ?></td>
                        <td><?php echo h($p['telefone']); ?></td>
                        <td><?php echo h($p['email']); ?></td>
                        <td>
                            <?php if ($p['ativo']): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarDataCompleta($p['criado_em']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>pessoas/form?id=<?php echo urlencode($p['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="<?php echo APP_URL; ?>pessoas/actions?action=alternar_status&id=<?php echo urlencode($p['id']); ?>" 
                                   class="btn btn-sm <?php echo $p['ativo'] ? 'btn-danger' : 'btn-success'; ?>" 
                                   title="<?php echo $p['ativo'] ? 'Desativar' : 'Ativar'; ?>"
                                   onclick="return confirm('<?php echo $p['ativo'] ? 'Desativar' : 'Ativar'; ?> esta pessoa?')">
                                    <i class="fas <?php echo $p['ativo'] ? 'fa-ban' : 'fa-check'; ?>"></i>
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
                Total: <?php echo count($pessoas); ?> pessoa(s) | 
                <?php 
                $ativos = count(array_filter($pessoas, function($p) { return $p['ativo']; }));
                echo $ativos . ' ativo(s), ' . (count($pessoas) - $ativos) . ' inativo(s)';
                ?>
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>