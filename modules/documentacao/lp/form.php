<?php
/**
 * MÓDULO: Documentação > Licença Provisória (LP)
 * Formulário de Criação/Edição da Licença Provisória
 * Dados da embarcação puxados automaticamente do cadastro
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar permissão
verificar_sessao();
verificar_cargo('ADMIN');

$editando = false;
$licenca = null;

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_lp WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $licenca = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$licenca) {
        setMensagem('error', 'Licença não encontrada.');
        redirecionar(APP_URL . 'documentacao/lp');
    }
}

// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_lp WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-LP-{$seq}/{$ano}";
}

// Buscar lista de embarcações ativas para o select
$stmt_emb = $pdo->prepare("SELECT id, nome, tipo, registro, proprietario
                           FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = ($editando ? 'Editar' : 'Nova') . ' Licença Provisória - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Licença Provisória' : 'Nova Licença Provisória'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/lp" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Se já estiver assinado, mostrar aviso -->
    <?php if ($editando && $licenca['assinado']): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;">
                    <i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                    <strong>Esta licença já foi assinada digitalmente.</strong><br>
                    Assinado por: <?php echo h($licenca['assinante_nome']); ?> em 
                    <?php echo formatarDataCompleta($licenca['assinatura_em']); ?> 
                    (IP: <?php echo h($licenca['assinatura_ip']); ?>)
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/lp/actions" id="formLicenca">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($licenca['id']); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação da Licença -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação da Licença</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número da Licença</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($licenca['numero_lp']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label for="tipo_licenca">Tipo de Licença *</label>
                        <select name="tipo_licenca" id="tipo_licenca" class="form-control" required>
                            <?php
                            $tipos = [
                                'construcao' => 'Construção',
                                'alteracao' => 'Alteração',
                                'reclassificacao' => 'Reclassificação',
                                'lcec' => 'LCEC (Licença de Construção/Exploração)'
                            ];
                            $current_tipo = $editando ? $licenca['tipo_licenca'] : 'construcao';
                            foreach ($tipos as $val => $label):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $current_tipo === $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php
                            $statuses = ['rascunho' => 'Rascunho', 'emitido' => 'Emitido', 'cancelado' => 'Cancelado'];
                            $current_status = $editando ? $licenca['status'] : 'rascunho';
                            foreach ($statuses as $val => $label):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $current_status === $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="data_emissao">Data de Emissão</label>
                        <input type="date" name="data_emissao" id="data_emissao" class="form-control" required
                               value="<?php echo $editando ? h($licenca['data_emissao']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="validade_dias">Validade (dias)</label>
                        <input type="number" name="validade_dias" id="validade_dias" class="form-control" min="1"
                               value="<?php echo $editando ? h($licenca['validade_dias']) : '180'; ?>"
                               onchange="calcularValidade()">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="validade_data">Data de Validade (calculada automaticamente)</label>
                        <input type="date" name="validade_data" id="validade_data" class="form-control"
                               value="<?php echo $editando ? h($licenca['validade_data']) : ''; ?>" readonly
                               style="background: var(--cor-sidebar);">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 2: Dados da Embarcação (puxados automaticamente) -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-ship"></i> Dados da Embarcação</h3>
            </div>
            <div class="card-body">
                <?php if (!$editando): ?>
                <!-- Select de embarcação para puxar dados automaticamente -->
                <div class="form-group">
                    <label for="embarcacao_id"><i class="fas fa-search"></i> Selecionar Embarcação do Cadastro</label>
                    <select id="embarcacao_id" class="form-control" onchange="carregarDadosEmbarcacao(this.value)">
                        <option value="">-- Selecione uma embarcação --</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>"
                                data-nome="<?php echo h($emb['nome']); ?>"
                                data-tipo="<?php echo h($emb['tipo']); ?>"
                                data-prop-nome="<?php echo h($emb['proprietario'] ?? ''); ?>">
                                <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao selecionar, o nome e tipo da embarcação serão preenchidos automaticamente.</small>
                </div>
                <hr>
                <?php endif; ?>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="nome_embarcacao">Nome da Embarcação *</label>
                        <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" required
                               value="<?php echo $editando ? h($licenca['nome_embarcacao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo_embarcacao">Tipo de Embarcação</label>
                        <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control"
                               value="<?php echo $editando ? h($licenca['tipo_embarcacao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_casco">Número do Casco</label>
                        <input type="text" name="numero_casco" id="numero_casco" class="form-control"
                               value="<?php echo $editando ? h($licenca['numero_casco']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($licenca['material_casco']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_total">Comprimento Total (m)</label>
                        <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['comprimento_total']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="boca_moldada">Boca Moldada (m)</label>
                        <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['boca_moldada']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="pontal_moldado">Pontal Moldado (m)</label>
                        <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($licenca['pontal_moldado']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Proprietário/Armador -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-user-tie"></i> Proprietário / Armador</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="proprietario_nome">Nome / Razão Social</label>
                        <input type="text" name="proprietario_nome" id="proprietario_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['proprietario_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="proprietario_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="proprietario_cpf_cnpj" id="proprietario_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['proprietario_cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="proprietario_endereco">Endereço</label>
                    <textarea name="proprietario_endereco" id="proprietario_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['proprietario_endereco']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: Estaleiro/Construtor -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-hard-hat"></i> Estaleiro / Construtor</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="estaleiro_nome">Nome / Razão Social</label>
                        <input type="text" name="estaleiro_nome" id="estaleiro_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['estaleiro_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="estaleiro_cpf_cnpj">CPF / CNPJ</label>
                        <input type="text" name="estaleiro_cpf_cnpj" id="estaleiro_cpf_cnpj" class="form-control"
                               value="<?php echo $editando ? h($licenca['estaleiro_cpf_cnpj']) : ''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="estaleiro_endereco">Endereço</label>
                    <textarea name="estaleiro_endereco" id="estaleiro_endereco" class="form-control" rows="2"><?php echo $editando ? h($licenca['estaleiro_endereco']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 5: Observações / Exigências -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-list"></i> Observações / Exigências</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <textarea name="observacoes_exigencias" id="observacoes_exigencias" class="form-control" rows="5"
                              placeholder="Condições, exigências técnicas, observações da licença..."><?php echo $editando ? h($licenca['observacoes_exigencias']) : ''; ?></textarea>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 6: Responsável pela Assinatura -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando ? h($licenca['assinante_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control"
                               placeholder="Ex: Engenheira Naval"
                               value="<?php echo $editando ? h($licenca['assinante_titulo']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control"
                               placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando ? h($licenca['assinante_registro']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="card mb-3">
            <div class="card-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="<?php echo APP_URL; ?>documentacao/lp" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar Licença' : 'Salvar Licença'; ?>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
/**
 * Carrega os dados da embarcação selecionada para o formulário
 */
function carregarDadosEmbarcacao(embarcacaoId) {
    if (!embarcacaoId) return;
    
    const select = document.getElementById('embarcacao_id');
    const option = select.options[select.selectedIndex];
    
    if (!option) return;
    
    // Mapear data attributes para os campos do formulário
    const campos = {
        'nome_embarcacao': 'nome',
        'tipo_embarcacao': 'tipo',
        'numero_casco': 'casco',
        'material_casco': 'material',
        'comprimento_total': 'comprimento',
        'boca_moldada': 'boca',
        'pontal_moldado': 'pontal',
        'proprietario_nome': 'propNome',
        'proprietario_cpf_cnpj': 'propCpf',
        'proprietario_endereco': 'propEndereco',
        'estaleiro_nome': 'estaleiroNome',
        'estaleiro_cpf_cnpj': 'estaleiroCpf',
        'estaleiro_endereco': 'estaleiroEndereco'
    };
    
    for (const [fieldId, dataAttr] of Object.entries(campos)) {
        const input = document.getElementById(fieldId);
        if (input) {
            const value = option.dataset[dataAttr] || '';
            input.value = value;
        }
    }
}

/**
 * Calcula a data de validade baseada na data de emissão + validade_dias
 */
function calcularValidade() {
    const dias = parseInt(document.getElementById('validade_dias').value);
    const dataEmissao = document.getElementById('data_emissao').value;
    const validadeData = document.getElementById('validade_data');
    
    if (dias && dataEmissao) {
        const data = new Date(dataEmissao);
        data.setDate(data.getDate() + dias);
        const ano = data.getFullYear();
        const mes = String(data.getMonth() + 1).padStart(2, '0');
        const dia = String(data.getDate()).padStart(2, '0');
        validadeData.value = `${ano}-${mes}-${dia}`;
    }
}

// Calcular validade automaticamente quando mudar data de emissão
document.getElementById('data_emissao').addEventListener('change', calcularValidade);
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>