<?php
$f = 'c:/sistema/modules/documentacao/cnarq/form.php';
$c = file_get_contents($f);
$c = str_replace(
"if (!\$editando) {\r\n    \$ano = date('y');\r\n\r\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---\r\n\r\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
"if (!\$editando) {\n    \$ano = date('y');\n    \$ano4 = date('Y');\n    \$stmt_num = \$pdo->prepare('SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano');\n    \$stmt_num->execute([':ano' => \$ano4]);\n    \$total = \$stmt_num->fetch()['total'];\n    \$seq = \$total + 1;\n    \$proximo_numero = \"AM-CNARQ-{\\$seq}/{\$ano}\";\n}\n\n// --- PRE-PREENCHIMENTO VIA AGENDAMENTO ---",
$c);
$c = str_replace(
"    \$ano4 = date('Y');\r\n    \$stmt_num = \$pdo->prepare(\"SELECT COUNT(*) as total FROM certificados_cnarq WHERE YEAR(criado_em) = :ano\");\r\n    \$stmt_num->execute([':ano' => \$ano4]);\r\n    \$total = \$stmt_num->fetch()['total'];\r\n    \$seq = \$total + 1;\r\n    \$proximo_numero = \"AM-CNARQ-{\\$seq}/{\$ano}\";\r\n}\r\n",
"",
$c);
// The duplicated $preenchimento
$c = str_replace(
"\$preenchimento = [\r\n    'nome_embarcacao'    => '', 'numero_inscricao'   => '', 'indicativo_chamada' => '',\r\n    'atividades_servicos'=> '', 'tipo_embarcacao'    => '', 'ano_construcao'     => '',\r\n    'comprimento_total'  => '', 'comprimento_casco' => '', 'comprimento_lpp'   => '',\r\n    'boca_moldada'       => '', 'boca_maxima'       => '', 'pontal_moldado'    => '',\r\n    'arqueacao_bruta'    => '', 'arqueacao_liquida' => '', 'metodo_arqueacao'  => '',\r\n    'relatorio_numero'   => '', 'data_vistoria'     => '', 'local_vistoria'    => ''\r\n];\r\n\r\nif (!\$editando && !empty(\$_GET['agendamento_id'])) {\r\n    \$stmtPre = \$pdo->prepare(\"\r\n        SELECT e.nome as emb_nome, e.registro, e.tipo_embarcacao,\r\n               e.comprimento_total, e.comprimento_casco, e.boca_moldada,\r\n               e.pontal_moldado, e.arqueacao_bruta\r\n        FROM agendamentos a\r\n        JOIN embarcacoes e ON a.embarcacao_id = e.id\r\n        WHERE a.id = :aid\r\n    \");\r\n    \$stmtPre->execute([':aid' => \$_GET['agendamento_id']]);\r\n    \$dadosPre = \$stmtPre->fetch(PDO::FETCH_ASSOC);\r\n\r\n    if (\$dadosPre) {\r\n        \$preenchimento['nome_embarcacao']    = \$dadosPre['emb_nome'];\r\n        \$preenchimento['numero_inscricao']   = \$dadosPre['registro'];\r\n        \$preenchimento['tipo_embarcacao']    = \$dadosPre['tipo_embarcacao'];\r\n        \$preenchimento['comprimento_total']  = \$dadosPre['comprimento_total'];\r\n        \$preenchimento['comprimento_casco']  = \$dadosPre['comprimento_casco'];\r\n        \$preenchimento['boca_moldada']       = \$dadosPre['boca_moldada'];\r\n        \$preenchimento['pontal_moldado']     = \$dadosPre['pontal_moldado'];\r\n        \$preenchimento['arqueacao_bruta']    = \$dadosPre['arqueacao_bruta'];\r\n    }\r\n}\r\n\r\n\$preenchimento = ",
"\$preenchimento = ",
$c);
file_put_contents($f, $c);
