<?php
/**
 * MODULO: VISTORIAS (EXPANSAO)
 * Arquivo: relatorio.php - Formulario de relatorio tecnico com
 *           tabela dinamica de exigencias, vinculado ao agendamento.
 * ACESSO: ?agendamento_id=UUID — ADMIN e VISTORIADOR
 * REGRA: Ao salvar, avanca status da OS para "Executado"
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$usuario_id = $_SESSION['usuario_id'];
$agendamento_id = $_GET['agendamento_id'] ?? '';

if (empty($agendamento_id)) {
    setMensagem('error', 'ID do agendamento nao informado.');
    redirecionar(APP_URL . 'agendamentos');
}

// ============================================
// BUSCAR DADOS DO AGENDAMENTO + CLIENTE + EMBARCACAO + OS
// ============================================
try {
    $stmt = $pdo->prepare("
        SELECT a.*,
               c.nome AS cliente_nome, c.cpf_cnpj AS cliente_cpfcnpj,
               c.telefone AS cliente_telefone, c.email AS cliente_email,
               e.nome AS embarcacao_nome, e.registro AS embarcacao_registro,
               e.tipo_embarcacao, e.ano AS embarcacao_ano,
               e.comprimento_total, e.boca_moldada, e.pontal_moldado,
               e.material_casco, e.arqueacao_bruta,
               u.nome AS vistoriador_nome,
               os.id AS os_id, os.numero AS os_numero, os.status AS os_status
        FROM agendamentos a
        INNER JOIN clientes c     ON a.cliente_id = c.id
        INNER JOIN embarcacoes e  ON a.embarcacao_id = e.id
        LEFT  JOIN usuarios u     ON a.vistoriador_id = u.id
        LEFT  JOIN ordens_servico os ON os.agendamento_id = a.id
        WHERE a.id = :id
    ");
    $stmt->execute([':id' => $agendamento_id]);
    $ag = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ag) {
        setMensagem('error', 'Agendamento nao encontrado.');
        redirecionar(APP_URL . 'agendamentos');
    }

    // VISTORIADOR so pode ver relatorio dos proprios agendamentos
    if ($cargo === 'VISTORIADOR' && $ag['vistoriador_id'] !== $usuario_id) {
        setMensagem('error', 'Acesso negado. Este agendamento nao esta atribuido a voce.');
        redirecionar(APP_URL . 'agendamentos');
    }
    
    // Se estiver aprovada, vistoriador não pode mais editar
    $stmtV_check = $pdo->prepare("SELECT status FROM vistorias WHERE agendamento_id = :id LIMIT 1");
    $stmtV_check->execute([':id' => $agendamento_id]);
    $vistoria_check = $stmtV_check->fetch(PDO::FETCH_ASSOC);
    if ($vistoria_check && in_array($vistoria_check['status'], ['APROVADA', 'APROVADA_COM_EXIGENCIAS']) && $cargo === 'VISTORIADOR') {
        setMensagem('error', 'Este relatório já foi aprovado e não pode mais ser modificado.');
        redirecionar(APP_URL . 'agendamentos');
    }

} catch (Exception $e) {
    error_log('Erro ao carregar agendamento relatorio: ' . $e->getMessage());
    setMensagem('error', 'Erro ao carregar dados do agendamento.');
    redirecionar(APP_URL . 'agendamentos');
}

// ============================================
// VERIFICAR SE JA EXISTE UMA VISTORIA VINCULADA
// ============================================
$vistoria = null;
$exigencias = [];

try {
    $stmtV = $pdo->prepare("SELECT * FROM vistorias WHERE agendamento_id = :agendamento_id LIMIT 1");
    $stmtV->execute([':agendamento_id' => $agendamento_id]);
    $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

    if ($vistoria) {
        // Carregar exigencias da vistoria existente
        $stmtE = $pdo->prepare("SELECT * FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id ORDER BY ordem ASC");
        $stmtE->execute([':vistoria_id' => $vistoria['id']]);
        $exigencias = $stmtE->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log('Erro ao buscar vistoria: ' . $e->getMessage());
}

$editando = !empty($vistoria);

// --- DETERMINAR ETAPA ATUAL ---
$status_vistoria = $vistoria['status'] ?? 'PENDENTE';
$pode_ir_etapa2 = in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS']);
$etapa_atual = 1;
if ($pode_ir_etapa2) $etapa_atual = 2;


// Se nao tem exigencias ainda, inicializa com um item vazio
if (empty($exigencias)) {
    $exigencias = [
        ['id' => '', 'ordem' => 1, 'item' => '', 'descricao' => '', 'conforme' => 'na', 'observacao' => '']
    ];
}

// ============================================
// LISTA DE EXIGENCIAS PADRAO SUGERIDAS
// ============================================
$itens_sugeridos = [
    'Casco e Estrutura',
    'Sistema de Governo (leme)',
    'Sistema de Propulsao',
    'Sistema Eletrico',
    'Sistema de Esgoto',
    'Sistema de Combate a Incendio',
    'Equipamentos de Salvamento',
    'Equipamentos de Navegacao',
    'Equipamentos de Comunicacao',
    'Maquinas Auxiliares',
    'Linha de Eixo e Helice',
    'Estanqueidade do Casco',
    'Borda Livre e Linhas de Carga',
    'Habitabilidade',
    'Documentacao de Bordo',
];

?>

<?php
$titulo_page = 'Relatorio Tecnico - ERP Sistema';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/sidebar.php';
?>

<div class="conteudo-principal">
<!-- BARRA DE ETAPAS -->
<div class="etapas-fluxo mb-4" style="display: flex; align-items: center; padding: 20px 0;">
    <div class="etapa <?= $etapa_atual >= 1 ? 'ativa' : '' ?>">
        <span class="etapa-numero">1</span>
        <span class="etapa-label">Relatório</span>
    </div>
    <div class="etapa-linha <?= $pode_ir_etapa2 ? 'completa' : '' ?>" style="flex: 1; height: 3px; background: #444; margin: 0 8px; margin-bottom: 20px;"></div>
    <div class="etapa <?= $pode_ir_etapa2 ? 'ativa' : 'bloqueada' ?>">
        <span class="etapa-numero">2</span>
        <span class="etapa-label">Certificado</span>
    </div>
</div>

<style>
.etapa { display: flex; flex-direction: column; align-items: center; gap: 4px; }
.etapa-numero { width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; }
.etapa.ativa .etapa-numero { background: #2ECC71; color: #000; }
.etapa.bloqueada .etapa-numero { background: #444; color: #888; }
.etapa-label { font-size: 12px; color: #ccc; }
.etapa-linha.completa { background: #2ECC71 !important; }
</style>

<!-- BOTÃO ETAPA 2 (somente ADMIN, somente quando aprovado) -->
<?php if (getCargo() === 'ADMIN' && $pode_ir_etapa2): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <strong>Relatório aprovado.</strong> Você pode gerar os certificados agora.
        <a href="<?= APP_URL ?>documentacao/novo_certificado?agendamento_id=<?= urlencode($agendamento_id) ?>" 
           class="btn btn-success ms-3">
            <i class="fas fa-certificate"></i> Ir para Etapa 2 — Gerar Certificado
        </a>
    </div>
<?php endif; ?>
    <div class="form-container" style="max-width: 950px;">
        <div class="form-header">
            <h3>
                <i class="fas fa-clipboard-list"></i> 
                Relatorio Tecnico de Vistoria
            </h3>
            <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <!-- ===== DADOS DO AGENDAMENTO ===== -->
        <div style="padding: 20px; border-bottom: 1px solid var(--cor-borda, rgba(255,255,255,0.08));">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px;">
                <div>
                    <small class="text-muted"><i class="fas fa-file-invoice"></i> OS</small>
                    <div style="font-weight: 600;"><?php echo $ag['os_numero'] ? h($ag['os_numero']) : '<em class="text-muted">Pendente</em>'; ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-calendar-day"></i> Data da Vistoria</small>
                    <div style="font-weight: 600;"><?php echo formatarData($ag['data_vistoria']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-user-check"></i> Vistoriador</small>
                    <div style="font-weight: 600;"><?php echo h($ag['vistoriador_nome'] ?? 'Nao atribuido'); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-map-marker-alt"></i> Local</small>
                    <div><?php echo h($ag['local'] ?: 'Nao informado'); ?></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 12px; margin-top: 12px;">
                <div>
                    <small class="text-muted"><i class="fas fa-user-tie"></i> Cliente</small>
                    <div style="font-weight: 600;"><?php echo h($ag['cliente_nome']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-ship"></i> Embarcacao</small>
                    <div style="font-weight: 600;"><?php echo h($ag['embarcacao_nome']); ?> <?php echo $ag['embarcacao_registro'] ? '(' . h($ag['embarcacao_registro']) . ')' : ''; ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-clipboard-check"></i> Tipo de Vistoria</small>
                    <div><?php echo h($ag['tipo_vistoria']); ?></div>
                </div>
                <div>
                    <small class="text-muted"><i class="fas fa-info-circle"></i> Status Agendamento</small>
                    <div><?php echo h($ag['status']); ?></div>
                </div>
            </div>
        </div>

        <!-- ===== FORMULARIO RELATORIO TECNICO ===== -->
        <form action="<?php echo APP_URL; ?>vistorias/actions?action=salvar_relatorio" method="POST" class="form-padrao" id="formRelatorio">
            <input type="hidden" name="csrf_token" value="<?php echo gerarCSRF(); ?>">
            <input type="hidden" name="agendamento_id" value="<?php echo h($agendamento_id); ?>">
            <?php if ($editando): ?>
                <input type="hidden" name="vistoria_id" value="<?php echo h($vistoria['id']); ?>">
            <?php endif; ?>

            <!-- ===== TABELA DINAMICA DE EXIGENCIAS ===== -->
            <div style="padding: 20px;">
                <h4 style="margin: 0 0 5px 0; font-size: 1rem; color: var(--cor-destaque, #2ECC71);">
                    <i class="fas fa-tasks"></i> Itens Inspecionados (Exigencias)
                </h4>
                <small class="text-muted">Adicione os itens a serem inspecionados. Use os botoes para adicionar/remover linhas.</small>

                <div style="margin: 15px 0; display: flex; gap: 8px; flex-wrap: wrap;" class="no-print">
                    <button type="button" class="btn btn-sm btn-primary" onclick="adicionarLinha()">
                        <i class="fas fa-plus"></i> Adicionar Item
                    </button>
                    <select id="itemSugerido" onchange="adicionarItemSugerido(this.value)" style="max-width: 300px;">
                        <option value="">-- Itens sugeridos --</option>
                        <?php foreach ($itens_sugeridos as $sug): ?>
                            <option value="<?php echo h($sug); ?>"><?php echo h($sug); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <table id="tabelaExigencias" style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: var(--cor-sidebar, #1a1a2e); border-bottom: 2px solid var(--cor-borda);">
                            <th style="width: 40px; text-align: center; padding: 8px 6px;">#</th>
                            <th style="text-align: left; padding: 8px 6px;">Item *</th>
                            <th style="text-align: left; padding: 8px 6px;">Descricao / Especificacao</th>
                            <th style="width: 80px; text-align: center; padding: 8px 6px;">Conforme?</th>
                            <th style="text-align: left; padding: 8px 6px;">Observacao</th>
                            <th style="width: 40px; text-align: center; padding: 8px 6px;" class="no-print"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exigencias as $idx => $ex): ?>
                        <tr class="linha-exigencia">
                            <td style="text-align: center; padding: 6px;">
                                <span class="ordem-num"><?php echo (int)$ex['ordem']; ?></span>
                                <input type="hidden" name="exigencia_id[]" value="<?php echo h($ex['id'] ?? ''); ?>">
                                <input type="hidden" name="exigencia_ordem[]" value="<?php echo (int)$ex['ordem']; ?>" class="ordem-input">
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_item[]" value="<?php echo h($ex['item']); ?>"
                                       placeholder="Nome do item inspecionado" required
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_descricao[]" value="<?php echo h($ex['descricao'] ?? ''); ?>"
                                       placeholder="Especificacao tecnica (opcional)"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="padding: 6px; text-align: center;">
                                <select name="exigencia_conforme[]" 
                                        style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);"
                                        class="select-conforme">
                                    <option value="na" <?php echo $ex['conforme'] === 'na' ? 'selected' : ''; ?>>N/A</option>
                                    <option value="sim" <?php echo $ex['conforme'] === 'sim' ? 'selected' : ''; ?>>Sim</option>
                                    <option value="nao" <?php echo $ex['conforme'] === 'nao' ? 'selected' : ''; ?>>Nao</option>
                                </select>
                            </td>
                            <td style="padding: 6px;">
                                <input type="text" name="exigencia_observacao[]" value="<?php echo h($ex['observacao'] ?? ''); ?>"
                                       placeholder="Observacao (opcional)"
                                       style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
                            </td>
                            <td style="text-align: center; padding: 6px;" class="no-print">
                                <button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)" title="Remover">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- ===== OBSERVACOES TECNICAS ===== -->
            <div style="padding: 0 20px 20px;">
                <div class="form-group">
                    <label for="observacoes_tecnicas">
                        <i class="fas fa-sticky-note"></i> Observacoes Tecnicas
                    </label>
                    <textarea id="observacoes_tecnicas" name="observacoes_tecnicas" rows="4"
                              placeholder="Observacoes tecnicas gerais, recomendacoes, restricoes encontradas..."
                              style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); resize: vertical;"><?php echo h($vistoria['observacoes_tecnicas'] ?? ''); ?></textarea>
                </div>

                <!-- Status da vistoria (resultado final) -->
                <div class="form-group" style="margin-top: 15px;">
                    <label for="status_vistoria">
                        <i class="fas fa-gavel"></i> Resultado Final da Vistoria *
                    </label>
                    <select id="status_vistoria" name="status_vistoria" required
                            style="width: 100%; padding: 10px 14px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 6px; color: var(--cor-texto, #ddd); font-size: 1rem;">
                        <option value="PENDENTE" <?php echo ($vistoria['status'] ?? '') === 'PENDENTE' ? 'selected' : ''; ?>>Pendente (relatorio em andamento)</option>
                        <option value="AGUARDANDO_APROVACAO" <?php echo ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO' ? 'selected' : ''; ?>>Aguardando Aprovação</option>
                        <?php if (getCargo() === 'ADMIN'): ?>
                        <option value="APROVADA" <?php echo ($vistoria['status'] ?? '') === 'APROVADA' ? 'selected' : ''; ?>>Aprovada</option>
                        <option value="APROVADA_COM_EXIGENCIAS" <?php echo ($vistoria['status'] ?? '') === 'APROVADA_COM_EXIGENCIAS' ? 'selected' : ''; ?>>Aprovada c/ Exigências</option>
                        <option value="REPROVADA" <?php echo ($vistoria['status'] ?? '') === 'REPROVADA' ? 'selected' : ''; ?>>Reprovada</option>
                        <option value="CANCELADA" <?php echo ($vistoria['status'] ?? '') === 'CANCELADA' ? 'selected' : ''; ?>>Cancelada</option>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- ===== BOTOES ===== -->
            <div class="form-actions" style="padding: 0 20px 20px;">
                <button type="submit" class="btn btn-primary" id="btnSalvar">
                    <i class="fas fa-save"></i> 
                    <?php echo $editando ? 'Atualizar Relatorio' : 'Salvar Relatorio'; ?>
                </button>
                <a href="<?php echo APP_URL; ?>agendamentos" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <?php if ($editando): ?>
                    <span class="text-muted" style="margin-left: 15px; font-size: 0.8rem;">
                        <i class="fas fa-info-circle"></i> 
                        Ao salvar com status <strong>Aprovada</strong> ou <strong>Reprovada</strong>, 
                        a OS avanca para <strong>"Executada"</strong> automaticamente.
                    </span>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<script>
let contadorLinhas = <?php echo count($exigencias); ?>;

function adicionarLinha() {
    contadorLinhas++;
    const tbody = document.querySelector('#tabelaExigencias tbody');
    const tr = document.createElement('tr');
    tr.className = 'linha-exigencia';
    tr.innerHTML = `
        <td style="text-align: center; padding: 6px;">
            <span class="ordem-num">${contadorLinhas}</span>
            <input type="hidden" name="exigencia_id[]" value="">
            <input type="hidden" name="exigencia_ordem[]" value="${contadorLinhas}" class="ordem-input">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_item[]" value="" 
                   placeholder="Nome do item inspecionado" required
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_descricao[]" value="" 
                   placeholder="Especificacao tecnica (opcional)"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px; text-align: center;">
            <select name="exigencia_conforme[]" 
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);"
                    class="select-conforme">
                <option value="na">N/A</option>
                <option value="sim">Sim</option>
                <option value="nao">Nao</option>
            </select>
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_observacao[]" value="" 
                   placeholder="Observacao (opcional)"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="text-align: center; padding: 6px;" class="no-print">
            <button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)" title="Remover">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    renumerarLinhas();
}

function removerLinha(btn) {
    const rows = document.querySelectorAll('#tabelaExigencias tbody tr.linha-exigencia');
    if (rows.length <= 1) {
        alert('E necessario pelo menos um item na tabela.');
        return;
    }
    btn.closest('tr').remove();
    renumerarLinhas();
}

function adicionarItemSugerido(valor) {
    if (!valor) return;
    document.getElementById('itemSugerido').value = '';
    contadorLinhas++;
    const tbody = document.querySelector('#tabelaExigencias tbody');
    const tr = document.createElement('tr');
    tr.className = 'linha-exigencia';
    tr.innerHTML = `
        <td style="text-align: center; padding: 6px;">
            <span class="ordem-num">${contadorLinhas}</span>
            <input type="hidden" name="exigencia_id[]" value="">
            <input type="hidden" name="exigencia_ordem[]" value="${contadorLinhas}" class="ordem-input">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_item[]" value="${escapeHtml(valor)}" 
                   placeholder="Nome do item inspecionado" required
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_descricao[]" value="" 
                   placeholder="Especificacao tecnica (opcional)"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="padding: 6px; text-align: center;">
            <select name="exigencia_conforme[]" 
                    style="width: 100%; padding: 6px 4px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);"
                    class="select-conforme">
                <option value="na">N/A</option>
                <option value="sim">Sim</option>
                <option value="nao">Nao</option>
            </select>
        </td>
        <td style="padding: 6px;">
            <input type="text" name="exigencia_observacao[]" value="" 
                   placeholder="Observacao (opcional)"
                   style="width: 100%; padding: 6px 10px; background: var(--cor-input-bg, #2a2a3e); border: 1px solid var(--cor-borda, #444); border-radius: 4px; color: var(--cor-texto, #ddd);">
        </td>
        <td style="text-align: center; padding: 6px;" class="no-print">
            <button type="button" class="btn btn-danger btn-sm" onclick="removerLinha(this)" title="Remover">
                <i class="fas fa-trash"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    renumerarLinhas();
}

function renumerarLinhas() {
    const rows = document.querySelectorAll('#tabelaExigencias tbody tr.linha-exigencia');
    rows.forEach((row, i) => {
        const num = i + 1;
        row.querySelector('.ordem-num').textContent = num;
        row.querySelector('.ordem-input').value = num;
    });
    contadorLinhas = rows.length;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Confirmacao ao salvar com status final
document.getElementById('formRelatorio').addEventListener('submit', function(e) {
    const status = document.getElementById('status_vistoria').value;
    if (status === 'APROVADA' || status === 'REPROVADA') {
        const msg = status === 'APROVADA'
            ? 'Ao salvar como APROVADA, a Ordem de Servico sera marcada como EXECUTADA e os certificados serao liberados. Deseja continuar?'
            : 'Ao salvar como REPROVADA, a Ordem de Servico sera marcada como EXECUTADA. Deseja continuar?';
        if (!confirm(msg)) {
            e.preventDefault();
        }
    }
});
</script>

<style>
.select-conforme option[value="sim"] { background: rgba(46,204,113,0.2); color: #2ECC71; }
.select-conforme option[value="nao"] { background: rgba(231,76,60,0.2); color: #E74C3C; }
.select-conforme option[value="na"] { background: rgba(150,150,150,0.2); color: #999; }
</style>

<?php if (getCargo() === 'ADMIN' && ($vistoria['status'] ?? '') === 'AGUARDANDO_APROVACAO'): ?>
<div class="card mt-4" style="border: 2px solid #f39c12; max-width: 950px; margin: 20px auto;">
    <div class="card-header" style="background:#f39c12;color:#000">
        <h4><i class="fas fa-gavel"></i> Decisão de Aprovação</h4>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= APP_URL ?>vistorias/actions?action=aprovar_ou_reprovar">
            <input type="hidden" name="csrf_token" value="<?= h(gerarCSRF()); ?>">
            <input type="hidden" name="id" value="<?= h($vistoria['id']); ?>">
            <div class="form-group mb-3">
                <label>Observação (obrigatória para reprovar, opcional para aprovar):</label>
                <textarea name="observacao_admin" class="form-control" rows="3"></textarea>
            </div>
            <button type="submit" name="decisao" value="aprovar" class="btn btn-success"
                onclick="return confirm('Confirmar APROVAÇÃO deste relatório?')">
                <i class="fas fa-check"></i> Aprovar Relatório
            </button>
            <button type="submit" name="decisao" value="reprovar" class="btn btn-danger ms-2"
                onclick="return confirm('Confirmar REPROVAÇÃO? A observação é obrigatória.')">
                <i class="fas fa-times"></i> Reprovar Relatório
            </button>
        </form>
    </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
