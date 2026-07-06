<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_logado'])) {
    echo json_encode([]);
    exit;
}

$q = $_GET['q'] ?? '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

function removerAcentosBusca($string) {
    $map = [
        'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','è'=>'e','ê'=>'e','í'=>'i','ì'=>'i','î'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ç'=>'c','Ç'=>'C','ñ'=>'n','Ñ'=>'N'
    ];
    return strtr(mb_strtolower($string, 'UTF-8'), $map);
}

$busca = removerAcentosBusca($q) . '%';

try {
    $stmt = $pdo->prepare("SELECT id, nome, uf FROM cidades WHERE nome_busca LIKE :busca ORDER BY nome ASC LIMIT 20");
    $stmt->execute([':busca' => $busca]);
    $cidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $result = [];
    foreach ($cidades as $c) {
        $result[] = [
            'id' => $c['id'],
            'text' => $c['nome'] . ' - ' . $c['uf'],
            'nome' => $c['nome'],
            'uf' => $c['uf']
        ];
    }
    
    echo json_encode($result);
} catch (Exception $e) {
    echo json_encode([]);
}
