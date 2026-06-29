<?php
$f = 'c:/sistema/modules/documentacao/cnbl/form.php';
$c = file_get_contents($f);
$c = str_replace(
"if (!\$editando) {\r\n    \$ano = date('y');\r\n\r\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
"if (!\$editando) {\n    \$ano = date('y');\n    \$ano4 = date('Y');\n    \$stmt_num = \$pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano');\n    \$stmt_num->execute([':ano' => \$ano4]);\n    \$total = \$stmt_num->fetch()['total'];\n    \$seq = \$total + 1;\n    \$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";\n}\n\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);
$c = str_replace(
"    \$ano4 = date('Y');\r\n    \$stmt_num = \$pdo->prepare(\"SELECT COUNT(*) as total FROM certificados_cnbl WHERE YEAR(criado_em) = :ano\");\r\n    \$stmt_num->execute([':ano' => \$ano4]);\r\n    \$total = \$stmt_num->fetch()['total'];\r\n    \$seq = \$total + 1;\r\n    \$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";\r\n}\r\n",
"",
$c);
file_put_contents($f, $c);
