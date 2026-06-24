<?php
/**
 * MODULO: PESSOAS
 * Arquivo: form.php - Formulario para criar / editar pessoa
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

// Buscar pessoa se for edicao
$id = $_GET['id'] ?? '';
$pessoa = null;
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM pessoas WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pessoa) {
            setMensagem('error', 'Pessoa nao encontrada.');
            redirecionar(APP_URL . 'pessoas');
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar pessoa: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados da pessoa.');
        redirecionar(APP_URL . 'pessoas');
    }
}

// Gerar CSRF token
$csrf = gerarCSRF();

// Formatar CPF para exibicao no form
$cpfFormatado = '';
if ($pessoa && !empty($pessoa['cpf'])) {
    $cpfFormatado = formatarCPF($pessoa['cpf']);
}

$titulo_page = ($isEdicao ? 'Editar' : 'Nova') . ' Pessoa - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-user-edit' : 'fa-user-plus'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Pessoa' : 'Nova Pessoa'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" 
                  action="<?php echo APP_URL; ?>pessoas/actions?action=salvar" 
                  id="formPessoa"
                  onsubmit="return validarFormulario('formPessoa')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="id" value="<?php echo h($pessoa['id'] ?? ''); ?>">

                <div class="grid-2">
                    <!-- Nome Completo -->
                    <div class="form-group">
                        <label for="nome_completo">
                            <i class="fas fa-user"></i> Nome completo *
                        </label>
                        <input type="text" 
                               id="nome_completo" 
                               name="nome_completo" 
                               placeholder="Nome completo da pessoa" 
                               required 
                               maxlength="200"
                               value="<?php echo h($pessoa['nome_completo'] ?? ''); ?>">
                    </div>

                    <!-- CPF -->
                    <div class="form-group">
                        <label for="cpf">
                            <i class="fas fa-id-card"></i> CPF *
                        </label>
                        <input type="text" 
                               id="cpf" 
                               name="cpf" 
                               placeholder="000.000.000-00" 
                               required 
                               maxlength="14"
                               value="<?php echo h($cpfFormatado); ?>"
                               oninput="mascararCPF(this)">
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Telefone -->
                    <div class="form-group">
                        <label for="telefone">
                            <i class="fas fa-phone"></i> Telefone
                        </label>
                        <input type="text" 
                               id="telefone" 
                               name="telefone" 
                               placeholder="(00) 00000-0000" 
                               maxlength="20"
                               value="<?php echo h($pessoa['telefone'] ?? ''); ?>">
                    </div>

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope"></i> Email
                        </label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               placeholder="pessoa@email.com" 
                               maxlength="150"
                               value="<?php echo h($pessoa['email'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Endereco -->
                <div class="form-group">
                    <label for="endereco">
                        <i class="fas fa-map-marker-alt"></i> Endereco
                    </label>
                    <textarea id="endereco" 
                              name="endereco" 
                              placeholder="Endereco completo" 
                              rows="2"
                              maxlength="500"><?php echo h($pessoa['endereco'] ?? ''); ?></textarea>
                </div>

                <!-- Observacoes -->
                <div class="form-group">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observacoes
                    </label>
                    <textarea id="observacoes" 
                              name="observacoes" 
                              placeholder="Observacoes adicionais" 
                              rows="3"
                              maxlength="1000"><?php echo h($pessoa['observacoes'] ?? ''); ?></textarea>
                </div>

                <!-- Status -->
                <div class="form-group">
                    <label>
                        <i class="fas fa-toggle-on"></i> Status
                    </label>
                    <div style="display: flex; align-items: center; gap: 10px; padding-top: 6px;">
                        <label class="toggle-label" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" 
                                   name="ativo" 
                                   value="1" 
                                   <?php echo ($pessoa['ativo'] ?? 1) ? 'checked' : ''; ?>
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <span>Pessoa ativa</span>
                        </label>
                    </div>
                </div>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> 
                            Criado em: <?php echo formatarDataCompleta($pessoa['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> 
                            Atualizado: <?php echo formatarDataCompleta($pessoa['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Criar Pessoa'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>pessoas" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Mascara para CPF
function mascararCPF(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.substring(0, 11);
    
    if (valor.length > 9) {
        valor = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    } else if (valor.length > 6) {
        valor = valor.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
    } else if (valor.length > 3) {
        valor = valor.replace(/(\d{3})(\d{1,3})/, '$1.$2');
    }
    
    input.value = valor;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>