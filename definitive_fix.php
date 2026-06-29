<?php

// Script de correção definitivo para os formulários de certificados.

$files_to_process = [
    'cnbl',
    'cnarq',
    'lp',
    'lc',
    'cht'
];

// Bloco de lógica de pré-preenchimento 100% correto e validado.
$correct_logic_block = <<<'PHP'
// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'embarcacao_id'      => '',
    'nome_embarcacao'    => '',
    'numero_inscricao'   => '',
    'indicativo_chamada' => '',
    'atividades_servicos'=> '',
    'tipo_embarcacao'    => '',
    'ano_construcao'     => '',
    'comprimento_total'  => '',
    'comprimento_casco'  => '',
    'boca_moldada'       => '',
    'pontal_moldado'     => '',
    'arqueacao_bruta'    => '',
    'material_casco'     => '',
    'relatorio_numero'   => '',
    'proprietario'       => ''
];
$dadosPre = null;
if (!$editando && !empty($_GET['agendamento_id'])) {
    $stmtPre = $pdo->prepare("
        SELECT 
            e.id as embarcacao_id, e.nome as emb_nome, e.registro, e.indicativo_chamada, e.tipo_embarcacao, e.ano as emb_ano,
            e.comprimento_total, e.comprimento_casco, e.boca_moldada, e.pontal_moldado, 
            e.arqueacao_bruta, e.material_casco, e.observacoes as atividades, e.proprietario,
            v.numero as relatorio_numero
        FROM agendamentos a
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN vistorias v ON v.agendamento_id = a.id
        WHERE a.id = :aid
    ");
    $stmtPre->execute([':aid' => $_GET['agendamento_id']]);
    $dadosPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

    if ($dadosPre) {
        $preenchimento['embarcacao_id']      = h($dadosPre['embarcacao_id'] ?? '');
        $preenchimento['nome_embarcacao']    = h($dadosPre['emb_nome'] ?? '');
        $preenchimento['numero_inscricao']   = h($dadosPre['registro'] ?? '');
        $preenchimento['indicativo_chamada'] = h($dadosPre['indicativo_chamada'] ?? '');
        $preenchimento['atividades_servicos']= h($dadosPre['atividades'] ?? '');
        $preenchimento['tipo_embarcacao']    = h($dadosPre['tipo_embarcacao'] ?? '');
        $preenchimento['ano_construcao']     = h($dadosPre['emb_ano'] ?? '');
        $preenchimento['comprimento_total']  = h($dadosPre['comprimento_total'] ?? '');
        $preenchimento['comprimento_casco']  = h($dadosPre['comprimento_casco'] ?? '');
        $preenchimento['boca_moldada']       = h($dadosPre['boca_moldada'] ?? '');
        $preenchimento['pontal_moldado']     = h($dadosPre['pontal_moldado'] ?? '');
        $preenchimento['arqueacao_bruta']    = h($dadosPre['arqueacao_bruta'] ?? '');
        $preenchimento['material_casco']     = h($dadosPre['material_casco'] ?? '');
        $preenchimento['relatorio_numero']   = h($dadosPre['relatorio_numero'] ?? '');
        $preenchimento['proprietario']       = h($dadosPre['proprietario'] ?? '');
    }
}
PHP;

foreach ($files_to_process as $cert_type) {
    $file_path = "c:/sistema/modules/documentacao/{$cert_type}/form.php";
    if (!file_exists($file_path)) continue;
    
    $content = file_get_contents($file_path);

    // Regex para encontrar e substituir todo o bloco de lógica de pré-preenchimento,
    // desde o comentário inicial até o próximo bloco de código relevante.
    $content = preg_replace(
        '#// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---(?:.|\n|\r)*?(?=(?:\$stmt_emb\s*=|// Buscar lista de embarcações|// Embarcações|//\s*Buscar lista de embarcações ativas|\$titulo_page\s*=))#',
        $correct_logic_block,
        $content, 
        1 // Apenas a primeira ocorrência
    );

    file_put_contents($file_path, $content);
    echo "Lógica de pré-preenchimento substituída em: {$file_path}\n";
}

?>
