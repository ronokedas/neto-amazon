<?php
$f = 'c:/sistema/modules/documentacao/cnbl/form.php';
$c = file_get_contents($f);
$c = str_replace(
"    \$proximo_numero = \"AM-CNBL-{}/{\";",
"    \$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";",
$c);
$c = str_replace(
"    \$proximo_numero = \"AM-CNBL-{}/{};\n}",
"    \$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";\n}",
$c);
// wait, the output of my script replaced it exactly with what it was evaluated to.
file_put_contents($f, $c);
