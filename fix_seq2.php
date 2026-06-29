<?php
$f1 = 'c:/sistema/modules/documentacao/cnbl/form.php';
$c1 = file_get_contents($f1);
$c1 = str_replace("\$proximo_numero = \"AM-CNBL-{\\}/{\$ano}\";", "\$proximo_numero = \"AM-CNBL-{\\$seq}/{\$ano}\";", $c1);
file_put_contents($f1, $c1);

$f2 = 'c:/sistema/modules/documentacao/cnarq/form.php';
$c2 = file_get_contents($f2);
$c2 = str_replace("\$proximo_numero = \"AM-CNARQ-{\\}/{\$ano}\";", "\$proximo_numero = \"AM-CNARQ-{\\$seq}/{\$ano}\";", $c2);
file_put_contents($f2, $c2);
