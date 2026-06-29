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
    
    // Replace newline with literal string match since it was split
    $c = str_replace(
        "?>selected<?php endif; ?>?>",
        ">selected<?php endif; ?>",
        $c
    );
    
    file_put_contents($f, $c);
}
