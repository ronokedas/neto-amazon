<?php

// Script de correção final e definitiva para os formulários de certificados.

$files_to_process = [
    'cnbl',
    'cnarq',
    'lp',
    'lc',
    'cht'
];

// 1. Correção no array de definição do $preenchimento
$faulty_preenchimento_def = "'comprimento_casco' => '', 'comprimento_lpp' => '', 'boca_moldada' => '', 'boca_maxima' => '',\n    'pontal_moldado' => '', 'arqueacao_bruta' => '', 'arqueacao_liquida' => '', 'material_casco' => ''";
$corrected_preenchimento_def = "'comprimento_casco' => '', 'boca_moldada' => '',\n    'pontal_moldado' => '', 'arqueacao_bruta' => '', 'material_casco' => ''";

// 2. Correção na consulta SQL para usar a coluna correta para atividades
$faulty_sql_columns = 'e.arqueacao_bruta, e.material_casco, e.atividades, e.proprietario,';
$corrected_sql_columns = 'e.arqueacao_bruta, e.material_casco, e.observacoes as atividades, e.proprietario,';

// 3. Correção no bloco de mapeamento do $preenchimento
$faulty_mapping_block = <<<'PHP'
        $preenchimento['comprimento_casco']  = h($dadosPre['comprimento_casco'] ?? '');
        $preenchimento['comprimento_lpp']    = h($dadosPre['comprimento_lpp'] ?? '');
        $preenchimento['boca_moldada']       = h($dadosPre['boca_moldada'] ?? '');
        $preenchimento['boca_maxima']        = h($dadosPre['boca_maxima'] ?? '');
        $preenchimento['pontal_moldado']     = h($dadosPre['pontal_moldado'] ?? '');
        $preenchimento['arqueacao_bruta']    = h($dadosPre['arqueacao_bruta'] ?? '');
        $preenchimento['arqueacao_liquida']  = h($dadosPre['arqueacao_liquida'] ?? '');
PHP;

$corrected_mapping_block = <<<'PHP'
        $preenchimento['comprimento_casco']  = h($dadosPre['comprimento_casco'] ?? '');
        $preenchimento['boca_moldada']       = h($dadosPre['boca_moldada'] ?? '');
        $preenchimento['pontal_moldado']     = h($dadosPre['pontal_moldado'] ?? '');
        $preenchimento['arqueacao_bruta']    = h($dadosPre['arqueacao_bruta'] ?? '');
PHP;


foreach ($files_to_process as $cert_type) {
    $file_path = "c:/sistema/modules/documentacao/{$cert_type}/form.php";
    if (!file_exists($file_path)) continue;
    
    $content = file_get_contents($file_path);

    // Aplica as 3 correções
    $content = str_replace($faulty_preenchimento_def, $corrected_preenchimento_def, $content);
    $content = str_replace($faulty_sql_columns, $corrected_sql_columns, $content);
    $content = str_replace($faulty_mapping_block, $corrected_mapping_block, $content);

    file_put_contents($file_path, $content);
    echo "Correção final aplicada em: {$file_path}\n";
}

?>