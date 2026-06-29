<?php

// Este script corrige o erro fatal "Column not found" nos formulários.

$files_to_process = [
    'cnbl',
    'cnarq',
    'lp',
    'lc',
    'cht'
];

// A consulta SQL que estava causando o erro.
$faulty_sql_select = 'e.id as embarcacao_id, e.nome as emb_nome, e.registro, e.indicativo_chamada, e.tipo_embarcacao, e.ano as emb_ano,
            e.comprimento_total, e.comprimento_casco, e.comprimento_lpp, e.boca_moldada, e.boca_maxima, e.pontal_moldado, 
            e.arqueacao_bruta, e.arqueacao_liquida, e.material_casco, e.atividades, e.proprietario,';

// A consulta SQL corrigida, sem as colunas que não existem.
$corrected_sql_select = 'e.id as embarcacao_id, e.nome as emb_nome, e.registro, e.indicativo_chamada, e.tipo_embarcacao, e.ano as emb_ano,
            e.comprimento_total, e.comprimento_casco, e.boca_moldada, e.pontal_moldado, 
            e.arqueacao_bruta, e.material_casco, e.atividades, e.proprietario,';

foreach ($files_to_process as $cert_type) {
    $file_path = "c:/sistema/modules/documentacao/{$cert_type}/form.php";
    if (!file_exists($file_path)) continue;
    
    $content = file_get_contents($file_path);

    // Substitui a consulta SQL defeituosa pela correta.
    $content = str_replace($faulty_sql_select, $corrected_sql_select, $content);

    file_put_contents($file_path, $content);
    echo "Consulta SQL corrigida em: {$file_path}\n";
}

?>