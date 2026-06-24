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
            $erros[] = 'Selecione uma pessoa responsavel.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, ativo FROM pessoas WHERE id = :id");
                $stmt->execute([':id' => $pessoa_id]);
                $pes = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$pes) {
                    $erros[] = 'Pessoa nao encontrada.';
                } elseif (!$pes['ativo']) {
                    $erros[] = 'Pessoa inativa. Nao e possivel criar vistoria com pessoa inativa.';
                }
            } catch (Exception $e) {
                error_log('Erro ao validar pessoa: ' . $e->getMessage());
                $erros[] = 'Erro ao validar pessoa.';
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
            $stmt = $pdo->prepare("INSERT INTO vistorias (id, embarcacao_id, pessoa_id, data_vistoria, observacoes, status, criado_por) VALUES (:id, :embarcacao_id, :pessoa_id, :data_vistoria, :observacoes, 'PENDENTE', :criado_por)");
            $stmt->execute([
                ':id'            => gerarUUID(),
                ':embarcacao_id' => $embarcacao_id,
                ':pessoa_id'     => $pessoa_id,
                ':data_vistoria' => $data_vistoria,
                ':observacoes'   => $observacoes,
                ':criado_por'    => $_SESSION['usuario_id']
            ]);

            unset($_SESSION['wizard_embarcacao_id']);
            unset($_SESSION['wizard_pessoa_id']);

            log_atividade('vistoria_criada', "Vistoria criada para embarcacao ID: {$embarcacao_id}.");
            setMensagem('success', 'Vistoria criada com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao salvar vistoria: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar vistoria. Tente novamente.');
        }

        redirecionar(APP_URL . 'vistorias');
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

        $statusesValidos = ['PENDENTE', 'APROVADA', 'REPROVADA', 'CANCELADA'];

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
        $data_vistoria = trim($_POST['data_vistoria'] ?? '');
        $observacoes   = trim($_POST['observacoes'] ?? '');

        if ($passo >= 1) $_SESSION['wizard_embarcacao_id'] = $embarcacao_id;
        if ($passo >= 2) $_SESSION['wizard_pessoa_id'] = $pessoa_id;
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
        $observacoes_tecnicas = trim($_POST['observacoes_tecnicas'] ?? '');
        $status_vistoria      = $_POST['status_vistoria'] ?? 'PENDENTE';
        $itens                = $_POST['exigencia_item'] ?? [];
        $descricoes           = $_POST['exigencia_descricao'] ?? [];
        $conformes            = $_POST['exigencia_conforme'] ?? [];
        $observacoes_exig     = $_POST['exigencia_observacao'] ?? [];
        $exigencia_ids        = $_POST['exigencia_id'] ?? [];
        $ordens               = $_POST['exigencia_ordem'] ?? [];

        if (empty($agendamento_id)) {
            setMensagem('error', 'Agendamento nao informado.');
            redirecionar(APP_URL . 'agendamentos');
        }

        $statusValidos = ['PENDENTE', 'APROVADA', 'REPROVADA'];
        if (!in_array($status_vistoria, $statusValidos)) {
            setMensagem('error', 'Status de vistoria invalido.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }

        try {
            $pdo->beginTransaction();

            $stmtAg = $pdo->prepare("SELECT * FROM agendamentos WHERE id = :id");
            $stmtAg->execute([':id' => $agendamento_id]);
            $ag = $stmtAg->fetch(PDO::FETCH_ASSOC);

            if (!$ag) {
                throw new Exception('Agendamento nao encontrado.');
            }

            if (empty($vistoria_id)) {
                // Gerar numero do relatorio conforme o tipo de vistoria
                $is_arqueacao = stripos($ag['tipo_vistoria'], 'arquea') !== false;
                $numero_relatorio = $is_arqueacao
                    ? gerarNumeroDocumento('REL-AP', 'AM-REL-AP')
                    : gerarNumeroDocumento('REL-V', 'AM-REL-V');

                // Criar nova vistoria com numero
                $stmtV = $pdo->prepare("
                    INSERT INTO vistorias (id, numero, embarcacao_id, pessoa_id, agendamento_id, data_vistoria, observacoes_tecnicas, status, criado_por)
                    VALUES (UUID(), :numero, :embarcacao_id, :pessoa_id, :agendamento_id, :data_vistoria, :obs_tecnicas, :status, :criado_por)
                ");
                $stmtV->execute([
                    ':numero'         => $numero_relatorio,
                    ':embarcacao_id'  => $ag['embarcacao_id'],
                    ':pessoa_id'      => $ag['cliente_id'],
                    ':agendamento_id' => $agendamento_id,
                    ':data_vistoria'  => $ag['data_vistoria'],
                    ':obs_tecnicas'   => $observacoes_tecnicas ?: null,
                    ':status'         => $status_vistoria,
                    ':criado_por'     => $_SESSION['usuario_id'],
                ]);
                $vistoria_id = $pdo->lastInsertId();
            } else {
                // Atualizar vistoria existente
                $stmtV = $pdo->prepare("
                    UPDATE vistorias
                    SET observacoes_tecnicas = :obs_tecnicas, status = :status
                    WHERE id = :id
                ");
                $stmtV->execute([
                    ':obs_tecnicas' => $observacoes_tecnicas ?: null,
                    ':status'       => $status_vistoria,
                    ':id'           => $vistoria_id,
                ]);

                // Remover exigencias antigas para reinserir
                $stmtDel = $pdo->prepare("DELETE FROM vistoria_exigencias WHERE vistoria_id = :vistoria_id");
                $stmtDel->execute([':vistoria_id' => $vistoria_id]);
            }

            // Inserir exigencias
            if (!empty($itens)) {
                $stmtEx = $pdo->prepare("
                    INSERT INTO vistoria_exigencias (id, vistoria_id, ordem, item, descricao, conforme, observacao)
                    VALUES (UUID(), :vistoria_id, :ordem, :item, :descricao, :conforme, :observacao)
                ");
                foreach ($itens as $i => $item) {
                    $item = trim($item);
                    if (empty($item)) continue;
                    $stmtEx->execute([
                        ':vistoria_id' => $vistoria_id,
                        ':ordem'       => (int)($ordens[$i] ?? ($i + 1)),
                        ':item'        => $item,
                        ':descricao'   => trim($descricoes[$i] ?? '') ?: null,
                        ':conforme'    => $conformes[$i] ?? 'na',
                        ':observacao'  => trim($observacoes_exig[$i] ?? '') ?: null,
                    ]);
                }
            }

            // REGRA: Se status for APROVADA ou REPROVADA, avancar OS para Executado
            if (in_array($status_vistoria, ['APROVADA', 'REPROVADA'])) {
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
            if (in_array($status_vistoria, ['APROVADA', 'REPROVADA'])) {
                $msg .= ' Ordem de Servico avancada para EXECUTADA. Certificados liberados.';
            }
            setMensagem('success', $msg);
            redirecionar(APP_URL . 'agendamentos');

        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Erro ao salvar relatorio: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar relatorio tecnico.');
            redirecionar(APP_URL . 'vistorias/relatorio?agendamento_id=' . urlencode($agendamento_id));
        }
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'vistorias');
}