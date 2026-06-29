<?php
$files = [
    'c:/sistema/modules/documentacao/cnbl/form.php',
    'c:/sistema/modules/documentacao/cnarq/form.php',
    'c:/sistema/modules/documentacao/lp/form.php',
    'c:/sistema/modules/documentacao/lc/form.php',
    'c:/sistema/modules/documentacao/cht/form.php'
];

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $c = file_get_contents($f);
    
    // Fix the query to select embarcacao_id
    $c = str_replace(
        "SELECT e.nome as emb_nome,",
        "SELECT e.id as embarcacao_id, e.nome as emb_nome,",
        $c
    );
    
    // Fix the broken selected tag
    // The broken text is usually: <?php if (!empty($_GET['agendamento_id']) && isset($dadosPre['embarcacao_id']) && $dadosPre['embarcacao_id'] == $emb['id']): ?>selected<?php endif; ?>?>
    // Or similar
    $c = preg_replace(
        "/\?>selected<\?php endif; \?>\?>/",
        " selected<?php endif; ?>>",
        $c
    );
    
    file_put_contents($f, $c);
}
