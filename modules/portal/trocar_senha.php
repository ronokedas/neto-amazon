<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

requireClienteLogin();

$erro_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $erro_msg = 'Token de segurança inválido. Atualize a página e tente novamente.';
    } else {
        $senha = $_POST['senha'] ?? '';
        $confirmacao = $_POST['confirmacao'] ?? '';

        if (strlen($senha) < 8) {
            $erro_msg = 'A nova senha deve ter pelo menos 8 caracteres.';
        } elseif ($senha !== $confirmacao) {
            $erro_msg = 'A confirmação não confere com a nova senha.';
        } else {
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE cliente_portal_acessos SET senha_hash = :hash, forcar_troca_senha = 0 WHERE cliente_id = :cliente_id")
                ->execute([':hash' => $hash, ':cliente_id' => clientePortalId()]);
            $_SESSION['cliente_forcar_troca_senha'] = false;
            setMensagem('success', 'Senha atualizada com sucesso.');
            header('Location: ' . APP_URL . 'portal');
            exit;
        }
    }
}

$titulo_page = 'Trocar senha - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-auth">
    <div class="portal-auth-card">
        <h1>Criar nova senha</h1>
        <p>Por segurança, troque a senha temporária antes de acessar seus documentos.</p>
        <?php if ($erro_msg): ?>
            <div class="message error"><i class="fas fa-circle-exclamation"></i><span><?php echo h($erro_msg); ?></span></div>
        <?php endif; ?>
        <form method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <div class="form-group">
                <label for="senha">Nova senha</label>
                <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="confirmacao">Confirmar nova senha</label>
                <input type="password" id="confirmacao" name="confirmacao" required minlength="8" autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-key"></i> Salvar senha</button>
        </form>
    </div>
</section>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
