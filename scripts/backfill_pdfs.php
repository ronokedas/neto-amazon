<?php
/**
 * Script de Backfill: Gera PDFs para certificados já assinados no passado que não possuem arquivo físico.
 * Roda via CLI ou browser.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';

// Limite de tempo de execução e memória
set_time_limit(0);
ini_set('memory_limit', '512M');

echo "Iniciando backfill de PDFs...\n\n";

$tabelas = [
    'certificados_csn' => ['tipo' => 'csn', 'numero' => 'numero', 'pasta' => 'csn'],
    'certificados_cnbl' => ['tipo' => 'cnbl', 'numero' => 'numero', 'pasta' => 'cnbl'],
    'certificados_cnarq' => ['tipo' => 'cnarq', 'numero' => 'numero', 'pasta' => 'cnarq'],
    'certificados_lp' => ['tipo' => 'lp', 'numero' => 'numero_lp', 'pasta' => 'lp'],
    'certificados_lc' => ['tipo' => 'lc', 'numero' => 'numero_lc', 'pasta' => 'lc'],
    'certificados_cht' => ['tipo' => 'cht', 'numero' => 'numero_relatorio_ht', 'pasta' => 'cht'],
];

foreach ($tabelas as $tabela => $config) {
    echo "Processando {$tabela}...\n";
    
    // Obter todos que estão assinados, mas sem caminho
    $stmt = $pdo->prepare("SELECT id, {$config['numero']} as num, assinatura_em, token_assinatura FROM {$tabela} WHERE assinado = 1 AND (caminho_arquivo_pdf IS NULL OR caminho_arquivo_pdf = '') AND ativo = 1");
    $stmt->execute();
    $certificados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($certificados)) {
        echo "Nenhum certificado pendente em {$tabela}.\n\n";
        continue;
    }
    
    echo "Encontrados " . count($certificados) . " certificados em {$tabela}.\n";
    
    foreach ($certificados as $cert) {
        $id = $cert['id'];
        
        // Ano da assinatura ou atual
        $ano = !empty($cert['assinatura_em']) ? date('Y', strtotime($cert['assinatura_em'])) : date('Y');
        
        $prefix = strtoupper($config['tipo']) . '_';
        $nome_arquivo_pdf = $prefix . str_replace('/', '-', $cert['num']) . '.pdf';
        $caminho_relativo = 'storage/certificados/' . $ano . '/' . $config['pasta'] . '/' . $nome_arquivo_pdf;
        $salvar_pdf_caminho = __DIR__ . '/../' . $caminho_relativo;
        
        $dir_pdf = dirname($salvar_pdf_caminho);
        if (!is_dir($dir_pdf)) {
            mkdir($dir_pdf, 0777, true);
        }
        
        // Fazer require no pdf.php correspondente
        $_GET['id'] = ''; // limpar id para não forçar login
        $_GET['token'] = $cert['token_assinatura']; // usar modo público
        
        // Dependendo da tabela, o include é diferente
        $include_path = '';
        if ($config['tipo'] == 'csn') $include_path = __DIR__ . '/../modules/documentacao/certificados/pdf.php';
        else $include_path = __DIR__ . '/../modules/documentacao/' . $config['pasta'] . '/pdf.php';
        
        if (file_exists($include_path)) {
            // Usar shell_exec para rodar num processo separado e evitar Cannot redeclare
            $cmd = "php -r '\$_GET[\"token\"]=\"{$cert['token_assinatura']}\"; \$salvar_pdf_caminho=\"{$salvar_pdf_caminho}\"; require \"{$include_path}\";' 2>&1";
            $output_cli = shell_exec($cmd);
            
            if (file_exists($salvar_pdf_caminho)) {
                $hash_pdf = hash_file('sha256', $salvar_pdf_caminho);
                $stmt_upd = $pdo->prepare("UPDATE {$tabela} SET caminho_arquivo_pdf = :caminho, hash_arquivo_pdf = :hash WHERE id = :id");
                $stmt_upd->execute([
                    ':caminho' => $caminho_relativo,
                    ':hash' => $hash_pdf,
                    ':id' => $id
                ]);
                
                // Log 
                if (function_exists('log_atividade')) {
                    log_atividade('pdf_backfill', "Gerado PDF retroativo (snapshot) para {$tabela} ({$cert['num']})");
                }
                echo "  [OK] PDF gerado: {$nome_arquivo_pdf}\n";
            } else {
                echo "  [ERRO] Arquivo não foi criado fisicamente: {$salvar_pdf_caminho}\n";
                echo "         Output: " . trim($output_cli) . "\n";
            }
        } else {
            echo "  [ERRO] Arquivo de geração não encontrado: {$include_path}\n";
        }
    }
    echo "\n";
}

echo "Backfill concluído!\n";
