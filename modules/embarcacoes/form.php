<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: form.php - Formulario para criar / editar embarcacao
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

// Buscar embarcacao se for edicao
$id = $_GET['id'] ?? '';
$embarcacao = null;
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM embarcacoes WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $embarcacao = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$embarcacao) {
            setMensagem('error', 'Embarcacao nao encontrada.');
            redirecionar(APP_URL . 'embarcacoes');
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar embarcacao: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados da embarcacao.');
        redirecionar(APP_URL . 'embarcacoes');
    }
}

// Gerar CSRF token
$csrf = gerarCSRF();

$titulo_page = ($isEdicao ? 'Editar' : 'Nova') . ' Embarcacao - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Embarcacao' : 'Nova Embarcacao'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" 
                  action="<?php echo APP_URL; ?>embarcacoes/actions?action=salvar" 
                  id="formEmbarcacao"
                  onsubmit="return validarFormulario('formEmbarcacao')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="id" value="<?php echo h($embarcacao['id'] ?? ''); ?>">

                <div class="grid-2">
                    <!-- Nome -->
                    <div class="form-group">
                        <label for="nome">
                            <i class="fas fa-ship"></i> Nome da embarcacao *
                        </label>
                        <input type="text" 
                               id="nome" 
                               name="nome" 
                               placeholder="Ex: Lancha Alpha" 
                               required 
                               maxlength="150"
                               value="<?php echo h($embarcacao['nome'] ?? ''); ?>">
                    </div>

                    <!-- Registro -->
                    <div class="form-group">
                        <label for="registro">
                            <i class="fas fa-hashtag"></i> Registro *
                        </label>
                        <input type="text" 
                               id="registro" 
                               name="registro" 
                               placeholder="Ex: REG-00123" 
                               required 
                               maxlength="80"
                               value="<?php echo h($embarcacao['registro'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Tipo -->
                    <div class="form-group">
                        <label for="tipo">
                            <i class="fas fa-tags"></i> Tipo
                        </label>
                        <input type="text" 
                               id="tipo" 
                               name="tipo" 
                               placeholder="Ex: Lancha, Iate, Barco..." 
                               maxlength="100"
                               value="<?php echo h($embarcacao['tipo'] ?? ''); ?>">
                    </div>

                    <!-- Ano -->
                    <div class="form-group">
                        <label for="ano">
                            <i class="fas fa-calendar"></i> Ano
                        </label>
                        <input type="number" 
                               id="ano" 
                               name="ano" 
                               placeholder="Ex: 2020" 
                               min="1900" 
                               max="2099"
                               value="<?php echo h($embarcacao['ano'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Proprietario -->
                <div class="form-group">
                    <label for="proprietario">
                        <i class="fas fa-user"></i> Proprietario
                    </label>
                    <input type="text" 
                           id="proprietario" 
                           name="proprietario" 
                           placeholder="Nome do proprietario" 
                           maxlength="150"
                           value="<?php echo h($embarcacao['proprietario'] ?? ''); ?>">
                </div>

                <!-- Observacoes -->
                <div class="form-group">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observacoes
                    </label>
                    <textarea id="observacoes" 
                              name="observacoes" 
                              rows="4" 
                              placeholder="Informacoes adicionais sobre a embarcacao..."><?php echo h($embarcacao['observacoes'] ?? ''); ?></textarea>
                </div>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> 
                            Criado em: <?php echo formatarDataCompleta($embarcacao['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> 
                            Atualizado: <?php echo formatarDataCompleta($embarcacao['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Criar Embarcacao'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>embarcacoes" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>