<?php
/**
 * SCRIPT: FATURAMENTO DE CONTRATOS RECORRENTES
 * Pode ser executado via CRONJOB diariamente ou acessado via navegador (protegido).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Proteção para nao rodar via web acidentalmente sem um token se necessário
// if (php_sapi_name() !== 'cli') die('Acesso restrito'); // Exemplo

$hoje = date('Y-m-d');
$log = [];

try {
    $pdo->beginTransaction();

    // Buscar contratos assinados e ativos que possuem faturamento para hoje ou datas anteriores
    $stmt = $pdo->prepare("
        SELECT id, cliente_id, numero, valor_total, frequencia, dia_vencimento, proximo_faturamento, renovacao_automatica, data_vencimento
        FROM contratos 
        WHERE ativo = 1 
          AND status = 'ASSINADO' 
          AND frequencia != 'ÚNICA' 
          AND proximo_faturamento <= :hoje
    ");
    $stmt->execute([':hoje' => $hoje]);
    $contratos = $stmt->fetchAll();

    $qtdGerados = 0;

    foreach ($contratos as $c) {
        // Verifica se já passou da data de encerramento
        if (!$c['renovacao_automatica'] && $c['data_vencimento'] && $c['data_vencimento'] < $hoje) {
            // Contrato expirado
            $pdo->prepare("UPDATE contratos SET status = 'CANCELADO' WHERE id = :id")->execute([':id' => $c['id']]);
            $log[] = "Contrato {$c['numero']} expirado e cancelado.";
            continue;
        }

        // Criar lançamento no financeiro
        $novoIdLanc = gerarUUID();
        $desc = "Mensalidade Contrato " . ($c['numero'] ?: 'S/N') . " - Ref: " . date('m/Y', strtotime($c['proximo_faturamento']));
        
        $stmtFin = $pdo->prepare("
            INSERT INTO financeiro_lancamentos (id, tipo, descricao, valor, data, categoria, observacoes)
            VALUES (:id, 'RECEITA', :desc, :valor, :data, 'Mensalidade', :obs)
        ");
        $stmtFin->execute([
            ':id' => $novoIdLanc,
            ':desc' => $desc,
            ':valor' => $c['valor_total'],
            ':data' => $c['proximo_faturamento'],
            ':obs' => 'Gerado automaticamente pelo faturamento recorrente.'
        ]);

        // Calcular próximo vencimento
        $novaData = $c['proximo_faturamento'];
        if ($c['frequencia'] === 'MENSAL') {
            $novaData = date('Y-m-d', strtotime('+1 month', strtotime($novaData)));
        } elseif ($c['frequencia'] === 'TRIMESTRAL') {
            $novaData = date('Y-m-d', strtotime('+3 months', strtotime($novaData)));
        } elseif ($c['frequencia'] === 'SEMESTRAL') {
            $novaData = date('Y-m-d', strtotime('+6 months', strtotime($novaData)));
        } elseif ($c['frequencia'] === 'ANUAL') {
            $novaData = date('Y-m-d', strtotime('+1 year', strtotime($novaData)));
        }
        
        // Ajustar o dia se não bater com dia_vencimento (ex: mês de 28 dias)
        $dia_certo = str_pad($c['dia_vencimento'], 2, '0', STR_PAD_LEFT);
        $novaDataAjustada = substr($novaData, 0, 8) . $dia_certo;
        
        // Se a data ajustada for válida (ex: 31/02 não é), tenta ajustar, senao deixa o fim do mes.
        if (checkdate(substr($novaDataAjustada, 5, 2), $dia_certo, substr($novaDataAjustada, 0, 4))) {
            $novaData = $novaDataAjustada;
        }

        // Atualizar próximo faturamento no contrato
        $pdo->prepare("UPDATE contratos SET proximo_faturamento = :pf WHERE id = :id")->execute([
            ':pf' => $novaData,
            ':id' => $c['id']
        ]);

        $qtdGerados++;
        $log[] = "Lançamento {$desc} gerado. Próximo: {$novaData}.";
    }

    $pdo->commit();
    
    echo "Faturamento recorrente concluído! {$qtdGerados} lançamentos gerados.\n";
    foreach ($log as $l) {
        echo "- {$l}\n";
    }

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Erro: " . $e->getMessage() . "\n";
}
