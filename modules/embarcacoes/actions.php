<?php
/**
 * MODULO: EMBARCACOES
 * Arquivo: actions.php - Processar acoes (salvar, desativar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao do modulo
verificar_sessao();
$cargo = getCargo();
if (!in_array($cargo, ['ADMIN', 'VISTORIADOR'])) {
    setMensagem('error', 'Acesso negado. Voce nao tem permissao para acessar este modulo.');
    redirecionar(APP_URL . 'dashboard');
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {

    // ==============================
    // SALVAR (CRIAR / EDITAR)
    // ==============================
    case 'salvar':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setMensagem('error', 'Requisicao invalida.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        $id           = trim($_POST['id'] ?? '');
        $nome         = trim($_POST['nome'] ?? '');
        $registro     = trim($_POST['registro'] ?? '');
        $tipo         = trim($_POST['tipo'] ?? '');
        $proprietario = trim($_POST['proprietario'] ?? '');
        $ano          = trim($_POST['ano'] ?? '');
        $observacoes  = trim($_POST['observacoes'] ?? '');

        // Validacoes
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome da embarcacao e obrigatorio.';
        } elseif (strlen($nome) < 2) {
            $erros[] = 'O nome deve ter pelo menos 2 caracteres.';
        }

        if (empty($registro)) {
            $erros[] = 'O registro e obrigatorio.';
        } elseif (strlen($registro) < 2) {
            $erros[] = 'O registro deve ter pelo menos 2 caracteres.';
        }

        if (!empty($ano) && ($ano < 1900 || $ano > 2099)) {
            $erros[] = 'O ano deve estar entre 1900 e 2099.';
        }

        // Verificar registro duplicado
        $isEdicao = !empty($id);
        if (empty($erros)) {
            try {
                $sqlCheck = "SELECT id FROM embarcacoes WHERE registro = :registro";
                $paramsCheck = [':registro' => $registro];
                if ($isEdicao) {
                    $sqlCheck .= " AND id <> :id";
                    $paramsCheck[':id'] = $id;
                }
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute($paramsCheck);
                if ($stmtCheck->fetch()) {
                    $erros[] = 'Ja existe uma embarcacao com este registro.';
                }
            } catch (Exception $e) {
                error_log('Erro ao verificar registro: ' . $e->getMessage());
                $erros[] = 'Erro ao validar dados.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            $url = APP_URL . 'embarcacoes/form';
            if ($isEdicao) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        // Preparar dados
        $dados = [
            ':nome'         => $nome,
            ':registro'     => $registro,
            ':tipo'         => $tipo ?: null,
            ':proprietario' => $proprietario ?: null,
            ':ano'          => !empty($ano) ? (int)$ano : null,
            ':observacoes'  => $observacoes ?: null,
        ];

        try {
            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE embarcacoes SET nome = :nome, registro = :registro, tipo = :tipo, proprietario = :proprietario, ano = :ano, observacoes = :observacoes WHERE id = :id");
                $dados[':id'] = $id;
                $stmt->execute($dados);
                setMensagem('success', 'Embarcacao atualizada com sucesso!');
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO embarcacoes (id, nome, registro, tipo, proprietario, ano, observacoes, criado_por) VALUES (:id, :nome, :registro, :tipo, :proprietario, :ano, :observacoes, :criado_por)");
                $dados[':id'] = gerarUUID();
                $dados[':criado_por'] = $_SESSION['usuario_id'];
                $stmt->execute($dados);
                setMensagem('success', 'Embarcacao criada com sucesso!');
            }
        } catch (Exception $e) {
            error_log('Erro ao salvar embarcacao: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar embarcacao. Tente novamente.');
        }

        redirecionar(APP_URL . 'embarcacoes');
        break;

    // ==============================
    // DESATIVAR (SOFT DELETE)
    // ==============================
    case 'desativar':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'embarcacoes');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, nome FROM embarcacoes WHERE id = :id AND ativo = 1");
            $stmt->execute([':id' => $id]);
            $embarcacao = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$embarcacao) {
                setMensagem('error', 'Embarcacao nao encontrada ou ja desativada.');
                redirecionar(APP_URL . 'embarcacoes');
            }

            $stmt = $pdo->prepare("UPDATE embarcacoes SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);

            setMensagem('success', 'Embarcacao "' . $embarcacao['nome'] . '" desativada com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao desativar embarcacao: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar embarcacao.');
        }

        redirecionar(APP_URL . 'embarcacoes');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'embarcacoes');
}