<?php
/**
 * MODULO: CONFIGURACOES
 * Arquivo: actions.php - Salvar configurações do sistema
 * Acesso: apenas ADMIN
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

verificar_sessao();
verificar_cargo('ADMIN');

$action = $_POST['action'] ?? '';

if ($action !== 'salvar' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    setMensagem('error', 'Ação inválida.');
    redirecionar(APP_URL . 'configuracoes');
}

// Verificar CSRF
if (!isset($_POST['csrf_token']) || !verificarCSRF($_POST['csrf_token'])) {
    setMensagem('error', 'Token de segurança inválido.');
    redirecionar(APP_URL . 'configuracoes');
}

$configs = $_POST['cfg'] ?? [];

if (empty($configs)) {
    setMensagem('error', 'Nenhuma configuração enviada.');
    redirecionar(APP_URL . 'configuracoes');
}

try {
    $stmt = $pdo->prepare("UPDATE configuracoes SET valor = :valor WHERE chave = :chave");

    foreach ($configs as $chave => $valor) {
        $valor = trim((string)$valor);

        // Validar meta_mensal
        if ($chave === 'meta_mensal') {
            $valor = trim((string)$valor);
            // Se tem vírgula, é formato brasileiro: "50.000,00" ou "50000,00"
            if (strpos($valor, ',') !== false) {
                // Remove pontos de milhar primeiro
                $valor = str_replace('.', '', $valor);
                // Converte vírgula decimal para ponto
                $valor = str_replace(',', '.', $valor);
            }
            // Se não tem vírgula, já está em formato americano ou inteiro (ex: "50000" ou "50000.00")
            $valor = floatval($valor);
            if ($valor <= 0) {
                setMensagem('error', 'O valor da meta mensal deve ser um número positivo.');
                redirecionar(APP_URL . 'configuracoes');
            }
            $valor = number_format($valor, 2, '.', '');
        }

        $stmt->execute([':valor' => $valor, ':chave' => $chave]);
    }

    setMensagem('success', 'Configurações salvas com sucesso!');
} catch (Exception $e) {
    error_log('Erro ao salvar configurações: ' . $e->getMessage());
    setMensagem('error', 'Erro ao salvar configurações. Tente novamente.');
}

redirecionar(APP_URL . 'configuracoes');