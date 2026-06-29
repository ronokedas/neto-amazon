<?php
/**
 * MÓDULO: Documentação > Certificados CSN
 * Formulário de Criação/Edição do Certificado
 */

require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../includes/auth.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Verificar permissão
verificar_sessao();
verificar_cargo(['ADMIN', 'VENDEDOR', 'VISTORIADOR']);

$editando = false;
$certificado = null;
$distribuicao = [];
$convalidacoes = [];

// Se tem ID, é edição
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];
    $editando = true;

    $stmt = $pdo->prepare("SELECT * FROM certificados_csn WHERE id = :id AND ativo = 1");
    $stmt->execute([':id' => $id]);
    $certificado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$certificado) {
        setMensagem('error', 'Certificado não encontrado.');
        redirecionar(APP_URL . 'documentacao/certificados');
    }

    // Buscar distribuição de passageiros
    $stmt_dist = $pdo->prepare("SELECT * FROM csn_distribuicao_passageiros WHERE certificado_id = :cert_id ORDER BY id");
    $stmt_dist->execute([':cert_id' => $id]);
    $distribuicao = $stmt_dist->fetchAll(PDO::FETCH_ASSOC);

    // Buscar convalidações
    $stmt_conv = $pdo->prepare("SELECT * FROM csn_convalidacoes WHERE certificado_id = :cert_id ORDER BY id");
    $stmt_conv->execute([':cert_id' => $id]);
    $convalidacoes = $stmt_conv->fetchAll(PDO::FETCH_ASSOC);
}

// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'nome_embarcacao'    => '', 'numero_inscricao'   => '', 'indicativo_chamada' => '',
    'atividades_servicos'=> '', 'tipo_embarcacao'    => '', 'ano_construcao'     => '',
    'comprimento_m'      => '', 'arqueacao_bruta'    => '', 'material_casco'     => '',
    'relatorio_numero'   => '', 'data_vistoria_seco' => '', 'data_vistoria_flutuando' => '',
    'local_vistoria'     => ''
];

if (!$editando && !empty($_GET['agendamento_id'])) {
    $stmtPre = $pdo->prepare("
        SELECT e.nome as emb_nome, e.registro, e.tipo_embarcacao, e.ano as emb_ano,
               e.comprimento_total, e.arqueacao_bruta, e.material_casco,
               v.numero as relatorio_numero
        FROM agendamentos a
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN vistorias v ON v.agendamento_id = a.id
        WHERE a.id = :aid
    ");
    $stmtPre->execute([':aid' => $_GET['agendamento_id']]);
    $dadosPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

    if ($dadosPre) {
        $preenchimento['nome_embarcacao']    = $dadosPre['emb_nome'];
        $preenchimento['numero_inscricao']   = $dadosPre['registro'];
        $preenchimento['tipo_embarcacao']    = $dadosPre['tipo_embarcacao'];
        $preenchimento['ano_construcao']     = $dadosPre['emb_ano'];
        $preenchimento['comprimento_m']      = $dadosPre['comprimento_total'];
        $preenchimento['arqueacao_bruta']    = $dadosPre['arqueacao_bruta'];
        $preenchimento['material_casco']     = $dadosPre['material_casco'];
        $preenchimento['relatorio_numero']   = $dadosPre['relatorio_numero'];
    }
}


// Gerar próximo número (se não estiver editando)
$proximo_numero = '';
if (!$editando) {
    $ano = date('y');
    $ano4 = date('Y');
    $stmt_num = $pdo->prepare("SELECT COUNT(*) as total FROM certificados_csn WHERE YEAR(criado_em) = :ano");
    $stmt_num->execute([':ano' => $ano4]);
    $total = $stmt_num->fetch()['total'];
    $seq = $total + 1;
    $proximo_numero = "AM-CSN-{$seq}/{$ano}";
}

$titulo_page = ($editando ? 'Editar' : 'Novo') . ' Certificado CSN - ' . APP_NAME;
require_once __DIR__ . '/../../../includes/header.php';
require_once __DIR__ . '/../../../includes/sidebar.php';
?>

<div class="conteudo-principal">
    <div class="tabela-header">
        <h2>
            <i class="fas fa-file-certificate"></i> 
            <?php echo $editando ? 'Editar Certificado CSN' : 'Novo Certificado CSN'; ?>
        </h2>
        <a href="<?php echo APP_URL; ?>documentacao/certificados" class="btn btn-secondary">
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

    <?php if ($_SESSION['usuario_cargo'] ?? '' !== 'ADMIN'): ?>
        <div class="alert alert-info" style="margin-bottom: 20px;">
            <i class="fas fa-eye"></i> <strong>Modo de visualização</strong> — Você não tem permissão para editar este certificado.
        </div>
    <?php endif; ?>
    <form method="POST" action="<?php echo APP_URL; ?>documentacao/certificados/actions" id="formCertificado" <?php echo ($_SESSION['usuario_cargo'] ?? '') !== 'ADMIN' ? 'style="pointer-events: none; opacity: 0.7;"' : ''; ?>>
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

        <!-- SEÇÃO 2: Dados da Embarcação -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-ship"></i> Dados da Embarcação</h3>
            </div>
            <div class="card-body">
                <div class="grid-3">
                    <div class="form-group">
                        <label for="nome_embarcacao">Nome da Embarcação *</label>
                        <input type="text" name="nome_embarcacao" id="nome_embarcacao" class="form-control" required
                               value="<?php echo $editando ? h($certificado['nome_embarcacao']) : h($preenchimento['nome_embarcacao']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="numero_inscricao">N° de Inscrição</label>
                        <input type="text" name="numero_inscricao" id="numero_inscricao" class="form-control"
                               value="<?php echo $editando ? h($certificado['numero_inscricao']) : h($preenchimento['numero_inscricao']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="indicativo_chamada">Indicativo de Chamada</label>
                        <input type="text" name="indicativo_chamada" id="indicativo_chamada" class="form-control"
                               value="<?php echo $editando ? h($certificado['indicativo_chamada']) : h($preenchimento['indicativo_chamada']); ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="atividades_servicos">Atividades ou Serviços</label>
                        <input type="text" name="atividades_servicos" id="atividades_servicos" class="form-control"
                               value="<?php echo $editando ? h($certificado['atividades_servicos']) : h($preenchimento['atividades_servicos']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="tipo_embarcacao">Tipo de Embarcação</label>
                        <input type="text" name="tipo_embarcacao" id="tipo_embarcacao" class="form-control"
                               value="<?php echo $editando ? h($certificado['tipo_embarcacao']) : h($preenchimento['tipo_embarcacao']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="ano_construcao">Ano de Construção</label>
                        <input type="text" name="ano_construcao" id="ano_construcao" class="form-control"
                               maxlength="4" placeholder="AAAA"
                               value="<?php echo $editando ? h($certificado['ano_construcao']) : h($preenchimento['ano_construcao']); ?>">
                    </div>
                </div>
                <div class="grid-3">
                    <div class="form-group">
                        <label for="comprimento_m">Comprimento (m)</label>
                        <input type="number" name="comprimento_m" id="comprimento_m" class="form-control" step="0.01"
                               value="<?php echo $editando ? h($certificado['comprimento_m']) : h($preenchimento['comprimento_m']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="arqueacao_bruta">Arqueação Bruta</label>
                        <input type="text" name="arqueacao_bruta" id="arqueacao_bruta" class="form-control"
                               value="<?php echo $editando ? h($certificado['arqueacao_bruta']) : h($preenchimento['arqueacao_bruta']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="material_casco">Material do Casco</label>
                        <input type="text" name="material_casco" id="material_casco" class="form-control"
                               value="<?php echo $editando ? h($certificado['material_casco']) : h($preenchimento['material_casco']); ?>">
                    </div>
                </div>

                <!-- Tipo de Navegação (checkboxes) -->
                <div class="form-group">
                    <label>Tipo de Navegação</label>
                    <div class="d-flex gap-2" style="flex-wrap: wrap;">
                        <?php
                        $tipos_nav = ['MAR ABERTO', 'INTERIOR', 'APOIO PORTUÁRIO'];
                        $selected_tipos = $editando ? explode(',', $certificado['tipo_navegacao'] ?? '') : [];
                        foreach ($tipos_nav as $tn):
                        ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="tipo_navegacao[]" value="<?php echo $tn; ?>"
                                       <?php echo in_array($tn, $selected_tipos) ? 'checked' : ''; ?>>
                                <?php echo $tn; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Área de Navegação (checkboxes) -->
                <div class="form-group">
                    <label>Área de Navegação</label>
                    <div class="d-flex gap-2" style="flex-wrap: wrap;">
                        <?php
                        $areas_nav = ['Longo Curso', 'Cabotagem', 'Apoio Marítimo', 'Área 1', 'Área 2'];
                        $selected_areas = $editando ? explode(',', $certificado['area_navegacao'] ?? '') : [];
                        foreach ($areas_nav as $an):
                        ?>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="checkbox" name="area_navegacao[]" value="<?php echo $an; ?>"
                                       <?php echo in_array($an, $selected_areas) ? 'checked' : ''; ?>>
                                <?php echo $an; ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Motor -->
                <div class="grid-2">
                    <div class="form-group">
                        <label for="fabricante_motor">Fabricante, Modelo e Número do Motor</label>
                        <input type="text" name="fabricante_motor" id="fabricante_motor" class="form-control"
                               value="<?php echo $editando ? h($certificado['fabricante_motor']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="potencia_kw">Potência Propulsiva Total (kW)</label>
                        <input type="text" name="potencia_kw" id="potencia_kw" class="form-control"
                               value="<?php echo $editando ? h($certificado['potencia_kw']) : ''; ?>">
                    </div>
                </div>

                <!-- Carga e Passageiros -->
                <div class="grid-3">
                    <div class="form-group">
                        <label>Autorizado a Transportar Carga no Convés</label>
                        <div class="d-flex gap-2">
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="autorizado_carga" value="1"
                                       <?php echo ($editando && $certificado['autorizado_carga']) ? 'checked' : ''; ?>> SIM
                            </label>
                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                                <input type="radio" name="autorizado_carga" value="0"
                                       <?php echo !$editando || !$certificado['autorizado_carga'] ? 'checked' : ''; ?>> NÃO
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="qtd_passageiros">Qtd. Autorizada de Passageiros</label>
                        <input type="number" name="qtd_passageiros" id="qtd_passageiros" class="form-control"
                               value="<?php echo $editando ? h($certificado['qtd_passageiros']) : '0'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="obs_passageiros">Observação Passageiros</label>
                        <input type="text" name="obs_passageiros" id="obs_passageiros" class="form-control"
                               placeholder="Ex: Vide verso"
                               value="<?php echo $editando ? h($certificado['obs_passageiros']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 3: Vistoria e Certificação -->
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
                               value="<?php echo $editando ? h($certificado['relatorio_numero']) : h($preenchimento['relatorio_numero']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="local_vistoria">Local da Vistoria</label>
                        <input type="text" name="local_vistoria" id="local_vistoria" class="form-control"
                               value="<?php echo $editando ? h($certificado['local_vistoria']) : h($preenchimento['local_vistoria']); ?>">
                    </div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label for="data_vistoria_seco">Data da Vistoria em Seco</label>
                        <input type="date" name="data_vistoria_seco" id="data_vistoria_seco" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria_seco']) : h($preenchimento['data_vistoria_seco']); ?>">
                    </div>
                    <div class="form-group">
                        <label for="data_vistoria_flutuando">Data da Vistoria Flutuando</label>
                        <input type="date" name="data_vistoria_flutuando" id="data_vistoria_flutuando" class="form-control"
                               value="<?php echo $editando ? h($certificado['data_vistoria_flutuando']) : h($preenchimento['data_vistoria_flutuando']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Acessibilidade para Transporte Coletivo Aquaviário</label>
                    <div class="d-flex gap-2">
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="acessibilidade" value="sim"
                                   <?php echo ($editando && $certificado['acessibilidade_sim']) ? 'checked' : ''; ?>> SIM
                        </label>
                        <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                            <input type="radio" name="acessibilidade" value="nao"
                                   <?php echo !$editando || $certificado['acessibilidade_nao'] ? 'checked' : ''; ?>> NÃO
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- SEÇÃO 4: Responsável pela Assinatura -->
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

        <!-- SEÇÃO 5: Distribuição de Passageiros -->
        <div class="card mb-3">
            <div class="card-header">
                <h3><i class="fas fa-users"></i> Distribuição de Passageiros</h3>
            </div>
            <div class="card-body">
                <table class="tabela" id="tabelaPassageiros">
                    <thead>
                        <tr>
                            <th style="width: 70%;">Local</th>
                            <th style="width: 20%;">Quantidade</th>
                            <th style="width: 10%; text-align: center;">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($distribuicao)): ?>
                            <?php foreach ($distribuicao as $i => $d): ?>
                                <tr class="row-passageiro">
                                    <td>
                                        <input type="text" name="passageiro_local[]" class="form-control" 
                                               placeholder="Ex: Convés Superior"
                                               value="<?php echo h($d['local_nome']); ?>">
                                    </td>
                                    <td>
                                        <input type="number" name="passageiro_qtd[]" class="form-control" min="0"
                                               value="<?php echo h($d['quantidade']); ?>">
                                    </td>
                                    <td style="text-align: center;">
                                        <button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)" title="Remover">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" onclick="adicionarLinhaPassageiro()" style="margin-top: 10px;">
                    <i class="fas fa-plus"></i> Adicionar Linha
                </button>
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
                <a href="<?php echo APP_URL; ?>documentacao/certificados" class="btn btn-secondary">
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
function adicionarLinhaPassageiro() {
    const tbody = document.querySelector('#tabelaPassageiros tbody');
    const row = document.createElement('tr');
    row.classList.add('row-passageiro');
    row.innerHTML = `
        <td>
            <input type="text" name="passageiro_local[]" class="form-control" placeholder="Ex: Convés Superior">
        </td>
        <td>
            <input type="number" name="passageiro_qtd[]" class="form-control" min="0" value="0">
        </td>
        <td style="text-align: center;">
            <button type="button" class="btn btn-sm btn-danger" onclick="removerLinha(this)" title="Remover">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;
    tbody.appendChild(row);
}

function removerLinha(btn) {
    const row = btn.closest('tr');
    row.remove();
}
</script>

<?php require_once __DIR__ . '/../../../includes/footer.php'; ?>
