<?php
/**
 * MÓDULO: Documentação > Certificados CNARQ
 * Formulário de Criação/Edição do Certificado Nacional de Arqueação
 * Dados da embarcação puxados automaticamente do cadastro
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar permissão
verificar_sessao();
verificar_cargo('ADMIN');

$editando = false;
$certificado = null;
$convalidacoes = [];

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_cnarq WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/cnarq');
    }

    // Buscar convalidações (tabela genérica)
    $stmt_conv = $pdo->prepare("SELECT * FROM cert_convalidacoes WHERE certificado_id = :cert_id AND tipo_certificado = 'CNARQ' ORDER BY id");
    $stmt_conv->execute([':cert_id' => $id]);
    $convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);
}

// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CNARQ-{$seq}/{$ano}";
}

// Buscar lista de embarcações ativas para o select
$stmt_emb = $pdo->prepare("SELECT id, nome, tipo, registro
                           FROM embarcacoes WHERE ativo = 1 ORDER BY nome");
$stmt_emb->execute();
$embarcacoes = $stmt_emb->fetchAll(PDO::FETCH_ASSOC);

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CNARQ - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CNARQ' : 'Novo Certificado CNARQ'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/cnarq" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Voltar
        </a>
    </div>

    <!-- Se já estiver assinado, mostrar aviso -->
    <?php if ($editando && $certificado['assinado']): ?>
        <div class="card mb-3" style="border-left: 4px solid var(--cor-destaque);">
            <div class="card-body">
                <p style="margin:0;">
                    <i class="fas fa-lock" style="color: var(--cor-destaque);"></i>
                    <strong>Este certificado já foi assinado digitalmente.</strong><br>
                    Assinado por: <?php echo h($certificado['assinante_nome']); ?> em 
                    <?php echo formatarDataCompleta($certificado['assinatura_em']); ?> 
                    (IP: <?php echo h($certificado['assinatura_ip']); ?>)
                </p>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo APP_URL; ?>documentacao/cnarq/actions" id="formCertificado">
        <input type="hidden" name="action" value="salvar">
        <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
        <?php if ($editando): ?>
            <input type="hidden" name="id" value="<?php echo h($certificado['id']); ?>">
        <?php endif; ?>

        <!-- SEÇÃO 1: Identificação do Certificado -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-id-card"></i> Identificação do Certificado</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label>Número do Certificado</label>
                        <input type="text" class="form-control" value="<?php echo $editando ? h($certificado['numero']) : h($proximo_numero); ?>" readonly 
                               style="background: var(--cor-sidebar); font-weight: bold;">
                    </div>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select name="status" id="status" class="form-control">
                            <?php
                            $statuses = ['rascunho' => 'Rascunho', 'emitido' => 'Emitido', 'cancelado' => 'Cancelado'];
                            $current_status = $editando ? $certificado['status'] : 'rascunho';
                            foreach ($statuses as $val => $label):
                            ?>
                                <option value="<?php echo $val; ?>" <?php echo $current_status === $val ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="data_emissao">Data de Emissão</label>
                        <input type="date" name="data_emissao" id="data_emissao" class="form-control" required
                               value="<?php echo $editando ? h($certificado['data_emissao']) : date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_validade">Data de Validade</label>
                        <input type="date" name="data_validade" id="data_validade" class="form-control" required
                               value="<?php echo $editando ? h($certificado['data_validade']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_emissao">Local de Emissão</label>
                        <input type="text" name="local_emissao" id="local_emissao" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_emissao']) : 'Belém-PA'; ?>">
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
                                data-tipo="<?php echo h($emb['tipo']); ?>">
                                <?php echo h($emb['nome']) . ' (' . h($emb['tipo']) . ' - ' . h($emb['registro']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Ao selecionar, os dados básicos da embarcação serão preenchidos automaticamente.</small>
                </div>
                <hr>
                <?php endif; ?>

                <div class="grid-3">
                    <div class="form-group">
                        <label for="nome_embarcacao">Nome da Embarcação *</label>
                        <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" required
                               value="<?php echo $editando ? h($certificado['nome_embarcacao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_inscricao">N° de Inscrição</label>
                        <input type="text" name="numero_inscricao" id="numero_inscricao" class="form-control"
                               value="<?php echo $editando ? h($certificado['numero_inscricao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="indicativo_chamada">Indicativo de Chamada</label>
                        <input type="text" name="indicativo_chamada" id="indicativo_chamada" class="form-control"
                               value="<?php echo $editando ? h($certificado['indicativo_chamada']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="tipo_embarcacao">Tipo de Embarcação</label>
                        <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control"
                               value="<?php echo $editando ? h($certificado['tipo_embarcacao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="ano_construcao">Ano de Construção</label>
                        <input type="text" name="ano_construcao" id="ano_construcao" class="form-control"
                               maxlength="4" placeholder="AAAA"
                               value="<?php echo $editando ? h($certificado['ano_construcao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($certificado['material_casco']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="porto_inscricao">Porto de Inscrição</label>
                        <input type="text" name="porto_inscricao" id="porto_inscricao" class="form-control"
                               value="<?php echo $editando ? h($certificado['porto_inscricao']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_construcao">Local de Construção</label>
                        <input type="text" name="local_construcao" id="local_construcao" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_construcao']) : ''; ?>">
                    </div>
                </div>

                <!-- Dimensões para Arqueação -->
                <div class="grid-3">
                    <div class="form-group">
                        <label for="comprimento_total">Comprimento Total (m)</label>
                        <input type="number" name="comprimento_total" id="comprimento_total" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_total']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_casco">Comprimento do Casco (m)</label>
                        <input type="number" name="comprimento_casco" id="comprimento_casco" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_casco']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="comprimento_lpp">Comprimento LPP (m)</label>
                        <input type="number" name="comprimento_lpp" id="comprimento_lpp" class="form-control" step="0.01"
                               placeholder="Entre perpendiculares"
                               value="<?php echo $editando ? h($certificado['comprimento_lpp']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="boca_moldada">Boca Moldada (m)</label>
                        <input type="number" name="boca_moldada" id="boca_moldada" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['boca_moldada']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="boca_maxima">Boca Máxima (m)</label>
                        <input type="number" name="boca_maxima" id="boca_maxima" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['boca_maxima']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="pontal_moldado">Pontal Moldado (m)</label>
                        <input type="number" name="pontal_moldado" id="pontal_moldado" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['pontal_moldado']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Arqueação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-balance-scale"></i> Arqueação</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="arqueacao_bruta">Arqueação Bruta (AB)</label>
                        <input type="number" name="arqueacao_bruta" id="arqueacao_bruta" class="form-control" step="0.01" min="0"
                               value="<?php echo $editando ? h($certificado['arqueacao_bruta']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="arqueacao_liquida">Arqueação Líquida (AL)</label>
                        <input type="number" name="arqueacao_liquida" id="arqueacao_liquida" class="form-control" step="0.01" min="0"
                               value="<?php echo $editando ? h($certificado['arqueacao_liquida']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="metodo_arqueacao">Método de Arqueação</label>
                        <select name="metodo_arqueacao" id="metodo_arqueacao" class="form-control">
                            <option value="">-- Selecione --</option>
                            <?php
                            $metodos = ['NORMAM-202', 'Convenção Internacional', 'Lei nº 9.537/1997', 'Outro'];
                            $selected_met = $editando ? $certificado['metodo_arqueacao'] : '';
                            foreach ($metodos as $met):
                            ?>
                                <option value="<?php echo $met; ?>" <?php echo $selected_met === $met ? 'selected' : ''; ?>>
                                    <?php echo $met; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: Vistoria e Certificação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-check"></i> Vistoria e Certificação</h3>
            </div>
            <div class="card-body">
                <div class="grid-2">
                    <div class="form-group">
                        <label for="relatorio_numero">N° Relatório de Vistorias</label>
                        <input type="text" name="relatorio_numero" id="relatorio_numero" class="form-control"
                               placeholder="Ex: AM-REL-AP:100/26"
                               value="<?php echo $editando ? h($certificado['relatorio_numero']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_vistoria">Local da Vistoria</label>
                        <input type="text" name="local_vistoria" id="local_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_vistoria']) : ''; ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="data_vistoria">Data da Vistoria</label>
                        <input type="date" name="data_vistoria" id="data_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 5: Responsável pela Assinatura -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-user-tie"></i> Responsável pela Assinatura</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="assinante_nome">Nome Completo</label>
                        <input type="text" name="assinante_nome" id="assinante_nome" class="form-control"
                               value="<?php echo $editando ? h($certificado['assinante_nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_titulo">Título/Cargo</label>
                        <input type="text" name="assinante_titulo" id="assinante_titulo" class="form-control"
                               placeholder="Ex: Engenheira Naval"
                               value="<?php echo $editando ? h($certificado['assinante_titulo']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="assinante_registro">Registro Profissional</label>
                        <input type="text" name="assinante_registro" id="assinante_registro" class="form-control"
                               placeholder="Ex: CREA: 22.482"
                               value="<?php echo $editando ? h($certificado['assinante_registro']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 6: Convalidações -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-calendar-check"></i> Convalidações</h3>
            </div>
            <div class="card-body">
                <div class="tabela-container">
                    <table class="tabela" id="tabelaConvalidacoes">
                        <thead>
                            <tr>
                                <th>N° Vistoria</th>
                                <th>A Realizar Entre</th>
                                <th>E</th>
                                <th>Lugar e Data da Realização</th>
                                <th>Vistoriador</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $vistorias_padrao = ['1ª VIST. ANUAL', '2ª VIST. ANUAL', '3ª VIST. ANUAL', '4ª VIST. ANUAL'];
                            for ($i = 0; $i < 4; $i++):
                                $conv = $convalidacoes[$i] ?? null;
                            ?>
                                <tr>
                                    <td>
                                        <input type="text" name="conv_numero[]" class="form-control" 
                                               value="<?php echo h($conv['numero_vistoria'] ?? $vistorias_padrao[$i]); ?>" readonly
                                               style="background: var(--cor-sidebar);">
                                    </td>
                                    <td>
                                        <input type="date" name="conv_data_inicio[]" class="form-control"
                                               value="<?php echo h($conv['data_inicio'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <input type="date" name="conv_data_fim[]" class="form-control"
                                               value="<?php echo h($conv['data_fim'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="conv_local_data[]" class="form-control"
                                               placeholder="Lugar e data"
                                               value="<?php echo h($conv['local_data'] ?? ''); ?>">
                                    </td>
                                    <td>
                                        <input type="text" name="conv_vistoriador[]" class="form-control"
                                               placeholder="Nome do vistoriador"
                                               value="<?php echo h($conv['vistoriador'] ?? ''); ?>">
                                    </td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Botões de Ação -->
        <div class="card mb-3">
            <div class="card-footer" style="display: flex; gap: 10px; justify-content: flex-end;">
                <a href="<?php echo APP_URL; ?>documentacao/cnarq" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $editando ? 'Atualizar Certificado' : 'Salvar Certificado'; ?>
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
    
    // Preencher nome e tipo da embarcação
    const nomeEmb = document.getElementById('nome_embarcacao');
    const tipoEmb = document.getElementById('tipo_embarcacao');
    if (nomeEmb) nomeEmb.value = option.dataset.nome || '';
    if (tipoEmb) tipoEmb.value = option.dataset.tipo || '';
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>