<?php
/**
 * MODULO: PERFIL DO USUÁRIO
 * Arquivo: index.php - Edição do próprio perfil e senha
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login (qualquer usuário logado pode acessar)
verificar_sessao();

$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do usuário logado
try {
    $stmt = $pdo->prepare("SELECT id, nome, email, cargo, ativo, criado_em, atualizado_em FROM usuarios WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $usuario_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        setMensagem('error', 'Usuário não encontrado.');
        redirecionar(APP_URL . 'login?action=logout');
    }
} catch (Exception $e) {
    error_log('Erro ao buscar perfil: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar dados do perfil.');
    redirecionar(APP_URL . 'dashboard');
}

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_perfil'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verificarCSRF($csrf)) {
        setMensagem('error', 'Token de segurança inválido. Tente novamente.');
        redirecionar(APP_URL . 'perfil');
    }

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirma_senha = $_POST['confirma_senha'] ?? '';

    // Validações básicas
    if (empty($nome) || empty($email)) {
        setMensagem('error', 'Nome e email são obrigatórios.');
        redirecionar(APP_URL . 'perfil');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setMensagem('error', 'Email inválido.');
        redirecionar(APP_URL . 'perfil');
    }

    // Se quiser alterar senha, precisa informar a senha atual
    if (!empty($nova_senha) || !empty($confirma_senha)) {
        if (empty($senha_atual)) {
            setMensagem('error', 'Informe a senha atual para alterar a senha.');
            redirecionar(APP_URL . 'perfil');
        }

        // Verificar senha atual
        $stmt = $pdo->prepare("SELECT senha_hash FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $usuario_id]);
        $hash_salvo = $stmt->fetchColumn();

        if (!password_verify($senha_atual, $hash_salvo)) {
            setMensagem('error', 'Senha atual incorreta.');
            redirecionar(APP_URL . 'perfil');
        }

        if (strlen($nova_senha) < 6) {
            setMensagem('error', 'A nova senha deve ter no mínimo 6 caracteres.');
            redirecionar(APP_URL . 'perfil');
        }

        if ($nova_senha !== $confirma_senha) {
            setMensagem('error', 'A confirmação da senha não confere.');
            redirecionar(APP_URL . 'perfil');
        }

        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
    } else {
        $senha_hash = null;
    }

    // Atualizar dados
    try {
        if ($senha_hash) {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, senha_hash = :senha, atualizado_em = NOW() WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':senha' => $senha_hash,
                ':id' => $usuario_id
            ]);
        } else {
            $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, atualizado_em = NOW() WHERE id = :id");
            $stmt->execute([
                ':nome' => $nome,
                ':email' => $email,
                ':id' => $usuario_id
            ]);
        }

        // Atualizar sessão
        $_SESSION['usuario_nome'] = $nome;
        $_SESSION['usuario_email'] = $email;

        setMensagem('success', 'Perfil atualizado com sucesso!');
        redirecionar(APP_URL . 'perfil');
    } catch (Exception $e) {
        error_log('Erro ao atualizar perfil: ' . $e->getMessage());
        setMensagem('error', 'Erro ao atualizar perfil. Tente novamente.');
        redirecionar(APP_URL . 'perfil');
    }
}

$csrf = gerarCSRF();
$titulo_page = 'Meu Perfil - ' . APP_NAME;
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--accent); margin: 0;">
                <i class="fas fa-user-circle"></i> Meu Perfil
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="formPerfil" onsubmit="return validarFormulario('formPerfil')">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="atualizar_perfil" value="1">

                <!-- Nome -->
                <div class="form-group">
                    <label for="nome">
                        <i class="fas fa-user"></i> Nome completo *
                    </label>
                    <input type="text" 
                           id="nome" 
                           name="nome" 
                           placeholder="Seu nome completo" 
                           required 
                           maxlength="150"
                           value="<?php echo h($usuario['nome']); ?>">
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> Email *
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           placeholder="seu@email.com" 
                           required 
                           maxlength="150"
                           value="<?php echo h($usuario['email']); ?>">
                </div>

                <hr style="border-color: var(--border); margin: 24px 0;">

                <!-- Senha atual (obrigatória para alterar senha) -->
                <div class="form-group">
                    <label for="senha_atual">
                        <i class="fas fa-lock"></i> Senha atual
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="senha_atual" 
                               name="senha_atual" 
                               placeholder="Digite sua senha atual para alterá-la"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('senha_atual', 'icone-senha-atual')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-senha-atual"></i>
                        </button>
                    </div>
                    <small class="text-muted">Preencha apenas se desejar alterar sua senha.</small>
                </div>

                <!-- Nova senha -->
                <div class="form-group">
                    <label for="nova_senha">
                        <i class="fas fa-key"></i> Nova senha
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="nova_senha" 
                               name="nova_senha" 
                               placeholder="Mínimo 6 caracteres"
                               minlength="6"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('nova_senha', 'icone-nova-senha')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-nova-senha"></i>
                        </button>
                    </div>
                </div>

                <!-- Confirmar nova senha -->
                <div class="form-group">
                    <label for="confirma_senha">
                        <i class="fas fa-check-double"></i> Confirmar nova senha
                    </label>
                    <div class="password-input" style="position: relative;">
                        <input type="password" 
                               id="confirma_senha" 
                               name="confirma_senha" 
                               placeholder="Repita a nova senha"
                               minlength="6"
                               style="padding-right: 40px;">
                        <button type="button" 
                                class="toggle-senha" 
                                onclick="toggleSenha('confirma_senha', 'icone-confirma-senha')"
                                style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer;">
                            <i class="fas fa-eye" id="icone-confirma-senha"></i>
                        </button>
                    </div>
                </div>

                <!-- Informações da conta -->
                <div style="background: var(--bg-surface-2); border-radius: 8px; padding: 14px 18px; margin-top: 20px; font-size: 13px; color: var(--text-secondary);">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span><i class="fas fa-user-tag"></i> Cargo:</span>
                        <strong style="color: var(--text-primary);"><?php echo h($usuario['cargo']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                        <span><i class="fas fa-calendar-plus"></i> Criado em:</span>
                        <strong style="color: var(--text-primary);"><?php echo formatarDataCompleta($usuario['criado_em']); ?></strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span><i class="fas fa-calendar-check"></i> Atualizado:</span>
                        <strong style="color: var(--text-primary);"><?php echo formatarDataCompleta($usuario['atualizado_em']); ?></strong>
                    </div>
                </div>

                <!-- Botões -->
                <div class="d-flex gap-2" style="margin-top: 24px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Salvar Alterações
                    </button>
                    <a href="<?php echo APP_URL; ?>dashboard" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>