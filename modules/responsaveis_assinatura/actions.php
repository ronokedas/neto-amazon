<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarCSRF($_POST['csrf_token'] ?? '')) {
        setMensagem('error', 'Token de seguranca invalido. Tente novamente.');
        redirecionar(APP_URL . 'responsaveis_assinatura');
    }

    $action = $_POST['action'] ?? '';

    $nome_completo = trim($_POST['nome_completo'] ?? '');
    $cargo_titulo = trim($_POST['cargo_titulo'] ?? '');
    $registro_profissional = trim($_POST['registro_profissional'] ?? '');
    $ativo = isset($_POST['ativo']) ? (int)$_POST['ativo'] : 1;

    if ($nome_completo === '' || $cargo_titulo === '') {
        setMensagem('error', 'Nome completo e cargo/titulo sao obrigatorios.');
        redirecionar(APP_URL . 'responsaveis_assinatura/form');
    }

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO responsaveis_assinatura 
                (nome_completo, cargo_titulo, registro_profissional, ativo) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $nome_completo,
                $cargo_titulo,
                $registro_profissional,
                $ativo
            ]);
            header("Location: " . APP_URL . "responsaveis_assinatura?success=" . urlencode("Responsável cadastrado com sucesso."));
            exit;
        } elseif ($action === 'update') {
            $id = (int)$_POST['id'];
            $stmt = $pdo->prepare("
                UPDATE responsaveis_assinatura 
                SET nome_completo = ?, 
                    cargo_titulo = ?, 
                    registro_profissional = ?, 
                    ativo = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $nome_completo,
                $cargo_titulo,
                $registro_profissional,
                $ativo,
                $id
            ]);
            header("Location: " . APP_URL . "responsaveis_assinatura?success=" . urlencode("Responsável atualizado com sucesso."));
            exit;
        }
    } catch (PDOException $e) {
        error_log('Erro ao salvar responsavel de assinatura: ' . $e->getMessage());
        setMensagem('error', 'Erro ao salvar responsavel de assinatura.');
        redirecionar(APP_URL . 'responsaveis_assinatura');
    }
}

header("Location: " . APP_URL . "responsaveis_assinatura");
exit;
