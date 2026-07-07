<?php
/**
 * MODULO: CONTRATOS
 * Arquivo: actions.php - CRUD
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
if (getCargo() !== 'ADMIN' && getCargo() !== 'VENDEDOR') {
    setMensagem('error', 'Acesso negado.');
    redirecionar(APP_URL . 'dashboard');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'salvar':
        if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
            setMensagem('error', 'Token de segurança inválido.');
            redirecionar(APP_URL . 'contratos');
        }

        $id = $_POST['id'] ?? '';
        $cliente_id = $_POST['cliente_id'] ?? '';
        $proposta_id = $_POST['proposta_id'] ?? null;
        if (empty($proposta_id)) $proposta_id = null;
        
        $numero = $_POST['numero'] ?? '';
        $status = $_POST['status'] ?? 'MINUTA';
        $data_emissao = $_POST['data_emissao'] ?? null;
        if (empty($data_emissao)) $data_emissao = null;

        $valor_total = $_POST['valor_total'] ?? 0;
        $conteudo = $_POST['conteudo'] ?? '';
        
        $frequencia = $_POST['frequencia'] ?? 'ÚNICA';
        $dia_vencimento = $_POST['dia_vencimento'] ?? null;
        if (empty($dia_vencimento) || $frequencia === 'ÚNICA') $dia_vencimento = null;
        $renovacao_automatica = isset($_POST['renovacao_automatica']) ? (int)$_POST['renovacao_automatica'] : 1;

        if (empty($cliente_id) || empty($data_emissao) || $valor_total === '') {
            $campos = [];
            if (empty($cliente_id)) $campos['cliente_id'] = 'Selecione o cliente.';
            if (empty($data_emissao)) $campos['data_emissao'] = 'Informe a data de emissao.';
            if ($valor_total === '') $campos['valor_total'] = 'Informe o valor total.';
            setMensagem('error', 'Revise os campos obrigatorios indicados.', $campos);
            redirecionar(APP_URL . 'contratos/form' . ($id ? "?id=$id" : ''));
        }

        $data_vencimento = date('Y-m-d', strtotime($data_emissao . ' +30 days'));

        // Calcula próximo faturamento inicial se for recorrente
        $proximo_faturamento = null;
        if ($frequencia !== 'ÚNICA' && $dia_vencimento && $status === 'ASSINADO') {
            // Regra básica: próximo mês no dia estipulado
            $proximo_faturamento = date('Y-m-') . str_pad($dia_vencimento, 2, '0', STR_PAD_LEFT);
            if ($proximo_faturamento < date('Y-m-d')) {
                // Já passou deste dia neste mês, joga pro mês que vem
                $proximo_faturamento = date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-') . '01')));
                $proximo_faturamento = substr($proximo_faturamento, 0, 8) . str_pad($dia_vencimento, 2, '0', STR_PAD_LEFT);
            }
        }

        try {
            if ($id) {
                // Update
                $stmt = $pdo->prepare("
                    UPDATE contratos SET 
                        cliente_id = :cliente_id,
                        proposta_id = :proposta_id,
                        status = :status,
                        data_emissao = :data_emissao,
                        data_vencimento = :data_vencimento,
                        valor_total = :valor_total,
                        conteudo = :conteudo,
                        frequencia = :frequencia,
                        dia_vencimento = :dia_vencimento,
                        renovacao_automatica = :renovacao_automatica
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':id' => $id,
                    ':cliente_id' => $cliente_id,
                    ':proposta_id' => $proposta_id,
                    ':status' => $status,
                    ':data_emissao' => $data_emissao,
                    ':data_vencimento' => $data_vencimento,
                    ':valor_total' => $valor_total,
                    ':conteudo' => $conteudo,
                    ':frequencia' => $frequencia,
                    ':dia_vencimento' => $dia_vencimento,
                    ':renovacao_automatica' => $renovacao_automatica
                ]);
                
                // Se assinado agora, e recorrente sem proximo_faturamento, define
                if ($status === 'ASSINADO' && $frequencia !== 'ÚNICA') {
                    $stmtCheck = $pdo->prepare("SELECT proximo_faturamento FROM contratos WHERE id = :id");
                    $stmtCheck->execute([':id' => $id]);
                    $pf = $stmtCheck->fetchColumn();
                    if (!$pf && $proximo_faturamento) {
                        $pdo->prepare("UPDATE contratos SET proximo_faturamento = :pf WHERE id = :id")->execute([':pf' => $proximo_faturamento, ':id' => $id]);
                    }
                }
                
                setMensagem('success', 'Contrato atualizado com sucesso.');
            } else {
                // Insert
                $novo_id = gerarUUID();
                
                // Se numero vazio, gera automatico CT-Y/M-XXXX
                if (empty($numero)) {
                    $numero = 'CT-' . date('Y/m') . '-' . substr(str_shuffle("0123456789"), 0, 4);
                }

                $stmt = $pdo->prepare("
                    INSERT INTO contratos (
                        id, cliente_id, proposta_id, numero, status, data_emissao, data_vencimento, valor_total, conteudo, frequencia, dia_vencimento, proximo_faturamento, renovacao_automatica, criado_por
                    ) VALUES (
                        :id, :cliente_id, :proposta_id, :numero, :status, :data_emissao, :data_vencimento, :valor_total, :conteudo, :frequencia, :dia_vencimento, :proximo_faturamento, :renovacao_automatica, :criado_por
                    )
                ");
                $stmt->execute([
                    ':id' => $novo_id,
                    ':cliente_id' => $cliente_id,
                    ':proposta_id' => $proposta_id,
                    ':numero' => $numero,
                    ':status' => $status,
                    ':data_emissao' => $data_emissao,
                    ':data_vencimento' => $data_vencimento,
                    ':valor_total' => $valor_total,
                    ':conteudo' => $conteudo,
                    ':frequencia' => $frequencia,
                    ':dia_vencimento' => $dia_vencimento,
                    ':proximo_faturamento' => $proximo_faturamento,
                    ':renovacao_automatica' => $renovacao_automatica,
                    ':criado_por' => $_SESSION['usuario_id'] ?? null
                ]);
                setMensagem('success', 'Contrato criado com sucesso.');
            }
        } catch (Exception $e) {
            setMensagem('error', 'Erro ao salvar: ' . $e->getMessage());
        }
        
        redirecionar(APP_URL . 'contratos');
        break;

    case 'excluir':
        if (!verificarCSRF($_GET['csrf_token'] ?? '')) {
            setMensagem('error', 'Token de segurança inválido.');
            redirecionar(APP_URL . 'contratos');
        }
        
        $id = $_GET['id'] ?? '';
        
        if ($id) {
            try {
                // Soft delete
                $stmt = $pdo->prepare("UPDATE contratos SET ativo = 0 WHERE id = :id AND status != 'ASSINADO'");
                $stmt->execute([':id' => $id]);
                if ($stmt->rowCount() > 0) {
                    setMensagem('success', 'Contrato excluído com sucesso.');
                } else {
                    setMensagem('error', 'Contrato assinado não pode ser excluído.');
                }
            } catch (Exception $e) {
                setMensagem('error', 'Erro ao excluir: ' . $e->getMessage());
            }
        }
        
        redirecionar(APP_URL . 'contratos');
        break;

    default:
        redirecionar(APP_URL . 'contratos');
}
