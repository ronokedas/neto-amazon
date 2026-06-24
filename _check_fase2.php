<?php
/**
 * Script temporário - Revisão Fase 2
 * Verifica tabelas, dados e serviços
 */
require_once __DIR__ . '/config.php';

echo "===== REVISÃO FASE 2 =====\n\n";

// 1. Verificar tabelas
$tabelas = ['servicos', 'propostas', 'propostas_embarcacoes', 'propostas_servicos'];
foreach ($tabelas as $t) {
    $stmt = $pdo->query("SHOW TABLES LIKE '{$t}'");
    $existe = $stmt->rowCount() > 0 ? 'OK' : 'PENDENTE - NÃO EXISTE';
    echo "item1: Tabela '{$t}': {$existe}\n";
}

echo "\n";

// 2. Verificar serviços (quantidade e nomes)
$stmt = $pdo->query("SELECT id, nome, preco_padrao FROM servicos WHERE ativo = 1 ORDER BY nome");
$servicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "item2: Serviços ativos: " . count($servicos) . " (esperado 11)\n";
foreach ($servicos as $s) {
    echo "   - [{$s['id']}] {$s['nome']} (R$ {$s['preco_padrao']})\n";
}

echo "\n";

// 3. Verificar se os preços estão zerados
$todosZero = true;
foreach ($servicos as $s) {
    if ((float)$s['preco_padrao'] > 0) {
        $todosZero = false;
    }
}
echo "item3: Preços dos serviços: " . ($todosZero ? "PENDENTE - Todos zerados (R$ 0,00). O seed inseriu com 0.00." : "OK - Há preços cadastrados") . "\n";

echo "\n";

// 4. Verificar charset das tabelas
$stmt = $pdo->query("SELECT @@character_set_database AS db_charset, @@collation_database AS db_collation");
$charset = $stmt->fetch();
echo "item_encoding: Charset do banco: {$charset['db_charset']} / {$charset['db_collation']}\n";

// 5. Verificar se há registros na propostas
$stmt = $pdo->query("SELECT COUNT(*) as cnt FROM propostas");
$cnt = $stmt->fetchColumn();
echo "item5: Propostas registradas: {$cnt}\n";

// 6. Verificar sequenciais_documentos
$stmt = $pdo->query("SELECT * FROM sequenciais_documentos");
$seqs = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "item6: Sequenciais de documentos: " . count($seqs) . " registros\n";
foreach ($seqs as $seq) {
    echo "   - {$seq['tipo_documento']}: {$seq['ultimo_numero']} (ano {$seq['ano']})\n";
}

echo "\n===== FIM =====\n";