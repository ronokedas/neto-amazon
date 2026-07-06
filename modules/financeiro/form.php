<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: form.php - Formulario para criar / editar lancamento
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e cargo ADMIN
verificar_sessao();
if (!podeAcessar('financeiro')) {
    header('Location: ' . APP_URL . 'dashboard?erro=sem_permissao');
    exit;
}

// Buscar lancamento se for edicao
$id = $_GET['id'] ?? '';
$lancamento = null;
$isEdicao = false;

if (!empty($id)) {
    $isEdicao = true;
    try {
        $stmt = $pdo->prepare("SELECT * FROM financeiro_lancamentos WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $lancamento = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lancamento) {
            setMensagem('error', 'Lancamento nao encontrado.');
            redirecionar(APP_URL . 'financeiro');
        }
    } catch (Exception $e) {
        error_log('Erro ao buscar lancamento: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do lancamento.');
        redirecionar(APP_URL . 'financeiro');
    }
}

// Gerar CSRF token
$csrf = gerarCSRF();

// Valor formatado para exibicao no form
$valorFormatado = '';
if ($lancamento) {
    $valorFormatado = number_format($lancamento['valor'], 2, ',', '.');
}

$titulo_page = ($isEdicao ? 'Editar' : 'Novo') . ' Lancamento - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="card" style="max-width: 700px;">
        <div class="card-header">
            <h3 style="color: var(--cor-destaque); margin: 0;">
                <i class="fas <?php echo $isEdicao ? 'fa-edit' : 'fa-plus-circle'; ?>"></i>
                <?php echo $isEdicao ? 'Editar Lancamento' : 'Novo Lancamento'; ?>
            </h3>
        </div>
        <div class="card-body">
            <form method="POST" 
                  action="<?php echo APP_URL; ?>financeiro/actions?action=salvar" 
                  id="formFinanceiro"
                  onsubmit="return validarFormulario('formFinanceiro')">
                
                <input type="hidden" name="csrf_token" value="<?php echo h($csrf); ?>">
                <input type="hidden" name="id" value="<?php echo h($lancamento['id'] ?? ''); ?>">

                <div class="grid-2">
                    <!-- Tipo -->
                    <div class="form-group">
                        <label for="tipo">
                            <i class="fas fa-tag"></i> Tipo *
                        </label>
                        <select id="tipo" name="tipo" required>
                            <option value="">Selecione...</option>
                            <option value="RECEITA" <?php echo ($lancamento['tipo'] ?? '') === 'RECEITA' ? 'selected' : ''; ?>>
                                💰 Receita
                            </option>
                            <option value="DESPESA" <?php echo ($lancamento['tipo'] ?? '') === 'DESPESA' ? 'selected' : ''; ?>>
                                💸 Despesa
                            </option>
                        </select>
                    </div>

                    <!-- Frequência (Recorrência) -->
                    <div class="form-group">
                        <label for="frequencia">
                            <i class="fas fa-sync"></i> Recorrência *
                        </label>
                        <select id="frequencia" name="frequencia" required>
                            <option value="unica" <?php echo ($lancamento['frequencia'] ?? 'unica') === 'unica' ? 'selected' : ''; ?>>Lançamento Único</option>
                            <option value="mensal" <?php echo ($lancamento['frequencia'] ?? '') === 'mensal' ? 'selected' : ''; ?>>Mensal</option>
                            <option value="trimestral" <?php echo ($lancamento['frequencia'] ?? '') === 'trimestral' ? 'selected' : ''; ?>>Trimestral</option>
                            <option value="anual" <?php echo ($lancamento['frequencia'] ?? '') === 'anual' ? 'selected' : ''; ?>>Anual</option>
                        </select>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Vencimento -->
                    <div class="form-group">
                        <label for="data_vencimento">
                            <i class="fas fa-calendar"></i> Vencimento *
                        </label>
                        <input type="date" 
                               id="data_vencimento" 
                               name="data_vencimento" 
                               required
                               value="<?php echo h($lancamento['data_vencimento'] ?? date('Y-m-d')); ?>">
                    </div>

                    <!-- Cliente / Despachante -->
                    <div class="form-group">
                        <label for="cliente_id">
                            <i class="fas fa-user-tie"></i> Pessoa / Despachante
                        </label>
                        <select id="cliente_id" name="cliente_id" style="width: 100%; padding: 8px 12px; border: 1px solid var(--cor-borda); border-radius: 4px;">
                            <option value="">-- Selecione (Opcional) --</option>
                            <?php
                            try {
                                $stmtCli = $pdo->query("SELECT id, nome, perfil, cpf_cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC");
                                while ($c = $stmtCli->fetch(PDO::FETCH_ASSOC)) {
                                    $selected = (($lancamento['cliente_id'] ?? '') == $c['id']) ? 'selected' : '';
                                    // Adiciona badge visual (na listagem) ou sufixo para Despachantes
                                    $sufixo = ($c['perfil'] == 'despachante') ? ' [Despachante]' : '';
                                    echo "<option value='".h($c['id'])."' $selected>".h($c['nome'])." (".h($c['cpf_cnpj']).")" . $sufixo . "</option>";
                                }
                            } catch (Exception $e) {
                                error_log('Erro ao carregar clientes no financeiro: ' . $e->getMessage());
                            }
                            ?>
                        </select>
                        <small class="text-muted">Importante para vincular pagamentos de serviços (ex: Despachantes).</small>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Status -->
                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-check-circle"></i> Status *
                        </label>
                        <select id="status" name="status" required>
                            <option value="PAGO" <?php echo ($lancamento['status'] ?? 'PAGO') === 'PAGO' ? 'selected' : ''; ?>>
                                Pago / Recebido
                            </option>
                            <option value="PENDENTE" <?php echo ($lancamento['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>
                                Pendente
                            </option>
                            <option value="CANCELADO" <?php echo ($lancamento['status'] ?? '') === 'CANCELADO' ? 'selected' : ''; ?>>
                                Cancelado
                            </option>
                        </select>
                    </div>

                    <!-- Data Pagamento -->
                    <div class="form-group">
                        <label for="data">
                            <i class="fas fa-calendar-check"></i> Data Pagamento
                        </label>
                        <input type="date" 
                               id="data" 
                               name="data" 
                               value="<?php echo h($lancamento['data'] ?? ''); ?>">
                        <small class="text-muted">Se pago, preencha a data</small>
                    </div>
                </div>

                <div class="grid-2">
                    <!-- Descricao -->
                    <div class="form-group">
                        <label for="descricao">
                            <i class="fas fa-align-left"></i> Descricao *
                        </label>
                        <input type="text" 
                               id="descricao" 
                               name="descricao" 
                               placeholder="Descricao do lancamento" 
                               required 
                               maxlength="300"
                               value="<?php echo h($lancamento['descricao'] ?? ''); ?>">
                    </div>

                    <!-- Valor -->
                    <div class="form-group">
                        <label for="valor">
                            <i class="fas fa-dollar-sign"></i> Valor (R$) *
                        </label>
                        <input type="text" 
                               id="valor" 
                               name="valor" 
                               placeholder="0,00" 
                               required
                               maxlength="12"
                               value="<?php echo h($valorFormatado); ?>"
                               oninput="formatarMoedaInput(this)">
                    </div>
                </div>

                <!-- Categoria -->
                <div class="form-group">
                    <label for="categoria">
                        <i class="fas fa-folder"></i> Categoria
                    </label>
                    <input type="text" 
                           id="categoria" 
                           name="categoria" 
                           placeholder="Ex: Servicos, Material, Frete, Combustivel..." 
                           maxlength="100"
                           value="<?php echo h($lancamento['categoria'] ?? ''); ?>">
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
                              maxlength="1000"><?php echo h($lancamento['observacoes'] ?? ''); ?></textarea>
                </div>

                <?php if ($isEdicao): ?>
                <!-- Info de data -->
                <div class="grid-2" style="margin-top: 10px;">
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-plus"></i> 
                            Criado em: <?php echo formatarDataCompleta($lancamento['criado_em'] ?? ''); ?>
                        </label>
                    </div>
                    <div class="form-group">
                        <label class="text-muted" style="font-size: 0.8rem;">
                            <i class="fas fa-calendar-check"></i> 
                            Atualizado: <?php echo formatarDataCompleta($lancamento['atualizado_em'] ?? ''); ?>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Botoes -->
                <div class="d-flex gap-2" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <?php echo $isEdicao ? 'Atualizar' : 'Salvar Lancamento'; ?>
                    </button>
                    <a href="<?php echo APP_URL; ?>financeiro" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Formatar valor monetario em tempo real (formato brasileiro)
function formatarMoedaInput(input) {
    var valor = input.value.replace(/\D/g, '');
    if (valor === '') {
        input.value = '';
        return;
    }
    valor = (parseInt(valor) / 100).toFixed(2);
    valor = valor.replace('.', ',');
    valor = valor.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    input.value = valor;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>