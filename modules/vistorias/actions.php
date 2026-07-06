<?php
/**
 * MODULO: VISTORIAS
 * Arquivo: actions.php - Processar acoes (salvar vistoria, alterar status, salvar relatorio)
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR VISTORIA (WIZARD)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $embarcacao_id = trim($_POST['embarcacao_id'] ?? '');
        $pessoa_id     = trim($_POST['pessoa_id'] ?? '');
        $armador_id    = trim($_POST['armador_id'] ?? '');
        $data_vistoria = trim($_POST['data_vistoria'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');

        // Validacoes
        $erros = [];

        if (empty($embarcacao_id)) {
            $erros[] = 'Selecione uma embarcacao.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, ativo FROM embarcacoes WHERE id = :id");
                $stmt->execute([':id' => $embarcacao_id]);
                $emb = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$emb) {
                    $erros[] = 'Embarcacao nao encontrada.';
                } elseif (!$emb['ativo']) {
                    $erros[] = 'Embarcacao inativa. Nao e possivel criar vistoria para embarcacao inativa.';
                }
            } catch (Exception $e) {
                error_log('Erro ao validar embarcacao: ' . $e->getMessage());
                $erros[] = 'Erro ao validar embarcacao.';
            }
        }

        if (empty($pessoa_id)) {
            $erros[] = 'Selecione um responsável (Proprietário).';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, status FROM clientes WHERE id = :id");
                $stmt->execute([':id' => $pessoa_id]);
                $pes = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pes) {
                    $erros[] = 'Responsável não encontrado.';
                } elseif ($pes['status'] !== 'ATIVO') {
                    $erros[] = 'Responsável inativo. Não é possível criar vistoria com contato inativo.';
                }
            } catch (Exception $e) {
                error_log('Erro ao validar responsável: ' . $e->getMessage());
                $erros[] = 'Erro ao validar responsável.';
            }
        }

        if (empty($data_vistoria)) {
            $erros[] = 'A data da vistoria e obrigatoria.';
        } else {
            $dataObj = DateTime::createFromFormat('Y-m-d', $data_vistoria);
            if (!$dataObj || $dataObj->format('Y-m-d') !== $data_vistoria) {
                $erros[] = 'Data da vistoria invalida.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            redirecionar(APP_URL . 'vistorias/nova');
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO vistorias (id, embarcacao_id, pessoa_id, armador_id, data_vistoria, observacoes, status, criado_por) VALUES (:id, :embarcacao_id, :pessoa_id, :armador_id, :data_vistoria, :observacoes, 'PENDENTE', :criado_por)");
            $stmt->execute([
                ':id'            => gerarUUID(),
                ':embarcacao_id' => $embarcacao_id,
                ':pessoa_id'     => $pessoa_id,
                ':armador_id'    => !empty($armador_id) ? $armador_id : null,
                ':data_vistoria' => $data_vistoria,
                ':observacoes'   => $observacoes,
                ':criado_por'    => $_SESSION['usuario_id']
            ]);

            unset($_SESSION['wizard_embarcacao_id']);
            unset($_SESSION['wizard_pessoa_id']);
            unset($_SESSION['wizard_armador_id']);

            log_atividade('vistoria_criada', "Vistoria criada para embarcacao ID: {$embarcacao_id}.");
            setMensagem('success', 'Vistoria criada com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao salvar vistoria: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar vistoria. Tente novamente.');
        }

        redirecionar(APP_URL . 'vistorias');
        break;

    // ==============================
    // ATRIBUIR VISTORIADOR E DATA
    // ==============================
    case 'atribuir_vistoriador':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        if (getCargo() !== 'ADMIN') {
            setMensagem('error', 'Apenas administradores podem atribuir vistoriador.');
            redirecionar(APP_URL . 'vistorias');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $id             = trim($_POST['id'] ?? '');
        $vistoriador_id = trim($_POST['vistoriador_id'] ?? '');
        $data_vistoria  = trim($_POST['data_vistoria'] ?? '');

        if (empty($id) || empty($vistoriador_id) || empty($data_vistoria)) {
            setMensagem('error', 'Preencha todos os campos obrigatórios.');
            redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        }

        try {
            $pdo->beginTransaction();

            $stmtV = $pdo->prepare("SELECT agendamento_id FROM vistorias WHERE id = :id");
            $stmtV->execute([':id' => $id]);
            $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

            if (!$vistoria) {
                throw new Exception('Vistoria não encontrada.');
            }

            // Atualiza a vistoria
            $stmtUpdV = $pdo->prepare("UPDATE vistorias SET data_vistoria = :data_vistoria WHERE id = :id");
            $stmtUpdV->execute([
                ':data_vistoria' => $data_vistoria,
                ':id'            => $id
            ]);

            // Se tiver agendamento, atualiza também
            if (!empty($vistoria['agendamento_id'])) {
                $stmtUpdAg = $pdo->prepare("UPDATE agendamentos SET vistoriador_id = :vistoriador_id, data_vistoria = :data_vistoria WHERE id = :agendamento_id");
                $stmtUpdAg->execute([
                    ':vistoriador_id' => $vistoriador_id,
                    ':data_vistoria'  => $data_vistoria,
                    ':agendamento_id' => $vistoria['agendamento_id']
                ]);
            } else {
                // Criar agendamento se não existir
                $stmtVData = $pdo->prepare("SELECT embarcacao_id, pessoa_id FROM vistorias WHERE id = :id");
                $stmtVData->execute([':id' => $id]);
                $vData = $stmtVData->fetch(PDO::FETCH_ASSOC);

                $novo_agendamento_id = gerarUUID();
                $stmtNewAg = $pdo->prepare("
                    INSERT INTO agendamentos (id, cliente_id, embarcacao_id, vistoriador_id, data_vistoria, status, tipo_vistoria, criado_por)
                    VALUES (:id, :cliente_id, :embarcacao_id, :vistoriador_id, :data_vistoria, 'pendente', 'Vistoria Avulsa', :criado_por)
                ");
                $stmtNewAg->execute([
                    ':id' => $novo_agendamento_id,
                    ':cliente_id' => $vData['pessoa_id'],
                    ':embarcacao_id' => $vData['embarcacao_id'],
                    ':vistoriador_id' => $vistoriador_id,
                    ':data_vistoria' => $data_vistoria,
                    ':criado_por' => $_SESSION['usuario_id']
                ]);

                // Atualizar a vistoria com o novo agendamento
                $stmtUpdV2 = $pdo->prepare("UPDATE vistorias SET agendamento_id = :agendamento_id WHERE id = :id");
                $stmtUpdV2->execute([
                    ':agendamento_id' => $novo_agendamento_id,
                    ':id' => $id
                ]);
            }

            $pdo->commit();
            log_atividade('vistoria_agendada', "Vistoria ID: {$id} agendada para {$data_vistoria} com vistoriador {$vistoriador_id}.");
            setMensagem('success', 'Vistoria agendada e vistoriador atribuído com sucesso!');
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao atribuir vistoriador: ' . $e->getMessage());
            setMensagem('error', 'Erro ao atribuir vistoriador.');
        }

        redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        break;

    // ==============================
    // ALTERAR STATUS (APENAS ADMIN)
    // ==============================
    case 'alterar_status':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        if (getCargo() !== 'ADMIN') {
            setMensagem('error', 'Apenas administradores podem alterar o status da vistoria.');
            redirecionar(APP_URL . 'vistorias');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'vistorias');
        }

        $id            = trim($_POST['id'] ?? '');
        $novo_status   = trim($_POST['status'] ?? '');
        $resultado     = trim($_POST['resultado'] ?? '');

        // Status alinhados com o fluxo real do relatorio tecnico
        // (salvar_relatorio / aprovacao_relatorios usam os mesmos valores).
        $statusesValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'];

        $erros = [];
        if (empty($id)) $erros[] = 'ID da vistoria invalido.';
        if (!in_array($novo_status, $statusesValidos)) $erros[] = 'Status invalido.';

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            redirecionar(APP_URL . 'vistorias');
        }

        try {
            $stmt = $pdo->prepare("UPDATE vistorias SET status = :status, resultado = :resultado WHERE id = :id");
            $stmt->execute([
                ':status'   => $novo_status,
                ':resultado' => $resultado,
                ':id'       => $id
            ]);

            log_atividade('vistoria_status', "Vistoria ID: {$id} alterada para status {$novo_status}.");
            setMensagem('success', 'Status da vistoria atualizado com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao alterar status da vistoria: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status da vistoria.');
        }

        redirecionar(APP_URL . 'vistorias/detalhe?id=' . urlencode($id));
        break;

    // ==============================
    // SALVAR DADOS DO WIZARD (PARCIAL)
    // ==============================
    case 'salvar_wizard':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'vistorias');
        }

        $passo = intval($_POST['passo'] ?? 1);
        $embarcacao_id = trim($_POST['embarcacao_id'] ?? '');
        $pessoa_id     = trim($_POST['pessoa_id'] ?? '');
        $armador_id    = trim($_POST['armador_id'] ?? '');
        $data_vistoria = trim($_POST['data_vistoria'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');

        if ($passo >= 1) $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
        if ($passo >= 2) $_SESSION['wizard_pessoa_id'] = $pessoa_id;
        if ($passo >= 3) $_SESSION['wizard_armador_id'] = $armador_id;
        $_SESSION['wizard_data_vistoria'] = $data_vistoria;
        $_SESSION['wizard_observacoes']   = $observacoes;

        $proximo = $passo + 1;
        if ($proximo > 3) $proximo = 3;

        redirecionar(APP_URL . 'vistorias/nova?passo=' . $proximo);
        break;

    // ==============================
    // SALVAR RELATORIO TECNICO (EXPANSAO FASE 3)
    // ==============================
    case 'salvar_relatorio':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $agendamento_id       = $_POST['agendamento_id'] ?? '';
        $vistoria_id          = $_POST['vistoria_id'] ?? '';
        $armador_id           = trim($_POST['armador_id'] ?? '');
        $data_vistoria        = trim($_POST['data_vistoria'] ?? '');
        $observacoes_tecnicas = trim($_POST['observacoes_tecnicas'] ?? '');
        $status_vistoria      = $_POST['status_vistoria'] ?? 'PENDENTE';
        
        // Avulsas
        $itens                = $_POST['exigencia_item'] ?? [];
        $descricoes           = $_POST['exigencia_descricao'] ?? [];
        $status_items         = $_POST['status_item'] ?? [];
        $observacoes_exig     = $_POST['exigencia_observacao'] ?? [];
        $exigencia_ids        = $_POST['exigencia_id'] ?? [];
        $ordens               = $_POST['exigencia_ordem'] ?? [];
        
        // Checklist
        $checklist_ids        = $_POST['checklist_id'] ?? [];
        $checklist_status     = $_POST['checklist_status'] ?? [];
        $checklist_obs        = $_POST['checklist_observacao'] ?? [];
        $checklist_venc       = $_POST['checklist_vencimento'] ?? [];
        $checklist_item_normam= $_POST['checklist_item_normam'] ?? [];

        if (empty($agendamento_id)) {
            setMensagem('error', 'Agendamento nao informado.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $statusValidos = ['PENDENTE', 'AGUARDANDO_APROVACAO', 'APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'];
        if (!in_array($status_vistoria, $statusValidos)) {
            setMensagem('error', 'Status de vistoria invalido.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        
        if (getCargo() === 'VISTORIADOR' && in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA', 'CANCELADA'])) {
            setMensagem('error', 'Vistoriadores só podem salvar relatórios como Pendente ou Aguardando Aprovação.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }

        // Guard: impedir edicao de relatorio técnico já finalizado.
        // VISTORIADOR não pode alterar vistoria com status terminal (homologada/reprovada).
        // ADMIN mantém liberdade para corrigir/reabrir quando necessário.
        $statusTerminais = ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'];
        if (!empty($vistoria_id)) {
            $stmtStatusAtual = $pdo->prepare("SELECT status FROM vistorias WHERE id = :id LIMIT 1");
            $stmtStatusAtual->execute([':id' => $vistoria_id]);
            $status_atual = $stmtStatusAtual->fetchColumn();
            if ($status_atual && in_array($status_atual, $statusTerminais, true) && getCargo() !== 'ADMIN') {
                setMensagem('error', 'Este relatório já está finalizado (' . $status_atual . ') e não pode ser editado por vistoriadores.');
                redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
            }
        }

        try {
            $pdo->beginTransaction();

            $stmtAg = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmtAg->execute([':id' => $agendamento_id]);
            $ag = $stmtAg->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                throw new Exception('Agendamento nao encontrado.');
            }

            // Relatorio anterior (se existir) do form (mas não enviamos, vamos buscar novamente ou atualizar)
            $stmtAnt = $pdo->prepare("SELECT id FROM vistorias WHERE embarcacao_id = :emb_id AND status IN ('APROVADA', 'APROVADA_COM_EXIGENCIAS') ORDER BY data_vistoria DESC, id DESC LIMIT 1");
            $stmtAnt->execute([':emb_id' => $ag['embarcacao_id']]);
            $relatorio_anterior_id = $stmtAnt->fetchColumn() ?: null;

            // Gerar texto automático
            $txt_gerado = "";
            $cumpridas = [];
            $transcritas = [];
            if (!empty($itens)) {
                foreach ($itens as $i => $item) {
                    $st = $status_items[$i] ?? 'inserida';
                    $ordem = (int)($ordens[$i] ?? ($i + 1));
                    if ($st === 'cumprida') {
                        $cumpridas[] = $ordem;
                    } elseif ($st === 'nao_cumprida_transcrita' || $st === 'cumprida_parcial_reescrita') {
                        $transcritas[] = $ordem;
                    }
                }
            }
            if (!empty($cumpridas)) {
                $txt_gerado .= "As exigências n.º " . implode(', ', $cumpridas) . " foram CUMPRIDAS.\n";
            }
            if (!empty($transcritas)) {
                $txt_gerado .= "As exigências n.º " . implode(', ', $transcritas) . " foram TRANSCRITAS ou REESCRITAS.";
            }

            if (empty($vistoria_id)) {
                // Gerar numero do relatorio conforme o tipo de vistoria
                $is_arqueacao = stripos($ag['tipo_vistoria'], 'arquea') !== false;
                $numero_relatorio = $is_arqueacao
                    ? gerarNumeroDocumento('REL-AP', 'AM-REL-AP')
                    : gerarNumeroDocumento('REL-V', 'AM-REL-V');

                // Criar nova vistoria com numero
                $vistoria_id = gerarUUID();
                $stmtV = $pdo->prepare("
                    INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, armador_id, agendamento_id, data_vistoria, observacoes_tecnicas, status, criado_por, relatorio_anterior_id, texto_observacoes_geradas)
                    VALUES (:id, :numero, :embarcacao_id, :pessoa_id, :armador_id, :agendamento_id, :data_vistoria, :obs_tecnicas, :status, :criado_por, :rel_ant, :txt_gerado)
                ");
                $stmtV->execute([
                    ':id'             => $vistoria_id,
                    ':numero'         => $numero_relatorio,
                    ':embarcacao_id'  => $ag['embarcacao_id'],
                    ':pessoa_id'      => $ag['cliente_id'],
                    ':armador_id'     => $armador_id ?: null,
                    ':agendamento_id' => $agendamento_id,
                    ':data_vistoria'  => $data_vistoria ?: $ag['data_vistoria'],
                    ':obs_tecnicas'   => $observacoes_tecnicas ?: null,
                    ':status'         => $status_vistoria,
                    ':criado_por'     => $_SESSION['usuario_id'],
                    ':rel_ant'        => $relatorio_anterior_id,
                    ':txt_gerado'     => $txt_gerado ?: null,
                ]);
            } else {
                // Obter numero existente
                $stmtCheck = $pdo->prepare("SELECT numero FROM vistorias WHERE id = :id");
                $stmtCheck->execute([':id' => $vistoria_id]);
                $numero_relatorio = $stmtCheck->fetchColumn() ?: '';

                // Atualizar vistoria existente
                $stmtV = $pdo->prepare("
                    UPDATE vistorias
                    SET armador_id = :armador_id,
                        data_vistoria = :data_vistoria,
                        observacoes_tecnicas = :obs_tecnicas, status = :status,
                        aprovado_por = IF(:status_check IN ('APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA'), :aprovador, aprovado_por),
                        data_aprovacao = IF(:status2 IN ('APROVADA','APROVADA_COM_EXIGENCIAS','REPROVADA'), NOW(), data_aprovacao),
                        texto_observacoes_geradas = :txt_gerado,
                        relatorio_anterior_id = :rel_ant
                    WHERE id = :id
                ");
                $stmtV->execute([
                    ':armador_id'   => $armador_id ?: null,
                    ':data_vistoria'=> $data_vistoria ?: $ag['data_vistoria'],
                    ':obs_tecnicas' => $observacoes_tecnicas ?: null,
                    ':status'       => $status_vistoria,
                    ':status_check' => $status_vistoria,
                    ':aprovador'    => $_SESSION['usuario_id'],
                    ':status2'      => $status_vistoria,
                    ':txt_gerado'   => $txt_gerado ?: null,
                    ':rel_ant'      => $relatorio_anterior_id,
                    ':id'           => $vistoria_id,
                ]);

                // Remover exigencias antigas para reinserir
                $stmtDel = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                $stmtDel->execute([':vistoria_id' => $vistoria_id]);

                // Remover respostas antigas do checklist para gravar apenas o estado atual do formulario.
                $stmtDelChecklist = $pdo->prepare("DELETE FROM vistoria_checklist_respostas WHERE vistoria_id = :vistoria_id");
                $stmtDelChecklist->execute([':vistoria_id' => $vistoria_id]);
            }

            // Inserir respostas do Checklist e Exigencias associadas
            $ordem_global = 1;
            
            $stmtEx = $pdo->prepare("
                INSERT INTO vistoria_exigencias (id, vistoria_id, ordem, item, descricao, conforme, observacao, status_item, catalogo_id, vencimento)
                VALUES (UUID(), :vistoria_id, :ordem, :item, :descricao, :conforme, :observacao, :status_item, :catalogo_id, :vencimento)
            ");
            
            if (!empty($checklist_ids)) {
                $stmtChecklist = $pdo->prepare("
                    INSERT INTO vistoria_checklist_respostas (id, vistoria_id, catalogo_id, status, observacao, vencimento, item_normam)
                    VALUES (UUID(), :vistoria_id, :catalogo_id, :status, :observacao, :vencimento, :item_normam)
                    ON DUPLICATE KEY UPDATE status = :status_upd, observacao = :obs_upd, vencimento = :venc_upd, item_normam = :item_normam_upd
                ");
                
                $stmtCatFetch = $pdo->prepare("SELECT descricao, item_normam FROM exigencias_catalogo WHERE id = :id");
                
                foreach ($checklist_ids as $i => $cat_id) {
                    $status_resp = trim($checklist_status[$i] ?? '');
                    if (empty($status_resp)) continue; // não respondeu
                    
                    $obs = trim($checklist_obs[$i] ?? '') ?: null;
                    $venc = trim($checklist_venc[$i] ?? '') ?: null;
                    $item_normam = trim($checklist_item_normam[$i] ?? '') ?: null;
                    
                    $stmtChecklist->execute([
                        ':vistoria_id' => $vistoria_id,
                        ':catalogo_id' => $cat_id,
                        ':status'      => $status_resp,
                        ':observacao'  => $obs,
                        ':vencimento'  => $venc,
                        ':item_normam' => $item_normam,
                        ':status_upd'  => $status_resp,
                        ':obs_upd'     => $obs,
                        ':venc_upd'    => $venc,
                        ':item_normam_upd' => $item_normam
                    ]);
                    
                    // Se nao conforme, adiciona a vistoria_exigencias automaticamente
                    if ($status_resp === 'NAO_CONFORME') {
                        $stmtCatFetch->execute([':id' => $cat_id]);
                        $cat_dados = $stmtCatFetch->fetch(PDO::FETCH_ASSOC);
                        
                        $item_texto = 'Item do Checklist';
                        if ($cat_dados && $cat_dados['item_normam']) {
                            $item_texto = 'Item Normam: ' . $cat_dados['item_normam'];
                        }
                        
                        $stmtEx->execute([
                            ':vistoria_id' => $vistoria_id,
                            ':ordem'       => $ordem_global++,
                            ':item'        => $item_texto,
                            ':descricao'   => $cat_dados['descricao'] ?? 'Sem descrição',
                            ':conforme'    => 'nao',
                            ':observacao'  => $obs,
                            ':status_item' => 'pendente',
                            ':catalogo_id' => $cat_id,
                            ':vencimento'  => $venc
                        ]);
                    }
                }
            }

            // Inserir exigencias Avulsas
            if (!empty($itens)) {
                foreach ($itens as $i => $item) {
                    $item = trim($item);
                    if (empty($item)) continue;

                    $status_it = $status_items[$i] ?? 'inserida';
                    $conforme = 'na';
                    if ($status_it === 'cumprida') $conforme = 'sim';
                    elseif (in_array($status_it, ['pendente', 'nao_cumprida_transcrita', 'cumprida_parcial_reescrita'])) $conforme = 'nao';

                    $stmtEx->execute([
                        ':vistoria_id' => $vistoria_id,
                        ':ordem'       => $ordem_global++,
                        ':item'        => $item,
                        ':descricao'   => trim($descricoes[$i] ?? '') ?: null,
                        ':conforme'    => $conforme,
                        ':observacao'  => trim($observacoes_exig[$i] ?? '') ?: null,
                        ':status_item' => $status_it,
                        ':catalogo_id' => null,
                        ':vencimento'  => null
                    ]);
                }
            }

            // REGRA: Se status for APROVADA ou REPROVADA, avancar OS para Executado
            if (in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'])) {
                $stmtOs = $pdo->prepare("
                    UPDATE ordens_servico
                    SET status = 'executado'
                    WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')
                ");
                $stmtOs->execute([':agendamento_id' => $agendamento_id]);

                $stmtAgUpd = $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id");
                $stmtAgUpd->execute([':id' => $agendamento_id]);
            }

            $pdo->commit();

            log_atividade('relatorio_salvo', "Relatorio tecnico {$numero_relatorio} salvo para agendamento ID: {$agendamento_id}. Status: {$status_vistoria}.");
            $msg = 'Relatorio tecnico salvo com sucesso!';
            if (in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS', 'REPROVADA'])) {
                $msg .= ' Ordem de Servico avancada para EXECUTADA. Certificados liberados.';
            }
            setMensagem('success', $msg);
            if (in_array($status_vistoria, ['APROVADA', 'APROVADA_COM_EXIGENCIAS'])) {
                redirecionar(APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode($agendamento_id));
            } else {
                redirecionar(APP_URL . 'agendamentos');
            }

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao salvar relatorio: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            setMensagem('error', 'Erro ao salvar relatorio tecnico: ' . $e->getMessage());
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        break;

    // ==============================
    // APROVAR OU REPROVAR RELATORIO (ADMIN)
    // ==============================
    case 'aprovar_ou_reprovar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        verificar_cargo('ADMIN');

        $id = $_POST['id'] ?? '';
        $decisao = $_POST['decisao'] ?? '';
        $observacao = sanitizar($_POST['observacao_admin'] ?? '');

        if (empty($id)) {
            setMensagem('error', 'ID da vistoria invalido.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        if ($decisao === 'reprovar' && empty($observacao)) {
            setMensagem('error', 'A observacao e obrigatoria ao reprovar um relatorio.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $stmtV = $pdo->prepare("SELECT * FROM vistorias WHERE id = :id");
        $stmtV->execute([':id' => $id]);
        $vistoria = $stmtV->fetch(PDO::FETCH_ASSOC);

        if (!$vistoria) {
            setMensagem('error', 'Vistoria nao encontrada.');
            redirecionar(APP_URL . 'documentacao/aprovacao_relatorios');
        }

        $agendamento_id = $vistoria['agendamento_id'] ?? null;

                if ($decisao === 'aprovar') {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("SELECT COUNT(*) FROM vistoria_exigencias WHERE vistoria_id = :id AND conforme = 'nao'");
                $stmt->execute([':id' => $id]);
                $nao_conformes = (int)$stmt->fetchColumn();

                $novo_status = ($nao_conformes > 0) ? 'APROVADA_COM_EXIGENCIAS' : 'APROVADA';
                $stmt = $pdo->prepare("UPDATE vistorias SET status = :status, observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
                $stmt->execute([
                    ':status' => $novo_status,
                    ':obs' => $observacao ?: null,
                    ':aprovador' => $_SESSION['usuario_id'],
                    ':id' => $id
                ]);

                if ($agendamento_id) {
                    $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')")->execute([':agendamento_id' => $agendamento_id]);
                    $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $agendamento_id]);
                }

                $pdo->commit();

                log_atividade('relatorio_aprovado', "Relatorio ID {$id} aprovado. Status: {$novo_status}.");
                setMensagem('success', 'Relatorio aprovado com sucesso.');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Erro ao aprovar vistoria: ' . $e->getMessage());
                setMensagem('error', 'Erro ao processar aprovação. Tente novamente.');
            }
        } else {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE vistorias SET status = 'REPROVADA', observacao_admin = :obs, aprovado_por = :aprovador, data_aprovacao = NOW() WHERE id = :id");
                $stmt->execute([
                    ':obs' => $observacao ?: null,
                    ':aprovador' => $_SESSION['usuario_id'],
                    ':id' => $id
                ]);
                
                if ($agendamento_id) {
                    $pdo->prepare("UPDATE ordens_servico SET status = 'executado' WHERE agendamento_id = :agendamento_id AND status IN ('pendente', 'em_andamento')")->execute([':agendamento_id' => $agendamento_id]);
                    $pdo->prepare("UPDATE agendamentos SET status = 'concluido' WHERE id = :id")->execute([':id' => $agendamento_id]);
                }
                
                $pdo->commit();
                log_atividade('relatorio_reprovado', "Relatorio ID {$id} reprovado.");
                setMensagem('error', 'Relatorio reprovado. Agendamento concluído.');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Erro ao reprovar vistoria: ' . $e->getMessage());
                setMensagem('error', 'Erro ao reprovar relatório.');
            }
        }

        if ($decisao === 'aprovar' && $agendamento_id) {
            redirecionar(APP_URL . 'documentacao/novo_certificado?agendamento_id=' . urlencode($agendamento_id));
        } else {
            redirecionar(APP_URL . 'agendamentos');
        }
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'vistorias');
}
