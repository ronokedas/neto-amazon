<?php
/**
 * AJAX: Busca Global
 * Retorna resultados em JSON para o campo de busca do topbar
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Apenas usuarios logados
if (!isset($_SESSION['usuario_logado'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$termo = trim($_GET['q'] ?? '');

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$resultados = [];

try {
    // Buscar embarcacoes
    $stmt = $pdo->prepare("SELECT id, nome, 'embarcacao' as tipo, CONCAT('embarcacoes') as url 
                           FROM embarcacoes WHERE nome LIKE :termo AND ativo = 1 LIMIT 5");
    $stmt->execute([':termo' => "%{$termo}%"]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultados[] = $row;
    }

    // Buscar pessoas
    $stmt = $pdo->prepare("SELECT id, nome_completo as nome, 'pessoa' as tipo, CONCAT('pessoas') as url 
                           FROM pessoas WHERE nome_completo LIKE :termo AND ativo = 1 LIMIT 5");
    $stmt->execute([':termo' => "%{$termo}%"]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultados[] = $row;
    }

    // Buscar clientes (também na tabela pessoas)
    $stmt = $pdo->prepare("SELECT id, nome_completo as nome, 'cliente' as tipo, CONCAT('clientes') as url 
                           FROM pessoas WHERE nome_completo LIKE :termo AND ativo = 1 LIMIT 5");
    $stmt->execute([':termo' => "%{$termo}%"]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultados[] = $row;
    }

    // Buscar vistorias (pelo nome da embarcacao vinculada)
    $stmt = $pdo->prepare("SELECT v.id, e.nome as nome, 'vistoria' as tipo, CONCAT('vistorias') as url 
                           FROM vistorias v 
                           INNER JOIN embarcacoes e ON v.embarcacao_id = e.id 
                           WHERE e.nome LIKE :termo LIMIT 5");
    $stmt->execute([':termo' => "%{$termo}%"]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $resultados[] = $row;
    }

} catch (Exception $e) {
    echo json_encode(['erro' => 'Erro na busca']);
    exit;
}

echo json_encode($resultados);