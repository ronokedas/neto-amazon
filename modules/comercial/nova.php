<?php
/**
 * MÓDULO: COMERCIAL > PROPOSTAS
 * Arquivo: nova.php - Wizard de nova proposta
 * Passo 1: Selecionar cliente → carregar embarcações automaticamente
 * Passo 2: Selecionar serviços por embarcação com preço automático, desconto e total geral
 * Passo 3: Revisão e confirmação
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (getCargo() !== 'ADMIN') {
    setMensagem('error', 'Acesso negado. Apenas Administradores podem criar propostas.');
    redirecionar(APP_URL . 'dashboard');
}

// Buscar clientes ativos
try {
    $stmtClientes = $pdo->query("SELECT id, nome, perfil, cpf_cnpj FROM clientes WHERE status = 'ATIVO' ORDER BY nome ASC");
    $clientes = $stmtClientes->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $clientes = [];
}

// Buscar todos os serviços ativos
try {
    $stmtServicos = $pdo->query("SELECT id, nome, descricao, preco_padrao FROM servicos WHERE ativo = 1 ORDER BY nome ASC");
    $servicos = $stmtServicos->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $servicos = [];
}

$titulo_page = 'Nova Proposta - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">

    <!-- Cabeçalho do Wizard -->
    <div class="welcome-section" style="margin-bottom: 20px;">
        <div>
            <h1><i class="fas fa-file-invoice"></i> Nova Proposta</h1>
            <p>Preencha os passos abaixo para gerar uma proposta comercial.</p>
        </div>
        <a href="<?php echo APP_URL; ?>comercial/propostas" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Cancelar
        </a>
    </div>

    <!-- Indicador de Passos (Stepper) -->
    <div class="wizard-steps" id="stepper" style="display: flex; gap: 0; margin-bottom: 25px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 12px; overflow: hidden;">
        <div class="wizard-step active" data-step="1" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-destaque); color: #fff; font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">1</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-destaque); font-weight: 600;">Cliente</span>
        </div>
        <div class="wizard-step" data-step="2" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; opacity: 0.5;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-borda); color: var(--cor-texto-secundario); font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">2</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-texto-secundario); font-weight: 500;">Serviços</span>
        </div>
        <div class="wizard-step" data-step="3" style="flex: 1; text-align: center; padding: 15px 10px; cursor: pointer; transition: all 0.3s; border-bottom: 3px solid transparent; opacity: 0.5;">
            <span class="step-number" style="display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; border-radius: 50%; background: var(--cor-borda); color: var(--cor-texto-secundario); font-weight: 700; font-size: 0.85rem; margin-bottom: 6px;">3</span>
            <span class="step-label" style="display: block; font-size: 0.8rem; color: var(--cor-texto-secundario); font-weight: 500;">Revisão</span>
        </div>
    </div>

    <!-- Formulário principal -->
    <form id="wizardForm" action="<?php echo APP_URL; ?>comercial/propostas/actions" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <input type="hidden" name="action" value="criar">
        <input type="hidden" id="dadosCliente" name="dados_cliente" value="">
        <input type="hidden" id="dadosServicosJson" name="dados_servicos_json" value="">

        <!-- ===== PASSO 1: SELECIONAR CLIENTE ===== -->
        <div class="wizard-panel active" id="passo1">
            <div class="card">
                <div class="card-header">
                    <h3><i class="fas fa-user-tie"></i> Passo 1: Selecione o Cliente</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($clientes)): ?>
                        <div class="tabela-vazia">
                            <i class="fas fa-user-tie"></i>
                            <h3>Nenhum cliente cadastrado</h3>
                            <p>Cadastre um cliente antes de criar uma proposta.</p>
                            <a href="<?php echo APP_URL; ?>clientes/form" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Novo Cliente
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="filtros" style="margin-bottom: 15px;">
                            <div class="form-group" style="margin-bottom: 0; flex: 1;">
                                <label><i class="fas fa-search"></i> Buscar cliente</label>
                                <input type="text" id="buscaClienteWizard" placeholder="Nome, CPF/CNPJ..." onkeyup="filtrarClientes()">
                            </div>
                        </div>
                        <div class="cliente-grid" id="clienteGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px; max-height: 400px; overflow-y: auto; padding: 5px;">
                            <?php foreach ($clientes as $c): ?>
                            <label class="cliente-card" style="display: flex; align-items: center; gap: 12px; padding: 14px 16px; background: var(--cor-fundo); border: 2px solid var(--cor-borda); border-radius: 10px; cursor: pointer; transition: all 0.2s;">
                                <input type="radio" name="cliente_id" value="<?php echo h($c['id']); ?>" 
                                       data-nome="<?php echo h($c['nome']); ?>"
                                       data-perfil="<?php echo h(ucfirst($c['perfil'])); ?>"
                                       data-cpfcnpj="<?php echo h($c['cpf_cnpj'] ?? '-'); ?>"
                                       onchange="clienteSelecionado(this)" style="display: none;">
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: rgba(46,204,113,0.15); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user-tie" style="color: var(--cor-destaque);"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-weight: 600; color: var(--cor-texto); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo h($c['nome']); ?></div>
                                    <small style="color: var(--cor-texto-secundario);"><?php echo h(ucfirst($c['perfil'])); ?> &middot; <?php echo h($c['cpf_cnpj'] ?? 'N/I'); ?></small>
                                </div>
                                <i class="fas fa-chevron-right" style="color: var(--cor-borda); transition: color 0.2s;"></i>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="form-actions" style="margin-top: 20px; text-align: right;">
                <button type="button" class="btn btn-primary" onclick="irParaPasso(2)" id="btnPasso1" disabled>
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ===== PASSO 2: SERVIÇOS POR EMBARCAÇÃO ===== -->
        <div class="wizard-panel" id="passo2" style="display: none;">
            <!-- Info do cliente selecionado -->
            <div id="passo2ClienteInfo" style="margin-bottom: 20px; padding: 12px 16px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 10px; display: flex; align-items: center; gap: 12px;">
                <i class="fas fa-user-tie" style="color: var(--cor-destaque); font-size: 1.2rem;"></i>
                <span style="color: var(--cor-texto-secundario);">Cliente: <strong id="passo2ClienteNome" style="color: var(--cor-texto);"></strong></span>
            </div>

            <div id="embarcacoesServicosContainer">
                <div id="paso2Loading" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--cor-destaque);"></i>
                    <p style="margin-top: 10px; color: var(--cor-texto-secundario);">Carregando embarcações do cliente...</p>
                </div>
                <div id="paso2Content" style="display: none;"></div>
                <div id="paso2Vazio" style="display: none;" class="tabela-vazia">
                    <i class="fas fa-ship"></i>
                    <h3>Nenhuma embarcação vinculada</h3>
                    <p>Este cliente não possui embarcações vinculadas. Vincule embarcações ao cliente primeiro.</p>
                </div>
            </div>

            <!-- Painel de Totais -->
            <div id="totaisPainel" style="display: none; margin-top: 25px; padding: 20px; background: var(--cor-painel); border: 1px solid var(--cor-borda); border-radius: 12px;">
                <h4 style="color: var(--cor-destaque); margin-bottom: 15px;"><i class="fas fa-calculator"></i> Resumo Financeiro</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                    <div style="text-align: center; padding: 12px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);">
                        <small class="text-muted" style="display: block; margin-bottom: 4px;">Subtotal</small>
                        <span id="subtotal" style="font-size: 1.2rem; font-weight: 700; color: var(--cor-texto);">R$ 0,00</span>
                    </div>
                    <div style="text-align: center; padding: 12px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);">
                        <small class="text-muted" style="display: block; margin-bottom: 4px;">Desconto</small>
                        <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <input type="number" id="descontoGlobal" name="desconto_global" value="0" min="0" max="100" step="0.01"
                                   style="width: 80px; padding: 6px 8px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.9rem;"
                                   onchange="atualizarTotais()" title="% de desconto sobre o subtotal">
                            <span style="font-weight: 600; color: var(--cor-texto-secundario);">%</span>
                        </div>
                        <small id="descontoValor" class="text-muted" style="display: block; margin-top: 4px;">- R$ 0,00</small>
                    </div>
                    <div style="text-align: center; padding: 12px; background: rgba(46,204,113,0.08); border-radius: 8px; border: 1px solid var(--cor-destaque);">
                        <small style="display: block; margin-bottom: 4px; color: var(--cor-destaque); font-weight: 500;">TOTAL GERAL</small>
                        <span id="totalGeral" style="font-size: 1.5rem; font-weight: 700; color: var(--cor-destaque);">R$ 0,00</span>
                    </div>
                </div>
                <!-- Parcelas -->
                <div style="margin-top: 15px;">
                    <div class="form-group" style="margin-bottom: 10px;">
                        <label for="parcelas">Número de Parcelas</label>
                        <select id="parcelas" name="parcelas" style="width: auto; min-width: 150px;" onchange="atualizarTotais()">
                            <option value="1">1x (à vista)</option>
                            <option value="2">2x</option>
                            <option value="3" selected>3x</option>
                            <option value="4">4x</option>
                            <option value="5">5x</option>
                            <option value="6">6x</option>
                            <option value="12">12x</option>
                        </select>
                    </div>
                    <div id="parcelasInfo" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda); color: var(--cor-texto-secundario); font-size: 0.9rem;">
                    </div>
                </div>
            </div>

            <div class="form-actions" style="margin-top: 20px; display: flex; justify-content: space-between;">
                <button type="button" class="btn btn-secondary" onclick="irParaPasso(1)">
                    <i class="fas fa-arrow-left"></i> Voltar
                </button>
                <button type="button" class="btn btn-primary" onclick="irParaPasso(3)" id="btnPasso2">
                    Próximo <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- ===== PASSO 3: REVISÃO E CONFIRMAÇÃO ===== -->
        <div class="wizard-panel" id="passo3" style="display: none;">
            <div id="reviewLoading" style="text-align: center; padding: 40px;">
                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--cor-destaque);"></i>
                <p style="margin-top: 10px; color: var(--cor-texto-secundario);">Montando revisão...</p>
            </div>
            <div id="reviewContent" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-check-double"></i> Revisão da Proposta</h3>
                    </div>
                    <div class="card-body">
                        <!-- Resumo Cliente -->
                        <div class="review-section" style="margin-bottom: 20px;">
                            <h4 style="color: var(--cor-destaque); margin-bottom: 10px;"><i class="fas fa-user-tie"></i> Cliente</h4>
                            <div id="reviewCliente" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);"></div>
                        </div>
                        <!-- Serviços por Embarcação -->
                        <div class="review-section" style="margin-bottom: 20px;">
                            <h4 style="color: var(--cor-destaque); margin-bottom: 10px;"><i class="fas fa-ship"></i> Serviços por Embarcação</h4>
                            <div id="reviewPorEmbarcacao" style="padding: 12px 16px; background: var(--cor-fundo); border-radius: 8px; border: 1px solid var(--cor-borda);"></div>
                        </div>
                        <!-- Totais -->
                        <div id="reviewTotal" style="padding: 16px 20px; background: rgba(46,204,113,0.08); border: 1px solid var(--cor-destaque); border-radius: 10px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Subtotal:</span>
                                <span id="rSubtotal" style="font-weight: 600; color: var(--cor-texto);">R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="text-muted">Desconto (<span id="rDescontoPerc">0</span>%):</span>
                                <span id="rDesconto" style="font-weight: 600; color: var(--cor-erro);">- R$ 0,00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 8px; border-top: 1px solid var(--cor-borda);">
                                <span style="font-weight: 600; color: var(--cor-destaque);">TOTAL GERAL:</span>
                                <span id="rTotalGeral" style="font-size: 1.5rem; font-weight: 700; color: var(--cor-destaque);">R$ 0,00</span>
                            </div>
                            <div id="rParcelas" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--cor-borda); color: var(--cor-texto-secundario); font-size: 0.9rem;"></div>
                        </div>

                        <!-- Forma de Pagamento -->
                        <div style="margin-top: 20px;">
                            <div class="form-group">
                                <label for="forma_pagamento"><i class="fas fa-credit-card"></i> Forma de Pagamento</label>
                                <select id="forma_pagamento" name="forma_pagamento" style="width: auto; min-width: 200px;">
                                    <option value="parcelado" selected>Parcelado (cartão / boleto parcelado)</option>
                                    <option value="a_vista">À Vista</option>
                                    <option value="boleto">Boleto Bancário</option>
                                    <option value="pix">PIX</option>
                                </select>
                            </div>
                        </div>

                        <!-- Observações -->
                        <div style="margin-top: 20px;">
                            <div class="form-group">
                                <label for="observacoes"><i class="fas fa-sticky-note"></i> Observações</label>
                                <textarea id="observacoes" name="observacoes" rows="3" style="width: 100%;"
                                          placeholder="Condições especiais, validade da proposta, informações adicionais..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-actions" style="margin-top: 20px; display: flex; justify-content: space-between;">
                    <button type="button" class="btn btn-secondary" onclick="irParaPasso(2)">
                        <i class="fas fa-arrow-left"></i> Voltar
                    </button>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Gerar Proposta
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Template dos serviços (será clonado via JS para cada embarcação) -->
<template id="templateServicosPorEmbarcacao">
    <div class="card embarcacao-bloco" style="margin-bottom: 20px;">
        <div class="card-header" style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleEmbarcacaoBloco(this)">
            <i class="fas fa-ship" style="color: var(--cor-destaque);"></i>
            <h3 class="emb-nome" style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;"></h3>
            <span class="emb-total" style="font-weight: 700; color: var(--cor-destaque); font-size: 1rem; margin-right: 10px;"></span>
            <i class="fas fa-chevron-down" style="color: var(--cor-texto-secundario); transition: transform 0.3s;"></i>
        </div>
        <div class="card-body emb-body" style="display: block;">
            <table style="width: 100%; border-collapse: collapse;" class="servicos-tabela">
                <thead>
                    <tr style="border-bottom: 1px solid var(--cor-borda);">
                        <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                        <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                        <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                        <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                        <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody class="servicos-tbody"></tbody>
            </table>
        </div>
    </div>
</template>

<template id="templateServicoLinha">
    <tr style="border-bottom: 1px solid var(--cor-borda); transition: background 0.2s;" class="servico-linha">
        <td style="padding: 8px 12px; text-align: center;">
            <input type="checkbox" class="check-servico" onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);">
        </td>
        <td style="padding: 8px 12px;">
            <span class="servico-nome" style="font-weight: 500;"></span>
            <br><small class="servico-desc text-muted"></small>
        </td>
        <td style="padding: 8px 12px; text-align: center;">
            <input type="number" class="qtd-servico" value="1" min="1" max="99"
                   style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;"
                   onchange="servicoQtdChanged(this)" onfocus="this.select()">
        </td>
        <td style="padding: 8px 12px; text-align: right;">
            <span class="preco-unitario" style="font-weight: 500;"></span>
        </td>
        <td style="padding: 8px 12px; text-align: right;">
            <span class="subtotal-servico" style="font-weight: 600; color: var(--cor-destaque);"></span>
        </td>
    </tr>
</template>

<script>
// ============ DADOS GLOBAIS ============
const ALL_SERVICOS = <?php echo json_encode($servicos, JSON_UNESCAPED_UNICODE); ?>;
let clienteSelecionadoData = null;
let embarcacoesCarregadas = []; // { id, nome, registro }

// ============ NAVEGAÇÃO DO WIZARD ============
function irParaPasso(numero) {
    document.querySelectorAll('.wizard-panel').forEach(p => p.style.display = 'none');
    document.getElementById('passo' + numero).style.display = 'block';

    // Atualiza stepper
    document.querySelectorAll('.wizard-step').forEach(step => {
        const s = parseInt(step.dataset.step);
        step.classList.remove('active');
        step.style.opacity = (s <= numero) ? '1' : '0.5';
        const numEl = step.querySelector('.step-number');
        const lblEl = step.querySelector('.step-label');
        if (s <= numero) {
            numEl.style.background = 'var(--cor-destaque)';
            numEl.style.color = '#fff';
            lblEl.style.color = 'var(--cor-destaque)';
            lblEl.style.fontWeight = '600';
        } else {
            numEl.style.background = 'var(--cor-borda)';
            numEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.color = 'var(--cor-texto-secundario)';
            lblEl.style.fontWeight = '500';
        }
        if (s === numero) {
            step.classList.add('active');
            step.style.borderBottomColor = 'var(--cor-destaque)';
        } else {
            step.style.borderBottomColor = 'transparent';
        }
    });

    // Ações específicas
    if (numero === 2) carregarPasso2();
    if (numero === 3) montarRevisao();

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============ PASSO 1: CLIENTE ============
function filtrarClientes() {
    const termo = document.getElementById('buscaClienteWizard').value.toLowerCase();
    document.querySelectorAll('.cliente-card').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(termo) ? 'flex' : 'none';
    });
}

function clienteSelecionado(radio) {
    document.querySelectorAll('.cliente-card').forEach(c => {
        c.style.borderColor = 'var(--cor-borda)';
        c.style.background = 'var(--cor-fundo)';
        c.querySelector('.fa-chevron-right').style.color = 'var(--cor-borda)';
    });
    const card = radio.closest('.cliente-card');
    card.style.borderColor = 'var(--cor-destaque)';
    card.style.background = 'rgba(46,204,113,0.08)';
    card.querySelector('.fa-chevron-right').style.color = 'var(--cor-destaque)';

    clienteSelecionadoData = {
        id: radio.value,
        nome: radio.dataset.nome,
        perfil: radio.dataset.perfil,
        cpfcnpj: radio.dataset.cpfcnpj
    };
    document.getElementById('dadosCliente').value = JSON.stringify(clienteSelecionadoData);
    document.getElementById('btnPasso1').disabled = false;
}

// ============ PASSO 2: SERVIÇOS POR EMBARCAÇÃO ============
function carregarPasso2() {
    if (!clienteSelecionadoData) return;

    document.getElementById('paso2Loading').style.display = 'block';
    document.getElementById('paso2Content').style.display = 'none';
    document.getElementById('paso2Vazio').style.display = 'none';
    document.getElementById('totaisPainel').style.display = 'none';
    document.getElementById('passo2ClienteNome').textContent = clienteSelecionadoData.nome;

    fetch('<?php echo APP_URL; ?>comercial/propostas/actions?action=embarcacoes_cliente&cliente_id=' + encodeURIComponent(clienteSelecionadoData.id))
        .then(r => r.json())
        .then(data => {
            document.getElementById('paso2Loading').style.display = 'none';

            if (!data.embarcacoes || data.embarcacoes.length === 0) {
                document.getElementById('paso2Vazio').style.display = 'block';
                embarcacoesCarregadas = [];
                return;
            }

            embarcacoesCarregadas = data.embarcacoes;
            construirGradeServicos(data.embarcacoes);
        })
        .catch(err => {
            document.getElementById('paso2Loading').style.display = 'none';
            document.getElementById('paso2Vazio').style.display = 'block';
            document.getElementById('paso2Vazio').querySelector('p').textContent = 'Erro ao carregar embarcações.';
            console.error(err);
        });
}

function construirGradeServicos(embarcacoes) {
    const container = document.getElementById('paso2Content');
    const tplBloco = document.getElementById('templateServicosPorEmbarcacao');
    const tplLinha = document.getElementById('templateServicoLinha');

    let html = '';
    embarcacoes.forEach((emb, idx) => {
        const bloco = tplBloco.content.cloneNode(true);
        bloco.querySelector('.emb-nome').textContent = emb.nome + (emb.registro ? ' (' + emb.registro + ')' : '');
        bloco.querySelector('.emb-total').id = 'embTotal_' + emb.id;

        const tbody = bloco.querySelector('.servicos-tbody');
        ALL_SERVICOS.forEach(s => {
            const linha = tplLinha.content.cloneNode(true);
            linha.querySelector('.check-servico').dataset.embId = emb.id;
            linha.querySelector('.check-servico').dataset.servId = s.id;
            linha.querySelector('.servico-nome').textContent = s.nome;
            linha.querySelector('.servico-desc').textContent = (s.descricao && s.descricao.length > 60) ? s.descricao.substring(0, 60) + '...' : (s.descricao || '');
            linha.querySelector('.qtd-servico').dataset.embId = emb.id;
            linha.querySelector('.qtd-servico').dataset.servId = s.id;
            linha.querySelector('.preco-unitario').textContent = formatarMoeda(parseFloat(s.preco_padrao));
            linha.querySelector('.subtotal-servico').id = 'sub_' + emb.id + '_' + s.id;
            linha.querySelector('.subtotal-servico').textContent = formatarMoeda(0);
            linha.querySelector('.subtotal-servico').dataset.preco = s.preco_padrao;
            tbody.appendChild(linha);
        });

        // Append bloco ao container
        const wrapper = document.createElement('div');
        wrapper.appendChild(bloco);
        container.appendChild(wrapper);
        container.appendChild(bloco); // precisa ser assim com templates
    });

    // Reconstruir usando innerHTML pois template clonado é complexo
    // Vamos usar abordagem direta com strings
    container.innerHTML = '';
    embarcacoes.forEach(emb => {
        let blocoHtml = `
        <div class="card embarcacao-bloco" style="margin-bottom: 20px;">
            <div class="card-header" style="display: flex; align-items: center; gap: 10px; cursor: pointer;" onclick="toggleEmbarcacaoBloco(this)">
                <i class="fas fa-ship" style="color: var(--cor-destaque);"></i>
                <h3 style="flex: 1; color: var(--cor-texto); font-size: 1rem; margin: 0;">${esc(emb.nome)} ${emb.registro ? '<small class="text-muted">(' + esc(emb.registro) + ')</small>' : ''}</h3>
                <span id="embTotal_${emb.id}" style="font-weight: 700; color: var(--cor-destaque); font-size: 1rem; margin-right: 10px;">${formatarMoeda(0)}</span>
                <i class="fas fa-chevron-down" style="color: var(--cor-texto-secundario); transition: transform 0.3s;"></i>
            </div>
            <div class="card-body emb-body">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid var(--cor-borda);">
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 40px;"></th>
                            <th style="text-align: left; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem;">Serviço</th>
                            <th style="text-align: center; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 70px;">Qtd</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Preço Unit.</th>
                            <th style="text-align: right; padding: 8px 12px; color: var(--cor-texto-secundario); font-size: 0.8rem; width: 110px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>`;

        ALL_SERVICOS.forEach(s => {
            blocoHtml += `
                        <tr class="servico-linha" style="border-bottom: 1px solid var(--cor-borda);">
                            <td style="padding: 8px 12px; text-align: center;">
                                <input type="checkbox" class="check-servico" data-emb-id="${emb.id}" data-serv-id="${s.id}" 
                                       onchange="servicoToggled(this)" style="width: 16px; height: 16px; cursor: pointer; accent-color: var(--cor-destaque);">
                            </td>
                            <td style="padding: 8px 12px;">
                                <span style="font-weight: 500;">${esc(s.nome)}</span>
                                ${s.descricao ? '<br><small class="text-muted">' + esc(s.descricao.length > 60 ? s.descricao.substring(0, 60) + '...' : s.descricao) + '</small>' : ''}
                            </td>
                            <td style="padding: 8px 12px; text-align: center;">
                                <input type="number" value="1" min="1" max="99" data-emb-id="${emb.id}" data-serv-id="${s.id}"
                                       class="qtd-servico" onchange="servicoQtdChanged(this)" onfocus="this.select()"
                                       style="width: 55px; padding: 4px 6px; background: var(--cor-fundo); border: 1px solid var(--cor-borda); border-radius: 6px; color: var(--cor-texto); text-align: center; font-size: 0.85rem;" disabled>
                            </td>
                            <td style="padding: 8px 12px; text-align: right;">
                                <span style="font-weight: 500;">${formatarMoeda(parseFloat(s.preco_padrao))}</span>
                            </td>
                            <td style="padding: 8px 12px; text-align: right;">
                                <span id="sub_${emb.id}_${s.id}" data-preco="${s.preco_padrao}" style="font-weight: 600; color: var(--cor-destaque);">${formatarMoeda(0)}</span>
                            </td>
                        </tr>`;
        });

        blocoHtml += `</tbody></table></div></div>`;
        container.innerHTML += blocoHtml;
    });

    document.getElementById('paso2Content').style.display = 'block';
    document.getElementById('totaisPainel').style.display = 'block';
    atualizarTotais();
}

function toggleEmbarcacaoBloco(headerEl) {
    const body = headerEl.nextElementSibling;
    const chevron = headerEl.querySelector('.fa-chevron-down');
    if (body.style.display === 'none') {
        body.style.display = 'block';
        chevron.style.transform = 'rotate(0deg)';
    } else {
        body.style.display = 'none';
        chevron.style.transform = 'rotate(-90deg)';
    }
}

// ============ INTERAÇÕES NOS SERVIÇOS ============
function servicoToggled(checkbox) {
    const embId = checkbox.dataset.embId;
    const servId = checkbox.dataset.servId;
    const linha = checkbox.closest('tr');
    const qtdInput = linha.querySelector('.qtd-servico');

    if (checkbox.checked) {
        linha.style.background = 'rgba(46,204,113,0.05)';
        qtdInput.disabled = false;
        qtdInput.value = 1;
    } else {
        linha.style.background = '';
        qtdInput.disabled = true;
        qtdInput.value = 0;
    }

    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function servicoQtdChanged(input) {
    const embId = input.dataset.embId;
    const servId = input.dataset.servId;
    atualizarSubtotalServico(embId, servId);
    atualizarTotais();
}

function atualizarSubtotalServico(embId, servId) {
    const linha = document.querySelector(`.check-servico[data-emb-id="${embId}"][data-serv-id="${servId}"]`).closest('tr');
    const checkbox = linha.querySelector('.check-servico');
    const qtdInput = linha.querySelector('.qtd-servico');
    const subEl = document.getElementById('sub_' + embId + '_' + servId);

    if (!checkbox.checked) {
        subEl.textContent = formatarMoeda(0);
        return;
    }

    const preco = parseFloat(subEl.dataset.preco) || 0;
    const qtd = Math.max(1, parseInt(qtdInput.value) || 1);
    qtdInput.value = qtd;
    const subtotal = preco * qtd;
    subEl.textContent = formatarMoeda(subtotal);
}

// ============ TOTAIS ============
function atualizarTotais() {
    let subtotalGeral = 0;

    // Calcula subtotal por embarcação e geral
    document.querySelectorAll('.embarcacao-bloco').forEach(bloco => {
        let embTotal = 0;
        const checks = bloco.querySelectorAll('.check-servico');
        checks.forEach(cb => {
            if (cb.checked) {
                const embId = cb.dataset.embId;
                const servId = cb.dataset.servId;
                const subEl = document.getElementById('sub_' + embId + '_' + servId);
                const preco = parseFloat(subEl.dataset.preco) || 0;
                const qtdInput = bloco.querySelector(`.qtd-servico[data-emb-id="${embId}"][data-serv-id="${servId}"]`);
                const qtd = Math.max(1, parseInt(qtdInput?.value) || 1);
                embTotal += preco * qtd;
            }
        });
        subtotalGeral += embTotal;
        // Atualiza o total da embarcação no header
        const embTotalEl = bloco.querySelector('[id^="embTotal_"]');
        if (embTotalEl) embTotalEl.textContent = formatarMoeda(embTotal);
    });

    // Desconto
    const descontoPerc = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    const descontoValor = subtotalGeral * (descontoPerc / 100);
    const totalGeral = Math.max(0, subtotalGeral - descontoValor);

    // Atualiza display
    document.getElementById('subtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('descontoValor').textContent = '- ' + formatarMoeda(descontoValor);
    document.getElementById('totalGeral').textContent = formatarMoeda(totalGeral);

    // Parcelas
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;
    const valorParcela = totalGeral / parcelas;
    let ph = '';
    for (let i = 1; i <= parcelas; i++) {
        ph += `<div style="padding: 3px 0;">Parcela ${i}/<strong>${parcelas}: ${formatarMoeda(valorParcela)}</strong></div>`;
    }
    document.getElementById('parcelasInfo').innerHTML = ph;
}

// ============ PASSO 3: REVISÃO ============
function montarRevisao() {
    // Coleta todos os dados dos serviços selecionados
    const dadosServicos = [];
    let subtotalGeral = 0;

    document.querySelectorAll('.embarcacao-bloco').forEach(bloco => {
        const embHeader = bloco.querySelector('.card-header h3');
        const embNome = embHeader ? embHeader.textContent.replace(/\s*\(.*/, '').trim() : '';
        const embId = '';
        let embTotal = 0;
        const servicosDaEmb = [];

        const checks = bloco.querySelectorAll('.check-servico:checked');
        checks.forEach(cb => {
            const embId = cb.dataset.embId;
            const servId = cb.dataset.servId;
            const linha = cb.closest('tr');
            const nomeServ = linha.querySelector('td:nth-child(2) span').textContent.trim();
            const preco = parseFloat(document.getElementById('sub_' + embId + '_' + servId).dataset.preco) || 0;
            const qtd = Math.max(1, parseInt(linha.querySelector('.qtd-servico').value) || 1);
            const subtotal = preco * qtd;
            embTotal += subtotal;
            servicosDaEmb.push({ servico_id: servId, nome: nomeServ, preco, qtd, subtotal, quantidade: qtd });
        });

        if (checks.length > 0) {
            // Pegar nome real e registro da embarcação
            const embData = embarcacoesCarregadas.find(e => e.id === checks[0].dataset.embId);
            dadosServicos.push({
                embarcacao_id: checks[0].dataset.embId,
                embarcacao_nome: embData ? embData.nome : embNome,
                embarcacao_registro: embData ? (embData.registro || 'N/I') : '',
                total: embTotal,
                servicos: servicosDaEmb
            });
            subtotalGeral += embTotal;
        }
    });

    // Salva JSON para envio
    document.getElementById('dadosServicosJson').value = JSON.stringify(dadosServicos);

    // Desconto e total
    const descontoPerc = parseFloat(document.getElementById('descontoGlobal').value) || 0;
    const descontoValor = subtotalGeral * (descontoPerc / 100);
    const totalGeral = Math.max(0, subtotalGeral - descontoValor);
    const parcelas = parseInt(document.getElementById('parcelas').value) || 1;

    // Monta HTML da revisão
    document.getElementById('reviewCliente').innerHTML = `
        <strong>${clienteSelecionadoData?.nome || ''}</strong><br>
        <small class="text-muted">Perfil: ${clienteSelecionadoData?.perfil || ''} &middot; CPF/CNPJ: ${clienteSelecionadoData?.cpfcnpj || ''}</small>`;

    let revEmbHtml = '';
    dadosServicos.forEach(ds => {
        revEmbHtml += `
        <div style="margin-bottom: 15px; padding: 12px; background: var(--cor-sidebar); border-radius: 8px; border: 1px solid var(--cor-borda);">
            <h5 style="color: var(--cor-destaque); margin-bottom: 8px;">
                <i class="fas fa-ship"></i> ${esc(ds.embarcacao_nome)}
                ${ds.embarcacao_registro !== 'N/I' ? '<small class="text-muted">(' + esc(ds.embarcacao_registro) + ')</small>' : ''}
            </h5>
            <table style="width: 100%; border-collapse: collapse;">
                <thead><tr style="border-bottom: 1px solid var(--cor-borda);">
                    <th style="text-align: left; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem;">Serviço</th>
                    <th style="text-align: center; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 50px;">Qtd</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Unit.</th>
                    <th style="text-align: right; padding: 6px; color: var(--cor-texto-secundario); font-size: 0.75rem; width: 90px;">Subtotal</th>
                </tr></thead><tbody>`;
        ds.servicos.forEach(sv => {
            revEmbHtml += `<tr style="border-bottom: 1px solid var(--cor-borda);">
                <td style="padding: 6px;">${esc(sv.nome)}</td>
                <td style="text-align: center; padding: 6px;">${sv.qtd}</td>
                <td style="text-align: right; padding: 6px;">${formatarMoeda(sv.preco)}</td>
                <td style="text-align: right; padding: 6px; font-weight: 600;">${formatarMoeda(sv.subtotal)}</td>
            </tr>`;
        });
        revEmbHtml += `<tr><td colspan="3" style="text-align: right; padding: 6px; font-weight: 600;">Total da Embarcação:</td>
            <td style="text-align: right; padding: 6px; font-weight: 700; color: var(--cor-destaque);">${formatarMoeda(ds.total)}</td></tr>`;
        revEmbHtml += '</tbody></table></div>';
    });
    document.getElementById('reviewPorEmbarcacao').innerHTML = revEmbHtml || '<p class="text-muted">Nenhum serviço selecionado.</p>';

    // Totais
    document.getElementById('rSubtotal').textContent = formatarMoeda(subtotalGeral);
    document.getElementById('rDescontoPerc').textContent = descontoPerc.toFixed(2).replace('.', ',');
    document.getElementById('rDesconto').textContent = '- ' + formatarMoeda(descontoValor);
    document.getElementById('rTotalGeral').textContent = formatarMoeda(totalGeral);

    const valorParcela = totalGeral / parcelas;
    let rph = '';
    for (let i = 1; i <= parcelas; i++) {
        rph += `${i}x de <strong>${formatarMoeda(valorParcela)}</strong>`;
        if (parcelas > 1 && i < parcelas) rph += ' &middot; ';
    }
    document.getElementById('rParcelas').innerHTML = rph;

    // Mostra conteúdo, esconde loading
    document.getElementById('reviewLoading').style.display = 'none';
    document.getElementById('reviewContent').style.display = 'block';
}

// ============ UTILITÁRIOS ============
function formatarMoeda(valor) {
    return 'R$ ' + valor.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function esc(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
}
</script>

<style>
.wizard-step.active .step-label { color: var(--cor-destaque) !important; font-weight: 600 !important; }
.cliente-card:hover { border-color: var(--cor-destaque) !important; }
.servico-linha:hover { background: rgba(46,204,113,0.03) !important; }
.emb-body table { font-size: 0.9rem; }
</style>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>