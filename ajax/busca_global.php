<?php
/**
 * AJAX: Busca global do topbar.
 */
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaLogado()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Nao autenticado']);
    exit;
}

$termo = trim($_GET['q'] ?? '');
if (mb_strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

$resultados = [];
$like = '%' . $termo . '%';

try {
    $stmt = $pdo->prepare("
        SELECT id, nome, 'embarcacao' AS tipo,
               CONCAT('embarcacoes/form?id=', id) AS url
        FROM embarcacoes
        WHERE nome LIKE :termo AND ativo = 1
        LIMIT 5
    ");
    $stmt->execute([':termo' => $like]);
    $resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

    // A antiga tabela pessoas foi consolidada em clientes.
    $stmt = $pdo->prepare("
        SELECT id, nome, perfil AS tipo,
               CASE perfil
                   WHEN 'armador' THEN CONCAT('armadores/form?id=', id)
                   WHEN 'despachante' THEN CONCAT('despachantes/form?id=', id)
                   ELSE CONCAT('proprietarios/form?id=', id)
               END AS url
        FROM clientes
        WHERE nome LIKE :termo AND status = 'ATIVO'
        LIMIT 5
    ");
    $stmt->execute([':termo' => $like]);
    $resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));

    $stmt = $pdo->prepare("
        SELECT v.id, e.nome, 'vistoria' AS tipo,
               CONCAT('vistorias/detalhe?id=', v.id) AS url
        FROM vistorias v
        INNER JOIN embarcacoes e ON e.id = v.embarcacao_id
        WHERE e.nome LIKE :termo
        LIMIT 5
    ");
    $stmt->execute([':termo' => $like]);
    $resultados = array_merge($resultados, $stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    error_log('Erro na busca global: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['erro' => 'Erro na busca']);
    exit;
}

echo json_encode($resultados, JSON_UNESCAPED_UNICODE);
