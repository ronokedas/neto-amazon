<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: nova.php - Wizard em 3 telas para criar vistoria
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('vistorias')) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

// Determinar passo atual
$passo = intval($_GET['passo'] ?? 1);
if ($passo < 1 || $passo > 3) $passo = 1;

// Dados do wizard preservados na sessao
$embarcacao_id = $_SESSION['wizard_embarcacao_id'] ?? '';
$pessoa_id     = $_SESSION['wizard_pessoa_id'] ?? '';
$data_vistoria = $_SESSION['wizard_data_vistoria'] ?? date('Y-m-d');
$observacoes   = $_SESSION['wizard_observacoes'] ?? '';

// Se voltou para passo 1, pegar dados do POST tambem
if ($passo === 1 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $embarcacao_id = trim($_POST['embarcacao_id'] ?? $embarcacao_id);
    $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
}
if ($passo === 2 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $pessoa_id     = trim($_POST['pessoa_id'] ?? $pessoa_id);
    $embarcacao_id = trim($_POST['embarcacao_id'] ?? $embarcacao_id);
    $_SESSION['wizard_pessoa_id'] = $pessoa_id;
    $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
}
if ($passo === 3 && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $embarcacao_id = trim($_POST['embarcacao_id'] ?? $embarcacao_id);
    $pessoa_id     = trim($_POST['pessoa_id'] ?? $pessoa_id);
    $data_vistoria = trim($_POST['data_vistoria'] ?? $data_vistoria);
    $observacoes   = trim($_POST['observacoes'] ?? $observacoes);
    $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
    $_SESSION['wizard_pessoa_id'] = $pessoa_id;
    $_SESSION['wizard_data_vistoria'] = $data_vistoria;
    $_SESSION['wizard_observacoes'] = $observacoes;
}

// Se passo > 1, verificar se tem dados obrigatorios do passo anterior
if ($passo >= 2 && empty($embarcacao_id)) {
    setMensagem('error', 'Selecione uma embarcacao primeiro.');
    redirecionar(APP_URL . 'vistorias/nova?passo=1');
}
if ($passo === 3 && empty($pessoa_id)) {
    setMensagem('error', 'Selecione uma pessoa responsavel primeiro.');
    redirecionar(APP_URL . 'vistorias/nova?passo=2');
}

// Buscar dados para exibicao
$embarcacao = null;
if (!empty($embarcacao_id)) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome, tipo, registro, proprietario, ano FROM embarcacoes WHERE id = :id AND ativo = 1");
        $stmt->execute([':id' => $embarcacao_id]);
        $embarcacao = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erro ao buscar embarcacao: ' . $e->getMessage());
    }
}

$pessoa = null;
if (!empty($pessoa_id)) {
    try {
        $stmt = $pdo->prepare("SELECT id, nome_completo, cpf, telefone, email FROM pessoas WHERE id = :id AND ativo = 1");
        $stmt->execute([':id' => $pessoa_id]);
        $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log('Erro ao buscar pessoa: ' . $e->getMessage());
    }
}

// Gerar CSRF
$csrf = gerarCSRF();

$titulo_page = 'Nova Vistoria - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 800px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas fa-clipboard-check"></i> Nova Vistoria
            </h3>
        </div>

        <!-- Indicador de progresso -->
        <div style="padding: 20px 30px 0;">
            <div style="display: flex; align-items: center; justify-content: center; gap: 0;">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <div style="display: flex; align-items: center;">
                        <div style="width: 36px; height: 36px; border-radius: 50%; 
                                    display: flex; align-items: center; justify-content: center;
                                    font-weight: bold; font-size: 0.9rem;
                                    background: <?php echo ($i <= $passo) ? 'var(--cor-destaque, #28a745)' : '#6c757d'; ?>;
                                    color: white;">
                            <?php if ($i < $passo): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo $i; ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($i < 3): ?>
                            <div style="width: 80px; height: 3px; 
                                        background: <?php echo ($i < $passo) ? 'var(--cor-destaque, #28a745)' : '#6c757d'; ?>;">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>
            <div style="text-align: center; margin-top: 8px; font-size: 0.85rem; color: var(--cor-texto-secundario, #6c757d);">
                Passo <?php echo $passo; ?> de 3
                <?php if ($passo === 1): ?>
                     — Selecione a Embarcacao
                <?php elseif ($passo === 2): ?>
                     — Selecione a Pessoa Responsavel
                <?php else: ?>
                     — Revisao e Confirmacao
                <?php endif; ?>
            </div>
        </div>

        <div class="card-body" style="padding-top: 10px;">

            <!-- ==================== PASSO 1 ==================== -->
            <?php if ($passo === 1): ?>
            <form method="POST" action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_wizard">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="passo" value="1">

                <h4 style="margin-bottom: 15px;">
                    <i class="fas fa-ship"></i> Passo 1: Selecione a Embarcacao
                </h4>

                <!-- Campo de busca -->
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Buscar embarcacao</label>
                    <input type="text" 
                           id="buscaEmbarcacao" 
                           placeholder="Nome, tipo ou registro..." 
                           onkeyup="filtrarListaEmbarcacoes()"
                           style="width: 100%; padding: 8px 12px;">
                </div>

                <!-- Lista de embarcacoes ativas -->
                <?php
                try {
                    $stmtEmb = $pdo->query("SELECT id, nome, tipo, registro, proprietario, ano FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC");
                    $embarcacoes = $stmtEmb->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $embarcacoes = [];
                }
                ?>

                <?php if (empty($embarcacoes)): ?>
                    <div class="tabela-vazia" style="padding: 30px;">
                        <i class="fas fa-ship"></i>
                        <h3>Nenhuma embarcacao ativa</h3>
                        <p>Cadastre uma embarcacao antes de criar uma vistoria.</p>
                    </div>
                <?php else: ?>
                    <div id="listaEmbarcacoes" style="max-height: 350px; overflow-y: auto; border: 1px solid var(--cor-borda, #dee2e6); border-radius: 8px;">
                        <?php foreach ($embarcacoes as $emb): ?>
                        <label class="item-embarcacao" 
                               data-nome="<?php echo h(strtolower($emb['nome'])); ?>"
                               data-tipo="<?php echo h(strtolower($emb['tipo'] ?? '')); ?>"
                               data-registro="<?php echo h(strtolower($emb['registro'] ?? '')); ?>"
                               style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; 
                                      border-bottom: 1px solid var(--cor-borda, #eee); cursor: pointer;
                                      transition: background 0.2s;">
                            <input type="radio" 
                                   name="embarcacao_id" 
                                   value="<?php echo h($emb['id']); ?>"
                                   <?php echo $embarcacao_id === $emb['id'] ? 'checked' : ''; ?>
                                   required
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <div style="flex: 1;">
                                <strong><?php echo h($emb['nome']); ?></strong>
                                <?php if (!empty($emb['registro'])): ?>
                                    <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 8px;">
                                        Reg: <?php echo h($emb['registro']); ?>
                                    </span>
                                <?php endif; ?>
                                <br>
                                <small style="color: var(--cor-texto-secundario, #6c757d);">
                                    <?php if (!empty($emb['tipo'])) echo h($emb['tipo']); ?>
                                    <?php if (!empty($emb['ano'])) echo ' • ' . h($emb['ano']); ?>
                                    <?php if (!empty($emb['proprietario'])) echo ' • ' . h($emb['proprietario']); ?>
                                </small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Proximo
                    </button>
                    <a href="<?php echo APP_URL; ?>vistorias" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>

            <!-- ==================== PASSO 2 ==================== -->
            <?php elseif ($passo === 2): ?>
            <form method="POST" action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_wizard">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="passo" value="2">
                <input type="hidden" name="embarcacao_id" value="<?php echo h($embarcacao_id); ?>">

                <h4 style="margin-bottom: 15px;">
                    <i class="fas fa-user"></i> Passo 2: Selecione a Pessoa Responsavel
                </h4>

                <!-- Embarcacao selecionada (resumo) -->
                <?php if ($embarcacao): ?>
                <div style="background: var(--cor-sidebar); padding: 10px 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--cor-destaque, #28a745);">
                    <small style="color: var(--cor-texto-secundario, #6c757d);">Embarcacao selecionada:</small><br>
                    <strong><i class="fas fa-ship"></i> <?php echo h($embarcacao['nome']); ?></strong>
                    <?php if (!empty($embarcacao['registro'])): ?>
                        <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 5px;">(<?php echo h($embarcacao['registro']); ?>)</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- Campo de busca -->
                <div class="form-group">
                    <label><i class="fas fa-search"></i> Buscar pessoa</label>
                    <input type="text" 
                           id="buscaPessoaWiz" 
                           placeholder="Nome ou CPF..." 
                           onkeyup="filtrarListaPessoas()"
                           style="width: 100%; padding: 8px 12px;">
                </div>

                <!-- Lista de pessoas ativas -->
                <?php
                try {
                    $stmtPes = $pdo->query("SELECT id, nome_completo, cpf, telefone, email FROM pessoas WHERE ativo = 1 ORDER BY nome_completo ASC");
                    $pessoas = $stmtPes->fetchAll(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    $pessoas = [];
                }
                ?>

                <?php if (empty($pessoas)): ?>
                    <div class="tabela-vazia" style="padding: 30px;">
                        <i class="fas fa-users"></i>
                        <h3>Nenhuma pessoa ativa</h3>
                        <p>Cadastre uma pessoa antes de criar uma vistoria.</p>
                    </div>
                <?php else: ?>
                    <div id="listaPessoas" style="max-height: 350px; overflow-y: auto; border: 1px solid var(--cor-borda, #dee2e6); border-radius: 8px;">
                        <?php foreach ($pessoas as $pes): ?>
                        <label class="item-pessoa" 
                               data-nome="<?php echo h(strtolower($pes['nome_completo'])); ?>"
                               data-cpf="<?php echo h(strtolower($pes['cpf'] ?? '')); ?>"
                               style="display: flex; align-items: center; gap: 12px; padding: 12px 15px; 
                                      border-bottom: 1px solid var(--cor-borda, #eee); cursor: pointer;
                                      transition: background 0.2s;">
                            <input type="radio" 
                                   name="pessoa_id" 
                                   value="<?php echo h($pes['id']); ?>"
                                   <?php echo $pessoa_id === $pes['id'] ? 'checked' : ''; ?>
                                   required
                                   style="width: 18px; height: 18px; cursor: pointer;">
                            <div style="flex: 1;">
                                <strong><?php echo h($pes['nome_completo']); ?></strong>
                                <?php if (!empty($pes['cpf'])): ?>
                                    <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 8px;">
                                        CPF: <?php echo h(formatarCPF($pes['cpf'])); ?>
                                    </span>
                                <?php endif; ?>
                                <br>
                                <small style="color: var(--cor-texto-secundario, #6c757d);">
                                    <?php if (!empty($pes['telefone'])) echo 'Tel: ' . h($pes['telefone']); ?>
                                    <?php if (!empty($pes['email'])) echo ' • ' . h($pes['email']); ?>
                                </small>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <a href="<?php echo APP_URL; ?>vistorias/nova?passo=1" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-arrow-right"></i> Proximo
                    </button>
                    <a href="<?php echo APP_URL; ?>vistorias" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>

            <!-- ==================== PASSO 3 ==================== -->
            <?php elseif ($passo === 3): ?>
            <form method="POST" action="<?php echo APP_URL; ?>vistorias/actions?action=salvar" id="formConfirmarVistoria">
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="embarcacao_id" value="<?php echo h($embarcacao_id); ?>">
                <input type="hidden" name="pessoa_id" value="<?php echo h($pessoa_id); ?>">

                <h4 style="margin-bottom: 15px;">
                    <i class="fas fa-check-double"></i> Passo 3: Revisao e Confirmacao
                </h4>

                <!-- Resumo da Embarcacao -->
                <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--cor-destaque, #28a745);">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-ship" style="color: var(--cor-destaque, #28a745); font-size: 1.1rem;"></i>
                        <strong style="font-size: 1rem;">Embarcacao</strong>
                    </div>
                    <?php if ($embarcacao): ?>
                        <div style="margin-left: 30px;">
                            <strong><?php echo h($embarcacao['nome']); ?></strong>
                            <?php if (!empty($embarcacao['registro'])): ?>
                                <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 8px;">Reg: <?php echo h($embarcacao['registro']); ?></span>
                            <?php endif; ?>
                            <br>
                            <small style="color: var(--cor-texto-secundario, #6c757d);">
                                <?php if (!empty($embarcacao['tipo'])) echo h($embarcacao['tipo']); ?>
                                <?php if (!empty($embarcacao['ano'])) echo ' • Ano: ' . h($embarcacao['ano']); ?>
                                <?php if (!empty($embarcacao['proprietario'])) echo ' • Proprietario: ' . h($embarcacao['proprietario']); ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div style="margin-left: 30px; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i> Embarcacao nao encontrada
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Resumo da Pessoa -->
                <div style="background: var(--cor-sidebar); padding: 15px 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #17a2b8;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <i class="fas fa-user" style="color: #17a2b8; font-size: 1.1rem;"></i>
                        <strong style="font-size: 1rem;">Pessoa Responsavel</strong>
                    </div>
                    <?php if ($pessoa): ?>
                        <div style="margin-left: 30px;">
                            <strong><?php echo h($pessoa['nome_completo']); ?></strong>
                            <?php if (!empty($pessoa['cpf'])): ?>
                                <span style="color: var(--cor-texto-secundario, #6c757d); margin-left: 8px;">CPF: <?php echo h(formatarCPF($pessoa['cpf'])); ?></span>
                            <?php endif; ?>
                            <br>
                            <small style="color: var(--cor-texto-secundario, #6c757d);">
                                <?php if (!empty($pessoa['telefone'])) echo 'Tel: ' . h($pessoa['telefone']); ?>
                                <?php if (!empty($pessoa['email'])) echo ' • Email: ' . h($pessoa['email']); ?>
                            </small>
                        </div>
                    <?php else: ?>
                        <div style="margin-left: 30px; color: #dc3545;">
                            <i class="fas fa-exclamation-triangle"></i> Pessoa nao encontrada
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Data da vistoria -->
                <div class="grid-2">
                    <div class="form-group">
                        <label for="data_vistoria">
                            <i class="fas fa-calendar"></i> Data da Vistoria *
                        </label>
                        <input type="date" 
                               id="data_vistoria" 
                               name="data_vistoria" 
                               required 
                               value="<?php echo h($data_vistoria); ?>">
                    </div>
                </div>

                <!-- Observacoes -->
                <div class="form-group">
                    <label for="observacoes">
                        <i class="fas fa-sticky-note"></i> Observacoes
                    </label>
                    <textarea id="observacoes" 
                              name="observacoes" 
                              placeholder="Observacoes sobre a vistoria..." 
                              rows="4"
                              maxlength="2000"><?php echo h($observacoes); ?></textarea>
                </div>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <a href="<?php echo APP_URL; ?>vistorias/nova?passo=2" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Anterior
                    </a>
                    <button type="submit" class="btn btn-success" 
                            onclick="return confirm('Confirmar o cadastro desta vistoria?')">
                        <i class="fas fa-check-double"></i> Confirmar e Cadastrar
                    </button>
                    <a href="<?php echo APP_URL; ?>vistorias" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Filtrar embarcacoes na lista
function filtrarListaEmbarcacoes() {
    var busca = document.getElementById('buscaEmbarcacao').value.toLowerCase();
    var itens = document.querySelectorAll('.item-embarcacao');
    itens.forEach(function(item) {
        var nome = item.getAttribute('data-nome');
        var tipo = item.getAttribute('data-tipo');
        var registro = item.getAttribute('data-registro');
        var texto = nome + ' ' + tipo + ' ' + registro;
        item.style.display = texto.indexOf(busca) !== -1 ? '' : 'none';
    });
}

// Filtrar pessoas na lista
function filtrarListaPessoas() {
    var busca = document.getElementById('buscaPessoaWiz').value.toLowerCase();
    var itens = document.querySelectorAll('.item-pessoa');
    itens.forEach(function(item) {
        var nome = item.getAttribute('data-nome');
        var cpf = item.getAttribute('data-cpf');
        var texto = nome + ' ' + cpf;
        item.style.display = texto.indexOf(busca) !== -1 ? '' : 'none';
    });
}

// Hover effect nos itens clicaveis
document.querySelectorAll('.item-embarcacao, .item-pessoa').forEach(function(item) {
    item.addEventListener('mouseenter', function() {
        this.style.background = 'rgba(46, 204, 113, 0.05)';
    });
    item.addEventListener('mouseleave', function() {
        this.style.background = '';
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>