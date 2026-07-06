<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$responsavel = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM responsaveis_assinatura WHERE id = ?");
    $stmt->execute([$id]);
    $responsavel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$responsavel) {
        header("Location: " . APP_URL . "responsaveis_assinatura?error=" . urlencode("Responsável não encontrado."));
        exit;
    }
}

$page_title = $id ? "Editar Responsável" : "Novo Responsável";
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?= $page_title ?></h1>
        <a href="<?= APP_URL ?>responsaveis_assinatura" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <form action="<?= APP_URL ?>responsaveis_assinatura/actions" method="POST">
                <input type="hidden" name="csrf_token" value="<?= gerarCSRF() ?>">
                <input type="hidden" name="action" value="<?= $id ? 'update' : 'create' ?>">
                <?php if ($id): ?>
                    <input type="hidden" name="id" value="<?= $id ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="nome_completo" class="form-label">Nome Completo *</label>
                        <input type="text" class="form-control" id="nome_completo" name="nome_completo" 
                               value="<?= htmlspecialchars($responsavel['nome_completo'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="cargo_titulo" class="form-label">Cargo/Título *</label>
                        <input type="text" class="form-control" id="cargo_titulo" name="cargo_titulo" 
                               value="<?= htmlspecialchars($responsavel['cargo_titulo'] ?? '') ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="registro_profissional" class="form-label">Registro Profissional</label>
                        <input type="text" class="form-control" id="registro_profissional" name="registro_profissional" 
                               value="<?= htmlspecialchars($responsavel['registro_profissional'] ?? '') ?>">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="ativo" class="form-label">Status *</label>
                        <select class="form-control" id="ativo" name="ativo" required>
                            <option value="1" <?= ($responsavel['ativo'] ?? 1) == 1 ? 'selected' : '' ?>>Ativo</option>
                            <option value="0" <?= isset($responsavel['ativo']) && $responsavel['ativo'] == 0 ? 'selected' : '' ?>>Inativo</option>
                        </select>
                    </div>
                </div>

                <div class="text-right mt-4">
                    <a href="<?= APP_URL ?>responsaveis_assinatura" class="btn btn-secondary mr-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i> Salvar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
