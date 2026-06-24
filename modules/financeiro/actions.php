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
verificar_cargo('ADMIN');

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

        $id          = trim($_POST['id'] ?? '');
        $tipo        = $_POST['tipo'] ?? '';
        $descricao   = trim($_POST['descricao'] ?? '');
        $valor       = trim($_POST['valor'] ?? '');
        $data        = trim($_POST['data'] ?? '');
        $categoria   = trim($_POST['categoria'] ?? '');
        $observacoes = trim($_POST['observacoes'] ?? '');

        // Validacoes
        $erros = [];

        if (!in_array($tipo, ['RECEITA', 'DESPESA'])) {
            $erros[] = 'Tipo invalido. Selecione RECEITA ou DESPESA.';
        }

        if (empty($descricao)) {
            $erros[] = 'A descricao e obrigatoria.';
        } elseif (strlen($descricao) < 3) {
            $erros[] = 'A descricao deve ter pelo menos 3 caracteres.';
        }

        // Validar e converter valor (aceitar formato brasileiro 1.234,56)
        $valorLimpo = str_replace(['.', ','], ['', '.'], $valor);
        if (!is_numeric($valorLimpo) || floatval($valorLimpo) <= 0) {
            $erros[] = 'O valor deve ser um numero positivo.';
        } else {
            $valorLimpo = number_format(floatval($valorLimpo), 2, '.', '');
        }

        if (empty($data)) {
            $erros[] = 'A data e obrigatoria.';
        } else {
            $dataObj = DateTime::createFromFormat('Y-m-d', $data);
            if (!$dataObj || $dataObj->format('Y-m-d') !== $data) {
                $erros[] = 'Data invalida.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            $url = APP_URL . 'financeiro/form';
            if (!empty($id)) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            $isEdicao = !empty($id);

            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE financeiro_lancamentos SET tipo = :tipo, descricao = :descricao, valor = :valor, data = :data, categoria = :categoria, observacoes = :observacoes WHERE id = :id");
                $stmt->execute([
                    ':tipo'        => $tipo,
                    ':descricao'   => $descricao,
                    ':valor'       => $valorLimpo,
                    ':data'        => $data,
                    ':categoria'   => $categoria,
                    ':observacoes' => $observacoes,
                    ':id'          => $id
                ]);
                setMensagem('success', 'Lancamento atualizado com sucesso!');
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO financeiro_lancamentos (id, tipo, descricao, valor, data, categoria, observacoes, criado_por) VALUES (:id, :tipo, :descricao, :valor, :data, :categoria, :observacoes, :criado_por)");
                $stmt->execute([
                    ':id'          => gerarUUID(),
                    ':tipo'        => $tipo,
                    ':descricao'   => $descricao,
                    ':valor'       => $valorLimpo,
                    ':data'        => $data,
                    ':categoria'   => $categoria,
                    ':observacoes' => $observacoes,
                    ':criado_por'  => $_SESSION['usuario_id']
                ]);
                setMensagem('success', 'Lancamento criado com sucesso!');
            }
        } catch (Exception $e) {
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