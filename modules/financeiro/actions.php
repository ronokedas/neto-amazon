<?php
/**
 * MODULO: FINANCEIRO
 * Arquivo: actions.php - Processar acoes (salvar, excluir lancamentos)
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

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'financeiro');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        $id              = trim($_POST['id'] ?? '');
        $tipo            = $_POST['tipo'] ?? '';
        $frequencia      = $_POST['frequencia'] ?? 'unica';
        $status          = $_POST['status'] ?? 'PAGO';
        $data_vencimento = trim($_POST['data_vencimento'] ?? '');
        $cliente_id      = trim($_POST['cliente_id'] ?? '');
        $data            = trim($_POST['data'] ?? '');
        $descricao       = trim($_POST['descricao'] ?? '');
        $valor           = trim($_POST['valor'] ?? '');
        $categoria       = trim($_POST['categoria'] ?? '');
        $observacoes     = trim($_POST['observacoes'] ?? '');

        if (empty($data)) $data = null;

        // Validacoes
        $erros = [];
        $errosCampos = [];

        if (!in_array($tipo, ['RECEITA', 'DESPESA'])) {
            $erros[] = 'Tipo invalido. Selecione RECEITA ou DESPESA.';
            $errosCampos['tipo'] = 'Selecione Receita ou Despesa.';
        }

        if (empty($descricao)) {
            $erros[] = 'A descricao e obrigatoria.';
            $errosCampos['descricao'] = 'Informe a descricao do lancamento.';
        } elseif (strlen($descricao) < 3) {
            $erros[] = 'A descricao deve ter pelo menos 3 caracteres.';
            $errosCampos['descricao'] = 'Use pelo menos 3 caracteres.';
        }

        // Validar e converter valor (aceitar formato brasileiro 1.234,56)
        $valorLimpo = str_replace(['.', ','], ['', '.'], $valor);
        if (!is_numeric($valorLimpo) || floatval($valorLimpo) <= 0) {
            $erros[] = 'O valor deve ser um numero positivo.';
            $errosCampos['valor'] = 'Informe um valor maior que zero.';
        } else {
            $valorLimpo = number_format(floatval($valorLimpo), 2, '.', '');
        }

        if (empty($data_vencimento)) {
            $erros[] = 'A data de vencimento e obrigatoria.';
            $errosCampos['data_vencimento'] = 'Informe a data de vencimento.';
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros), $errosCampos);
            $url = APP_URL . 'financeiro/form';
            if (!empty($id)) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            $isEdicao = !empty($id);

            $pdo->beginTransaction();

            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE financeiro_lancamentos SET tipo = :tipo, frequencia = :frequencia, status = :status, data_vencimento = :data_vencimento, cliente_id = :cliente_id, descricao = :descricao, valor = :valor, data = :data, categoria = :categoria, observacoes = :observacoes WHERE id = :id");
                $stmt->execute([
                    ':tipo'            => $tipo,
                    ':frequencia'      => $frequencia,
                    ':status'          => $status,
                    ':data_vencimento' => $data_vencimento,
                    ':cliente_id'      => $cliente_id ?: null,
                    ':descricao'       => $descricao,
                    ':valor'           => $valorLimpo,
                    ':data'            => $data,
                    ':categoria'       => $categoria,
                    ':observacoes'     => $observacoes,
                    ':id'              => $id
                ]);

                // Lógica de recorrência ao pagar
                if ($status === 'PAGO' && $frequencia !== 'unica') {
                    // Verifica se já foi gerado (evitar duplicação).
                    // Para simplificar, verificamos se existe algum lançamento com mesma descricao e data_vencimento maior que a deste
                    $stmtClone = $pdo->prepare("SELECT COUNT(*) as qtd FROM financeiro_lancamentos WHERE descricao = :descricao AND data_vencimento > :dv_atual");
                    $stmtClone->execute([':descricao' => $descricao, ':dv_atual' => $data_vencimento]);
                    $ja_existe = $stmtClone->fetch()['qtd'] > 0;

                    if (!$ja_existe) {
                        // Calcula nova data
                        $meses = 1;
                        if ($frequencia === 'trimestral') $meses = 3;
                        if ($frequencia === 'anual') $meses = 12;

                        $nova_data_vencimento = date('Y-m-d', strtotime("+{$meses} months", strtotime($data_vencimento)));

                        $stmtInsert = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                        $stmtInsert->execute([
                            ':id'              => gerarUUID(),
                            ':tipo'            => $tipo,
                            ':frequencia'      => $frequencia,
                            ':status'          => 'PENDENTE',
                            ':data_vencimento' => $nova_data_vencimento,
                            ':cliente_id'      => $cliente_id ?: null,
                            ':descricao'       => $descricao,
                            ':valor'           => $valorLimpo,
                            ':data'            => null,
                            ':categoria'       => $categoria,
                            ':observacoes'     => $observacoes,
                            ':criado_por'      => $_SESSION['usuario_id'] ?? null
                        ]);
                    }
                }
                $pdo->commit();
                setMensagem('success', 'Lancamento atualizado com sucesso!');
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                $stmt->execute([
                    ':id'              => gerarUUID(),
                    ':tipo'            => $tipo,
                    ':frequencia'      => $frequencia,
                    ':status'          => $status,
                    ':data_vencimento' => $data_vencimento,
                    ':cliente_id'      => $cliente_id ?: null,
                    ':descricao'       => $descricao,
                    ':valor'           => $valorLimpo,
                    ':data'            => $data,
                    ':categoria'       => $categoria,
                    ':observacoes'     => $observacoes,
                    ':criado_por'      => $_SESSION['usuario_id'] ?? null
                ]);

                // Lógica de recorrência se for criado já como PAGO
                if ($status === 'PAGO' && $frequencia !== 'unica') {
                    $meses = 1;
                    if ($frequencia === 'trimestral') $meses = 3;
                    if ($frequencia === 'anual') $meses = 12;

                    $nova_data_vencimento = date('Y-m-d', strtotime("+{$meses} months", strtotime($data_vencimento)));

                    $stmtInsert = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, frequencia, status, data_vencimento, cliente_id, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :frequencia, :status, :data_vencimento, :cliente_id, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                    $stmtInsert->execute([
                        ':id'              => gerarUUID(),
                        ':tipo'            => $tipo,
                        ':frequencia'      => $frequencia,
                        ':status'          => 'PENDENTE',
                        ':data_vencimento' => $nova_data_vencimento,
                        ':cliente_id'      => $cliente_id ?: null,
                        ':descricao'       => $descricao,
                        ':valor'           => $valorLimpo,
                        ':data'            => null,
                        ':categoria'       => $categoria,
                        ':observacoes'     => $observacoes,
                        ':criado_por'      => $_SESSION['usuario_id'] ?? null
                    ]);
                }
                $pdo->commit();
                setMensagem('success', 'Lancamento criado com sucesso!');
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Erro ao salvar lancamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar lancamento. Tente novamente.');
        }

        redirecionar(APP_URL . 'financeiro');
        break;

    // ==============================
    // EXCLUIR
    // ==============================
    case 'excluir':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'financeiro');
        }

        try {
            $stmt = $pdo->prepare("UPDATE financeiro_lancamentos SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setMensagem('success', 'Lancamento excluido com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao excluir lancamento: ' . $e->getMessage());
            setMensagem('error', 'Erro ao excluir lancamento.');
        }

        redirecionar(APP_URL . 'financeiro');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'financeiro');
}
