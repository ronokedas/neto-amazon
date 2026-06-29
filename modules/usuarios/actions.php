<?php
/**
 * MODULO: USUARIOS
 * Arquivo: actions.php - Processar acoes (salvar, desativar, excluir)
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
            redirecionar(APP_URL . 'usuarios');
        }

        // Verificar CSRF
        $csrf = $_POST['csrf_token'] ?? '';
        if (!verificarCSRF($csrf)) {
            setMensagem('error', 'Token de seguranca invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        $id          = trim($_POST['id'] ?? '');
        $nome        = trim($_POST['nome'] ?? '');
        $email       = trim($_POST['email'] ?? '');
        $cargo       = $_POST['cargo'] ?? 'VISTORIADOR';
        $senha       = $_POST['senha'] ?? '';
        $confirma    = $_POST['senha_confirma'] ?? '';
        $ativo       = isset($_POST['ativo']) ? 1 : 0;

        // Validacoes
        $erros = [];

        if (empty($nome)) {
            $erros[] = 'O nome e obrigatorio.';
        } elseif (strlen($nome) < 3) {
            $erros[] = 'O nome deve ter pelo menos 3 caracteres.';
        }

        if (empty($email)) {
            $erros[] = 'O email e obrigatorio.';
        } elseif (!validarEmail($email)) {
            $erros[] = 'Email invalido.';
        }

        if (!in_array($cargo, ['ADMIN', 'VENDEDOR', 'VISTORIADOR'])) {
            $erros[] = 'Cargo invalido.';
        }

        // Se e criacao, senha e obrigatoria
        $isEdicao = !empty($id);
        if (!$isEdicao) {
            if (empty($senha)) {
                $erros[] = 'A senha e obrigatoria para novos usuarios.';
            } elseif (strlen($senha) < 6) {
                $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
            }
            if ($senha !== $confirma) {
                $erros[] = 'As senhas nao conferem.';
            }
        } else {
            // Se senha informada na edicao, validar
            if (!empty($senha)) {
                if (strlen($senha) < 6) {
                    $erros[] = 'A senha deve ter pelo menos 6 caracteres.';
                }
                if ($senha !== $confirma) {
                    $erros[] = 'As senhas nao conferem.';
                }
            }
        }

        // Verificar email duplicado
        if (empty($erros)) {
            try {
                $sqlCheck = "SELECT id FROM usuarios WHERE email = :email";
                $paramsCheck = [':email' => $email];
                if ($isEdicao) {
                    $sqlCheck .= " AND id <> :id";
                    $paramsCheck[':id'] = $id;
                }
                $stmtCheck = $pdo->prepare($sqlCheck);
                $stmtCheck->execute($paramsCheck);
                if ($stmtCheck->fetch()) {
                    $erros[] = 'Ja existe um usuario com este email.';
                }
            } catch (Exception $e) {
                error_log('Erro ao verificar email: ' . $e->getMessage());
                $erros[] = 'Erro ao validar dados.';
            }
        }

        if (!empty($erros)) {
            setMensagem('error', implode(' ', $erros));
            // Retornar para o form com os dados
            $url = APP_URL . 'usuarios/form';
            if ($isEdicao) $url .= '?id=' . urlencode($id);
            redirecionar($url);
        }

        try {
            if ($isEdicao) {
                // Atualizar
                if (!empty($senha)) {
                    $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, cargo = :cargo, senha_hash = :senha, ativo = :ativo WHERE id = :id");
                    $stmt->execute([
                        ':nome'   => $nome,
                        ':email'  => $email,
                        ':cargo'  => $cargo,
                        ':senha'  => $senhaHash,
                        ':ativo'  => $ativo,
                        ':id'     => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nome = :nome, email = :email, cargo = :cargo, ativo = :ativo WHERE id = :id");
                    $stmt->execute([
                        ':nome'   => $nome,
                        ':email'  => $email,
                        ':cargo'  => $cargo,
                        ':ativo'  => $ativo,
                        ':id'     => $id
                    ]);
                }
                setMensagem('success', 'Usuario atualizado com sucesso!');
            } else {
                // Criar
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (id, nome, email, senha_hash, cargo, ativo) VALUES (:id, :nome, :email, :senha, :cargo, :ativo)");
                $stmt->execute([
                    ':id'     => gerarUUID(),
                    ':nome'   => $nome,
                    ':email'  => $email,
                    ':senha'  => $senhaHash,
                    ':cargo'  => $cargo,
                    ':ativo'  => $ativo
                ]);
                setMensagem('success', 'Usuario criado com sucesso!');
            }
        } catch (Exception $e) {
            error_log('Erro ao salvar usuario: ' . $e->getMessage());
            setMensagem('error', 'Erro ao salvar usuario. Tente novamente.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    // ==============================
    // DESATIVAR / ATIVAR
    // ==============================
    case 'alternar_status':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        // Nao permitir desativar a si mesmo
        if ($id === $_SESSION['usuario_id']) {
            setMensagem('error', 'Voce nao pode desativar seu proprio usuario.');
            redirecionar(APP_URL . 'usuarios');
        }

        try {
            $stmt = $pdo->prepare("SELECT id, ativo FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario) {
                setMensagem('error', 'Usuario nao encontrado.');
                redirecionar(APP_URL . 'usuarios');
            }

            $novoStatus = $usuario['ativo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = :ativo WHERE id = :id");
            $stmt->execute([':ativo' => $novoStatus, ':id' => $id]);

            $msgStatus = $novoStatus ? 'ativado' : 'desativado';
            setMensagem('success', "Usuario {$msgStatus} com sucesso!");
        } catch (Exception $e) {
            error_log('Erro ao alterar status: ' . $e->getMessage());
            setMensagem('error', 'Erro ao alterar status do usuario.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    // ==============================
    // EXCLUIR (PERMANENTE)
    // ==============================
    case 'excluir':
        $id = $_GET['id'] ?? '';
        if (empty($id)) {
            setMensagem('error', 'ID invalido.');
            redirecionar(APP_URL . 'usuarios');
        }

        // Nao permitir excluir a si mesmo
        if ($id === $_SESSION['usuario_id']) {
            setMensagem('error', 'Voce nao pode excluir seu proprio usuario.');
            redirecionar(APP_URL . 'usuarios');
        }

        try {
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setMensagem('success', 'Usuario desativado com sucesso!');
        } catch (Exception $e) {
            error_log('Erro ao desativar usuario: ' . $e->getMessage());
            setMensagem('error', 'Erro ao desativar usuario. Tente novamente.');
        }

        redirecionar(APP_URL . 'usuarios');
        break;

    default:
        setMensagem('error', 'Acao nao reconhecida.');
        redirecionar(APP_URL . 'usuarios');
}