<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

verificar_sessao();

$termo = $_GET['q'] ?? '';
$bloco = $_GET['bloco'] ?? '';

if (strlen($termo) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $sql = "SELECT id, titulo, descricao, item_normam FROM exigencias_catalogo WHERE (titulo LIKE :q OR descricao LIKE :q OR item_normam LIKE :q)";
    $params = [':q' => '%' . $termo . '%'];
    
    if (!empty($bloco)) {
        $sql .= " AND bloco_vistoria = :bloco";
        $params[':bloco'] = $bloco;
    }
    
    $sql .= " ORDER BY titulo ASC LIMIT 20";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($resultados);
} catch (Exception $e) {
    echo json_encode([]);
}
