<?php
/**
 * MODULO: AGENDAMENTOS
 * Arquivo: form.php - Formulario cadastro/edicao de agendamento
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$id = $_GET['id'] ?? null;
$editando = !empty($id);

$agendamento = [
    'id'              => '',
    'proposta_id'     => '',
    'embarcacao_id'   => '',
    'cliente_id'      => '',
    'vistoriador_id'  => '',
    'tipo_vistoria'   => '',
    'data_vistoria'   => '',
    'hora_vistoria'   => '',
    'local'           => '',
    'contato_nome'    => '',
    'contato_telefone' => '',
    'status'          => 'pendente',
    'observacoes'     => '',
];

// Se editando, carregar dados
if ($editando) {
    // VISTORIADOR nao pode editar agendamentos existentes
    if ($cargo === 'VISTORIADOR') {
        setMensagem('error', 'Acesso negado. Vistoriadores nao podem editar agendamentos.');
        redirecionar(APP_URL . 'agendamentos');
    }
    try {
        $stmt = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $dados = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dados) {
            $agendamento = array_merge($agendamento, $dados);
        } else {
            setMensagem('error', 'Agendamento não encontrado.');
            redirecionar(APP_URL . 'agendamentos');
        }
    } catch (Exception $e) {
        error_log('Erro ao carregar agendamento: ' . $e->getMessage());
        setMensagem('error', 'Erro ao carregar dados do agendamento.');
        redirecionar(APP_URL . 'agendamentos');
    }
}

// Carregar listas auxiliares
try {
    // Clientes ativos
    $clientes = $pdo->query("SELECT id, nome, cpf_cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Propostas aprovadas ou enviadas (para vincular ao agendamento)
    $propostas = $pdo->query("
        SELECT p.id, p.numero, c.nome AS cliente_nome, p.valor_total 
        FROM propostas p 
        INNER JOIN clientes c ON p.cliente_id = c.id 
        WHERE p.status IN ('enviada','aprovada') 
        ORDER BY p.data_emissao DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Vistoriadores ativos (apenas ADMIN pode selecionar)
    $vistoriadores = [];
    if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR') {
        $vistoriadores = $pdo->query("SELECT id, nome FROM usuarios WHERE ativo = 1 AND cargo = 'VISTORIADOR' ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Servicos cadastrados (para tipo_vistoria como sugestão)
    $servicos = $pdo->query("SELECT id, nome FROM servicos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Embarcações ativas
    $embarcacoes = $pdo->query("SELECT id, nome, registro FROM embarcacoes WHERE ativo = 1 ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    error_log('Erro ao carregar listas: ' . $e->getMessage());
    $clientes = [];
    $propostas = [];
    $vistoriadores = [];
    $servicos = [];
    $embarcacoes = [];
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Agendamento - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="form-container">
        <div class="form-header">
            <h3>
                <i class="fas fa-calendar-check"></i> 
                <?php echo $editando ? 'Editar Agendamento' : 'Novo Agendamento'; ?>
            </h3>
            <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <form action="<?php echo APP_URL; ?>agendamentos/actions" method="POST" class="form-padrao">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="action" value="<?php echo $editando ? 'editar' : 'inserir'; ?>">
            <?php if ($cargo === 'VENDEDOR'): ?>
                <input type="hidden" name="vendedor_id" value="<?php echo h($_SESSION['usuario_id']); ?>">
            <?php endif; ?>
            <?php if ($editando): ?>
                <input type="hidden" name="id" value="<?php echo h($agendamento['id']); ?>">
            <?php endif; ?>

            <!-- Vinculo com proposta -->
            <div class="form-row">
                <div class="form-group col-12">
                    <label for="proposta_id"><i class="fas fa-file-invoice"></i> Proposta (opcional)</label>
                    <select id="proposta_id" name="proposta_id" onchange="carregarDadosProposta(this.value)">
                        <option value="">-- Sem proposta vinculada --</option>
                        <?php foreach ($propostas as $prop): ?>
                            <option value="<?php echo h($prop['id']); ?>" 
                                    data-cliente="<?php echo h($prop['cliente_nome']); ?>"
                                    <?php echo $agendamento['proposta_id'] === $prop['id'] ? 'selected' : ''; ?>>
                                <?php echo h($prop['numero']); ?> — <?php echo h($prop['cliente_nome']); ?> (<?php echo formatarMoeda($prop['valor_total']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao selecionar uma proposta, cliente e embarcação são preenchidos automaticamente.</small>
                </div>
            </div>

            <!-- Cliente e Embarcacao -->
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="cliente_id">Cliente *</label>
                    <select id="cliente_id" name="cliente_id" required onchange="carregarEmbarcacoesCliente(this.value)">
                        <option value="">-- Selecione o cliente --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?php echo h($cli['id']); ?>" 
                                    <?php echo $agendamento['cliente_id'] === $cli['id'] ? 'selected' : ''; ?>>
                                <?php echo h($cli['nome']); ?> <?php echo $cli['cpf_cnpj'] ? '(' . h($cli['cpf_cnpj']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="embarcacao_id">Embarcação *</label>
                    <select id="embarcacao_id" name="embarcacao_id" required>
                        <option value="">-- Selecione a embarcação --</option>
                        <?php foreach ($embarcacoes as $emb): ?>
                            <option value="<?php echo h($emb['id']); ?>" 
                                    <?php echo $agendamento['embarcacao_id'] === $emb['id'] ? 'selected' : ''; ?>>
                                <?php echo h($emb['nome']); ?> <?php echo $emb['registro'] ? '(' . h($emb['registro']) . ')' : ''; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Tipo de vistoria e Vistoriador -->
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="tipo_vistoria">Tipo de Vistoria *</label>
                    <select id="tipo_vistoria" name="tipo_vistoria" required>
                        <option value="">-- Selecione o tipo --</option>
                        <?php foreach ($servicos as $srv): ?>
                            <option value="<?php echo h($srv['nome']); ?>" 
                                    <?php echo $agendamento['tipo_vistoria'] === $srv['nome'] ? 'selected' : ''; ?>>
                                <?php echo h($srv['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group col-6">
                    <label for="vistoriador_id">Vistoriador Responsável</label>
                    <?php if ($cargo === 'ADMIN' || $cargo === 'VENDEDOR'): ?>
                        <select id="vistoriador_id" name="vistoriador_id">
                            <option value="">-- Selecione o vistoriador --</option>
                            <?php foreach ($vistoriadores as $v): ?>
                                <option value="<?php echo h($v['id']); ?>" 
                                        <?php echo $agendamento['vistoriador_id'] === $v['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($v['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="text" class="form-control" readonly 
                               value="<?php echo h($_SESSION['usuario_nome'] ?? 'Você (VISTORIADOR)'); ?>">
                        <input type="hidden" name="vistoriador_id" value="<?php echo h($_SESSION['usuario_id']); ?>">
                        <small class="text-muted">Como vistoriador, você será automaticamente atribuído.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Data, Hora e Local -->
            <div class="form-row">
                <div class="form-group col-4">
                    <label for="data_vistoria">Data da Vistoria *</label>
                    <input type="date" id="data_vistoria" name="data_vistoria" required
                           value="<?php echo h($agendamento['data_vistoria']); ?>"
                           min="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group col-3">
                    <label for="hora_vistoria">Hora</label>
                    <input type="time" id="hora_vistoria" name="hora_vistoria"
                           value="<?php echo h($agendamento['hora_vistoria']); ?>">
                </div>
                <div class="form-group col-5">
                    <label for="local">Local</label>
                    <input type="text" id="local" name="local"
                           value="<?php echo h($agendamento['local']); ?>"
                           placeholder="Endereço / estaleiro / porto">
                </div>
            </div>

            <!-- Contato no local -->
            <div class="form-row">
                <div class="form-group col-6">
                    <label for="contato_nome">Nome do Contato no Local</label>
                    <input type="text" id="contato_nome" name="contato_nome"
                           value="<?php echo h($agendamento['contato_nome']); ?>"
                           placeholder="Pessoa de contato">
                </div>
                <div class="form-group col-6">
                    <label for="contato_telefone">Telefone do Contato</label>
                    <input type="text" id="contato_telefone" name="contato_telefone"
                           value="<?php echo h($agendamento['contato_telefone']); ?>"
                           placeholder="(91) 99999-9999"
                           oninput="mascararTelefone(this)">
                </div>
            </div>

            <!-- Observacoes -->
            <div class="form-row">
                <div class="form-group col-12">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" rows="3"
                              placeholder="Instruções, materiais necessários, pontos de atenção..."><?php echo h($agendamento['observacoes']); ?></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Atualizar' : 'Salvar'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
var embarcacoesOriginais = null;

function mascararTelefone(input) {
    let valor = input.value.replace(/\D/g, '');
    if (valor.length > 11) valor = valor.slice(0, 11);
    if (valor.length > 6) {
        valor = valor.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3');
    } else if (valor.length > 2) {
        valor = valor.replace(/^(\d{2})(\d{1,4})$/, '($1) $2');
    } else if (valor.length > 0) {
        valor = valor.replace(/^(\d{1,2})$/, '($1');
    }
    input.value = valor;
}

function carregarDadosProposta(propostaId) {
    const selectEmbarcacao = document.getElementById('embarcacao_id');
    const selectCliente = document.getElementById('cliente_id');
    const campoVistoria = document.getElementById('tipo_vistoria');
    if (!propostaId) {
        if (selectCliente) selectCliente.value = '';
        if (campoVistoria) campoVistoria.value = '';
        restaurarEmbarcacoes();
        return;
    }
    if (!embarcacoesOriginais && selectEmbarcacao) {
        embarcacoesOriginais = selectEmbarcacao.innerHTML;
    }
    fetch('<?php echo APP_URL; ?>agendamentos/actions?action=buscar_proposta&proposta_id=' + propostaId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.cliente_id && selectCliente) {
                    selectCliente.value = data.cliente_id;
                }
                if (selectEmbarcacao && data.embarcacoes && data.embarcacoes.length > 0) {
                    let options = '<option value="">Selecione a embarcacao</option>';
                    data.embarcacoes.forEach(function(emb) {
                        const selected = (emb.id === data.embarcacao_id) ? ' selected' : '';
                        options += '<option value="' + emb.id + '"' + selected + '>' + emb.nome + '</option>';
                    });
                    selectEmbarcacao.innerHTML = options;
                }
                if (campoVistoria && data.tipo_vistoria) {
                    campoVistoria.value = data.tipo_vistoria;
                }
            }
        })
        .catch(err => console.error('Erro ao carregar proposta:', err));
}

function restaurarEmbarcacoes() {
    const selectEmbarcacao = document.getElementById('embarcacao_id');
    if (selectEmbarcacao && embarcacoesOriginais) {
        selectEmbarcacao.innerHTML = embarcacoesOriginais;
    }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>