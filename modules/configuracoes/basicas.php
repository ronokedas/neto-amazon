<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: basicas.php - Configurações Básicas do Sistema
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

// Garantir que as colunas de acesso existam na tabela usuarios
try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN acesso_documentacao TINYINT(1) DEFAULT 0");
} catch (Exception $e) {}

try {
    $pdo->exec("ALTER TABLE usuarios ADD COLUMN acesso_financeiro TINYINT(1) DEFAULT 0");
} catch (Exception $e) {}

// Salvar se for um POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
        setMensagem('error', 'Token de segurança inválido.');
        redirecionar(APP_URL . 'configuracoes/basicas');
    }

    $usuarios_doc = isset($_POST['acesso_documentacao']) && is_array($_POST['acesso_documentacao']) ? $_POST['acesso_documentacao'] : [];
    $usuarios_fin = isset($_POST['acesso_financeiro']) && is_array($_POST['acesso_financeiro']) ? $_POST['acesso_financeiro'] : [];
    
    // Filtrar strings válidas
    $ids_doc = array_values(array_filter($usuarios_doc, function($val) { return is_string($val) && strlen($val) > 0; }));
    $ids_fin = array_values(array_filter($usuarios_fin, function($val) { return is_string($val) && strlen($val) > 0; }));

    try {
        $pdo->beginTransaction();
        
        // Zera acessos primeiro
        $pdo->exec("UPDATE usuarios SET acesso_documentacao = 0, acesso_financeiro = 0 WHERE cargo != 'ADMIN'");
        
        // Ativa Documentacao
        if (!empty($ids_doc)) {
            $in_doc = str_repeat('?,', count($ids_doc) - 1) . '?';
            $stmt_doc = $pdo->prepare("UPDATE usuarios SET acesso_documentacao = 1 WHERE id IN ($in_doc)");
            $stmt_doc->execute($ids_doc);
        }

        // Ativa Financeiro
        if (!empty($ids_fin)) {
            $in_fin = str_repeat('?,', count($ids_fin) - 1) . '?';
            $stmt_fin = $pdo->prepare("UPDATE usuarios SET acesso_financeiro = 1 WHERE id IN ($in_fin)");
            $stmt_fin->execute($ids_fin);
        }
        
        $pdo->commit();
        setMensagem('success', 'Configurações salvas com sucesso!');
    } catch (Exception $e) {
        $pdo->rollBack();
        setMensagem('error', 'Erro ao salvar permissões: ' . $e->getMessage());
    }
    redirecionar(APP_URL . 'configuracoes/basicas');
}

// Buscar usuários (exceto ADMIN)
try {
    $stmt = $pdo->query("SELECT id, nome, email, cargo, ativo, acesso_documentacao, acesso_financeiro FROM usuarios WHERE cargo != 'ADMIN' ORDER BY nome ASC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $usuarios = [];
}

$titulo_page = 'Configurações Básicas - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
            <div>
                <h1><i class="fas fa-users-cog"></i> Configurações Básicas</h1>
                <p>Gerencie o acesso de usuários a módulos específicos.</p>
            </div>
            <a href="<?php echo APP_URL; ?>configuracoes" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>

    <div class="card" style="max-width: 900px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque);"><i class="fas fa-shield-alt"></i> Controle de Acesso aos Módulos</h3>
        </div>
        <div class="card-body">
            <p style="margin-bottom: 20px; color: var(--cor-texto-secundario);">
                Selecione abaixo quais usuários terão acesso aos módulos restritos (Documentação e Financeiro). 
                <br><small>Administradores têm acesso garantido a todos os módulos por padrão.</small>
            </p>

            <form method="POST" action="<?php echo APP_URL; ?>configuracoes/basicas">
                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">

                <div class="table-responsive" style="margin-bottom: 30px;">
                    <table class="table table-hover table-bordered">
                        <thead>
                            <tr style="background: var(--bg-surface-2);">
                                <th>Usuário</th>
                                <th>Cargo</th>
                                <th style="width: 130px; text-align: center;"><i class="fas fa-book-open"></i> Docs</th>
                                <th style="width: 130px; text-align: center;"><i class="fas fa-dollar-sign"></i> Financeiro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($usuarios)): ?>
                                <tr>
                                    <td colspan="4" class="text-center">Nenhum usuário encontrado.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($usuarios as $u): ?>
                                    <?php 
                                        $tem_doc = (int)$u['acesso_documentacao'] === 1;
                                        $chk_doc = $tem_doc ? 'checked' : ''; 
                                        $tem_fin = (int)$u['acesso_financeiro'] === 1;
                                        $chk_fin = $tem_fin ? 'checked' : ''; 
                                    ?>
                                    <tr>
                                        <td style="vertical-align: middle;">
                                            <strong><?php echo h($u['nome']); ?></strong><br>
                                            <small class="text-muted"><?php echo h($u['email']); ?></small>
                                        </td>
                                        <td style="vertical-align: middle;"><span class="badge bg-secondary"><?php echo h($u['cargo']); ?></span></td>
                                        
                                        <!-- Documentação -->
                                        <td style="text-align: center; vertical-align: middle; <?php echo $tem_doc ? 'background-color: rgba(40,167,69,0.1);' : ''; ?>">
                                            <div style="margin-bottom: 5px;">
                                                <input type="checkbox" name="acesso_documentacao[]" value="<?php echo $u['id']; ?>" <?php echo $chk_doc; ?> style="transform: scale(1.5); cursor: pointer;">
                                            </div>
                                            <?php if ($tem_doc): ?>
                                                <span class="badge bg-success" style="font-size: 0.7rem;">Liberado</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" style="font-size: 0.7rem;">Bloqueado</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Financeiro -->
                                        <td style="text-align: center; vertical-align: middle; <?php echo $tem_fin ? 'background-color: rgba(40,167,69,0.1);' : ''; ?>">
                                            <div style="margin-bottom: 5px;">
                                                <input type="checkbox" name="acesso_financeiro[]" value="<?php echo $u['id']; ?>" <?php echo $chk_fin; ?> style="transform: scale(1.5); cursor: pointer;">
                                            </div>
                                            <?php if ($tem_fin): ?>
                                                <span class="badge bg-success" style="font-size: 0.7rem;">Liberado</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger" style="font-size: 0.7rem;">Bloqueado</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Salvar Permissões
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>