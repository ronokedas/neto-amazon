<?php
$f = 'c:/sistema/modules/documentacao/cnbl/form.php';
$c = file_get_contents($f);
$c = preg_replace(
"/if \(!\\\$editando\) \{\s*\\\$ano = date\('y'\);\s*\/\/\s*---\s*PRE-PREENCHIMENTO VIA AGENDAMENTO\s*---/",
"if (!\$editando) {\n    \$ano = date('y');\n    \$ano4 = date('Y');\n    \$stmt_num = \$pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano');\n    \$stmt_num->execute([':ano' => \$ano4]);\n    \$total = \$stmt_num->fetch()['total'];\n    \$seq = \$total + 1;\n    \$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";\n}\n\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);
$c = preg_replace(
"/\s*\\\$ano4 = date\('Y'\);\s*\\\$stmt_num = \\\$pdo->prepare\(\"SELECT COUNT\(\*\) as total FROM certificados_cnbl WHERE YEAR\(criado_em\) = :ano\"\);\s*\\\$stmt_num->execute\(\[':ano' => \\\$ano4\]\);\s*\\\$total = \\\$stmt_num->fetch\(\)\['total'\];\s*\\\$seq = \\\$total \+ 1;\s*\\\$proximo_numero = \"AM-CNBL-\{\\\$seq\}\/\{\\\$ano\}\";\s*\}/",
"",
$c);
file_put_contents($f, $c);

$f = 'c:/sistema/modules/documentacao/cnarq/form.php';
$c = file_get_contents($f);
$c = preg_replace(
"/if \(!\\\$editando\) \{\s*\\\$ano = date\('y'\);\s*\/\/\s*---\s*PRE-PREENCHIMENTO VIA AGENDAMENTO\s*---\s*\/\/\s*---\s*PRE-PREENCHIMENTO VIA AGENDAMENTO\s*---/",
"if (!\$editando) {\n    \$ano = date('y');\n    \$ano4 = date('Y');\n    \$stmt_num = \$pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano');\n    \$stmt_num->execute([':ano' => \$ano4]);\n    \$total = \$stmt_num->fetch()['total'];\n    \$seq = \$total + 1;\n    \$proximo_numero = \"AM-CNARQ-{\\$seq}/{\$ano}\";\n}\n\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);
$c = preg_replace(
"/\s*\\\$ano4 = date\('Y'\);\s*\\\$stmt_num = \\\$pdo->prepare\(\"SELECT COUNT\(\*\) as total FROM certificados_cnarq WHERE YEAR\(criado_em\) = :ano\"\);\s*\\\$stmt_num->execute\(\[':ano' => \\\$ano4\]\);\s*\\\$total = \\\$stmt_num->fetch\(\)\['total'\];\s*\\\$seq = \\\$total \+ 1;\s*\\\$proximo_numero = \"AM-CNARQ-\{\\\$seq\}\/\{\\\$ano\}\";\s*\}/",
"",
$c);

$c = preg_replace(
"/\\\$preenchimento = \[\s*'nome_embarcacao'\s*=> '', 'numero_inscricao'.*?if \(!\\\$editando && !empty\(\\\$_GET\['agendamento_id'\]\)\) \{.*?\}\s*\}/s",
"",
$c, 1);

file_put_contents($f, $c);

$f = 'c:/sistema/modules/documentacao/cht/form.php';
$c = file_get_contents($f);
$c = preg_replace(
"/<a href=\"<\?php echo APP_URL; \?>documentacao\/cht\" class=\"btn btn-secondary\"><i class=\"fas fa-arrow-left\"><\/i> Voltar<\/a>\s*<\/div>\s*\/\/\s*---\s*PRE-PREENCHIMENTO VIA AGENDAMENTO\s*---/s",
"<a href=\"<?php echo APP_URL; ?>documentacao/cht\" class=\"btn btn-secondary\"><i class=\"fas fa-arrow-left\"></i> Voltar</a>\n    </div>\n\n<?php\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);

$c = preg_replace(
"/}\s*\}\s*<\?php if \(\\\$editando && \\\$cht\['assinado'\]\): \?>/s",
"    }\n}\n?>\n    <?php if (\$editando && \$cht['assinado']): ?>",
$c);

$c = preg_replace(
"/<div class=\"card mb-3\">\s*\/\/\s*Ao carregar.*?<\?php endif; \?>\s*<div class=\"card-footer\"/s",
"<div class=\"card mb-3\">\n            <div class=\"card-footer\"",
$c);

file_put_contents($f, $c);
