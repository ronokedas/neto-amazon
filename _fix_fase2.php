<?php
/**
 * Script de correção Fase 2
 * 1. Corrigir encoding dos nomes dos serviços
 * 2. Adicionar preços padrão aos serviços
 */
require_once __DIR__ . '/config.php';

echo "===== CORREÇÃO FASE 2 =====\n\n";

// Mapeamento de serviços com nomes corretos e preços
$servicos_corretos = [
    'Análise de Planos Ec1'                => 2500.00,
    'Análise de Planos Ec2'                => 2500.00,
    'Vistoria Inicial Seco'                => 3500.00,
    'Vistoria Inicial Flutuando'           => 3500.00,
    'Vistoria Inicial de Borda Livre'      => 2800.00,
    'Vistoria Inicial de Arqueação'        => 3200.00,
    'Acompanhamento de Ultrassom'          => 1800.00,
    'Vistoria Anual'                       => 2200.00,
    'Vistoria Anual Periódica'             => 2500.00,
    'Vistoria Intermediária'               => 3000.00,
    'Licença Provisória'                   => 1500.00,
];

// Mapeamento de nomes quebrados para corrigir
$nome_quebrado_para_correto = [];
$stmt = $pdo->query("SELECT id, nome FROM servicos");
$todos = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($todos as $s) {
    $nomeAtual = $s['nome'];
    foreach ($servicos_corretos as $nomeCorreto => $preco) {
        // Compara ignorando encoding: extrai apenas ASCII do nome atual
        $asciiAtual = preg_replace('/[^\x20-\x7E]/', '', $nomeAtual);
        $asciiCorreto = preg_replace('/[^\x20-\x7E]/', '', $nomeCorreto);
        
        if (strcasecmp($asciiAtual, $asciiCorreto) === 0) {
            $nome_quebrado_para_correto[] = [
                'id' => $s['id'],
                'nome_antigo' => $nomeAtual,
                'nome_correto' => $nomeCorreto,
                'preco' => $preco,
            ];
            break;
        }
    }
}

echo "Serviços encontrados para corrigir: " . count($nome_quebrado_para_correto) . "\n\n";

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("UPDATE servicos SET nome = :nome, preco_padrao = :preco WHERE id = :id");
    
    foreach ($nome_quebrado_para_correto as $item) {
        $stmt->execute([
            ':nome'  => $item['nome_correto'],
            ':preco' => $item['preco'],
            ':id'    => $item['id'],
        ]);
        echo "UPDATE: '{$item['nome_antigo']}' -> '{$item['nome_correto']}' (R$ {$item['preco']})\n";
    }
    
    $pdo->commit();
    echo "\nCorreção concluída com sucesso!\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "ERRO: " . $e->getMessage() . "\n";
}

// Verificar resultado
echo "\n===== VERIFICAÇÃO FINAL =====\n";
$stmt = $pdo->query("SELECT nome, preco_padrao FROM servicos WHERE ativo = 1 ORDER BY nome");
$resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($resultado as $r) {
    $status = (mb_detect_encoding($r['nome'], 'UTF-8', true) === 'UTF-8') ? '✓' : '✗';
    echo "  {$status} {$r['nome']} - R$ " . number_format($r['preco_padrao'], 2, ',', '.') . "\n";
}

echo "\n===== FIM =====\n";