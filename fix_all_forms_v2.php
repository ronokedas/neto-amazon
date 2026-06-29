<?php

// Este script corrige múltiplos problemas nos formulários de certificados.

$files_to_process = [
    'cnbl',
    'cnarq',
    'lp',
    'lc',
    'cht'
];

// Bloco de lógica de pré-preenchimento, padronizado e seguro.
// Baseado no formulário CSN que já funciona corretamente.
$new_preenchimento_logic = <<<'PHP'
// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---
$preenchimento = [
    'embarcacao_id' => '', 'nome_embarcacao' => '', 'numero_inscricao' => '', 'indicativo_chamada' => '',
    'atividades_servicos'=> '', 'tipo_embarcacao' => '', 'ano_construcao' => '', 'comprimento_total' => '',
    'comprimento_casco' => '', 'comprimento_lpp' => '', 'boca_moldada' => '', 'boca_maxima' => '',
    'pontal_moldado' => '', 'arqueacao_bruta' => '', 'arqueacao_liquida' => '', 'material_casco' => '',
    'relatorio_numero' => '', 'proprietario' => '', 'numero_casco' => ''
];
$dadosPre = null;
if (!$editando && !empty($_GET['agendamento_id'])) {
    $stmtPre = $pdo->prepare("
        SELECT 
            e.id as embarcacao_id, e.nome as emb_nome, e.registro, e.indicativo_chamada, e.tipo_embarcacao, e.ano as emb_ano,
            e.comprimento_total, e.comprimento_casco, e.comprimento_lpp, e.boca_moldada, e.boca_maxima, e.pontal_moldado, 
            e.arqueacao_bruta, e.arqueacao_liquida, e.material_casco, e.atividades, e.proprietario,
            v.numero as relatorio_numero
        FROM agendamentos a
        JOIN embarcacoes e ON a.embarcacao_id = e.id
        LEFT JOIN vistorias v ON v.agendamento_id = a.id
        WHERE a.id = :aid
    ");
    $stmtPre->execute([':aid' => $_GET['agendamento_id']]);
    $dadosPre = $stmtPre->fetch(PDO::FETCH_ASSOC);

    if ($dadosPre) {
        // Mapeia todos os dados buscados para o array de preenchimento
        $preenchimento['embarcacao_id']      = $dadosPre['embarcacao_id'] ?? '';
        $preenchimento['nome_embarcacao']    = $dadosPre['emb_nome'] ?? '';
        $preenchimento['numero_inscricao']   = $dadosPre['registro'] ?? '';
        $preenchimento['indicativo_chamada'] = $dadosPre['indicativo_chamada'] ?? '';
        $preenchimento['atividades_servicos']= $dadosPre['atividades'] ?? '';
        $preenchimento['tipo_embarcacao']    = $dadosPre['tipo_embarcacao'] ?? '';
        $preenchimento['ano_construcao']     = $dadosPre['emb_ano'] ?? '';
        $preenchimento['comprimento_total']  = $dadosPre['comprimento_total'] ?? '';
        $preenchimento['comprimento_casco']  = $dadosPre['comprimento_casco'] ?? '';
        $preenchimento['comprimento_lpp']    = $dadosPre['comprimento_lpp'] ?? '';
        $preenchimento['boca_moldada']       = $dadosPre['boca_moldada'] ?? '';
        $preenchimento['boca_maxima']        = $dadosPre['boca_maxima'] ?? '';
        $preenchimento['pontal_moldado']     = $dadosPre['pontal_moldado'] ?? '';
        $preenchimento['arqueacao_bruta']    = $dadosPre['arqueacao_bruta'] ?? '';
        $preenchimento['arqueacao_liquida']  = $dadosPre['arqueacao_liquida'] ?? '';
        $preenchimento['material_casco']     = $dadosPre['material_casco'] ?? '';
        $preenchimento['relatorio_numero']   = $dadosPre['relatorio_numero'] ?? '';
        $preenchimento['proprietario']       = $dadosPre['proprietario'] ?? '';
    }
}
PHP;

// Bloco de atributos data- que foi copiado incorretamente para alguns arquivos.
$orphaned_block = 'data-nome="<?php echo h($emb[\\\'nome\\\']); ?>"
                                    data-registro="<?php echo h($emb[\\\'registro\\\']); ?>"
                                    data-tipo="<?php echo h($emb[\\\'tipo\\\']); ?>"
                                    data-comprimento_total="<?php echo h($emb[\\\'comprimento_total\\\'] ?? \\\'\\\'); ?>"
                                    data-comprimento_casco="<?php echo h($emb[\\\'comprimento_casco\\\'] ?? \\\'\\\'); ?>"
                                    data-boca_moldada="<?php echo h($emb[\\\'boca_moldada\\\'] ?? \\\'\\\'); ?>"
                                    data-pontal_moldado="<?php echo h($emb[\\\'pontal_moldado\\\'] ?? \\\'\\\'); ?>"
                                    data-arqueacao_bruta="<?php echo h($emb[\\\'arqueacao_bruta\\\'] ?? \\\'\\\'); ?>"';

foreach ($files_to_process as $cert_type) {
    $file_path = "c:/sistema/modules/documentacao/{$cert_type}/form.php";
    if (!file_exists($file_path)) continue;
    
    $content = file_get_contents($file_path);

    // 1. Remove o bloco órfão de atributos data-
    $content = str_replace($orphaned_block, '', $content);

    // 2. Substitui toda a lógica de pré-preenchimento antiga pela nova.
    $content = preg_replace(
        '#// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---(.|\n|\r)*?(\$stmt_emb\s*=|// Buscar lista de embarcações|// Embarcações|//\s*Buscar lista de embarcações ativas|\$titulo_page\s*=)#',
        $new_preenchimento_logic . "\n\n" . '$2',
        $content, 1
    );
    
    // 3. Garante que os valores nos inputs sejam seguros contra nulos.
    // Ex: de h($var['key']) para h($var['key'] ?? '')
    // Ex: de h($preenchimento['key']) para h($preenchimento['key'] ?? '')
    $content = preg_replace(
        "/h\\(\\$(\\w+)\\[\\\\'(\\w+)\\\\'\\]\\)/", 
        "h(\$$1['$2'] ?? '')", 
        $content
    );

    file_put_contents($file_path, $content);
    echo "Corrigido: {$file_path}\\n";
}

?>
