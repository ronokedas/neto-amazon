<?php
/**
 * MODULO: PESSOAS
 * Arquivo: actions.php - Processar acoes (salvar, desativar)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Exigir login e permissao (ADMIN e VISTORIADOR)
verificar_sessao();
if (!podeAcessar('pessoas')) {
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
            redirecionar(APP_URL . 'pessoas');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'pessoas');
        }

        $id             = trim($_POST['id'] ?? '');
        $nome_completo  = trim($_POST['nome_completo'] ?? '');
        $cpf            = trim($_POST['cpf'] ?? '');
        $telefone       = trim($_POST['telefone'] ?? '');
        $email          = trim($_POST['email'] ?? '');
        $endereco       = trim($_POST['endereco'] ?? '');
        $observacoes    = trim($_POST['observacoes'] ?? '');
        $ativo          = isset($_POST['ativo']) ? 1 : 0;

        // Validacoes
        $erros = [];

        if (empty($nome_completo)) {
            $erros[] = 'O nome completo e obrigatorio.';
        } elseif (strlen($nome_completo) < 3) {
            $erros[] = 'O nome completo deve ter pelo menos 3 caracteres.';
        }

        // Validacao de CPF (obrigatorio)
        if (empty($cpf)) {
            $erros[] = 'O CPF e obrigatorio.';
        } else {
            // Limpar formatacao do CPF
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
            if (strlen($cpfLimpo) !== 11) {
                $erros[] = 'O CPF deve conter 11 digitos.';
            } elseif (!validarCPF($cpfLimpo)) {
                $erros[] = 'CPF invalido. Verifique os digitos.';
            }
        }

        // Validacao de email (se informado)
        if (!empty($email) && !validarEmail($email)) {
            $erros[] = 'Email invalido.';
        }

        // Verificar CPF duplicado
        if (empty($erros) && !empty($cpf)) {
            try {
                $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);
                $sqlCheck = "SELECT id FROM pessoas WHERE cpf = :cpf";
                $paramsCheck = [':cpf' => $cpfLimpo];
                $isEdicao = !empty($id);
                if ($isEdicao) {
                    $sqlCheck .= " AND id <> :id";
                    $paramsCheck[':id'] = $id;
                }
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute($paramsCheck);
                if ($stmtCheck->fetch()) {
                    $erros[] = 'Ja existe uma pessoa cadastrada com este CPF.';
                }
            } catch (Exception $e) {
                error_log('Erro ao verificar CPF: ' . $e->getMessage());
                $erros[] = 'Erro ao validar dados.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            $url = APP_URL . 'pessoas/form';
            if (!empty($id)) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            $isEdicao = !empty($id);
            $cpfLimpo = preg_replace('/[^0-9]/', '', $cpf);

            if ($isEdicao) {
                // Atualizar
                $stmt = $pdo->prepare("UPDATE pessoas SET nome_completo = :nome_completo, cpf = :cpf, telefone = :telefone, email = :email, endereco = :endereco, observacoes = :observacoes, ativo = :ativo WHERE id = :id");
                $stmt->execute([
                    ':nome_completo' => $nome_completo,
                    ':cpf'           => $cpfLimpo,
                    ':telefone'      => $telefone,
                    ':email'         => $email,
                    ':endereco'      => $endereco,
                    ':observacoes'   => $observacoes,
                    ':ativo'         => $ativo,
                    ':id'            => $id
                ]);
                setMensagem('success', 'Pessoa atualizada com sucesso!');
            } else {
                // Criar
                $stmt = $pdo->prepare("INSERT INTO pessoas (id, nome_completo, cpf, telefone, email, endereco, observacoes, ativo, criado_por) VALUES (:id, :nome_completo, :cpf, :telefone, :email, :endereco, :observacoes, :ativo, :criado_por)");
                $stmt->execute([
                    ':id'            => gerarUUID(),
                    ':nome_completo' => $nome_completo,
                    ':cpf'           => $cpfLimpo,
                    ':telefone'      => $telefone,
                    ':email'         => $email,
                    ':endereco'      => $endereco,
                    ':observacoes'   => $observacoes,
                    ':ativo'         => $ativo,
                    ':criado_por'    => $_SESSION['usuario_id']
                ]);
                setMensagem('success', 'Pessoa criada com sucesso!');
            }
        } catch (Exception $e) {
            error_log('Erro ao salvar pessoa: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar pessoa. Tente novamente.');
        }

        redirecionar(APP_URL . 'pessoas');
        break;

    // ==============================
    // DESATIVAR / ATIVAR
    // ==============================
    case 'alternar_status':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'pessoas');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, ativo FROM pessoas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $pessoa = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$pessoa) {
                setMensagem('error', 'Pessoa nao encontrada.');
                redirecionar(APP_URL . 'pessoas');
            }

            $novoStatus = $pessoa['ativo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE pessoas SET ativo = :ativo WHERE id = :id");
            $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);

            $msgStatus = $novoStatus ? 'ativada' : 'desativada';
            setMensagem('success', "Pessoa {$msgStatus} com sucesso!");
        } catch (Exception $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status da pessoa.');
        }

        redirecionar(APP_URL . 'pessoas');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'pessoas');
}