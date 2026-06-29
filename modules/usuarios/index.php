<?php
/**
 * MODULO: USUARIOS
 * Arquivo: index.php - Listagem de usuarios (apenas ADMIN)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
verificar_cargo('ADMIN');

// Buscar todos os usuarios
try {
    $stmt = $pdo->query("SELECT id, nome, email, cargo, ativo, criado_em, atualizado_em FROM usuarios ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log('Erro ao listar usuarios: ' . $e->getMessage());
    $usuarios = [];
}

// Verificar se veio mensagem de erro de permissao
$erro_permissao = isset($_GET['erro']) && $_GET['erro'] === 'sem_permissao';

$titulo_page = 'Usuarios - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-container">
        <div class="tabela-header">
            <h3><i class="fas fa-users"></i> Gerenciar Usuarios</h3>
            <a href="<?php echo APP_URL; ?>usuarios/form" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Novo Usuario
            </a>
        </div>

        <?php if ($erro_permissao): ?>
            <div class="message error" style="position: relative; top: 0; right: 0; margin: 15px 20px 0;">
                <i class="fas fa-exclamation-circle"></i>
                <span>Acesso negado. Voce nao tem permissao para acessar este modulo.</span>
                <button class="close-msg" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Filtro de busca -->
        <div class="filtros" style="margin: 15px 20px;">
            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                <label><i class="fas fa-search"></i> Buscar usuario</label>
                <input type="text" 
                       id="buscaUsuario" 
                       placeholder="Nome ou email..." 
                       onkeyup="filtrarTabela('buscaUsuario', 'tabelaUsuarios')">
            </div>
        </div>

        <?php if (empty($usuarios)): ?>
            <div class="tabela-vazia">
                <i class="fas fa-users"></i>
                <h3>Nenhum usuario encontrado</h3>
                <p>Clique em "Novo Usuario" para criar o primeiro usuario.</p>
            </div>
        <?php else: ?>
            <table id="tabelaUsuarios">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Email</th>
                        <th>Cargo</th>
                        <th>Status</th>
                        <th>Criado em</th>
                        <th>Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($u['nome']); ?></strong>
                        </td>
                        <td><?php echo h($u['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $u['cargo'] === 'ADMIN' ? 'badge-success' : ($u['cargo'] === 'VENDEDOR' ? 'badge-primary' : 'badge-info'); ?>">
                                <i class="fas <?php echo $u['cargo'] === 'ADMIN' ? 'fa-user-shield' : ($u['cargo'] === 'VENDEDOR' ? 'fa-user-tie' : 'fa-user-check'); ?>"></i>
                                <?php echo h($u['cargo'] === 'ADMIN' ? 'Administrador' : ($u['cargo'] === 'VENDEDOR' ? 'Vendedor' : 'Vistoriador')); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['ativo']): ?>
                                <span class="badge badge-success"><i class="fas fa-check-circle"></i> Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger"><i class="fas fa-times-circle"></i> Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo formatarDataCompleta($u['criado_em']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?php echo APP_URL; ?>usuarios/form?id=<?php echo urlencode($u['id']); ?>" 
                                   class="btn btn-secondary btn-sm" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($u['id'] !== $_SESSION['usuario_id']): ?>
                                    <a href="<?php echo APP_URL; ?>usuarios/actions?action=alternar_status&id=<?php echo urlencode($u['id']); ?>" 
                                       class="btn btn-sm <?php echo $u['ativo'] ? 'btn-danger' : 'btn-success'; ?>" 
                                       title="<?php echo $u['ativo'] ? 'Desativar' : 'Ativar'; ?>"
                                       onclick="return confirm('<?php echo $u['ativo'] ? 'Desativar' : 'Ativar'; ?> este usuario?')">
                                        <i class="fas <?php echo $u['ativo'] ? 'fa-ban' : 'fa-check'; ?>"></i>
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
                Total: <?php echo count($usuarios); ?> usuario(s) | 
                <?php 
                $ativos = count(array_filter($usuarios, function($u) { return $u['ativo']; }));
                echo $ativos . ' ativo(s), ' . (count($usuarios) - $ativos) . ' inativo(s)';
                ?>
            </small>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>