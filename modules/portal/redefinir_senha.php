<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/cliente_portal.php';

$token = trim($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = $token !== '' ? hash('sha256', $token) : '';
$erro_msg = '';
$reset = null;

if ($tokenHash !== '') {
    $stmt = $pdo->prepare("
        SELECT r.id, r.cliente_id, c.nome, c.email
        FROM cliente_password_resets r
        INNER JOIN clientes c ON c.id = r.cliente_id
        INNER JOIN cliente_portal_acessos a ON a.cliente_id = c.id AND a.ativo = 1
        WHERE r.token_hash = :token_hash
          AND r.usado_em IS NULL
          AND r.expira_em > NOW()
          AND c.status = 'ATIVO'
        LIMIT 1
    ");
    $stmt->execute([':token_hash' => $tokenHash]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$reset) {
        $erro_msg = 'Link inválido ou expirado.';
    } elseif (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        $erro_msg = 'Token de segurança inválido.';
    } else {
        $senha = $_POST['senha'] ?? '';
        $confirmacao = $_POST['confirmacao'] ?? '';
        if (strlen($senha) < 8) {
            $erro_msg = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($senha !== $confirmacao) {
            $erro_msg = 'A confirmação não confere.';
        } else {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE cliente_portal_acessos SET senha_hash = :hash, forcar_troca_senha = 0 WHERE cliente_id = :cliente_id")
                ->execute([':hash' => password_hash($senha, PASSWORD_DEFAULT), ':cliente_id' => $reset['cliente_id']]);
            $pdo->prepare("UPDATE cliente_password_resets SET usado_em = NOW() WHERE id = :id")
                ->execute([':id' => $reset['id']]);
            $pdo->commit();
            setMensagem('success', 'Senha redefinida com sucesso. Entre com sua nova senha.');
            header('Location: ' . APP_URL . 'portal/login');
            exit;
        }
    }
}

$titulo_page = 'Redefinir senha - Portal do Cliente';
require_once __DIR__ . '/../../includes/portal_header.php';
?>
<section class="portal-auth">
    <div class="portal-auth-card">
        <h1>Redefinir senha</h1>
        <?php if (!$reset): ?>
            <div class="message error"><i class="fas fa-circle-exclamation"></i><span>Link inválido ou expirado.</span></div>
            <a class="btn btn-secondary btn-full" href="<?php echo APP_URL; ?>portal/recuperar-senha">Solicitar novo link</a>
        <?php else: ?>
            <p>Crie uma nova senha para <?php echo h($reset['email']); ?>.</p>
            <?php if ($erro_msg): ?>
                <div class="message error"><i class="fas fa-circle-exclamation"></i><span><?php echo h($erro_msg); ?></span></div>
            <?php endif; ?>
            <form method="POST" class="form-padrao">
                <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
                <input type="hidden" name="token" value="<?php echo h($token); ?>">
                <div class="form-group">
                    <label for="senha">Nova senha</label>
                    <input type="password" id="senha" name="senha" required minlength="8" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmacao">Confirmar nova senha</label>
                    <input type="password" id="confirmacao" name="confirmacao" required minlength="8" autocomplete="new-password">
                </div>
                <button type="submit" class="btn btn-primary btn-full"><i class="fas fa-key"></i> Redefinir senha</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php require_once __DIR__ . '/../../includes/portal_footer.php'; ?>
