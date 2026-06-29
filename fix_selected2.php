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
    
    $c = preg_replace(
        '/<\?php if \(!empty\(\$_GET\[\'agendamento_id\'\]\) && isset\(\$dadosPre\[\'embarcacao_id\'\]\) && \$dadosPre\[\'embarcacao_id\'\] == \$emb\[\'id\'\]\):\s*>selected<\?php endif; \?>/s',
        "<?php echo (!empty(\$_GET['agendamento_id']) && isset(\$dadosPre['embarcacao_id']) && \$dadosPre['embarcacao_id'] == \$emb['id']) ? 'selected' : ''; ?>",
        $c
    );

    $c = preg_replace(
        '/<\?php if \(!empty\(\$_GET\[\'agendamento_id\'\]\) && isset\(\$dadosPre\[\'embarcacao_id\'\]\) && \$dadosPre\[\'embarcacao_id\'\] == \$emb\[\'id\'\]\):\s*\?>selected<\?php endif; \?>\?>/s',
        "<?php echo (!empty(\$_GET['agendamento_id']) && isset(\$dadosPre['embarcacao_id']) && \$dadosPre['embarcacao_id'] == \$emb['id']) ? 'selected' : ''; ?>",
        $c
    );

    file_put_contents($f, $c);
}
